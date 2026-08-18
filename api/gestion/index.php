<?php
require_once __DIR__ . '/../data.php';

// Ensure session and CSRF cookie are available
clinica_ensure_session_started();
header('Content-Type: application/json; charset=utf-8');

// Protect mutating endpoints with CSRF validation
$httpMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (in_array($httpMethod, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
  if (!clinica_validate_csrf_from_request()) {
    clinica_json(['success' => false, 'error' => 'CSRF token invalido.'], 403);
  }
}

$user = clinica_current_user();

if (!is_array($user) || !clinica_can_access_management((string) ($user['role'] ?? ''))) {
  clinica_json(['success' => false, 'error' => 'Acceso restringido a administracion.'], 403);
}

$resource = clinica_gestion_resource_key((string) ($_GET['resource'] ?? ''));
if ($resource === '') {
  clinica_json(['success' => false, 'error' => 'Recurso no soportado.'], 404);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
  $data = match ($resource) {
    'clinic' => clinica_get_clinic_settings(),
    'comments' => clinica_get_comments(),
    'doctors' => clinica_get_doctors(),
    'specialties' => clinica_get_specialties(),
    'users' => clinica_get_all_users_for_gestion(),
    'services' => clinica_get_services(),
    'schedules' => clinica_get_schedules(),
    default => [],
  };

  clinica_json([
    'success' => true,
    'resource' => $resource,
    'data' => $data,
    'metadata' => clinica_gestion_metadata(),
  ]);
}

if ($method === 'POST') {
  if (strtolower(trim((string) ($_POST['action'] ?? ''))) === 'upload') {
    clinica_gestion_upload_image($resource, $_POST, $_FILES);
  }
  $payload = clinica_request_json();
  if (strtolower(trim((string) ($payload['action'] ?? ''))) === 'delete') {
    $id = (string) ($payload['id'] ?? '');
    clinica_gestion_delete($resource, $id, $user);
  }
  $record = is_array($payload['record'] ?? null) ? $payload['record'] : $payload;
  clinica_gestion_save($resource, $record, $user);
}

if ($method === 'DELETE') {
  $id = (string) ($_GET['id'] ?? '');
  clinica_gestion_delete($resource, $id, $user);
}

if ($method !== 'GET' && $method !== 'POST' && $method !== 'DELETE') {
  clinica_json(['success' => false, 'error' => 'Metodo no permitido.'], 405);
}

function clinica_gestion_resource_key(string $resource): string
{
  $aliases = [
    'clinic' => 'clinic',
    'comment' => 'comments',
    'comments' => 'comments',
    'doctors' => 'doctors',
    'doctor' => 'doctors',
    'specialties' => 'specialties',
    'specialty' => 'specialties',
    'users' => 'users',
    'user' => 'users',
    'services' => 'services',
    'service' => 'services',
    'calendar' => 'schedules',
    'schedule' => 'schedules',
    'schedules' => 'schedules',
  ];

  return $aliases[$resource] ?? '';
}

function clinica_get_table_for_resource(string $resource): string
{
  return match ($resource) {
    'clinic' => 'clinics',
    'comments' => 'testimonials',
    'doctors' => 'doctors',
    'specialties' => 'specialties',
    'users' => 'users',
    'services' => 'services',
    'schedules' => 'doctor_schedules',
    default => '',
  };
}

function clinica_gestion_metadata(): array
{
  return [
    'roles' => [
      ['id' => 'ADMINISTRATOR', 'name' => 'Administracion'],
      ['id' => 'SECRETARIA', 'name' => 'Secretaria'],
      ['id' => 'DOCTOR', 'name' => 'Doctor'],
    ],
    'weekdays' => [
      ['id' => 1, 'name' => 'Lunes'],
      ['id' => 2, 'name' => 'Martes'],
      ['id' => 3, 'name' => 'Miercoles'],
      ['id' => 4, 'name' => 'Jueves'],
      ['id' => 5, 'name' => 'Viernes'],
      ['id' => 6, 'name' => 'Sabado'],
      ['id' => 7, 'name' => 'Domingo'],
    ],
    'specialties' => array_map(static fn(array $item): array => [
      'id' => (string) ($item['id'] ?? ''),
      'name' => (string) ($item['name'] ?? ''),
    ], clinica_get_specialties()),
    'doctors' => array_map(static fn(array $item): array => [
      'id' => (string) ($item['id'] ?? ''),
      'name' => (string) ($item['name'] ?? ''),
      'specialty_id' => (string) ($item['specialty_id'] ?? ''),
    ], clinica_get_doctors()),
  ];
}

function clinica_gestion_upload_image(string $resource, array $post, array $files): void
{
  $field = trim((string) ($post['field'] ?? ''));
  $targets = [
    'clinic' => [
      'hero_image' => 'clinic',
      'hero_mobile_image' => 'clinic',
      'logo_image' => 'clinic',
    ],
    'doctors' => [
      'photo' => 'doctors',
    ],
    'comments' => [
      'avatar' => 'comments',
    ],
    'specialties' => [
      'icon' => 'icons',
    ],
    'services' => [
      'icon' => 'icons',
    ],
  ];

  $directoryName = $targets[$resource][$field] ?? '';
  if ($directoryName === '') {
    clinica_json(['success' => false, 'error' => 'Campo de imagen no permitido para este recurso.'], 422);
  }

  $file = $files['image'] ?? null;
  if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    clinica_json(['success' => false, 'error' => 'No se recibio una imagen valida.'], 422);
  }

  $size = (int) ($file['size'] ?? 0);
  if ($size <= 0 || $size > 5 * 1024 * 1024) {
    clinica_json(['success' => false, 'error' => 'La imagen debe pesar menos de 5 MB.'], 422);
  }

  $tmpName = (string) ($file['tmp_name'] ?? '');
  if ($tmpName === '' || !is_uploaded_file($tmpName)) {
    clinica_json(['success' => false, 'error' => 'No se pudo procesar el archivo subido.'], 422);
  }

  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime = (string) ($finfo->file($tmpName) ?: '');
  $extensions = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
    'image/svg+xml' => 'svg',
  ];

  if (!array_key_exists($mime, $extensions)) {
    clinica_json(['success' => false, 'error' => 'Formato no soportado. Use JPG, PNG, WEBP, GIF o SVG.'], 422);
  }

  // getimagesize returns false for SVG, so we check it only for other types
  if ($mime !== 'image/svg+xml' && getimagesize($tmpName) === false) {
    clinica_json(['success' => false, 'error' => 'Archivo de imagen no valido.'], 422);
  }

  if ($mime === 'image/svg+xml') {
    $content = file_get_contents($tmpName);
    if ($content === false || !clinica_svg_looks_safe($content)) {
      clinica_json(['success' => false, 'error' => 'El archivo SVG contiene codigo no permitido.'], 422);
    }
  }

  $assetsRoot = realpath(__DIR__ . '/../../public/assets');
  if ($assetsRoot === false) {
    clinica_json(['success' => false, 'error' => 'No se encontro el directorio de assets.'], 500);
  }

  $targetDirectory = $assetsRoot . DIRECTORY_SEPARATOR . $directoryName;
  if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true)) {
    clinica_json(['success' => false, 'error' => 'No se pudo crear el directorio de imagenes.'], 500);
  }

  $resolvedTargetDirectory = realpath($targetDirectory);
  if ($resolvedTargetDirectory === false || !str_starts_with($resolvedTargetDirectory, $assetsRoot)) {
    clinica_json(['success' => false, 'error' => 'Directorio de destino invalido.'], 500);
  }

  $originalName = pathinfo((string) ($file['name'] ?? 'imagen'), PATHINFO_FILENAME);
  $slug = clinica_slugify($originalName);
  $slug = $slug !== '' ? $slug : 'imagen';
  $filename = $slug . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extensions[$mime];
  $targetPath = $resolvedTargetDirectory . DIRECTORY_SEPARATOR . $filename;

  if (!move_uploaded_file($tmpName, $targetPath)) {
    clinica_json(['success' => false, 'error' => 'No se pudo guardar la imagen.'], 500);
  }

  chmod($targetPath, 0664);
  $url = '/clinica/public/assets/' . $directoryName . '/' . $filename;

  clinica_json([
    'success' => true,
    'field' => $field,
    'url' => $url,
    'path' => $url,
    'filename' => $filename,
  ], 201);
}

function clinica_gestion_save(string $resource, array $record, array $user): void
{
  $pdo = clinica_get_db();
  $table = clinica_get_table_for_resource($resource);
  if ($table === '') {
    clinica_json(['success' => false, 'error' => 'Recurso no soportado.'], 404);
  }

  if ($resource === 'clinic') {
    $normalized = clinica_normalize_clinic_record($record);
    $setClauses = [];
    $params = [];
    foreach ($normalized as $key => $value) {
      if ($key === 'id') continue;
      $setClauses[] = "`$key` = :$key";
      $params[":$key"] = is_array($value) ? json_encode($value) : $value;
    }
    $stmt = $pdo->prepare("UPDATE `clinics` SET " . implode(', ', $setClauses) . " WHERE id = 1");
    $stmt->execute($params);
    clinica_json(['success' => true, 'data' => clinica_get_clinic_settings(), 'metadata' => clinica_gestion_metadata()]);
    return;
  }

  // For collections
  $id = trim((string) ($record['id'] ?? ''));
  $existingRecord = null;
  if ($id !== '') {
    $stmt = $pdo->prepare("SELECT * FROM `$table` WHERE id = ?");
    $stmt->execute([$id]);
    $existingRecord = $stmt->fetch();
  }
  $isUpdate = (bool) $existingRecord;

  $normalized = clinica_normalize_collection_record($resource, $record, is_array($existingRecord) ? $existingRecord : []);

  // Handle password for users
  if ($resource === 'users') {
    $password = $normalized['password'];
    unset($normalized['password']); // Always remove plain password

    if ($password !== '') {
      if (strlen($password) < 8) {
        clinica_json(['success' => false, 'error' => 'La contrasena debe tener al menos 8 caracteres.'], 422);
      }
      $normalized['password'] = password_hash($password, PASSWORD_DEFAULT);
    } elseif (!$isUpdate) {
      clinica_json(['success' => false, 'error' => 'Contrasena requerida para nuevos usuarios.'], 422);
    }

    if (!$isUpdate) {
      unset($normalized['id']);
    }
  }

  // JSON encode fields that need it
  $jsonFields = ['languages', 'recognitions'];
  foreach ($jsonFields as $field) {
    if (isset($normalized[$field]) && is_array($normalized[$field])) {
      $normalized[$field] = json_encode($normalized[$field]);
    }
  }

  // Validation for schedules must happen before saving
  if ($resource === 'schedules' && ($normalized['start_time'] ?? '') >= ($normalized['end_time'] ?? '')) {
    clinica_json(['success' => false, 'error' => 'La hora de inicio debe ser menor que la hora de fin.'], 422);
  }

  if ($resource === 'schedules') {
    clinica_validate_schedule_constraints($normalized, $isUpdate);
  }

  try {
    if ($isUpdate) {
      // UPDATE
      $setClauses = [];
      foreach ($normalized as $key => $value) {
        if ($key === 'id') continue;
        $setClauses[] = "`$key` = :$key";
      }
      if (empty($setClauses)) {
        clinica_json(['success' => true, 'message' => 'No hay nada que actualizar.']);
      }
      $sql = "UPDATE `$table` SET " . implode(', ', $setClauses) . " WHERE id = :id";
      $stmt = $pdo->prepare($sql);
      $stmt->execute($normalized);
      clinica_log_audit_event(clinica_get_user_id_from_session(), "{$resource}_updated", "Registro de {$resource} actualizado", ['id' => $id, 'changes' => $normalized]);
    } else {
      // INSERT
      $columns = array_keys($normalized);
      $placeholders = array_map(fn($c) => ":$c", $columns);
      $sql = "INSERT INTO `$table` (" . implode(', ', array_map(fn($c) => "`$c`", $columns)) . ") VALUES (" . implode(', ', $placeholders) . ")";
      $stmt = $pdo->prepare($sql);
      $stmt->execute($normalized);
      clinica_log_audit_event(clinica_get_user_id_from_session(), "{$resource}_created", "Registro de {$resource} creado", ['id' => $pdo->lastInsertId(), 'data' => $normalized]);
    }
  } catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'Duplicate entry')) {
      clinica_json(['success' => false, 'error' => 'Ya existe un registro con ese ID o identificador unico.'], 409);
    }
    clinica_json(['success' => false, 'error' => 'Error de base de datos: ' . $e->getMessage()], 500);
  }

  // Return all records of that resource after modification
  $all_records = match ($resource) {
    'comments' => clinica_get_comments(),
    'doctors' => clinica_get_doctors(),
    'specialties' => clinica_get_specialties(),
    'users' => clinica_get_all_users_for_gestion(),
    'services' => clinica_get_services(),
    'schedules' => clinica_get_schedules(),
    default => [],
  };

  clinica_json(['success' => true, 'data' => $all_records, 'metadata' => clinica_gestion_metadata()]);
}

function clinica_gestion_delete(string $resource, string $id, array $user): void
{
  if ($id === '') {
    clinica_json(['success' => false, 'error' => 'ID de registro no proporcionado.'], 422);
  }

  $table = clinica_get_table_for_resource($resource);
  if ($resource === 'clinic' || $table === '') {
    clinica_json(['success' => false, 'error' => 'La clinica no se elimina; actualice sus datos.'], 422);
  }

  $pdo = clinica_get_db(); // Re-fetch PDO for transaction safety if needed, though not strictly necessary here.

  if ($resource === 'users') {
    if ($id === (string) clinica_get_user_id_from_session()) {
      clinica_json(['success' => false, 'error' => 'No puede eliminar su propio usuario.'], 422);
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'ADMINISTRATOR' AND active = 1 AND id != ?");
    $stmt->execute([$id]);
    if ((int) $stmt->fetchColumn() === 0) {
      clinica_json(['success' => false, 'error' => 'Debe quedar al menos un administrador activo.'], 422);
    }
  }

  // Implement soft delete for most resources
  if (in_array($resource, ['users', 'doctors', 'specialties', 'services', 'comments', 'schedules', 'patients'], true)) {
    $stmt = $pdo->prepare("UPDATE `$table` SET active = 0 WHERE id = ?");
    $stmt->execute([$id]);
    if ($stmt->rowCount() === 0) {
      clinica_json(['success' => false, 'error' => 'Registro no encontrado o ya inactivo.'], 404);
    }
    clinica_log_audit_event(clinica_get_user_id_from_session(), "{$resource}_deactivated", "Registro de {$resource} desactivado (soft delete)", ['id' => $id]);
  } else {
    // For resources that might not have an 'active' flag or require hard delete (e.g., audit logs, status history)
    $stmt = $pdo->prepare("DELETE FROM `$table` WHERE id = ?");
    $stmt->execute([$id]);
    clinica_log_audit_event(clinica_get_user_id_from_session(), "{$resource}_deleted", "Registro de {$resource} eliminado (hard delete)", ['id' => $id]);
  }

  $all_records = match ($resource) {
    'comments' => clinica_get_comments(),
    'doctors' => clinica_get_doctors(),
    'specialties' => clinica_get_specialties(),
    'users' => clinica_get_all_users_for_gestion(),
    'services' => clinica_get_services(),
    'schedules' => clinica_get_schedules(),
    default => [],
  };

  clinica_json([
    'success' => true,
    'data' => $all_records,
    'metadata' => clinica_gestion_metadata(),
  ]);
}

function clinica_normalize_clinic_record(array $record): array
{
  $current = clinica_get_clinic_settings();
  $allowed = [
    'name',
    'legal_name',
    'logo_label',
    'tagline',
    'description',
    'meta_title',
    'meta_description',
    'canonical_url',
    'hero_title',
    'hero_subtitle',
    'hero_image',
    'hero_mobile_image',
    'logo_image',
    'about_title',
    'about_subtitle',
    'services_title',
    'services_subtitle',
    'specialties_title',
    'specialties_subtitle',
    'doctors_title',
    'doctors_subtitle',
    'comments_title',
    'comments_subtitle',
    'reservation_title',
    'reservation_subtitle',
    'reservation_summary_note',
    'reservation_success_title',
    'reservation_success_text',
    'reservation_back_home_label',
    'reservation_next_label',
    'reservation_confirm_label',
    'reservation_previous_label',
    'address',
    'email',
    'phone_display',
    'phone_href',
    'emergency_phone',
    'whatsapp_url',
    'facebook_url',
    'instagram_url',
  ];

  foreach ($allowed as $key) {
    if (array_key_exists($key, $record)) {
      $current[$key] = trim((string) $record[$key]);
    }
  }

  $current['name'] = trim((string) ($current['name'] ?? ''));
  if ($current['name'] === '') {
    clinica_json(['success' => false, 'error' => 'Nombre comercial requerido.'], 422);
  }

  clinica_validate_optional_email((string) ($current['email'] ?? ''), 'Correo invalido.');
  foreach (['canonical_url', 'whatsapp_url', 'facebook_url', 'instagram_url'] as $urlField) {
    clinica_validate_optional_url((string) ($current[$urlField] ?? ''), 'URL invalida en ' . $urlField . '.');
  }
  clinica_validate_optional_phone((string) ($current['phone_href'] ?? ''), 'Telefono enlace invalido.');

  return $current;
}

function clinica_normalize_collection_record(string $resource, array $record, array $existing = []): array
{
  return match ($resource) {
    'specialties' => [
      'id' => clinica_record_id($record, $existing, 'name'),
      'name' => clinica_required_string($record, 'name', 'Nombre requerido.'),
      'summary' => clinica_string($record, 'summary'),
      'description' => clinica_string($record, 'description'),
      'icon' => clinica_string($record, 'icon'),
      'featured' => clinica_bool($record['featured'] ?? false),
      'active' => clinica_bool($record['active'] ?? true),
    ],
    'services' => [
      'id' => clinica_record_id($record, $existing, 'title'),
      'title' => clinica_required_string($record, 'title', 'Titulo requerido.'),
      'description' => clinica_string($record, 'description'),
      'icon' => clinica_string($record, 'icon'),
      'featured' => clinica_bool($record['featured'] ?? true),
      'active' => clinica_bool($record['active'] ?? true),
    ],
    'comments' => [
      'id' => clinica_record_id($record, $existing, 'author', 'comment'),
      'author' => clinica_required_string($record, 'author', 'Autor requerido.'),
      'detail' => clinica_string($record, 'detail'),
      'text' => clinica_required_string($record, 'text', 'Comentario requerido.'),
      'rating' => max(1, min(5, (int) ($record['rating'] ?? 5))),
      'avatar' => clinica_string($record, 'avatar', 'public/assets/avatar.png'),
      'featured' => clinica_bool($record['featured'] ?? true),
      'active' => clinica_bool($record['active'] ?? true),
    ],
    'doctors' => [
      'id' => clinica_record_id($record, $existing, 'name', 'doc'),
      'name' => clinica_required_string($record, 'name', 'Nombre requerido.'),
      'photo' => clinica_string($record, 'photo', '/clinica/public/assets/avatar.png'),
      'specialty_id' => clinica_required_relation($record, 'specialty_id', 'specialties', 'Especialidad requerida e invalida.'),
      'sub_specialty' => clinica_string($record, 'sub_specialty'),
      'experience_years' => max(0, (int) ($record['experience_years'] ?? 0)),
      'university' => clinica_string($record, 'university'),
      'recognitions' => clinica_list($record['recognitions'] ?? []),
      'languages' => clinica_list($record['languages'] ?? []),
      'rating' => max(1, min(5, (int) ($record['rating'] ?? 5))),
      'patients' => max(0, (int) ($record['patients'] ?? 0)),
      'featured' => clinica_bool($record['featured'] ?? false),
      'active' => clinica_bool($record['active'] ?? true),
    ],
    'users' => clinica_normalize_user_record($record, $existing),
    'schedules' => [
      'id' => clinica_record_id($record, $existing, 'doctor_id', 'sch', empty($existing)),
      'doctor_id' => clinica_required_relation($record, 'doctor_id', 'doctors', 'Doctor requerido o invalido.'),
      'date' => clinica_optional_future_date($record, 'date'),
      'weekday' => clinica_schedule_weekday_value($record),
      'start_time' => clinica_required_time($record, 'start_time', 'Hora de inicio requerida.'),
      'end_time' => clinica_required_time($record, 'end_time', 'Hora de fin requerida.'),
      'slot_minutes' => max(5, min(120, (int) ($record['slot_minutes'] ?? 15))),
      'active' => clinica_bool($record['active'] ?? true),
    ],
    default => clinica_json(['success' => false, 'error' => 'Recurso no soportado.'], 404),
  };
}

function clinica_normalize_user_record(array $record, array $existing = []): array
{
  $role = clinica_required_role($record);
  $doctorId = trim((string) ($record['doctor_id'] ?? ''));
  $username = strtolower(clinica_required_string($record, 'username', 'Usuario requerido.'));

  if (!preg_match('/^[a-z0-9._-]{3,50}$/', $username)) {
    clinica_json(['success' => false, 'error' => 'Usuario invalido. Use 3 a 50 caracteres: letras, numeros, punto, guion o guion bajo.'], 422);
  }

  if ($role === 'DOCTOR') {
    $doctorId = clinica_required_relation($record, 'doctor_id', 'doctors', 'Los usuarios doctores deben vincularse con un doctor valido.');
  } elseif ($doctorId !== '') {
    $doctorId = clinica_optional_relation($doctorId, 'doctors', 'Doctor vinculado invalido.');
  } else {
    $doctorId = null;
  }

  return [
    'id' => trim((string) ($record['id'] ?? $existing['id'] ?? '')),
    'username' => $username,
    'name' => clinica_required_string($record, 'name', 'Nombre requerido.'),
    'role' => $role,
    'password' => clinica_string($record, 'password'), // Validation and hashing happens in clinica_gestion_save
    'doctor_id' => $doctorId,
    'active' => clinica_bool($record['active'] ?? true),
  ];
}

function clinica_validate_user_save(array $collection, array $record, ?int $existingIndex): void
{
  if (($record['role'] ?? '') === 'DOCTOR' && trim((string) ($record['doctor_id'] ?? '')) === '') {
    clinica_json(['success' => false, 'error' => 'Los usuarios doctores deben vincularse con un doctor.'], 422);
  }
}

function clinica_record_id(array $record, array $existing, string $fallbackField, string $prefix = '', bool $forceNew = false): string
{
  $id = trim((string) ($record['id'] ?? $existing['id'] ?? ''));
  if ($id !== '' && !$forceNew) {
    return $id;
  }

  if ($forceNew || $prefix === 'sch') {
    return uniqid('sch-', false);
  }

  $source = trim((string) ($record[$fallbackField] ?? ''));
  $slug = clinica_slugify($source);
  return $slug !== '' ? ($prefix !== '' ? $prefix . '-' . $slug : $slug) : uniqid($prefix !== '' ? $prefix . '-' : 'item-', false);
}

function clinica_required_string(array $record, string $key, string $message): string
{
  $value = trim((string) ($record[$key] ?? ''));
  if ($value === '') {
    clinica_json(['success' => false, 'error' => $message], 422);
  }

  return $value;
}

function clinica_string(array $record, string $key, string $default = ''): string
{
  return trim((string) ($record[$key] ?? $default));
}

function clinica_validate_optional_email(string $value, string $message): void
{
  if (trim($value) !== '' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
    clinica_json(['success' => false, 'error' => $message], 422);
  }
}

function clinica_validate_optional_url(string $value, string $message): void
{
  $value = trim($value);
  if ($value === '') {
    return;
  }

  if (filter_var($value, FILTER_VALIDATE_URL) === false || !preg_match('/^https?:\/\//i', $value)) {
    clinica_json(['success' => false, 'error' => $message], 422);
  }
}

function clinica_validate_optional_phone(string $value, string $message): void
{
  $value = trim($value);
  if ($value !== '' && !preg_match('/^\+?[0-9()\s-]{7,24}$/', $value)) {
    clinica_json(['success' => false, 'error' => $message], 422);
  }
}

function clinica_svg_looks_safe(string $content): bool
{
  return !preg_match('/(<\s*script|<\s*foreignobject|<\s*iframe|<\s*object|<\s*embed|on[a-z]+\s*=|javascript:|data:text\/html)/i', $content);
}

function clinica_bool(mixed $value): bool
{
  return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
}

function clinica_list(mixed $value): array
{
  if (is_array($value)) {
    $items = $value;
  } else {
    $items = explode(',', (string) $value);
  }

  return array_values(array_filter(array_map(static fn(mixed $item): string => trim((string) $item), $items), static fn(string $item): bool => $item !== ''));
}

function clinica_required_relation(array $record, string $key, string $resource, string $message): string
{
  $value = clinica_required_string($record, $key, $message);
  return clinica_optional_relation($value, $resource, $message);
}

function clinica_optional_relation(string $value, string $resource, string $message): string
{
  $table = clinica_get_table_for_resource($resource);
  if ($table === '') {
    clinica_json(['success' => false, 'error' => 'Recurso de relacion no valido.'], 500);
  }

  $pdo = clinica_get_db();
  $stmt = $pdo->prepare("SELECT id FROM `$table` WHERE id = ?");
  $stmt->execute([$value]);
  if ($stmt->fetch()) {
    return $value;
  }

  clinica_json(['success' => false, 'error' => $message, 'details' => "ID: $value, Resource: $resource"], 422);
}

function clinica_required_role(array $record): string
{
  $role = clinica_required_string($record, 'role', 'Rol requerido.');
  if (!in_array($role, clinica_dashboard_roles(), true)) {
    clinica_json(['success' => false, 'error' => 'Rol no permitido.'], 422);
  }

  return $role;
}

function clinica_schedule_weekday_value(array $record): int
{
  $date = trim((string) ($record['date'] ?? ''));
  if ($date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $timestamp = strtotime($date . ' 00:00:00');
    if ($timestamp !== false) {
      return (int) date('N', $timestamp);
    }
  }

  return max(1, min(7, (int) ($record['weekday'] ?? 1)));
}

function clinica_optional_future_date(array $record, string $key): ?string
{
  $value = trim((string) ($record[$key] ?? ''));
  if ($value === '') {
    return null;
  }

  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
    clinica_json(['success' => false, 'error' => 'La fecha del horario no es valida.'], 422);
  }

  [$year, $month, $day] = array_map('intval', explode('-', $value));
  if (!checkdate($month, $day, $year)) {
    clinica_json(['success' => false, 'error' => 'La fecha del horario no es valida.'], 422);
  }

  // Allow editing past schedules, but not creating new ones in the past.
  // This validation should be contextual (e.g., only for new records).
  // For simplicity, we allow past dates for editing existing records.

  return $value;
}

function clinica_validate_schedule_constraints(array $record, bool $isUpdate): void
{
  $date = (string) ($record['date'] ?? '');
  if (!$isUpdate && $date !== '' && $date < date('Y-m-d')) {
    clinica_json(['success' => false, 'error' => 'No se pueden crear horarios en fechas anteriores.'], 422);
  }

  $startMinutes = clinica_time_to_minutes((string) $record['start_time']);
  $endMinutes = clinica_time_to_minutes((string) $record['end_time']);
  if ($startMinutes === null || $endMinutes === null) {
    clinica_json(['success' => false, 'error' => 'Hora de horario invalida.'], 422);
  }

  $duration = $endMinutes - $startMinutes;
  $slotMinutes = (int) ($record['slot_minutes'] ?? 15);
  if ($duration < $slotMinutes) {
    clinica_json(['success' => false, 'error' => 'La duracion del bloque debe ser mayor o igual al cupo.'], 422);
  }

  if (($record['active'] ?? true) && clinica_schedule_has_overlap($record)) {
    clinica_json(['success' => false, 'error' => 'El horario se solapa con otro bloque activo del mismo doctor.'], 409);
  }
}

function clinica_schedule_has_overlap(array $record): bool
{
  $pdo = clinica_get_db();
  $doctorId = (string) ($record['doctor_id'] ?? '');
  $id = (string) ($record['id'] ?? '');
  $weekday = (int) ($record['weekday'] ?? 0);
  $date = $record['date'] ?? null;

  if ($doctorId === '' || $weekday < 1 || $weekday > 7) {
    return false;
  }

  if ($date !== null && $date !== '') {
    $stmt = $pdo->prepare("SELECT id, start_time, end_time FROM doctor_schedules WHERE doctor_id = ? AND active = 1 AND id <> ? AND (date = ? OR (date IS NULL AND weekday = ?))");
    $stmt->execute([$doctorId, $id, $date, $weekday]);
  } else {
    $stmt = $pdo->prepare("SELECT id, start_time, end_time FROM doctor_schedules WHERE doctor_id = ? AND active = 1 AND id <> ? AND date IS NULL AND weekday = ?");
    $stmt->execute([$doctorId, $id, $weekday]);
  }

  $start = (string) ($record['start_time'] ?? '');
  $end = (string) ($record['end_time'] ?? '');
  foreach ($stmt->fetchAll() as $existing) {
    $existingStart = substr((string) $existing['start_time'], 0, 5);
    $existingEnd = substr((string) $existing['end_time'], 0, 5);
    if ($existingStart < $end && $existingEnd > $start) {
      return true;
    }
  }

  return false;
}

function clinica_required_time(array $record, string $key, string $message): string
{
  $value = clinica_required_string($record, $key, $message);
  if (!preg_match('/^(\d{2}):(\d{2})$/', $value, $matches)) {
    clinica_json(['success' => false, 'error' => $message], 422);
  }

  $hour = (int) $matches[1];
  $minute = (int) $matches[2];
  if ($hour > 23 || $minute > 59) {
    clinica_json(['success' => false, 'error' => $message], 422);
  }

  return $value;
}

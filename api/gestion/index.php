<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../data.php';

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
  clinica_json([
    'success' => true,
    'resource' => $resource,
    'data' => $resource === 'clinic' ? clinica_get_clinic_settings() : clinica_get_database_resource($resource),
    'metadata' => clinica_gestion_metadata(),
  ]);
}

if ($method === 'POST' && strtolower(trim((string) ($_POST['action'] ?? ''))) === 'upload') {
  clinica_gestion_upload_image($resource, $_POST, $_FILES);
}

$payload = clinica_request_json();
$action = strtolower(trim((string) ($payload['action'] ?? 'save')));

if ($method === 'DELETE') {
  $action = 'delete';
  $payload['id'] = $_GET['id'] ?? ($payload['id'] ?? '');
}

if ($method !== 'POST' && $method !== 'PUT' && $method !== 'PATCH' && $method !== 'DELETE') {
  clinica_json(['success' => false, 'error' => 'Metodo no permitido.'], 405);
}

if ($action === 'delete') {
  clinica_gestion_delete($resource, (string) ($payload['id'] ?? ''), $user);
}

if ($resource === 'clinic') {
  $record = is_array($payload['record'] ?? null) ? $payload['record'] : $payload;
  $clinic = clinica_normalize_clinic_record($record);
  if (!clinica_save_database_resource('clinic', $clinic)) {
    clinica_json(['success' => false, 'error' => 'No se pudo guardar la clinica.'], 500);
  }

  clinica_json(['success' => true, 'data' => $clinic, 'metadata' => clinica_gestion_metadata()]);
}

$record = is_array($payload['record'] ?? null) ? $payload['record'] : $payload;
clinica_gestion_save($resource, $record, $user);

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
    ],
    'doctors' => [
      'photo' => 'doctors',
    ],
    'comments' => [
      'avatar' => 'comments',
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
  ];

  if (!array_key_exists($mime, $extensions) || getimagesize($tmpName) === false) {
    clinica_json(['success' => false, 'error' => 'Formato no soportado. Use JPG, PNG, WEBP o GIF.'], 422);
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
  $database = clinica_read_database();
  $collection = is_array($database[$resource] ?? null) ? $database[$resource] : [];
  $id = trim((string) ($record['id'] ?? ''));
  $existingIndex = null;
  $existingRecord = null;

  foreach ($collection as $index => $item) {
    if (($item['id'] ?? '') === $id && $id !== '') {
      $existingIndex = $index;
      $existingRecord = $item;
      break;
    }
  }

  $normalized = clinica_normalize_collection_record($resource, $record, is_array($existingRecord) ? $existingRecord : []);
  $id = (string) ($normalized['id'] ?? '');

  if ($id === '') {
    clinica_json(['success' => false, 'error' => 'El registro no tiene identificador valido.'], 422);
  }

  foreach ($collection as $index => $item) {
    if ($index !== $existingIndex && ($item['id'] ?? '') === $id) {
      clinica_json(['success' => false, 'error' => 'Ya existe un registro con ese ID.'], 409);
    }
  }

  if ($resource === 'users') {
    clinica_validate_user_save($collection, $normalized, $existingIndex);
  }

  if ($resource === 'schedules' && ($normalized['start_time'] ?? '') >= ($normalized['end_time'] ?? '')) {
    clinica_json(['success' => false, 'error' => 'La hora de inicio debe ser menor que la hora de fin.'], 422);
  }

  if ($existingIndex === null) {
    $collection[] = $normalized;
  } else {
    $collection[$existingIndex] = $normalized;
  }

  $database[$resource] = array_values($collection);
  if (!clinica_write_database($database)) {
    clinica_json(['success' => false, 'error' => 'No se pudo guardar el registro.'], 500);
  }

  clinica_json([
    'success' => true,
    'data' => $database[$resource],
    'record' => $normalized,
    'metadata' => clinica_gestion_metadata(),
  ]);
}

function clinica_gestion_delete(string $resource, string $id, array $user): void
{
  if ($resource === 'clinic') {
    clinica_json(['success' => false, 'error' => 'La clinica no se elimina; actualice sus datos.'], 422);
  }

  $database = clinica_read_database();
  $collection = is_array($database[$resource] ?? null) ? $database[$resource] : [];
  $target = null;
  $filtered = [];

  foreach ($collection as $item) {
    if (($item['id'] ?? '') === $id) {
      $target = $item;
      continue;
    }

    $filtered[] = $item;
  }

  if (!is_array($target)) {
    clinica_json(['success' => false, 'error' => 'Registro no encontrado.'], 404);
  }

  if ($resource === 'users') {
    if (($target['id'] ?? '') === ($user['id'] ?? '') || ($target['username'] ?? '') === ($user['username'] ?? '')) {
      clinica_json(['success' => false, 'error' => 'No puede eliminar su propio usuario.'], 422);
    }

    $remainingAdmins = array_values(array_filter($filtered, static fn(array $item): bool => ($item['role'] ?? '') === 'ADMINISTRATOR' && ($item['active'] ?? true)));
    if (count($remainingAdmins) === 0) {
      clinica_json(['success' => false, 'error' => 'Debe quedar al menos un administrador activo.'], 422);
    }
  }

  $database[$resource] = array_values($filtered);
  if (!clinica_write_database($database)) {
    clinica_json(['success' => false, 'error' => 'No se pudo eliminar el registro.'], 500);
  }

  clinica_json([
    'success' => true,
    'data' => $database[$resource],
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
      'specialty_id' => clinica_required_relation($record, 'specialty_id', 'specialties', 'Especialidad requerida.'),
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
    'users' => [
      'id' => clinica_record_id($record, $existing, 'username', 'user'),
      'username' => strtolower(clinica_required_string($record, 'username', 'Usuario requerido.')),
      'name' => clinica_required_string($record, 'name', 'Nombre requerido.'),
      'role' => clinica_required_role($record),
      'label' => clinica_string($record, 'label', (string) ($record['role'] ?? '')),
      'password' => clinica_password_value($record, $existing),
      'doctor_id' => clinica_string($record, 'doctor_id'),
      'active' => clinica_bool($record['active'] ?? true),
    ],
    'schedules' => [
      'id' => clinica_record_id($record, $existing, 'doctor_id', 'sch'),
      'doctor_id' => clinica_required_relation($record, 'doctor_id', 'doctors', 'Doctor requerido.'),
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

function clinica_validate_user_save(array $collection, array $record, ?int $existingIndex): void
{
  foreach ($collection as $index => $item) {
    if ($index !== $existingIndex && strtolower((string) ($item['username'] ?? '')) === strtolower((string) ($record['username'] ?? ''))) {
      clinica_json(['success' => false, 'error' => 'Ya existe un usuario con ese nombre.'], 409);
    }
  }

  if (($record['role'] ?? '') === 'DOCTOR' && trim((string) ($record['doctor_id'] ?? '')) === '') {
    clinica_json(['success' => false, 'error' => 'Los usuarios doctores deben vincularse con un doctor.'], 422);
  }
}

function clinica_record_id(array $record, array $existing, string $fallbackField, string $prefix = ''): string
{
  $id = trim((string) ($record['id'] ?? $existing['id'] ?? ''));
  if ($id !== '') {
    return $id;
  }

  if ($prefix === 'sch') {
    return uniqid('sch-', false);
  }

  $source = trim((string) ($record[$fallbackField] ?? ''));
  $slug = clinica_slugify($source);
  return $slug !== '' ? ($prefix !== '' ? $prefix . '-' . $slug : $slug) : uniqid($prefix !== '' ? $prefix . '-' : 'item-', false);
}

function clinica_slugify(string $value): string
{
  $value = strtolower(trim($value));
  $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
  $value = preg_replace('/[^a-z0-9]+/', '-', $value);
  return trim((string) $value, '-');
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
  $items = clinica_get_database_resource($resource);

  foreach (is_array($items) ? $items : [] as $item) {
    if (($item['id'] ?? '') === $value) {
      return $value;
    }
  }

  clinica_json(['success' => false, 'error' => $message], 422);
}

function clinica_required_role(array $record): string
{
  $role = clinica_required_string($record, 'role', 'Rol requerido.');
  if (!in_array($role, clinica_dashboard_roles(), true)) {
    clinica_json(['success' => false, 'error' => 'Rol no permitido.'], 422);
  }

  return $role;
}

function clinica_password_value(array $record, array $existing): string
{
  $password = trim((string) ($record['password'] ?? ''));
  if ($password !== '') {
    return $password;
  }

  $existingPassword = trim((string) ($existing['password'] ?? ''));
  if ($existingPassword !== '') {
    return $existingPassword;
  }

  clinica_json(['success' => false, 'error' => 'Contrasena requerida.'], 422);
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

function clinica_optional_future_date(array $record, string $key): string
{
  $value = trim((string) ($record[$key] ?? ''));
  if ($value === '') {
    return '';
  }

  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
    clinica_json(['success' => false, 'error' => 'La fecha del horario no es valida.'], 422);
  }

  [$year, $month, $day] = array_map('intval', explode('-', $value));
  if (!checkdate($month, $day, $year)) {
    clinica_json(['success' => false, 'error' => 'La fecha del horario no es valida.'], 422);
  }

  if ($value < date('Y-m-d')) {
    clinica_json(['success' => false, 'error' => 'La fecha del horario debe ser hoy o futura.'], 422);
  }

  return $value;
}

function clinica_required_time(array $record, string $key, string $message): string
{
  $value = clinica_required_string($record, $key, $message);
  if (!preg_match('/^\d{2}:\d{2}$/', $value)) {
    clinica_json(['success' => false, 'error' => $message], 422);
  }

  return $value;
}

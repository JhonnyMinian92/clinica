<?php

require_once __DIR__ . '/db.php';

const CLINICA_SESSION_TTL_SECONDS = 28800;

const CLINICA_MAX_LOGIN_ATTEMPTS = 5;
const CLINICA_LOGIN_LOCKOUT_TIME_MINUTES = 15;
const CLINICA_GESTION_DATA_VERSION = 1;
const CLINICA_APPOINTMENT_BUFFER_MINUTES = 15;
const CLINICA_APPOINTMENTS_SEED_VERSION = 1;

/**
 * Session & CSRF helpers
 */
function clinica_is_https(): bool
{
  if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
  if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') return true;
  if (!empty($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) return true;
  return false;
}

function clinica_apply_session_cookie_params(array $params): void
{
  if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params($params);
    return;
  }

  $path = ($params['path'] ?? '/') . '; samesite=' . ($params['samesite'] ?? 'Lax');
  session_set_cookie_params(
    (int) ($params['lifetime'] ?? 0),
    $path,
    (string) ($params['domain'] ?? ''),
    (bool) ($params['secure'] ?? false),
    (bool) ($params['httponly'] ?? true)
  );
}

function clinica_set_cookie(string $name, string $value, array $options): void
{
  if (PHP_VERSION_ID >= 70300) {
    setcookie($name, $value, $options);
    return;
  }

  $path = ($options['path'] ?? '/') . '; samesite=' . ($options['samesite'] ?? 'Lax');
  setcookie(
    $name,
    $value,
    (int) ($options['expires'] ?? 0),
    $path,
    (string) ($options['domain'] ?? ''),
    (bool) ($options['secure'] ?? false),
    (bool) ($options['httponly'] ?? false)
  );
}

function clinica_base_path(): string
{
  static $basePath = null;
  if ($basePath !== null) {
    return $basePath;
  }

  $configured = trim(clinica_env('APP_BASE_PATH', ''));
  if ($configured !== '') {
    $configured = '/' . trim($configured, '/');
    $basePath = $configured === '/' ? '' : $configured;
    return $basePath;
  }

  $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? ''));
  $basePath = (str_starts_with($scriptName, '/clinica/') || $scriptName === '/clinica') ? '/clinica' : '';
  return $basePath;
}

function clinica_url(string $path = ''): string
{
  $basePath = clinica_base_path();
  $path = trim($path);

  if ($path === '' || $path === '/') {
    return $basePath === '' ? '/' : $basePath . '/';
  }

  return ($basePath === '' ? '' : $basePath) . '/' . ltrim($path, '/');
}

function clinica_public_path(string $path): string
{
  $path = trim($path);
  if ($path === '' || preg_match('/^(https?:|data:|mailto:|tel:|#)/i', $path)) {
    return $path;
  }

  if (str_starts_with($path, '/clinica/')) {
    $path = substr($path, strlen('/clinica/'));
  }

  return clinica_url(ltrim($path, '/'));
}

function clinica_normalize_clinic_paths(array $clinic): array
{
  foreach (['hero_image', 'hero_mobile_image', 'logo_image'] as $field) {
    $value = trim((string) ($clinic[$field] ?? ''));
    if ($value !== '' && !str_starts_with($value, '<svg')) {
      $clinic[$field] = clinica_public_path($value);
    }
  }

  return $clinic;
}

function clinica_ensure_session_started(): void
{
  if (session_status() === PHP_SESSION_ACTIVE) {
    if (empty($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    clinica_set_csrf_cookie($_SESSION['csrf_token']);
    return;
  }

  $secure = clinica_is_https();
  $params = [
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
  ];

  // Apply session cookie params before starting session
  clinica_apply_session_cookie_params($params);
  session_start();

  // Ensure a CSRF token exists and provide it to JS via cookie (not HttpOnly)
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  clinica_set_csrf_cookie($_SESSION['csrf_token']);
}

function clinica_get_csrf_token(): string
{
  clinica_ensure_session_started();
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return (string) $_SESSION['csrf_token'];
}

function clinica_set_csrf_cookie(string $token): void
{
  if (headers_sent()) return;
  $secure = clinica_is_https();
  clinica_set_cookie('XSRF-TOKEN', $token, [
    'expires' => time() + 60 * 60 * 24, // 1 day
    'path' => '/',
    'domain' => '',
    'secure' => $secure,
    'httponly' => false, // Must be readable by JS for single-page apps
    'samesite' => 'Lax',
  ]);
}

function clinica_validate_csrf_from_request(): bool
{
  clinica_ensure_session_started();

  $token = '';
  // Check header first
  if (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
  } else {
    // Try common header name
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    if (!empty($headers['X-CSRF-Token'])) {
      $token = $headers['X-CSRF-Token'];
    } elseif (!empty($headers['x-csrf-token'])) {
      $token = $headers['x-csrf-token'];
    }
  }

  // Fallback: look for _csrf in JSON body or POST
  if ($token === '') {
    $payload = clinica_request_json();
    if (!empty($payload['_csrf'])) $token = $payload['_csrf'];
    if ($token === '' && !empty($_POST['_csrf'])) $token = $_POST['_csrf'];
  }

  if ($token === '' || empty($_SESSION['csrf_token'])) return false;

  return hash_equals((string) $_SESSION['csrf_token'], (string) $token);
}

function clinica_is_active_record(array $record): bool
{
  return (bool) ($record['active'] ?? true);
}

function clinica_decode_json_array($value): array
{
  if (is_array($value)) {
    return $value;
  }

  $decoded = json_decode((string) $value, true);
  return is_array($decoded) ? $decoded : [];
}

function clinica_seed_clinic_settings(): array
{
  $seed = clinica_read_database();
  return is_array($seed['clinic'] ?? null) ? clinica_normalize_clinic_paths($seed['clinic']) : [];
}

function clinica_seed_collection(string $key, bool $activeOnly = false): array
{
  $seed = clinica_read_database();
  $items = $seed[$key] ?? [];
  if (!is_array($items)) {
    return [];
  }

  $items = array_values(array_filter($items, 'is_array'));
  if ($activeOnly) {
    $items = array_values(array_filter($items, 'clinica_is_active_record'));
  }

  return $items;
}

function clinica_normalize_doctor_record(array $doctor): array
{
  $doctor['languages'] = clinica_decode_json_array($doctor['languages'] ?? []);
  $doctor['recognitions'] = clinica_decode_json_array($doctor['recognitions'] ?? []);
  return $doctor;
}

function clinica_get_clinic_settings(): array
{
  try {
    $pdo = clinica_get_db();
    $stmt = $pdo->query("SELECT * FROM clinics ORDER BY id ASC LIMIT 1");
    $clinic = $stmt->fetch();
  } catch (Throwable $e) {
    error_log('Clinic settings fallback: ' . $e->getMessage());
    return clinica_seed_clinic_settings();
  }

  if (!$clinic) {
    // If database has no clinic row yet, fall back to built-in seed clinic so the public site renders
    return clinica_seed_clinic_settings();
  }

  // Decode JSON fields
  $clinic['stats'] = clinica_decode_json_array($clinic['stats'] ?? []);
  $clinic['about_cards'] = clinica_decode_json_array($clinic['about_cards'] ?? []);

  return clinica_normalize_clinic_paths($clinic);
}

function clinica_get_specialties(bool $activeOnly = false): array
{
  try {
    $pdo = clinica_get_db();
    $sql = "SELECT * FROM specialties WHERE 1=1";
    if ($activeOnly) {
      $sql .= " AND active = 1";
    }
    $sql .= " ORDER BY name ASC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (Throwable $e) {
    error_log('Specialties fallback: ' . $e->getMessage());
    return clinica_seed_collection('specialties', $activeOnly);
  }
}

function clinica_get_services(bool $activeOnly = false): array
{
  try {
    $pdo = clinica_get_db();
    $sql = "SELECT * FROM services";
    if ($activeOnly) {
      $sql .= " WHERE active = 1";
    }
    $stmt = $pdo->query($sql); // No ORDER BY, assuming order is not critical or handled by frontend
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (Throwable $e) {
    error_log('Services fallback: ' . $e->getMessage());
    return clinica_seed_collection('services', $activeOnly);
  }
}

function clinica_get_comments(bool $activeOnly = false): array
{
  try {
    $pdo = clinica_get_db();
    $sql = "SELECT * FROM testimonials"; // Assuming 'comments' are stored in 'testimonials' table
    if ($activeOnly) {
      $sql .= " WHERE active = 1";
    }
    $stmt = $pdo->query($sql); // No ORDER BY, assuming order is not critical or handled by frontend
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (Throwable $e) {
    error_log('Comments fallback: ' . $e->getMessage());
    return clinica_seed_collection('comments', $activeOnly);
  }
}

function clinica_get_doctors(bool $activeOnly = false): array
{
  try {
    $pdo = clinica_get_db();
    $sql = "SELECT * FROM doctors WHERE 1=1";
    if ($activeOnly) {
      $sql .= " AND active = 1";
    }
    $sql .= " ORDER BY name ASC";
    $stmt = $pdo->query($sql);
    $doctors = $stmt->fetchAll();
  } catch (Throwable $e) {
    error_log('Doctors fallback: ' . $e->getMessage());
    $doctors = clinica_seed_collection('doctors', $activeOnly);
  }

  // Decode JSON fields
  return array_map('clinica_normalize_doctor_record', $doctors);
}

function clinica_get_schedules(bool $activeOnly = false): array
{
  try {
    $pdo = clinica_get_db();
    $sql = "SELECT * FROM doctor_schedules WHERE 1=1";
    if ($activeOnly) {
      $sql .= " AND active = 1";
    }
    $sql .= " ORDER BY doctor_id, date, weekday, start_time";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (Throwable $e) {
    error_log('Schedules fallback: ' . $e->getMessage());
    return clinica_seed_collection('schedules', $activeOnly);
  }
}

function clinica_get_all_specialties_map(): array
{
  static $specialtiesMap = null;
  if ($specialtiesMap === null) {
    $specialtiesMap = [];
    foreach (clinica_get_specialties() as $specialty) {
      $specialtiesMap[$specialty['id']] = $specialty['name'];
    }
  }
  return $specialtiesMap;
}

function clinica_specialty_name_by_id(string $specialtyId): string
{
  $map = clinica_get_all_specialties_map();
  return $map[$specialtyId] ?? $specialtyId;
}

function clinica_find_doctor(string $doctorId): ?array
{
  try {
    $pdo = clinica_get_db();
    $stmt = $pdo->prepare("SELECT * FROM doctors WHERE id = ?");
    $stmt->execute([$doctorId]);
    $doctor = $stmt->fetch();
  } catch (Throwable $e) {
    error_log('Find doctor fallback: ' . $e->getMessage());
    $doctor = null;
    foreach (clinica_seed_collection('doctors') as $seedDoctor) {
      if ((string) ($seedDoctor['id'] ?? '') === $doctorId) {
        $doctor = $seedDoctor;
        break;
      }
    }
  }

  if ($doctor) {
    $doctor = clinica_normalize_doctor_record($doctor);
  }
  return $doctor ?: null;
}

function clinica_schedule_shift_label(string $startTime, string $endTime): string
{
  if ($startTime <= '07:00' && $endTime >= '22:00') {
    return 'Completo';
  }

  if ($endTime <= '13:00') {
    return 'Mañana';
  }

  return 'Tarde';
}

function clinica_schedule_map_for_doctor(string $doctorId): array
{
  $map = [];
  try {
    $pdo = clinica_get_db();
    $stmt = $pdo->prepare("SELECT * FROM doctor_schedules WHERE doctor_id = ? AND active = 1 ORDER BY date, weekday, start_time");
    $stmt->execute([$doctorId]);
    $schedules = $stmt->fetchAll();
  } catch (Throwable $e) {
    error_log('Schedule map fallback: ' . $e->getMessage());
    $schedules = array_values(array_filter(clinica_seed_collection('schedules', true), function (array $schedule) use ($doctorId): bool {
      return (string) ($schedule['doctor_id'] ?? '') === $doctorId;
    }));
  }

  foreach ($schedules as $schedule) {
    $weekday = (string) ((int) ($schedule['weekday'] ?? 0));
    if ($weekday === '0') {
      continue;
    }

    $label = clinica_schedule_shift_label((string) ($schedule['start_time'] ?? ''), (string) ($schedule['end_time'] ?? ''));
    $date = (string) ($schedule['date'] ?? '');

    if ($date !== '') {
      $map[$date] = $label;
      continue;
    }

    $map[$weekday] = $label;
  }

  return $map;
}

function clinica_schedule_rules_for_doctor(string $doctorId): array
{
  $rules = [];
  try {
    $pdo = clinica_get_db();
    $stmt = $pdo->prepare("SELECT * FROM doctor_schedules WHERE doctor_id = ? AND active = 1 ORDER BY date, weekday, start_time");
    $stmt->execute([$doctorId]);
    $schedules = $stmt->fetchAll();
  } catch (Throwable $e) {
    error_log('Schedule rules fallback: ' . $e->getMessage());
    $schedules = array_values(array_filter(clinica_seed_collection('schedules', true), function (array $schedule) use ($doctorId): bool {
      return (string) ($schedule['doctor_id'] ?? '') === $doctorId;
    }));
  }

  foreach ($schedules as $schedule) {
    $weekday = (string) ((int) ($schedule['weekday'] ?? 0));
    if ($weekday === '0') {
      continue;
    }

    $rule = [
      'start_time' => (string) ($schedule['start_time'] ?? ''),
      'end_time' => (string) ($schedule['end_time'] ?? ''),
      'slot_minutes' => max(5, (int) ($schedule['slot_minutes'] ?? 15)),
    ];

    $date = (string) ($schedule['date'] ?? '');
    if ($date !== '') {
      $rule['date'] = $date;
      $rules[$date][] = $rule;
      continue;
    }

    $rules[$weekday][] = $rule;
  }

  return $rules;
}

function clinica_time_to_minutes(string $time): ?int
{
  if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)(?::[0-5]\d)?$/', trim($time), $matches)) {
    return null;
  }

  return ((int) $matches[1]) * 60 + (int) $matches[2];
}

function clinica_is_valid_time_string(?string $time): bool
{
  return clinica_time_to_minutes((string) $time) !== null;
}

function clinica_schedule_rule_matches(DateTimeImmutable $appointmentDateTime, array $rule): bool
{
  $appointmentMinutes = clinica_time_to_minutes($appointmentDateTime->format('H:i'));
  $startMinutes = clinica_time_to_minutes((string) ($rule['start_time'] ?? ''));
  $endMinutes = clinica_time_to_minutes((string) ($rule['end_time'] ?? ''));
  $slotMinutes = max(5, (int) ($rule['slot_minutes'] ?? 15));

  if ($appointmentMinutes === null || $startMinutes === null || $endMinutes === null || $startMinutes >= $endMinutes) {
    return false;
  }

  if ($appointmentMinutes < $startMinutes || $appointmentMinutes >= $endMinutes) {
    return false;
  }

  return (($appointmentMinutes - $startMinutes) % $slotMinutes) === 0;
}

function clinica_matching_schedule_rule(DateTimeImmutable $appointmentDateTime, array $scheduleRules, int $weekday): ?array
{
  $appointmentDate = $appointmentDateTime->format('Y-m-d');
  $rules = array_merge($scheduleRules[$appointmentDate] ?? [], $scheduleRules[(string) $weekday] ?? []);

  foreach ($rules as $rule) {
    if (clinica_schedule_rule_matches($appointmentDateTime, $rule)) {
      return $rule;
    }
  }

  return null;
}

/**
 * Checks if a given DateTime falls within an active schedule slot for a doctor.
 * Also respects slot_minutes.
 */
function clinica_is_time_slot_available(DateTimeImmutable $appointmentDateTime, array $scheduleRules, int $weekday): bool
{
  return clinica_matching_schedule_rule($appointmentDateTime, $scheduleRules, $weekday) !== null;
}

function clinica_get_slot_minutes_for_time(DateTimeImmutable $appointmentDateTime, array $scheduleRules, int $weekday): int
{
  $rule = clinica_matching_schedule_rule($appointmentDateTime, $scheduleRules, $weekday);
  return max(5, (int) ($rule['slot_minutes'] ?? 15));
}

function clinica_get_doctor_photo_url(array $doctor): string
{
  $photo = trim((string) ($doctor['photo'] ?? ''));
  // Un campo de foto vacío, nulo o con el avatar genérico se considera "sin foto personalizada".
  if ($photo !== '' && $photo !== '/clinica/public/assets/avatar.png' && $photo !== clinica_url('public/assets/avatar.png')) {
    return clinica_public_path($photo);
  }

  $name = trim((string) ($doctor['name'] ?? ''));
  if (str_starts_with($name, 'Dra.')) {
    return clinica_url('public/assets/doctora_avatar.png');
  }

  return clinica_url('public/assets/doctor_avatar.jpg');
}

function clinica_get_display_name($clinic_settings = null): string
{
  if ($clinic_settings === null) {
    $clinic_settings = clinica_get_clinic_settings();
  }

  $logoLabel = trim((string) ($clinic_settings['logo_label'] ?? ''));
  $clinicName = trim((string) ($clinic_settings['name'] ?? ''));
  $legalName = trim((string) ($clinic_settings['legal_name'] ?? ''));

  if ($logoLabel !== '') {
    return $logoLabel;
  }
  if ($clinicName !== '') {
    return $clinicName;
  }
  if ($legalName !== '') {
    return $legalName;
  }

  return 'Clinica Estandar';
}

function clinica_render_logo_element(array $clinic_settings): void
{
  $clinic_name = clinica_get_display_name($clinic_settings);
  $logo_image = $clinic_settings['logo_image'] ?? '';

  if (trim($logo_image) !== '') {
    if (str_starts_with(trim($logo_image), '<svg')) {
      echo $logo_image; // It's SVG code, render directly
    } else {
      echo '<img src="' . htmlspecialchars($logo_image) . '" alt="' . htmlspecialchars($clinic_name) . ' logo" class="logo-img">';
    }
  } else {
    // Fallback to text if no image is provided
    echo '<span class="clinic-name-header">' . htmlspecialchars($clinic_name) . '</span>';
  }
}

function clinica_public_doctor(array $doctor): array
{
  $specialtyId = (string) ($doctor['specialty_id'] ?? '');

  return [
    'id' => (string) ($doctor['id'] ?? ''),
    'nombre' => (string) ($doctor['name'] ?? ''),
    'foto' => clinica_get_doctor_photo_url($doctor),
    'especialidad' => clinica_specialty_name_by_id($specialtyId),
    'especialidad_id' => $specialtyId,
    'subespecialidad' => (string) ($doctor['sub_specialty'] ?? ''),
    'experiencia_anos' => (int) ($doctor['experience_years'] ?? 0),
    'universidad' => (string) ($doctor['university'] ?? ''),
    'reconocimientos' => array_values($doctor['recognitions'] ?? []),
    'idiomas' => array_values($doctor['languages'] ?? []),
    'valoracion' => (int) ($doctor['rating'] ?? 0),
    'pacientes' => (int) ($doctor['patients'] ?? 0),
    'horario_semanal' => clinica_schedule_map_for_doctor((string) ($doctor['id'] ?? '')),
    'horarios_detallados' => clinica_schedule_rules_for_doctor((string) ($doctor['id'] ?? '')),
    'featured' => (bool) ($doctor['featured'] ?? false),
    'active' => (bool) ($doctor['active'] ?? true),
  ];
}

function clinica_public_doctors_for_catalog(): array
{
  return array_map('clinica_public_doctor', clinica_get_doctors(true));
}

function clinica_public_doctors_grouped_by_specialty(): array
{
  $grouped = [];

  foreach (clinica_get_doctors(true) as $doctor) {
    $specialtyId = (string) ($doctor['specialty_id'] ?? '');
    if ($specialtyId === '') continue;
    $publicDoctor = clinica_public_doctor($doctor);
    $grouped[$specialtyId][] = [
      'id' => $publicDoctor['id'],
      'nombre' => $publicDoctor['nombre'],
      'foto' => $publicDoctor['foto'],
      'valoracion' => $publicDoctor['valoracion'],
      'horario_semanal' => $publicDoctor['horario_semanal'],
      'horarios_detallados' => $publicDoctor['horarios_detallados'],
    ];
  }

  return $grouped;
}

function clinica_public_specialties_for_catalog(bool $featuredOnly = false): array
{
  $specialties = clinica_get_specialties(true);

  if ($featuredOnly) {
    $specialties = array_values(array_filter($specialties, static function (array $item): bool {
      return (bool) ($item['featured'] ?? false);
    }));
  }

  return array_map(static function (array $specialty): array {
    return [
      'id' => (string) ($specialty['id'] ?? ''),
      'titulo' => (string) ($specialty['name'] ?? ''),
      'descripcion' => (string) ($specialty['description'] ?? $specialty['summary'] ?? ''),
      'resumen' => (string) ($specialty['summary'] ?? ''),
      'icono' => (string) ($specialty['icon'] ?? ''),
    ];
  }, $specialties);
}

function clinica_public_services_for_home(): array
{
  return array_map(static function (array $service): array {
    return [
      'id' => (string) ($service['id'] ?? ''),
      'titulo' => (string) ($service['title'] ?? ''),
      'descripcion' => (string) ($service['description'] ?? ''),
      'icono' => (string) ($service['icon'] ?? ''),
    ];
  }, clinica_get_services(true));
}

function clinica_public_comments_for_home(): array
{
  $comments = array_values(array_filter(clinica_get_comments(true), static function (array $item): bool {
    return (bool) ($item['featured'] ?? true);
  }));

  return array_map(static function (array $comment): array {
    return [
      'texto' => (string) ($comment['text'] ?? ''),
      'autor' => (string) ($comment['author'] ?? ''),
      'detalle' => (string) ($comment['detail'] ?? ''),
      'valoracion' => max(0, min(5, (int) ($comment['rating'] ?? 0))),
      'avatar' => (string) ($comment['avatar'] ?? 'public/assets/avatar.png'),
    ];
  }, $comments);
}

function clinica_public_doctors_for_home(): array
{
  $doctors = array_values(array_filter(clinica_get_doctors(true), static function (array $doctor): bool {
    return (bool) ($doctor['featured'] ?? false);
  }));

  return array_map(static function (array $doctor): array {
    $publicDoctor = clinica_public_doctor($doctor);
    return [
      'id' => $publicDoctor['id'],
      'nombre' => $publicDoctor['nombre'],
      'especialidad' => trim($publicDoctor['especialidad'] . ' ' . $publicDoctor['subespecialidad']),
      'experiencia' => $publicDoctor['experiencia_anos'] . '+ anos de experiencia',
      'foto' => $publicDoctor['foto'],
    ];
  }, $doctors);
}

function clinica_public_booked_slots(): array
{
  try {
    $pdo = clinica_get_db(); // This function is for public display, not for strict business logic validation.
    $stmt = $pdo->query("SELECT doctor_id, date, time FROM appointments WHERE status != 'rechazada'");
    $results = $stmt->fetchAll();
  } catch (Throwable $e) {
    error_log('Booked slots fallback: ' . $e->getMessage());
    return [];
  }

  $booked = [];
  foreach ($results as $row) {
    if (!isset($booked[$row['doctor_id']])) {
      $booked[$row['doctor_id']] = [];
    }
    $booked[$row['doctor_id']][$row['date']][] = substr($row['time'], 0, 5);
  }
  return $booked;
}

function clinica_demo_users(): array
{
  $pdo = clinica_get_db();
  $stmt = $pdo->query("SELECT id, username, password, name, role, doctor_id FROM users WHERE active = 1");
  $users = $stmt->fetchAll();

  $mapped = [];

  foreach ($users as $user) {
    $username = strtolower(trim((string) ($user['username'] ?? '')));
    if ($username === '') {
      continue;
    }

    // If the user is a doctor, their session ID should be their doctor_id.
    // This is crucial for the dashboard logic to find their appointments.
    $sessionUserId = ($user['role'] === 'DOCTOR' && !empty($user['doctor_id']))
      ? (string) $user['doctor_id']
      : (string) $user['id'];

    $mapped[$username] = [
      'id' => $sessionUserId,
      'username' => $username,
      'name' => (string) ($user['name'] ?? $username),
      'role' => (string) ($user['role'] ?? ''),
      'label' => (string) ($user['role'] ?? ''), // 'label' is not in the DB, using 'role' as fallback.
      // Intentionally do NOT expose password or password hash to the public mapping.
    ];
  }

  return $mapped;
}

function clinica_dashboard_roles(): array
{
  return ['ADMINISTRATOR', 'SECRETARIA', 'DOCTOR'];
}

function clinica_can_see_all_appointments(string $role): bool
{
  return in_array($role, ['ADMINISTRATOR', 'SECRETARIA'], true);
}

function clinica_can_see_past_appointments(string $role): bool
{
  return clinica_can_see_all_appointments($role);
}

function clinica_can_access_management(string $role): bool
{
  return $role === 'ADMINISTRATOR';
}

function clinica_permissions_for_role(string $role): array
{
  return [
    'dashboard' => in_array($role, clinica_dashboard_roles(), true),
    'management' => clinica_can_access_management($role),
    'all_appointments' => clinica_can_see_all_appointments($role),
    'past_appointments' => clinica_can_see_past_appointments($role),
    'own_current_future_appointments' => $role === 'DOCTOR',
  ];
}

function clinica_default_route_for_role(string $role): string
{
  return in_array($role, clinica_dashboard_roles(), true)
    ? clinica_url('pages/dashboard/')
    : clinica_url('pages/login/');
}

function clinica_normalize_internal_redirect(?string $redirect): string
{
  $redirect = trim((string) $redirect);
  if ($redirect === '') {
    return '';
  }

  $parts = parse_url($redirect);
  if ($parts === false || isset($parts['scheme']) || isset($parts['host'])) {
    return '';
  }

  $path = (string) ($parts['path'] ?? '');
  $basePath = clinica_base_path();
  if ($basePath !== '') {
    if (!str_starts_with($path, $basePath . '/')) {
      return '';
    }
  } elseif (!str_starts_with($path, '/') || str_starts_with($path, '//')) {
    return '';
  }

  $query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';
  $fragment = isset($parts['fragment']) && $parts['fragment'] !== '' ? '#' . $parts['fragment'] : '';

  return $path . $query . $fragment;
}

function clinica_role_can_open_redirect(string $redirect, string $role): bool
{
  $path = (string) (parse_url($redirect, PHP_URL_PATH) ?: '');
  $basePath = clinica_base_path();
  $relativePath = $basePath !== '' && str_starts_with($path, $basePath . '/')
    ? substr($path, strlen($basePath))
    : $path;

  if (!in_array($role, clinica_dashboard_roles(), true)) {
    return false;
  }

  if (str_starts_with($relativePath, '/pages/gestion/')) {
    return clinica_can_access_management($role);
  }

  if (str_starts_with($relativePath, '/pages/login/') || str_starts_with($relativePath, '/api/')) {
    return false;
  }

  return $basePath === ''
    ? str_starts_with($path, '/') && !str_starts_with($path, '//')
    : str_starts_with($path, $basePath . '/');
}

function clinica_resolve_login_redirect(?string $requestedRedirect, array $user): string
{
  $role = (string) ($user['role'] ?? '');
  $fallback = clinica_default_route_for_role($role);
  $target = clinica_normalize_internal_redirect($requestedRedirect);

  if ($target !== '' && clinica_role_can_open_redirect($target, $role)) {
    return $target;
  }

  return $fallback;
}

function clinica_statuses(): array
{
  return ['pendiente', 'atendida', 'no-presentado', 'rechazada', 'cancelada'];
}

function clinica_public_user(array $user): array
{
  $role = (string) ($user['role'] ?? '');

  return [
    'id' => (string) ($user['id'] ?? ''),
    'name' => (string) ($user['name'] ?? ''),
    'role' => $role,
    'label' => (string) ($user['label'] ?? $role),
    'permissions' => clinica_permissions_for_role($role),
  ];
}

function clinica_current_user(): ?array
{
  if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
  }

  $user = $_SESSION['user'] ?? null;
  if (!is_array($user)) {
    return null;
  }

  $startedAt = (int) ($_SESSION['auth_started_at'] ?? 0);
  if ($startedAt <= 0 || time() - $startedAt >= CLINICA_SESSION_TTL_SECONDS) {
    clinica_destroy_session();
    return null;
  }

  return $user;
}

function clinica_is_valid_dashboard_date(?string $date): bool
{
  if ($date === null || $date === '') {
    return false;
  }
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    return false;
  }
  [$year, $month, $day] = array_map('intval', explode('-', $date));
  return checkdate($month, $day, $year);
}

function clinica_normalize_date_scope(string $scope, string $role): string
{
  $allowedScopes = ['today', 'tomorrow', 'future', 'past', 'all'];
  if (!in_array($scope, $allowedScopes, true)) {
    return 'today'; // Default scope
  }

  // Doctors cannot see past or all appointments, normalize to 'future'
  if ($role === 'DOCTOR' && in_array($scope, ['past', 'all'], true)) {
    return 'future';
  }

  return $scope;
}

function clinica_dashboard_status_counts(array $appointments): array
{
  $counts = array_fill_keys(clinica_statuses(), 0);
  $counts['all'] = count($appointments);

  foreach ($appointments as $appointment) {
    $status = (string) ($appointment['estado'] ?? '');
    if (array_key_exists($status, $counts)) {
      $counts[$status]++;
    }
  }
  return $counts;
}

function clinica_get_user_id_from_session(): ?int
{
  $user = clinica_current_user();
  if (!is_array($user)) {
    return null;
  }

  $dbId = (int) ($user['db_id'] ?? 0);
  if ($dbId > 0) {
    return $dbId;
  }

  $role = (string) ($user['role'] ?? '');
  $sessionId = (string) ($user['id'] ?? '');
  if ($role !== 'DOCTOR' && ctype_digit($sessionId)) {
    return (int) $sessionId;
  }

  return null;
}

/**
 * Legacy function to read from JSON, only used by the seeder.
 */
function clinica_read_database(): array
{
  $path = __DIR__ . '/storage/gestion-data.json';

  // Prefer filesystem JSON if available (legacy), but fall back to in-code seed data.
  if (is_file($path)) {
    $raw = @file_get_contents($path);
    $decoded = $raw !== false ? json_decode($raw, true) : null;

    if (is_array($decoded)) {
      return $decoded;
    }
    // If file exists but can't be decoded, fall through to seed fallback.
  }

  // Fallback: return built-in seed data so seeding can run without external files.
  return clinica_get_seed_data();
}

/**
 * Provides the default seed data for the clinic.
 * This is used as a fallback if the database is empty or if the seeder is run.
 */
function clinica_get_seed_data(): array
{
  return [
    'clinic' => [
      'name' => 'Clinica Estandar',
      'legal_name' => 'Clinica Estandar S.A.',
      'logo_label' => 'Clinica Estandar',
      'logo_image' => '',
      'tagline' => 'Medicina de vanguardia con un enfoque humano y personalizado.',
      'description' => 'Centro medico privado con especialistas, tecnologia avanzada y atencion cercana.',
      'meta_title' => 'Clinica Estandar | Medicina de Vanguardia y Cuidado Humano',
      'meta_description' => 'Clinica Estandar: atencion medica especializada, tecnologia de punta y profesionales comprometidos con su bienestar.',
      'canonical_url' => 'http://localhost/clinica/',
      'hero_title' => 'Medicina de Vanguardia, Cuidado Humano',
      'hero_subtitle' => 'Combinamos alta tecnologia con especialistas comprometidos con su salud y bienestar.',
      'hero_image' => '/clinica/public/assets/banner.jpg',
      'hero_mobile_image' => '/clinica/public/assets/banner_mobil.jpg',
      'about_title' => 'Su Salud, Nuestra Prioridad',
      'about_subtitle' => 'Descubra por que somos la eleccion de confianza para miles de pacientes.',
      'services_title' => 'Nuestros Servicios',
      'services_subtitle' => 'Ofrecemos una gama completa de servicios medicos para cuidar de usted en cada etapa.',
      'specialties_title' => 'Nuestras Especialidades',
      'specialties_subtitle' => 'Cobertura medica integral para todas sus necesidades de salud.',
      'doctors_title' => 'Conozca a Nuestro Equipo',
      'doctors_subtitle' => 'Profesionales que combinan conocimiento, experiencia y vocacion.',
      'comments_title' => 'La Voz de Nuestros Pacientes',
      'comments_subtitle' => 'Historias reales de quienes han confiado su salud en nuestras manos.',
      'address' => 'Buenavista y Tarqui, Machala, Ecuador',
      'email' => 'info@clinicaestandar.com',
      'phone_display' => '(07) 293-3888',
      'phone_href' => '+59372933888',
      'emergency_phone' => '911',
      'whatsapp_url' => 'https://wa.me/593999999999',
      'facebook_url' => 'https://facebook.com/clinicaestandar',
      'instagram_url' => 'https://instagram.com/clinicaestandar',
    ],
  ];
}

function clinica_start_authenticated_session(array $user): void
{
  // Ensure session cookie params are set correctly and session started
  clinica_ensure_session_started();

  session_regenerate_id(true);
  $_SESSION['user'] = $user;
  $_SESSION['auth_started_at'] = time();
  $_SESSION['last_login_attempt_time'] = null; // Clear failed attempts on successful login
  // Refresh CSRF cookie when a new authenticated session starts
  clinica_set_csrf_cookie(clinica_get_csrf_token());
  clinica_set_session_cookie_expiration($_SESSION['auth_started_at'] + CLINICA_SESSION_TTL_SECONDS);
}

function clinica_session_expires_at(): ?int
{
  if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
  }

  $startedAt = (int) ($_SESSION['auth_started_at'] ?? 0);
  return $startedAt > 0 ? $startedAt + CLINICA_SESSION_TTL_SECONDS : null;
}

function clinica_set_session_cookie_expiration(int $expiresAt): void
{
  if (session_status() !== PHP_SESSION_ACTIVE || headers_sent()) {
    return;
  }

  $params = session_get_cookie_params();
  // Ensure secure flag reflects environment
  $secure = $params['secure'] ?? false;
  if (!$secure) {
    $secure = clinica_is_https();
  }

  clinica_set_cookie(session_name(), session_id(), [
    'expires' => $expiresAt,
    'path' => $params['path'] ?: '/',
    'domain' => $params['domain'] ?? '',
    'secure' => (bool) $secure,
    'httponly' => true,
    'samesite' => $params['samesite'] ?? 'Lax',
  ]);
}

function clinica_destroy_session(): void
{
  // Ensure session started so it can be destroyed
  clinica_ensure_session_started();

  $_SESSION = [];

  if (ini_get('session.use_cookies') && !headers_sent()) {
    $params = session_get_cookie_params();
    clinica_set_cookie(session_name(), '', [
      'expires' => time() - 42000,
      'path' => $params['path'] ?: '/',
      'domain' => $params['domain'] ?? '',
      'secure' => $params['secure'] ?? clinica_is_https(),
      'httponly' => true,
      'samesite' => $params['samesite'] ?? 'Lax',
    ]);

    // Remove CSRF token cookie as well
    clinica_set_cookie('XSRF-TOKEN', '', [
      'expires' => time() - 42000,
      'path' => '/',
      'domain' => '',
      'secure' => clinica_is_https(),
      'httponly' => false,
      'samesite' => 'Lax',
    ]);
  }

  session_destroy();
}

function clinica_json(array $payload, int $statusCode = 200): void
{
  http_response_code($statusCode);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit();
}

function clinica_log_audit_event(?int $userId, string $action, string $message, array $details = [], ?string $resourceType = null, ?string $resourceId = null): void
{
  try {
    $pdo = clinica_get_db();
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, resource_type, resource_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
      $userId,
      $action,
      $resourceType,
      $resourceId,
      json_encode($details),
      $ipAddress,
    ]);
  } catch (PDOException $e) {
    // Log to error_log or similar, but do not interrupt main flow
    error_log("Error logging audit event: " . $e->getMessage());
  }
}

function clinica_get_failed_login_attempts(string $username): int
{
  clinica_ensure_session_started();
  $lastAttemptTime = $_SESSION['last_login_attempt_time'][$username] ?? 0;
  $failedCount = $_SESSION['failed_login_attempts'][$username] ?? 0;

  // Reset count if lockout time has passed
  if ($lastAttemptTime > 0 && (time() - $lastAttemptTime) > (CLINICA_LOGIN_LOCKOUT_TIME_MINUTES * 60)) {
    $failedCount = 0;
    $_SESSION['failed_login_attempts'][$username] = 0;
    $_SESSION['last_login_attempt_time'][$username] = 0;
  }

  return $failedCount;
}

function clinica_record_failed_login_attempt(string $username): void
{
  clinica_ensure_session_started();
  $_SESSION['failed_login_attempts'][$username] = ($_SESSION['failed_login_attempts'][$username] ?? 0) + 1;
  $_SESSION['last_login_attempt_time'][$username] = time();
  clinica_log_audit_event(null, 'login_failed', 'Intento de autenticacion fallido', ['username' => $username], 'user', $username);
}

function clinica_clear_failed_login_attempts(string $username): void
{
  clinica_ensure_session_started();
  if (isset($_SESSION['failed_login_attempts'][$username])) {
    unset($_SESSION['failed_login_attempts'][$username]);
    unset($_SESSION['last_login_attempt_time'][$username]);
  }
}

function clinica_slugify(string $value): string
{
  $value = strtolower(trim($value));
  // Transliterate to ASCII
  $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
  // Replace non-alphanumeric chars with a hyphen
  $value = preg_replace('/[^a-z0-9]+/', '-', $value);
  // Trim hyphens from start and end
  return trim((string) $value, '-');
}

function clinica_request_json(): array
{
  $rawInput = file_get_contents('php://input');
  if ($rawInput === false || trim($rawInput) === '') {
    return [];
  }

  $rawInput = preg_replace('/^\xEF\xBB\xBF/', '', $rawInput);
  $payload = json_decode($rawInput, true);
  return is_array($payload) ? $payload : [];
}

function clinica_transform_appointment(array $appointment, array $user): array
{
  $transformed = $appointment;

  if (clinica_can_see_all_appointments((string) ($user['role'] ?? ''))) {
    $transformed['doctor_info'] = [
      'id' => $appointment['doctor_id'],
      'nombre' => $appointment['doctor_nombre'],
    ];
  }

  unset($transformed['doctor_id'], $transformed['doctor_nombre']);
  return $transformed;
}

function clinica_normalize_dashboard_status(?string $status): string
{
  $status = trim((string) $status);
  return in_array($status, clinica_statuses(), true) ? $status : 'all';
}

function clinica_get_all_doctors_for_select(): array
{
  $pdo = clinica_get_db();
  $stmt = $pdo->query("SELECT id, name AS nombre FROM doctors WHERE active = 1 ORDER BY name ASC"); // Filter by active
  return $stmt->fetchAll();
}

function clinica_get_all_users_for_gestion(): array
{
  $pdo = clinica_get_db();
  // We select all fields except the password hash for security.
  $stmt = $pdo->query("SELECT id, username, name, role, doctor_id, active, created_at, updated_at FROM users ORDER BY name ASC"); // Filter by active is not needed here as management can see inactive users
  return $stmt->fetchAll();
}

<?php
require_once __DIR__ . '/../data.php';
require_once __DIR__ . '/../notifications/service.php';

clinica_ensure_session_started();
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$payload = [];
if ($method === 'POST') {
  $payload = clinica_request_json();
}

if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true) && !clinica_validate_csrf_from_request()) {
  clinica_json(['success' => false, 'error' => 'CSRF token invalido.'], 403);
}

$action = strtolower(trim((string) ($payload['action'] ?? $_GET['action'] ?? '')));

if ($action === 'session') {
  if ($method !== 'GET') clinica_json(['success' => false, 'error' => 'Metodo no permitido para esta accion.'], 405);
  $user = clinica_current_user();
  if ($user) {
    clinica_json(['success' => true, 'authenticated' => true, 'user' => clinica_public_user($user), 'expires_at' => clinica_session_expires_at()]);
  }
  clinica_json(['success' => true, 'authenticated' => false]);
}

if ($action === 'login') {
  if ($method !== 'POST') clinica_json(['success' => false, 'error' => 'Metodo no permitido para esta accion.'], 405);
  $username = strtolower(trim((string) ($payload['username'] ?? '')));
  $password = (string) ($payload['password'] ?? '');
  $redirect = (string) ($payload['redirect'] ?? '');
  $failedAttempts = clinica_get_failed_login_attempts($username);
  if ($failedAttempts >= CLINICA_MAX_LOGIN_ATTEMPTS) {
    clinica_json(['success' => false, 'error' => 'Demasiados intentos fallidos. Intente de nuevo en ' . CLINICA_LOGIN_LOCKOUT_TIME_MINUTES . ' minutos.'], 429);
  }
  try {
    $pdo = clinica_get_db();
    $stmt = $pdo->prepare("SELECT id, username, password, name, role, doctor_id FROM users WHERE username = ? AND active = 1");
    $stmt->execute([$username]);
    $dbUser = $stmt->fetch(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
    error_log('Login DB authentication error: ' . $e->getMessage());
    clinica_json(['success' => false, 'error' => 'Error de base de datos al autenticar.'], 500);
  }
  $userIdForAudit = $dbUser['id'] ?? null;
  if (!$dbUser || !password_verify($password, (string) ($dbUser['password'] ?? ''))) {
    clinica_record_failed_login_attempt($username);
    clinica_json(['success' => false, 'error' => 'Usuario o contrasena incorrectos.'], 401);
  }
  $sessionUserId = ($dbUser['role'] === 'DOCTOR' && !empty($dbUser['doctor_id'])) ? (string) $dbUser['doctor_id'] : (string) $dbUser['id'];
  $user = ['id' => $sessionUserId, 'db_id' => (int) ($dbUser['id'] ?? 0), 'username' => strtolower(trim((string) ($dbUser['username'] ?? ''))), 'name' => (string) ($dbUser['name'] ?? ''), 'role' => (string) ($dbUser['role'] ?? ''), 'label' => (string) ($dbUser['role'] ?? ''), 'doctor_id' => !empty($dbUser['doctor_id']) ? (string) $dbUser['doctor_id'] : null];
  clinica_start_authenticated_session($user);
  clinica_log_audit_event($userIdForAudit, 'login_success', 'Autenticacion exitosa', ['username' => $username]);
  clinica_clear_failed_login_attempts($username);
  clinica_json(['success' => true, 'redirect' => clinica_resolve_login_redirect($redirect, $user)]);
}

if ($action === 'logout') {
  if ($method !== 'POST') clinica_json(['success' => false, 'error' => 'Metodo no permitido para esta accion.'], 405);
  clinica_destroy_session();
  clinica_json(['success' => true, 'redirect' => clinica_url('pages/login/')]);
}

if ($action === 'slot_status') {
  if ($method !== 'GET') clinica_json(['success' => false, 'error' => 'Metodo no permitido para esta accion.'], 405);
  $doctorId = trim((string) ($_GET['doctorId'] ?? ''));
  $date = trim((string) ($_GET['date'] ?? ''));
  if ($doctorId === '' || !clinica_is_valid_dashboard_date($date)) clinica_json(['success' => false, 'error' => 'Parametros invalidos.'], 422);

  try {
    $pdo = clinica_get_db();
    $doctorStmt = $pdo->prepare('SELECT id, active FROM doctors WHERE id = ? LIMIT 1');
    $doctorStmt->execute([$doctorId]);
    $doctor = $doctorStmt->fetch(PDO::FETCH_ASSOC);
    if (!$doctor || !(bool) $doctor['active']) {
      clinica_json(['success' => false, 'error' => 'El doctor no existe o no esta activo.'], 422);
    }

    $stmt = $pdo->prepare("SELECT time, slot_minutes FROM appointments WHERE doctor_id = ? AND date = ? AND status NOT IN ('rechazada', 'cancelada') ORDER BY time");
    $stmt->execute([$doctorId, $date]);
    $bookedSlots = array_map(static fn(array $row): array => [
      'time' => substr((string) ($row['time'] ?? ''), 0, 5),
      'slot_minutes' => max(5, (int) ($row['slot_minutes'] ?? 15)),
    ], $stmt->fetchAll(PDO::FETCH_ASSOC));
    $bookedTimes = array_values(array_unique(array_column($bookedSlots, 'time')));

    // Return the exact currently bookable slots as well as booked slots. This
    // lets the public UI refresh without duplicating schedule calculations.
    $rules = clinica_schedule_rules_for_doctor($doctorId);
    $dateObject = new DateTimeImmutable($date . ' 00:00:00');
    $weekday = (string) $dateObject->format('N');
    $blocks = array_merge($rules[$date] ?? [], $rules[$weekday] ?? []);
    $now = new DateTimeImmutable('now');
    $isToday = $dateObject->format('Y-m-d') === $now->format('Y-m-d');
    $currentMinutes = ((int) $now->format('H') * 60) + (int) $now->format('i');
    $availableTimes = [];
    $slotMinutesByTime = [];

    foreach ($blocks as $block) {
      $startMatch = preg_match('/^(\d{2}):(\d{2})/', (string) ($block['start_time'] ?? ''), $startParts) ? true : false;
      $endMatch = preg_match('/^(\d{2}):(\d{2})/', (string) ($block['end_time'] ?? ''), $endParts) ? true : false;
      if (!$startMatch || !$endMatch) continue;
      $start = ((int) $startParts[1] * 60) + (int) $startParts[2];
      $end = ((int) $endParts[1] * 60) + (int) $endParts[2];
      $duration = max(5, (int) ($block['slot_minutes'] ?? 15));
      if ($start >= $end) continue;

      for ($minute = $start; $minute < $end; $minute += $duration) {
        if ($isToday && $minute <= $currentMinutes) continue;
        $candidateEnd = $minute + $duration;
        $conflict = false;
        foreach ($bookedSlots as $booked) {
          if (!preg_match('/^(\d{2}):(\d{2})/', (string) $booked['time'], $bookedParts)) continue;
          $bookedStart = ((int) $bookedParts[1] * 60) + (int) $bookedParts[2];
          $bookedEnd = $bookedStart + max(5, (int) $booked['slot_minutes']);
          if (($minute - CLINICA_APPOINTMENT_BUFFER_MINUTES) < $bookedEnd
              && ($candidateEnd + CLINICA_APPOINTMENT_BUFFER_MINUTES) > $bookedStart) {
            $conflict = true;
            break;
          }
        }
        if ($conflict) continue;
        $timeValue = sprintf('%02d:%02d', intdiv($minute, 60), $minute % 60);
        $availableTimes[] = $timeValue;
        $slotMinutesByTime[$timeValue] = $duration;
      }
    }

    $availableTimes = array_values(array_unique($availableTimes));
    sort($availableTimes, SORT_STRING);
  } catch (Throwable $e) {
    error_log('Slot status lookup error: ' . $e->getMessage());
    clinica_json(['success' => false, 'error' => 'No se pudo consultar la disponibilidad.'], 500);
  }
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  header('Pragma: no-cache');
  clinica_json([
    'success' => true,
    'doctorId' => $doctorId,
    'date' => $date,
    'bookedTimes' => $bookedTimes,
    'bookedSlots' => $bookedSlots,
    'availableTimes' => $availableTimes,
    'availableCount' => count($availableTimes),
    'slotMinutesByTime' => $slotMinutesByTime,
    'serverTime' => (new DateTimeImmutable())->format(DATE_ATOM),
  ]);
}

// La creacion de citas tiene una unica implementacion canonica en /api/appointments/.
// Este alias queda para clientes legacy y redirige sin duplicar reglas de negocio.
if ($action === 'create_appointment') {
  if ($method !== 'POST') clinica_json(['success' => false, 'error' => 'Metodo no permitido para esta accion.'], 405);
  $basePath = rtrim((string) (defined('CLINICA_BASE_PATH') ? CLINICA_BASE_PATH : ''), '/');
  $target = $basePath . '/api/appointments/';
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
  $url = $scheme . '://' . $host . $target;
  header('Location: ' . $url, true, 307);
  exit;
}

clinica_json(['success' => false, 'error' => 'Accion no especificada o no valida.'], 400);

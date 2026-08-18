<?php
require_once __DIR__ . '/../data.php';

// Ensure session and CSRF cookie are set up correctly before handling requests
clinica_ensure_session_started();

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$payload = [];

if ($method === 'POST') {
  $payload = clinica_request_json();
}

// Protect mutating endpoints (except login) with CSRF validation
if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
  if (!clinica_validate_csrf_from_request()) {
    clinica_json(['success' => false, 'error' => 'CSRF token invalido.'], 403);
  }
}

$action = strtolower(trim((string) ($payload['action'] ?? $_GET['action'] ?? '')));

if ($action === 'session') {
  if ($method !== 'GET') {
    clinica_json(['success' => false, 'error' => 'Metodo no permitido para esta accion.'], 405);
  }
  $user = clinica_current_user();
  if ($user) {
    clinica_json(['success' => true, 'authenticated' => true, 'user' => clinica_public_user($user), 'expires_at' => clinica_session_expires_at()]);
  }
  clinica_json(['success' => true, 'authenticated' => false]);
}

if ($action === 'login') {
  if ($method !== 'POST') {
    clinica_json(['success' => false, 'error' => 'Metodo no permitido para esta accion.'], 405);
  }
  $username = strtolower(trim((string) ($payload['username'] ?? '')));
  $password = (string) ($payload['password'] ?? '');
  $redirect = (string) ($payload['redirect'] ?? '');

  // Authenticate against the users table directly to avoid exposing password hashes in memory sent to the client.
  // Brute force protection: check failed login attempts
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
    clinica_json(['success' => false, 'error' => 'Error de base de datos al autenticar.'], 500);
  }

  $userIdForAudit = $dbUser['id'] ?? null;

  if (!$dbUser || !password_verify($password, (string) ($dbUser['password'] ?? ''))) {
    clinica_record_failed_login_attempt($username);
    clinica_json(['success' => false, 'error' => 'Usuario o contrasena incorrectos.'], 401);
  }

  // Map DB user to session user but DO NOT include password or hash.
  $sessionUserId = ($dbUser['role'] === 'DOCTOR' && !empty($dbUser['doctor_id'])) ? (string) $dbUser['doctor_id'] : (string) $dbUser['id'];
  $user = [
    'id' => $sessionUserId,
    'db_id' => (int) ($dbUser['id'] ?? 0),
    'username' => strtolower(trim((string) ($dbUser['username'] ?? ''))),
    'name' => (string) ($dbUser['name'] ?? ''),
    'role' => (string) ($dbUser['role'] ?? ''),
    'label' => (string) ($dbUser['role'] ?? ''),
  ];

  clinica_start_authenticated_session($user);
  clinica_log_audit_event($userIdForAudit, 'login_success', 'Autenticacion exitosa', ['username' => $username]);
  clinica_clear_failed_login_attempts($username);
  $target = clinica_resolve_login_redirect($redirect, $user);
  clinica_json(['success' => true, 'redirect' => $target]);
}

if ($action === 'logout') {
  if ($method !== 'POST') {
    clinica_json(['success' => false, 'error' => 'Metodo no permitido para esta accion.'], 405);
  }
  clinica_destroy_session();
  clinica_json(['success' => true, 'redirect' => '/clinica/pages/login/']);
}

if ($action === 'create_appointment') {
  if ($method !== 'POST') {
    clinica_json(['success' => false, 'error' => 'Metodo no permitido para esta accion.'], 405);
  }

  $specialtyId = trim((string) ($payload['specialtyId'] ?? ''));
  $doctorId = trim((string) ($payload['doctorId'] ?? ''));
  $date = trim((string) ($payload['date'] ?? ''));
  $time = trim((string) ($payload['time'] ?? ''));
  $patientName = trim((string) ($payload['patientName'] ?? ''));
  $patientEmail = trim((string) ($payload['patientEmail'] ?? ''));
  $patientPhone = trim((string) ($payload['patientPhone'] ?? ''));
  $patientNotes = trim((string) ($payload['patientNotes'] ?? ''));

  if (empty($specialtyId) || empty($doctorId) || empty($date) || empty($time) || empty($patientName) || empty($patientEmail) || empty($patientPhone)) {
    clinica_log_audit_event(clinica_get_user_id_from_session(), 'appointment_creation_failed', 'Faltan datos requeridos', [
      'specialtyId' => $specialtyId,
      'doctorId' => $doctorId,
      'date' => $date,
      'time' => $time,
      'patientName' => $patientName,
      'patientEmail' => $patientEmail,
      'patientPhone' => $patientPhone,
      'patientNotes' => $patientNotes,
    ]);
    clinica_json(['success' => false, 'error' => 'Faltan datos requeridos para crear la cita.'], 422);
  }

  if (!clinica_is_valid_dashboard_date($date) || !clinica_is_valid_time_string($time)) {
    clinica_json(['success' => false, 'error' => 'Fecha u hora de cita invalida.'], 422);
  }

  if (!filter_var($patientEmail, FILTER_VALIDATE_EMAIL)) {
    clinica_json(['success' => false, 'error' => 'El correo electronico no es valido.'], 422);
  }

  if (!preg_match('/^\d{7,15}$/', $patientPhone)) {
    clinica_json(['success' => false, 'error' => 'El telefono debe contener entre 7 y 15 digitos.'], 422);
  }

  try {
    $pdo = clinica_get_db();
    $pdo->beginTransaction();

    // 1. Validar que la fecha y hora sean futuras
    $appointmentDateTime = new DateTimeImmutable("$date $time");
    if ($appointmentDateTime < new DateTimeImmutable()) {
      $pdo->rollBack();
      clinica_log_audit_event(clinica_get_user_id_from_session(), 'appointment_creation_failed', 'Fecha/hora de cita en el pasado', ['date' => $date, 'time' => $time]);
      clinica_json(['success' => false, 'error' => 'No se pueden agendar citas en el pasado.'], 422);
    }

    // 2. Validar que el doctor pertenezca a la especialidad
    $stmt = $pdo->prepare("SELECT id FROM doctors WHERE id = ? AND specialty_id = ? AND active = 1");
    $stmt->execute([$doctorId, $specialtyId]);
    if (!$stmt->fetch()) {
      $pdo->rollBack();
      clinica_log_audit_event(clinica_get_user_id_from_session(), 'appointment_creation_failed', 'Doctor no pertenece a la especialidad', ['doctorId' => $doctorId, 'specialtyId' => $specialtyId]);
      clinica_json(['success' => false, 'error' => 'El doctor seleccionado no pertenece a la especialidad indicada.'], 422);
    }

    // 3. Validar que la hora de la cita caiga dentro de un horario activo del doctor y respete slot_minutes
    $weekday = (int) $appointmentDateTime->format('N'); // 1 (for Monday) through 7 (for Sunday)
    $scheduleRules = clinica_schedule_rules_for_doctor($doctorId);
    if (!clinica_is_time_slot_available($appointmentDateTime, $scheduleRules, $weekday)) {
      $pdo->rollBack();
      clinica_log_audit_event(clinica_get_user_id_from_session(), 'appointment_creation_failed', 'Horario no disponible o fuera de reglas', ['doctorId' => $doctorId, 'date' => $date, 'time' => $time]);
      clinica_json(['success' => false, 'error' => 'El horario seleccionado no está disponible o no cumple con las reglas del doctor.'], 409);
    }

    // 4. Buscar o crear paciente
    // The patient email is unique, so we can use it to find existing patients.
    // If found, update name and phone. If not, create new.
    $stmt = $pdo->prepare("SELECT id FROM patients WHERE email = ?");
    $stmt->execute([$patientEmail]);
    $patientId = $stmt->fetchColumn();

    if (!$patientId) {
      $stmt = $pdo->prepare("INSERT INTO patients (name, email, phone) VALUES (?, ?, ?)");
      $stmt->execute([$patientName, $patientEmail, $patientPhone]);
      $patientId = $pdo->lastInsertId();
    } else {
      $stmt = $pdo->prepare("UPDATE patients SET name = ?, phone = ? WHERE id = ?");
      $stmt->execute([$patientName, $patientPhone, $patientId]);
    }

    // 5. Validar que no haya doble reserva (incluyendo buffer time)
    $bufferMinutes = CLINICA_APPOINTMENT_BUFFER_MINUTES;
    $checkStartTime = (new DateTimeImmutable("$date $time"))->modify("-$bufferMinutes minutes")->format('H:i:s');
    $checkEndTime = (new DateTimeImmutable("$date $time"))->modify("+$bufferMinutes minutes")->format('H:i:s');

    $stmt = $pdo->prepare("SELECT id FROM appointments WHERE doctor_id = ? AND date = ? AND status NOT IN ('rechazada', 'cancelada') AND ((time >= ? AND time < ?) OR (ADDTIME(time, SEC_TO_TIME(slot_minutes * 60)) > ? AND ADDTIME(time, SEC_TO_TIME(slot_minutes * 60)) <= ?))");
    $stmt->execute([$doctorId, $date, $checkStartTime, $checkEndTime, $checkStartTime, $checkEndTime]);
    if ($stmt->fetch()) {
      $pdo->rollBack();
      clinica_log_audit_event(clinica_get_user_id_from_session(), 'appointment_creation_failed', 'Conflicto de horario existente', ['doctorId' => $doctorId, 'date' => $date, 'time' => $time]);
      clinica_json(['success' => false, 'error' => 'El horario seleccionado ya no está disponible. Por favor, elija otro.'], 409);
    }

    // Get slot_minutes from schedule rules for the specific time
    $slotMinutes = clinica_get_slot_minutes_for_time($appointmentDateTime, $scheduleRules, $weekday);

    // 6. Insertar la cita
    $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, specialty_id, date, time, slot_minutes, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente')");
    $stmt->execute([$patientId, $doctorId, $specialtyId, $date, $time . ':00', $slotMinutes, $patientNotes]);

    $pdo->commit();
    clinica_log_audit_event(clinica_get_user_id_from_session(), 'appointment_created', 'Cita agendada exitosamente', ['appointment_id' => $pdo->lastInsertId(), 'patient_id' => $patientId, 'doctorId' => $doctorId, 'date' => $date, 'time' => $time]);
    clinica_json(['success' => true, 'message' => 'Cita creada exitosamente.']);
  } catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
      $pdo->rollBack();
    }
    clinica_json(['success' => false, 'error' => 'Error de base de datos al crear la cita.'], 500);
  }
}

clinica_json(['success' => false, 'error' => 'Accion no especificada o no valida.'], 400);

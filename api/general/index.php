<?php
require_once __DIR__ . '/../data.php';
require_once __DIR__ . '/../notifications/service.php';

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
    error_log('Login DB authentication error: ' . $e->getMessage());
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
  clinica_json(['success' => true, 'redirect' => clinica_url('pages/login/')]);
}

if ($action === 'slot_status') {
  if ($method !== 'GET') {
    clinica_json(['success' => false, 'error' => 'Metodo no permitido para esta accion.'], 405);
  }

  $doctorId = trim((string) ($_GET['doctorId'] ?? ''));
  $date = trim((string) ($_GET['date'] ?? ''));

  if ($doctorId === '' || !clinica_is_valid_dashboard_date($date)) {
    clinica_json(['success' => false, 'error' => 'Parametros invalidos.'], 422);
  }

  clinica_json([
    'success' => true,
    'doctorId' => $doctorId,
    'date' => $date,
    'bookedTimes' => clinica_public_booked_times_for($doctorId, $date),
    'serverTime' => (new DateTimeImmutable())->format(DATE_ATOM),
  ]);
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
  $patientCedula = strtoupper(trim((string) ($payload['patientCedula'] ?? '')));
  $patientEmail = strtolower(trim((string) ($payload['patientEmail'] ?? '')));
  $patientPhone = trim((string) ($payload['patientPhone'] ?? ''));
  $patientNotes = trim((string) ($payload['patientNotes'] ?? ''));
  $emailOptIn = array_key_exists('emailOptIn', $payload) ? (bool) $payload['emailOptIn'] : true;
  $whatsappOptIn = array_key_exists('whatsappOptIn', $payload) ? (bool) $payload['whatsappOptIn'] : false;

  if (empty($specialtyId) || empty($doctorId) || empty($date) || empty($time) || empty($patientName) || empty($patientCedula) || empty($patientEmail) || empty($patientPhone)) {
    clinica_log_audit_event(clinica_get_user_id_from_session(), 'appointment_creation_failed', 'Faltan datos requeridos', [
      'specialtyId' => $specialtyId,
      'doctorId' => $doctorId,
      'date' => $date,
      'time' => $time,
      'patientName' => $patientName,
      'patientCedula' => $patientCedula,
      'patientEmail' => $patientEmail,
      'patientPhone' => $patientPhone,
      'patientNotes' => $patientNotes,
    ]);
    clinica_json(['success' => false, 'error' => 'Faltan datos requeridos para crear la cita.'], 422);
  }

  if (!clinica_is_valid_dashboard_date($date) || !clinica_is_valid_time_string($time)) {
    clinica_json(['success' => false, 'error' => 'Fecha u hora de cita invalida.'], 422);
  }

  if (!preg_match('/^[A-Z0-9]{6,20}$/', $patientCedula)) {
    clinica_json(['success' => false, 'error' => 'El numero de cedula/documento no es valido.'], 422);
  }

  if (!filter_var($patientEmail, FILTER_VALIDATE_EMAIL)) {
    clinica_json(['success' => false, 'error' => 'El correo electronico no es valido.'], 422);
  }

  if (!preg_match('/^\d{7,15}$/', $patientPhone)) {
    clinica_json(['success' => false, 'error' => 'El telefono debe contener entre 7 y 15 digitos.'], 422);
  }

  $pdo = clinica_get_db();

  // Serializar las solicitudes de un mismo doctor para que la verificacion de
  // disponibilidad y la insercion sean atomicas ante intentos concurrentes
  // (miles de usuarios pudiendo pedir la misma hora al mismo tiempo).
  // GET_LOCK() names are capped at 64 chars in MySQL/MariaDB; hash the doctor id
  // so long ids can never overflow that limit.
  $lockName = 'clinica_appt_' . md5($doctorId);
  $lockAcquired = false;
  try {
    $lockStmt = $pdo->prepare('SELECT GET_LOCK(?, 5)');
    $lockStmt->execute([$lockName]);
    $lockAcquired = (bool) $lockStmt->fetchColumn();
  } catch (PDOException $e) {
    error_log('GET_LOCK error: ' . $e->getMessage());
  }

  if (!$lockAcquired) {
    clinica_json(['success' => false, 'error' => 'El sistema esta procesando otra reserva para este doctor. Intente nuevamente en unos segundos.'], 409);
  }

  $releaseLock = function () use ($pdo, $lockName): void {
    $stmt = $pdo->prepare('SELECT RELEASE_LOCK(?)');
    $stmt->execute([$lockName]);
  };

  $fail = function (string $error, int $code, string $auditAction, array $auditDetails = []) use ($pdo, $releaseLock): void {
    if ($pdo->inTransaction()) {
      $pdo->rollBack();
    }
    $releaseLock();
    clinica_log_audit_event(clinica_get_user_id_from_session(), $auditAction, $error, $auditDetails);
    clinica_json(['success' => false, 'error' => $error], $code);
  };

  try {
    $pdo->beginTransaction();

    // 1. Validar que la fecha y hora sean futuras
    $appointmentDateTime = new DateTimeImmutable("$date $time");
    if ($appointmentDateTime < new DateTimeImmutable()) {
      $fail('No se pueden agendar citas en el pasado.', 422, 'appointment_creation_failed', ['date' => $date, 'time' => $time]);
    }

    // 2. Validar que el doctor pertenezca a la especialidad
    $stmt = $pdo->prepare("SELECT id FROM doctors WHERE id = ? AND specialty_id = ? AND active = 1");
    $stmt->execute([$doctorId, $specialtyId]);
    if (!$stmt->fetch()) {
      $fail('El doctor seleccionado no pertenece a la especialidad indicada.', 422, 'appointment_creation_failed', ['doctorId' => $doctorId, 'specialtyId' => $specialtyId]);
    }

    // 3. Validar que la hora de la cita caiga dentro de un horario activo del doctor y respete slot_minutes
    $weekday = (int) $appointmentDateTime->format('N'); // 1 (for Monday) through 7 (for Sunday)
    $scheduleRules = clinica_schedule_rules_for_doctor($doctorId);
    if (!clinica_is_time_slot_available($appointmentDateTime, $scheduleRules, $weekday)) {
      $fail('El horario seleccionado no está disponible o no cumple con las reglas del doctor.', 409, 'appointment_creation_failed', ['doctorId' => $doctorId, 'date' => $date, 'time' => $time]);
    }

    // 4. Buscar o crear paciente
    // La cedula/documento es el identificador estable del paciente: a diferencia
    // del nombre o el correo, no cambia entre citas aunque el paciente escriba
    // su nombre distinto o use otro correo/telefono. Por eso es la clave
    // principal para vincular todas sus citas a un unico historial clinico.
    $stmt = $pdo->prepare("SELECT id, email FROM patients WHERE cedula = ?");
    $stmt->execute([$patientCedula]);
    $patientByCedula = $stmt->fetch();

    if ($patientByCedula) {
      $patientId = (int) $patientByCedula['id'];

      // No pisar el correo si ya pertenece a otro paciente (evita violar la
      // restriccion unica de email y evita "robarle" el correo a otro registro).
      $emailToSave = $patientByCedula['email'];
      if (strcasecmp($emailToSave, $patientEmail) !== 0) {
        $stmt = $pdo->prepare("SELECT id FROM patients WHERE email = ? AND id <> ?");
        $stmt->execute([$patientEmail, $patientId]);
        if (!$stmt->fetch()) {
          $emailToSave = $patientEmail;
        }
      }

      $stmt = $pdo->prepare("UPDATE patients SET name = ?, phone = ?, email = ?, email_opt_in = ?, whatsapp_opt_in = ? WHERE id = ?");
      $stmt->execute([$patientName, $patientPhone, $emailToSave, (int) $emailOptIn, (int) $whatsappOptIn, $patientId]);
    } else {
      // Sin coincidencia por cedula: puede ser un paciente nuevo o uno
      // registrado antes de que existiera este campo (cedula = NULL). En ese
      // ultimo caso, si el correo coincide exactamente, se asume que es la
      // misma persona y se "reclama" el registro asignandole su cedula.
      $stmt = $pdo->prepare("SELECT id, cedula FROM patients WHERE email = ?");
      $stmt->execute([$patientEmail]);
      $patientByEmail = $stmt->fetch();

      if ($patientByEmail && $patientByEmail['cedula'] === null) {
        $patientId = (int) $patientByEmail['id'];
        $stmt = $pdo->prepare("UPDATE patients SET name = ?, phone = ?, cedula = ?, email_opt_in = ?, whatsapp_opt_in = ? WHERE id = ?");
        $stmt->execute([$patientName, $patientPhone, $patientCedula, (int) $emailOptIn, (int) $whatsappOptIn, $patientId]);
      } elseif ($patientByEmail) {
        // El correo ya esta asociado a una cedula distinta: no fusionar a ciegas
        // (podria ser un correo familiar compartido); se pide aclarar el dato.
        $fail('Este correo ya esta registrado con otro numero de cedula/documento. Verifique sus datos o comuniquese con la clinica.', 409, 'appointment_creation_failed', ['patientEmail' => $patientEmail]);
      } else {
        $stmt = $pdo->prepare("INSERT INTO patients (name, email, phone, cedula, email_opt_in, whatsapp_opt_in) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$patientName, $patientEmail, $patientPhone, $patientCedula, (int) $emailOptIn, (int) $whatsappOptIn]);
        $patientId = (int) $pdo->lastInsertId();
      }
    }

    // 5. Validar el limite de citas diarias y la separacion minima entre citas del mismo paciente
    $stmt = $pdo->prepare("SELECT time FROM appointments WHERE patient_id = ? AND date = ? AND status NOT IN ('rechazada', 'cancelada')");
    $stmt->execute([$patientId, $date]);
    $existingPatientTimes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($existingPatientTimes) >= CLINICA_MAX_APPOINTMENTS_PER_PATIENT_PER_DAY) {
      $fail('Ya tiene el maximo de ' . CLINICA_MAX_APPOINTMENTS_PER_PATIENT_PER_DAY . ' citas permitidas para este dia.', 409, 'appointment_creation_failed', ['patient_id' => $patientId, 'date' => $date]);
    }

    $newAppointmentMinutes = ((int) $appointmentDateTime->format('H')) * 60 + (int) $appointmentDateTime->format('i');
    foreach ($existingPatientTimes as $existingTime) {
      $existingMinutes = clinica_time_to_minutes((string) $existingTime);
      if ($existingMinutes !== null && abs($newAppointmentMinutes - $existingMinutes) < CLINICA_MIN_MINUTES_BETWEEN_PATIENT_APPOINTMENTS) {
        $fail('Debe haber al menos ' . (CLINICA_MIN_MINUTES_BETWEEN_PATIENT_APPOINTMENTS / 60) . ' horas de diferencia entre sus citas del mismo dia.', 409, 'appointment_creation_failed', ['patient_id' => $patientId, 'date' => $date, 'time' => $time]);
      }
    }

    // 6. Validar que no haya doble reserva (incluyendo buffer time)
    $bufferMinutes = CLINICA_APPOINTMENT_BUFFER_MINUTES;
    $checkStartTime = (new DateTimeImmutable("$date $time"))->modify("-$bufferMinutes minutes")->format('H:i:s');
    $checkEndTime = (new DateTimeImmutable("$date $time"))->modify("+$bufferMinutes minutes")->format('H:i:s');

    $stmt = $pdo->prepare("SELECT id FROM appointments WHERE doctor_id = ? AND date = ? AND status NOT IN ('rechazada', 'cancelada') AND ((time >= ? AND time < ?) OR (ADDTIME(time, SEC_TO_TIME(slot_minutes * 60)) > ? AND ADDTIME(time, SEC_TO_TIME(slot_minutes * 60)) <= ?))");
    $stmt->execute([$doctorId, $date, $checkStartTime, $checkEndTime, $checkStartTime, $checkEndTime]);
    if ($stmt->fetch()) {
      $fail('El horario seleccionado ya no está disponible. Por favor, elija otro.', 409, 'appointment_creation_failed', ['doctorId' => $doctorId, 'date' => $date, 'time' => $time]);
    }

    // Get slot_minutes from schedule rules for the specific time
    $slotMinutes = clinica_get_slot_minutes_for_time($appointmentDateTime, $scheduleRules, $weekday);

    // 7. Insertar la cita
    $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, specialty_id, date, time, slot_minutes, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente')");
    $stmt->execute([$patientId, $doctorId, $specialtyId, $date, $time . ':00', $slotMinutes, $patientNotes]);

    $pdo->commit();
    $newAppointmentId = (int) $pdo->lastInsertId();
    $releaseLock();

    try {
      clinica_notify_appointment_created($newAppointmentId);
    } catch (Throwable $e) {
      error_log('Notification dispatch failed: ' . $e->getMessage());
    }

    clinica_log_audit_event(clinica_get_user_id_from_session(), 'appointment_created', 'Cita agendada exitosamente', ['appointment_id' => $newAppointmentId, 'patient_id' => $patientId, 'doctorId' => $doctorId, 'date' => $date, 'time' => $time]);
    clinica_json(['success' => true, 'message' => 'Cita creada exitosamente.']);
  } catch (PDOException $e) {
    if ($pdo->inTransaction()) {
      $pdo->rollBack();
    }
    $releaseLock();

    // El horario tiene un indice unico (doctor_id, date, time): si dos solicitudes
    // llegaron a coincidir exactamente, la base de datos rechaza el duplicado.
    // Los indices unicos de patients (cedula, email) pueden rechazar por el
    // mismo motivo si el mismo paciente envia dos solicitudes simultaneas
    // (p.ej. doble clic en "Confirmar" reservando con doctores distintos).
    if ((string) $e->getCode() === '23000') {
      $driverMessage = $e->errorInfo[2] ?? $e->getMessage();
      if (stripos($driverMessage, 'uq_patients_cedula') !== false || stripos((string) $driverMessage, "for key 'email'") !== false || stripos((string) $driverMessage, 'patients.email') !== false) {
        clinica_json(['success' => false, 'error' => 'Ya existe una solicitud en curso con sus datos. Por favor, intente nuevamente en unos segundos.'], 409);
      }
      clinica_json(['success' => false, 'error' => 'El horario seleccionado ya fue reservado por otra persona. Por favor, elija otro.'], 409);
    }

    error_log('Appointment creation DB error: ' . $e->getMessage());
    clinica_json(['success' => false, 'error' => 'Error de base de datos al crear la cita.'], 500);
  }
}

clinica_json(['success' => false, 'error' => 'Accion no especificada o no valida.'], 400);

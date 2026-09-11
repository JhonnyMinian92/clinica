<?php
require_once __DIR__ . '/../data.php';
require_once __DIR__ . '/../notifications/service.php';

clinica_ensure_session_started();
header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  clinica_json(['success' => false, 'error' => 'Metodo no permitido.'], 405);
}

if (!clinica_validate_csrf_from_request()) {
  clinica_json(['success' => false, 'error' => 'CSRF token invalido.'], 403);
}

$payload = clinica_request_json();
$action = strtolower(trim((string) ($payload['action'] ?? '')));
if ($action !== 'create_appointment') {
  clinica_json(['success' => false, 'error' => 'Accion no valida.'], 400);
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

if ($specialtyId === '' || $doctorId === '' || $date === '' || $time === '' || $patientName === '' || $patientCedula === '' || $patientEmail === '' || $patientPhone === '') {
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
$stage = 'schema';

try {
  // InfinityFree puede recibir una base importada con una version anterior del
  // esquema. La reserva debe poder actualizar las columnas criticas sin exigir
  // que el usuario reconstruya la base manualmente.
  clinica_ensure_appointment_schema($pdo);

  $stage = 'lock';
  $lockName = 'clinica_appt_' . md5($doctorId);
  $lockStmt = $pdo->prepare('SELECT GET_LOCK(?, 5)');
  $lockStmt->execute([$lockName]);
  $lockAcquired = (int) $lockStmt->fetchColumn() === 1;

  if (!$lockAcquired) {
    clinica_json(['success' => false, 'error' => 'El sistema esta procesando otra reserva para este doctor. Intente nuevamente en unos segundos.'], 409);
  }

  $releaseLock = static function (PDO $connection, string $name): void {
    try {
      $stmt = $connection->prepare('SELECT RELEASE_LOCK(?)');
      $stmt->execute([$name]);
    } catch (Throwable $e) {
      error_log('Appointment lock release error: ' . $e->getMessage());
    }
  };

  try {
    $stage = 'transaction';
    $pdo->beginTransaction();

    $stage = 'datetime';
    $appointmentDateTime = new DateTimeImmutable($date . ' ' . $time);
    if ($appointmentDateTime < new DateTimeImmutable()) {
      throw new ClinicaAppointmentConflict('No se pueden agendar citas en el pasado.', 422);
    }

    $stage = 'doctor';
    $stmt = $pdo->prepare('SELECT id FROM doctors WHERE id = ? AND specialty_id = ? AND active = 1');
    $stmt->execute([$doctorId, $specialtyId]);
    if (!$stmt->fetch()) {
      throw new ClinicaAppointmentConflict('El doctor seleccionado no pertenece a la especialidad indicada.', 422);
    }

    $stage = 'schedule';
    $weekday = (int) $appointmentDateTime->format('N');
    $scheduleRules = clinica_schedule_rules_for_doctor($doctorId);
    if (!clinica_is_time_slot_available($appointmentDateTime, $scheduleRules, $weekday)) {
      throw new ClinicaAppointmentConflict('El horario seleccionado no esta disponible o no cumple con las reglas del doctor.', 409);
    }

    $stage = 'patient_lookup';
    $stmt = $pdo->prepare('SELECT id, email FROM patients WHERE cedula = ?');
    $stmt->execute([$patientCedula]);
    $patientByCedula = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($patientByCedula) {
      $patientId = (int) $patientByCedula['id'];
      $emailToSave = (string) $patientByCedula['email'];

      if (strcasecmp($emailToSave, $patientEmail) !== 0) {
        $stmt = $pdo->prepare('SELECT id FROM patients WHERE email = ? AND id <> ?');
        $stmt->execute([$patientEmail, $patientId]);
        if (!$stmt->fetch()) {
          $emailToSave = $patientEmail;
        }
      }

      $stmt = $pdo->prepare('UPDATE patients SET name = ?, phone = ?, email = ?, email_opt_in = ?, whatsapp_opt_in = ? WHERE id = ?');
      $stmt->execute([$patientName, $patientPhone, $emailToSave, (int) $emailOptIn, (int) $whatsappOptIn, $patientId]);
    } else {
      $stmt = $pdo->prepare('SELECT id, cedula FROM patients WHERE email = ?');
      $stmt->execute([$patientEmail]);
      $patientByEmail = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($patientByEmail && $patientByEmail['cedula'] === null) {
        $patientId = (int) $patientByEmail['id'];
        $stmt = $pdo->prepare('UPDATE patients SET name = ?, phone = ?, cedula = ?, email_opt_in = ?, whatsapp_opt_in = ? WHERE id = ?');
        $stmt->execute([$patientName, $patientPhone, $patientCedula, (int) $emailOptIn, (int) $whatsappOptIn, $patientId]);
      } elseif ($patientByEmail) {
        throw new ClinicaAppointmentConflict('Este correo ya esta registrado con otro numero de cedula/documento. Verifique sus datos o comuniquese con la clinica.', 409);
      } else {
        $stmt = $pdo->prepare('INSERT INTO patients (name, email, phone, cedula, email_opt_in, whatsapp_opt_in) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$patientName, $patientEmail, $patientPhone, $patientCedula, (int) $emailOptIn, (int) $whatsappOptIn]);
        $patientId = (int) $pdo->lastInsertId();
      }
    }

    $stage = 'patient_limits';
    $stmt = $pdo->prepare("SELECT time FROM appointments WHERE patient_id = ? AND date = ? AND status NOT IN ('rechazada', 'cancelada')");
    $stmt->execute([$patientId, $date]);
    $existingPatientTimes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($existingPatientTimes) >= CLINICA_MAX_APPOINTMENTS_PER_PATIENT_PER_DAY) {
      throw new ClinicaAppointmentConflict('Ya tiene el maximo de ' . CLINICA_MAX_APPOINTMENTS_PER_PATIENT_PER_DAY . ' citas permitidas para este dia.', 409);
    }

    $newMinutes = ((int) $appointmentDateTime->format('H')) * 60 + (int) $appointmentDateTime->format('i');
    foreach ($existingPatientTimes as $existingTime) {
      $existingMinutes = clinica_time_to_minutes((string) $existingTime);
      if ($existingMinutes !== null && abs($newMinutes - $existingMinutes) < CLINICA_MIN_MINUTES_BETWEEN_PATIENT_APPOINTMENTS) {
        throw new ClinicaAppointmentConflict('Debe haber al menos ' . (CLINICA_MIN_MINUTES_BETWEEN_PATIENT_APPOINTMENTS / 60) . ' horas de diferencia entre sus citas del mismo dia.', 409);
      }
    }

    $stage = 'availability';
    $bufferMinutes = CLINICA_APPOINTMENT_BUFFER_MINUTES;
    $checkStart = (new DateTimeImmutable($date . ' ' . $time))->modify('-' . $bufferMinutes . ' minutes')->format('H:i:s');
    $checkEnd = (new DateTimeImmutable($date . ' ' . $time))->modify('+' . $bufferMinutes . ' minutes')->format('H:i:s');

    $stmt = $pdo->prepare("SELECT id FROM appointments WHERE doctor_id = ? AND date = ? AND status NOT IN ('rechazada', 'cancelada') AND ((time >= ? AND time < ?) OR (ADDTIME(time, SEC_TO_TIME(slot_minutes * 60)) > ? AND ADDTIME(time, SEC_TO_TIME(slot_minutes * 60)) <= ?)) LIMIT 1");
    $stmt->execute([$doctorId, $date, $checkStart, $checkEnd, $checkStart, $checkEnd]);
    if ($stmt->fetch()) {
      throw new ClinicaAppointmentConflict('El horario seleccionado ya no esta disponible. Por favor, elija otro.', 409);
    }

    $slotMinutes = clinica_get_slot_minutes_for_time($appointmentDateTime, $scheduleRules, $weekday);

    $stage = 'insert';
    $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, specialty_id, date, time, slot_minutes, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente')");
    $stmt->execute([$patientId, $doctorId, $specialtyId, $date, $time . ':00', $slotMinutes, $patientNotes]);
    $newAppointmentId = (int) $pdo->lastInsertId();

    $pdo->commit();
    $releaseLock($pdo, $lockName);

    try {
      clinica_notify_appointment_created($newAppointmentId);
    } catch (Throwable $e) {
      error_log('Notification dispatch failed: ' . $e->getMessage());
    }

    clinica_log_audit_event(clinica_get_user_id_from_session(), 'appointment_created', 'Cita agendada exitosamente', [
      'appointment_id' => $newAppointmentId,
      'patient_id' => $patientId,
      'doctorId' => $doctorId,
      'date' => $date,
      'time' => $time,
    ]);

    clinica_json(['success' => true, 'message' => 'Cita creada exitosamente.']);
  } catch (ClinicaAppointmentConflict $e) {
    if ($pdo->inTransaction()) {
      $pdo->rollBack();
    }
    $releaseLock($pdo, $lockName);
    clinica_log_audit_event(clinica_get_user_id_from_session(), 'appointment_creation_failed', $e->getMessage(), [
      'stage' => $stage,
      'doctorId' => $doctorId,
      'specialtyId' => $specialtyId,
      'date' => $date,
      'time' => $time,
    ]);
    clinica_json(['success' => false, 'error' => $e->getMessage()], $e->getCode() ?: 409);
  } catch (PDOException $e) {
    if ($pdo->inTransaction()) {
      $pdo->rollBack();
    }
    $releaseLock($pdo, $lockName);

    $sqlState = (string) $e->getCode();
    $driverMessage = (string) ($e->errorInfo[2] ?? $e->getMessage());
    error_log('Appointment creation DB error at stage ' . $stage . ': ' . $e->getMessage());

    if ($sqlState === '23000') {
      if (stripos($driverMessage, 'uq_patients_cedula') !== false || stripos($driverMessage, "for key 'email'") !== false || stripos($driverMessage, 'patients.email') !== false) {
        clinica_json(['success' => false, 'error' => 'Ya existe una solicitud en curso con sus datos. Por favor, intente nuevamente en unos segundos.'], 409);
      }
      if (stripos($driverMessage, 'uq_appointment_slot') !== false || stripos($driverMessage, 'appointments.doctor_id') !== false) {
        clinica_json(['success' => false, 'error' => 'El horario seleccionado ya fue reservado por otra persona. Por favor, elija otro.'], 409);
      }
    }

    clinica_log_audit_event(clinica_get_user_id_from_session(), 'appointment_creation_db_error', 'Error de BD durante la creacion de cita', [
      'stage' => $stage,
      'sqlstate' => $sqlState,
      'driver_error' => $driverMessage,
    ]);
    clinica_json(['success' => false, 'error' => 'No se pudo crear la cita por un problema de la base de datos. Codigo de referencia: ' . substr(hash('sha256', $stage . '|' . $sqlState . '|' . $driverMessage), 0, 10)], 500);
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) {
      $pdo->rollBack();
    }
    $releaseLock($pdo, $lockName);
    error_log('Appointment creation unexpected error at stage ' . $stage . ': ' . $e->getMessage());
    clinica_json(['success' => false, 'error' => 'No se pudo crear la cita. Intente nuevamente.'], 500);
  }
} catch (Throwable $e) {
  error_log('Appointment schema/bootstrap error: ' . $e->getMessage());
  clinica_json(['success' => false, 'error' => 'La base de datos no esta lista para crear citas. Ejecute la inicializacion de la base de datos y vuelva a intentar.'], 500);
}

class ClinicaAppointmentConflict extends RuntimeException
{
}

function clinica_ensure_appointment_schema(PDO $pdo): void
{
  $pdo->exec("CREATE TABLE IF NOT EXISTS patients (id INT UNSIGNED NOT NULL AUTO_INCREMENT, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(50) DEFAULT NULL, cedula VARCHAR(20) DEFAULT NULL, active BOOLEAN NOT NULL DEFAULT TRUE, email_opt_in BOOLEAN NOT NULL DEFAULT TRUE, whatsapp_opt_in BOOLEAN NOT NULL DEFAULT TRUE, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (id), UNIQUE KEY uq_patients_email (email)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
  $pdo->exec("CREATE TABLE IF NOT EXISTS appointments (id INT UNSIGNED NOT NULL AUTO_INCREMENT, patient_id INT UNSIGNED NOT NULL, doctor_id VARCHAR(50) NOT NULL, specialty_id VARCHAR(50) NOT NULL, date DATE NOT NULL, time TIME NOT NULL, slot_minutes INT UNSIGNED NOT NULL DEFAULT 15, status ENUM('pendiente','atendida','no-presentado','rechazada','cancelada') NOT NULL DEFAULT 'pendiente', notes TEXT DEFAULT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

  clinica_ensure_column($pdo, 'patients', 'cedula', 'ALTER TABLE patients ADD COLUMN cedula VARCHAR(20) DEFAULT NULL AFTER phone');
  clinica_ensure_column($pdo, 'patients', 'active', 'ALTER TABLE patients ADD COLUMN active BOOLEAN NOT NULL DEFAULT TRUE AFTER cedula');
  clinica_ensure_column($pdo, 'patients', 'email_opt_in', 'ALTER TABLE patients ADD COLUMN email_opt_in BOOLEAN NOT NULL DEFAULT TRUE AFTER active');
  clinica_ensure_column($pdo, 'patients', 'whatsapp_opt_in', 'ALTER TABLE patients ADD COLUMN whatsapp_opt_in BOOLEAN NOT NULL DEFAULT TRUE AFTER email_opt_in');
  clinica_ensure_column($pdo, 'appointments', 'slot_minutes', 'ALTER TABLE appointments ADD COLUMN slot_minutes INT UNSIGNED NOT NULL DEFAULT 15 AFTER time');

  clinica_ensure_index($pdo, 'patients', 'uq_patients_cedula', 'ALTER TABLE patients ADD UNIQUE KEY uq_patients_cedula (cedula)');
  clinica_ensure_index($pdo, 'appointments', 'uq_appointment_slot', 'ALTER TABLE appointments ADD UNIQUE KEY uq_appointment_slot (doctor_id, date, time)');
}

function clinica_ensure_column(PDO $pdo, string $table, string $column, string $sql): void
{
  $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
  $stmt->execute([$table, $column]);
  if ((int) $stmt->fetchColumn() === 0) {
    $pdo->exec($sql);
  }
}

function clinica_ensure_index(PDO $pdo, string $table, string $index, string $sql): void
{
  $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?');
  $stmt->execute([$table, $index]);
  if ((int) $stmt->fetchColumn() === 0) {
    $pdo->exec($sql);
  }
}

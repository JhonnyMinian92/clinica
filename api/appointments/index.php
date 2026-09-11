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
$patientPhone = preg_replace('/\D+/', '', trim((string) ($payload['patientPhone'] ?? '')));
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
$locks = [];

function clinica_appointment_lock(PDO $pdo, string $name): bool
{
  $stmt = $pdo->prepare('SELECT GET_LOCK(?, 5)');
  $stmt->execute([$name]);
  return (int) $stmt->fetchColumn() === 1;
}
function clinica_appointment_unlock(PDO $pdo, array &$locks): void
{
  foreach (array_reverse($locks) as $name) {
    try {
      $stmt = $pdo->prepare('SELECT RELEASE_LOCK(?)');
      $stmt->execute([$name]);
    } catch (Throwable $e) {
      error_log('Appointment lock release error: ' . $e->getMessage());
    }
  }
  $locks = [];
}
function clinica_appointment_fail(PDO $pdo, array &$locks, string $message, int $status, string $auditAction = 'appointment_creation_failed', array $details = []): void
{
  if ($pdo->inTransaction()) {
    $pdo->rollBack();
  }
  clinica_appointment_unlock($pdo, $locks);
  clinica_log_audit_event(clinica_get_user_id_from_session(), $auditAction, $message, $details);
  clinica_json(['success' => false, 'error' => $message], $status);
}

try {
  $patientLockName = 'clinica_patient_' . md5($patientCedula);
  $doctorLockName = 'clinica_doctor_' . md5($doctorId . '|' . $date);

  if (!clinica_appointment_lock($pdo, $patientLockName)) {
    clinica_json(['success' => false, 'error' => 'El sistema esta procesando otra solicitud para este paciente. Intente nuevamente en unos segundos.'], 409);
  }
  $locks[] = $patientLockName;
  if (!clinica_appointment_lock($pdo, $doctorLockName)) {
    clinica_appointment_fail($pdo, $locks, 'El sistema esta procesando otra reserva para este doctor. Intente nuevamente en unos segundos.', 409);
  }
  $locks[] = $doctorLockName;

  $pdo->beginTransaction();
  $stage = 'datetime';
  $appointmentDateTime = new DateTimeImmutable($date . ' ' . substr($time, 0, 8));
  if ($appointmentDateTime <= new DateTimeImmutable('now')) {
    clinica_appointment_fail($pdo, $locks, 'No se pueden agendar citas en el pasado ni en la hora actual.', 422);
  }

  $stage = 'doctor';
  $stmt = $pdo->prepare('SELECT id FROM doctors WHERE id = ? AND specialty_id = ? AND active = 1 FOR UPDATE');
  $stmt->execute([$doctorId, $specialtyId]);
  if (!$stmt->fetch()) {
    clinica_appointment_fail($pdo, $locks, 'El doctor seleccionado no pertenece a la especialidad indicada o no esta activo.', 422);
  }

  $stage = 'schedule';
  $weekday = (int) $appointmentDateTime->format('N');
  $scheduleRules = clinica_schedule_rules_for_doctor($doctorId);
  if (!clinica_is_time_slot_available($appointmentDateTime, $scheduleRules, $weekday)) {
    clinica_appointment_fail($pdo, $locks, 'El horario seleccionado no esta disponible o no cumple con las reglas del doctor.', 409);
  }
  $slotMinutes = (int) clinica_get_slot_minutes_for_time($appointmentDateTime, $scheduleRules, $weekday);
  if ($slotMinutes <= 0 || $slotMinutes > 1440) {
    clinica_appointment_fail($pdo, $locks, 'No se pudo determinar la duracion valida de la cita.', 409);
  }

  $stage = 'patient_lookup';
  $stmt = $pdo->prepare('SELECT id, email FROM patients WHERE cedula = ? FOR UPDATE');
  $stmt->execute([$patientCedula]);
  $patientByCedula = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($patientByCedula) {
    $patientId = (int) $patientByCedula['id'];
    $emailToSave = (string) $patientByCedula['email'];
    if (strcasecmp($emailToSave, $patientEmail) !== 0) {
      $stmt = $pdo->prepare('SELECT id FROM patients WHERE email = ? AND id <> ?');
      $stmt->execute([$patientEmail, $patientId]);
      if (!$stmt->fetch()) $emailToSave = $patientEmail;
    }
    $stmt = $pdo->prepare('UPDATE patients SET name = ?, phone = ?, email = ?, email_opt_in = ?, whatsapp_opt_in = ? WHERE id = ?');
    $stmt->execute([$patientName, $patientPhone, $emailToSave, (int) $emailOptIn, (int) $whatsappOptIn, $patientId]);
  } else {
    $stmt = $pdo->prepare('SELECT id, cedula FROM patients WHERE email = ? FOR UPDATE');
    $stmt->execute([$patientEmail]);
    $patientByEmail = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($patientByEmail && $patientByEmail['cedula'] === null) {
      $patientId = (int) $patientByEmail['id'];
      $stmt = $pdo->prepare('UPDATE patients SET name = ?, phone = ?, cedula = ?, email_opt_in = ?, whatsapp_opt_in = ? WHERE id = ?');
      $stmt->execute([$patientName, $patientPhone, $patientCedula, (int) $emailOptIn, (int) $whatsappOptIn, $patientId]);
    } elseif ($patientByEmail) {
      clinica_appointment_fail($pdo, $locks, 'Este correo ya esta registrado con otro numero de cedula/documento. Verifique sus datos o comuníquese con la clinica.', 409);
    } else {
      $stmt = $pdo->prepare('INSERT INTO patients (name, email, phone, cedula, email_opt_in, whatsapp_opt_in) VALUES (?, ?, ?, ?, ?, ?)');
      $stmt->execute([$patientName, $patientEmail, $patientPhone, $patientCedula, (int) $emailOptIn, (int) $whatsappOptIn]);
      $patientId = (int) $pdo->lastInsertId();
    }
  }

  $stage = 'patient_limits';
  $stmt = $pdo->prepare("SELECT time FROM appointments WHERE patient_id = ? AND date = ? AND status NOT IN ('rechazada', 'cancelada') FOR UPDATE");
  $stmt->execute([$patientId, $date]);
  $existingPatientTimes = $stmt->fetchAll(PDO::FETCH_COLUMN);
  if (count($existingPatientTimes) >= CLINICA_MAX_APPOINTMENTS_PER_PATIENT_PER_DAY) {
    clinica_appointment_fail($pdo, $locks, 'Ya tiene el maximo de ' . CLINICA_MAX_APPOINTMENTS_PER_PATIENT_PER_DAY . ' citas permitidas para este dia.', 409);
  }
  foreach ($existingPatientTimes as $existingTime) {
    $existingStart = new DateTimeImmutable($date . ' ' . substr((string) $existingTime, 0, 8));
    $gapMinutes = abs($appointmentDateTime->getTimestamp() - $existingStart->getTimestamp()) / 60;
    if ($gapMinutes < CLINICA_MIN_MINUTES_BETWEEN_PATIENT_APPOINTMENTS) {
      clinica_appointment_fail($pdo, $locks, 'Debe haber al menos ' . (CLINICA_MIN_MINUTES_BETWEEN_PATIENT_APPOINTMENTS / 60) . ' horas de diferencia entre sus citas del mismo dia.', 409);
    }
  }

  $stage = 'availability';
  $candidateStart = $appointmentDateTime->modify('-' . CLINICA_APPOINTMENT_BUFFER_MINUTES . ' minutes');
  $candidateEnd = $appointmentDateTime->modify('+' . ($slotMinutes + CLINICA_APPOINTMENT_BUFFER_MINUTES) . ' minutes');
  $stmt = $pdo->prepare("SELECT id, time, slot_minutes FROM appointments WHERE doctor_id = ? AND date = ? AND status NOT IN ('rechazada', 'cancelada') FOR UPDATE");
  $stmt->execute([$doctorId, $date]);
  foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $existing) {
    $existingStart = new DateTimeImmutable($date . ' ' . substr((string) $existing['time'], 0, 8));
    $existingDuration = max(1, (int) ($existing['slot_minutes'] ?? 15));
    $existingEnd = $existingStart->modify('+' . $existingDuration . ' minutes');
    if ($candidateStart < $existingEnd && $candidateEnd > $existingStart) {
      clinica_appointment_fail($pdo, $locks, 'El horario seleccionado ya no esta disponible. Por favor, elija otro.', 409);
    }
  }

  $stage = 'insert';
  $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, specialty_id, date, time, slot_minutes, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente')");
  $stmt->execute([$patientId, $doctorId, $specialtyId, $date, $appointmentDateTime->format('H:i:s'), $slotMinutes, $patientNotes]);
  $newAppointmentId = (int) $pdo->lastInsertId();
  $pdo->commit();
  clinica_appointment_unlock($pdo, $locks);

  try { clinica_notify_appointment_created($newAppointmentId); } catch (Throwable $e) { error_log('Notification dispatch failed: ' . $e->getMessage()); }
  clinica_log_audit_event(clinica_get_user_id_from_session(), 'appointment_created', 'Cita agendada exitosamente', [
    'appointment_id' => $newAppointmentId, 'patient_id' => $patientId, 'doctorId' => $doctorId,
    'date' => $date, 'time' => $appointmentDateTime->format('H:i:s'),
  ]);
  clinica_json(['success' => true, 'message' => 'Cita creada exitosamente.', 'appointmentId' => $newAppointmentId]);
} catch (PDOException $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  clinica_appointment_unlock($pdo, $locks);
  $sqlState = (string) $e->getCode();
  $driverMessage = (string) ($e->errorInfo[2] ?? $e->getMessage());
  error_log('Appointment creation DB error at stage ' . ($stage ?? 'unknown') . ': ' . $e->getMessage());
  if ($sqlState === '23000') {
    if (stripos($driverMessage, 'uq_appointment_slot') !== false || stripos($driverMessage, 'doctor_id') !== false) {
      clinica_json(['success' => false, 'error' => 'El horario seleccionado ya fue reservado por otra persona. Por favor, elija otro.'], 409);
    }
    if (stripos($driverMessage, 'uq_patients_cedula') !== false) {
      clinica_json(['success' => false, 'error' => 'Sus datos estan siendo procesados por otra solicitud. Intente nuevamente en unos segundos.'], 409);
    }
  }
  clinica_log_audit_event(clinica_get_user_id_from_session(), 'appointment_creation_db_error', 'Error de BD durante la creacion de cita', ['stage' => $stage ?? 'unknown', 'sqlstate' => $sqlState]);
  clinica_json(['success' => false, 'error' => 'No se pudo crear la cita por un problema de la base de datos. Codigo de referencia: ' . substr(hash('sha256', ($stage ?? 'unknown') . '|' . $sqlState), 0, 10)], 500);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  clinica_appointment_unlock($pdo, $locks);
  error_log('Appointment creation unexpected error at stage ' . ($stage ?? 'unknown') . ': ' . $e->getMessage());
  clinica_json(['success' => false, 'error' => 'No se pudo crear la cita. Intente nuevamente.'], 500);
}

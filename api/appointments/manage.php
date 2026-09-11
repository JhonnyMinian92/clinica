<?php
require_once __DIR__ . '/../data.php';

clinica_ensure_session_started();
header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  clinica_json(['success' => false, 'error' => 'Metodo no permitido.'], 405);
}
if (!clinica_validate_csrf_from_request()) {
  clinica_json(['success' => false, 'error' => 'CSRF token invalido.'], 403);
}

$action = strtolower(trim((string) ($_POST['action'] ?? $_GET['action'] ?? '')));
$cedula = strtoupper(trim((string) ($_POST['cedula'] ?? '')));
if (!preg_match('/^[A-Z0-9]{6,20}$/', $cedula)) {
  clinica_json(['success' => false, 'error' => 'Ingrese una cedula o documento valido.'], 422);
}

function clinica_manage_lock(PDO $pdo, string $name): bool
{
  $stmt = $pdo->prepare('SELECT GET_LOCK(?, 5)');
  $stmt->execute([$name]);
  return (int) $stmt->fetchColumn() === 1;
}
function clinica_manage_unlock(PDO $pdo, array &$locks): void
{
  foreach (array_reverse($locks) as $name) {
    try {
      $stmt = $pdo->prepare('SELECT RELEASE_LOCK(?)');
      $stmt->execute([$name]);
    } catch (Throwable $e) {
      error_log('Appointment management lock release error: ' . $e->getMessage());
    }
  }
  $locks = [];
}
function clinica_manage_datetime(string $date, string $time): DateTimeImmutable
{
  return new DateTimeImmutable($date . ' ' . substr($time, 0, 8));
}
function clinica_manage_history_table(PDO $pdo): void
{
  $pdo->exec("CREATE TABLE IF NOT EXISTS appointment_status_history (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    appointment_id INT UNSIGNED NOT NULL,
    old_status VARCHAR(30) NOT NULL,
    new_status VARCHAR(30) NOT NULL,
    changed_by_user_id INT UNSIGNED DEFAULT NULL,
    reason VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_appointment_status_history_appointment (appointment_id),
    KEY idx_appointment_status_history_created (created_at)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

$pdo = clinica_get_db();
$locks = [];
try {
  if ($action === 'lookup') {
    $now = new DateTimeImmutable('now');
    $stmt = $pdo->prepare("SELECT a.id, a.date, a.time, a.status, d.name AS doctor_name, s.name AS specialty_name
      FROM appointments a
      INNER JOIN patients p ON p.id = a.patient_id
      LEFT JOIN doctors d ON d.id = a.doctor_id
      LEFT JOIN specialties s ON s.id = a.specialty_id
      WHERE p.cedula = ? AND a.status NOT IN ('rechazada', 'cancelada') AND a.date >= ?
      ORDER BY a.date ASC, a.time ASC, a.id ASC");
    $stmt->execute([$cedula, $now->format('Y-m-d')]);
    $appointments = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
      $when = clinica_manage_datetime((string) $row['date'], (string) $row['time']);
      if ($when <= $now) continue;
      $appointments[] = [
        'id' => (int) $row['id'],
        'date' => (string) $row['date'],
        'time' => substr((string) $row['time'], 0, 5),
        'status' => (string) $row['status'],
        'doctor' => (string) ($row['doctor_name'] ?? 'Doctor no disponible'),
        'specialty' => (string) ($row['specialty_name'] ?? 'Especialidad no disponible'),
        'canCancel' => $row['status'] === 'pendiente',
      ];
    }
    clinica_json(['success' => true, 'appointments' => $appointments, 'message' => count($appointments) > 0 ? 'Se encontraron sus citas futuras.' : 'No encontramos citas futuras activas con ese documento.']);
  }

  if ($action !== 'cancel') {
    clinica_json(['success' => false, 'error' => 'Accion no valida.'], 400);
  }
  $appointmentId = (int) ($_POST['appointmentId'] ?? 0);
  if ($appointmentId <= 0) {
    clinica_json(['success' => false, 'error' => 'Datos de anulacion invalidos.'], 422);
  }

  // Same patient lock used by creation: cancellation cannot race with a booking
  // by the same patient, even when the other booking targets another doctor.
  $patientLockName = 'clinica_patient_' . md5($cedula);
  if (!clinica_manage_lock($pdo, $patientLockName)) {
    clinica_json(['success' => false, 'error' => 'El sistema esta procesando otra solicitud para este paciente. Intente nuevamente en unos segundos.'], 409);
  }
  $locks[] = $patientLockName;
  $pdo->beginTransaction();

  $stmt = $pdo->prepare("SELECT a.id, a.patient_id, a.doctor_id, a.date, a.time, a.status
    FROM appointments a INNER JOIN patients p ON p.id = a.patient_id
    WHERE a.id = ? AND p.cedula = ? FOR UPDATE");
  $stmt->execute([$appointmentId, $cedula]);
  $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$appointment) {
    $pdo->rollBack(); clinica_manage_unlock($pdo, $locks);
    clinica_json(['success' => false, 'error' => 'No se encontro una cita asociada a ese documento.'], 404);
  }
  if ((string) $appointment['status'] === 'cancelada') {
    $pdo->rollBack(); clinica_manage_unlock($pdo, $locks);
    clinica_json(['success' => false, 'error' => 'La cita ya fue anulada.'], 409);
  }
  if ((string) $appointment['status'] !== 'pendiente') {
    $pdo->rollBack(); clinica_manage_unlock($pdo, $locks);
    clinica_json(['success' => false, 'error' => 'Esta cita ya no puede ser anulada porque su estado es: ' . $appointment['status'] . '.'], 409);
  }
  $appointmentDateTime = clinica_manage_datetime((string) $appointment['date'], (string) $appointment['time']);
  if ($appointmentDateTime <= new DateTimeImmutable('now')) {
    $pdo->rollBack(); clinica_manage_unlock($pdo, $locks);
    clinica_json(['success' => false, 'error' => 'La cita ya llego a su hora de atencion y no puede ser anulada.'], 409);
  }

  $doctorLockName = 'clinica_doctor_' . md5((string) $appointment['doctor_id'] . '|' . (string) $appointment['date']);
  if (!clinica_manage_lock($pdo, $doctorLockName)) {
    $pdo->rollBack(); clinica_manage_unlock($pdo, $locks);
    clinica_json(['success' => false, 'error' => 'El sistema esta procesando otra reserva para ese doctor. Intente nuevamente en unos segundos.'], 409);
  }
  $locks[] = $doctorLockName;

  $stmt = $pdo->prepare('SELECT status, date, time FROM appointments WHERE id = ? FOR UPDATE');
  $stmt->execute([$appointmentId]);
  $lockedAppointment = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$lockedAppointment || (string) $lockedAppointment['status'] !== 'pendiente') {
    $pdo->rollBack(); clinica_manage_unlock($pdo, $locks);
    clinica_json(['success' => false, 'error' => 'La cita cambio de estado mientras se procesaba la anulacion. Intente nuevamente.'], 409);
  }
  $lockedDateTime = clinica_manage_datetime((string) $lockedAppointment['date'], (string) $lockedAppointment['time']);
  if ($lockedDateTime <= new DateTimeImmutable('now')) {
    $pdo->rollBack(); clinica_manage_unlock($pdo, $locks);
    clinica_json(['success' => false, 'error' => 'La cita ya llego a su hora de atencion y no puede ser anulada.'], 409);
  }

  clinica_manage_history_table($pdo);
  $update = $pdo->prepare("UPDATE appointments SET status = 'cancelada' WHERE id = ? AND status = 'pendiente'");
  $update->execute([$appointmentId]);
  if ($update->rowCount() !== 1) {
    $pdo->rollBack(); clinica_manage_unlock($pdo, $locks);
    clinica_json(['success' => false, 'error' => 'La cita cambio de estado mientras se procesaba la anulacion. Intente nuevamente.'], 409);
  }
  $history = $pdo->prepare("INSERT INTO appointment_status_history (appointment_id, old_status, new_status, changed_by_user_id, reason) VALUES (?, 'pendiente', 'cancelada', NULL, ?)");
  $history->execute([$appointmentId, 'Anulada por el paciente antes de la hora de atencion.']);
  $pdo->commit();
  clinica_manage_unlock($pdo, $locks);

  clinica_log_audit_event(null, 'appointment_cancelled_by_patient', 'Cita anulada por el paciente antes de su hora de atencion', ['appointment_id' => $appointmentId, 'date' => $appointment['date'], 'time' => $appointment['time']]);
  clinica_json(['success' => true, 'message' => 'Cita anulada correctamente. Ahora puede reservar otra cita en otro dia, hora o con otro doctor.', 'appointmentId' => $appointmentId]);
} catch (PDOException $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  clinica_manage_unlock($pdo, $locks);
  error_log('Appointment management DB error: ' . $e->getMessage());
  clinica_json(['success' => false, 'error' => 'No se pudo procesar la cita por un problema de la base de datos. Intente nuevamente.'], 500);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  clinica_manage_unlock($pdo, $locks);
  error_log('Appointment management error: ' . $e->getMessage());
  clinica_json(['success' => false, 'error' => 'No se pudo procesar la cita. Intente nuevamente.'], 500);
}

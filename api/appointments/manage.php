<?php

require_once __DIR__ . '/../data.php';

clinica_ensure_session_started();
header('Content-Type: application/json; charset=utf-8');

function clinica_appointment_manage_cedula(string $value): string
{
  return strtoupper(trim($value));
}

function clinica_appointment_manage_validate_cedula(string $cedula): bool
{
  return preg_match('/^[A-Z0-9]{6,20}$/', $cedula) === 1;
}

function clinica_appointment_manage_datetime(string $date, string $time): DateTimeImmutable
{
  return new DateTimeImmutable($date . ' ' . substr($time, 0, 8));
}

function clinica_appointment_manage_lookup(PDO $pdo, string $cedula): array
{
  $today = (new DateTimeImmutable('now'))->format('Y-m-d');

  $stmt = $pdo->prepare(
    "SELECT
      a.id,
      a.date,
      a.time,
      a.status,
      d.name AS doctor_name,
      s.name AS specialty_name
    FROM appointments a
    LEFT JOIN doctors d ON d.id = a.doctor_id
    LEFT JOIN specialties s ON s.id = a.specialty_id
    INNER JOIN patients p ON p.id = a.patient_id
    WHERE p.cedula = ?
      AND a.date >= ?
      AND a.status NOT IN ('rechazada', 'cancelada')
    ORDER BY a.date ASC, a.time ASC, a.id ASC"
  );
  $stmt->execute([$cedula, $today]);

  $appointments = [];
  $now = new DateTimeImmutable('now');

  foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $appointmentDateTime = clinica_appointment_manage_datetime((string) $row['date'], (string) $row['time']);
    $canCancel = $row['status'] === 'pendiente' && $appointmentDateTime > $now;

    $appointments[] = [
      'id' => (int) $row['id'],
      'date' => (string) $row['date'],
      'time' => substr((string) $row['time'], 0, 5),
      'status' => (string) $row['status'],
      'doctor' => (string) ($row['doctor_name'] ?? 'Doctor no disponible'),
      'specialty' => (string) ($row['specialty_name'] ?? 'Especialidad no disponible'),
      'canCancel' => $canCancel,
    ];
  }

  return $appointments;
}

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$action = strtolower(trim((string) ($_GET['action'] ?? $_POST['action'] ?? '')));

if ($action === 'lookup' && $method === 'GET') {
  $cedula = clinica_appointment_manage_cedula((string) ($_GET['cedula'] ?? ''));

  if (!clinica_appointment_manage_validate_cedula($cedula)) {
    clinica_json(['success' => false, 'error' => 'Ingrese una cedula o documento valido.'], 422);
  }

  try {
    $appointments = clinica_appointment_manage_lookup(clinica_get_db(), $cedula);
    clinica_json([
      'success' => true,
      'appointments' => $appointments,
      'message' => count($appointments) > 0
        ? 'Se encontraron sus citas futuras.'
        : 'No encontramos citas futuras activas con ese documento.',
    ]);
  } catch (Throwable $e) {
    error_log('Appointment lookup error: ' . $e->getMessage());
    clinica_json(['success' => false, 'error' => 'No se pudo consultar la cita en este momento.'], 500);
  }
}

if ($action === 'cancel' && $method === 'POST') {
  if (!clinica_validate_csrf_from_request()) {
    clinica_json(['success' => false, 'error' => 'CSRF token invalido.'], 403);
  }

  $cedula = clinica_appointment_manage_cedula((string) ($_POST['cedula'] ?? ''));
  $appointmentId = (int) ($_POST['appointmentId'] ?? 0);

  if (!clinica_appointment_manage_validate_cedula($cedula) || $appointmentId <= 0) {
    clinica_json(['success' => false, 'error' => 'Datos de anulacion invalidos.'], 422);
  }

  $pdo = clinica_get_db();

  try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
      "SELECT
        a.id,
        a.date,
        a.time,
        a.status
      FROM appointments a
      INNER JOIN patients p ON p.id = a.patient_id
      WHERE a.id = ? AND p.cedula = ?
      FOR UPDATE"
    );
    $stmt->execute([$appointmentId, $cedula]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appointment) {
      $pdo->rollBack();
      clinica_json(['success' => false, 'error' => 'No se encontro una cita asociada a ese documento.'], 404);
    }

    if ((string) $appointment['status'] === 'cancelada') {
      $pdo->rollBack();
      clinica_json(['success' => false, 'error' => 'La cita ya fue anulada.'], 409);
    }

    if ((string) $appointment['status'] !== 'pendiente') {
      $pdo->rollBack();
      clinica_json(['success' => false, 'error' => 'Esta cita ya no puede ser anulada porque su estado es: ' . $appointment['status'] . '.'], 409);
    }

    $appointmentDateTime = clinica_appointment_manage_datetime((string) $appointment['date'], (string) $appointment['time']);
    if ($appointmentDateTime <= new DateTimeImmutable('now')) {
      $pdo->rollBack();
      clinica_json(['success' => false, 'error' => 'La cita ya llego a su hora de atencion y no puede ser anulada.'], 409);
    }

    $update = $pdo->prepare("UPDATE appointments SET status = 'cancelada' WHERE id = ? AND status = 'pendiente'");
    $update->execute([$appointmentId]);

    if ($update->rowCount() !== 1) {
      $pdo->rollBack();
      clinica_json(['success' => false, 'error' => 'La cita cambio de estado mientras se procesaba la anulacion. Intente nuevamente.'], 409);
    }

    $history = $pdo->prepare("INSERT INTO appointment_status_history (appointment_id, old_status, new_status, changed_by_user_id, reason) VALUES (?, 'pendiente', 'cancelada', NULL, ?)");
    $history->execute([$appointmentId, 'Anulada por el paciente antes de la hora de atencion.']);

    $pdo->commit();

    clinica_log_audit_event(null, 'appointment_cancelled_by_patient', 'Cita anulada por el paciente antes de su hora de atencion', [
      'appointment_id' => $appointmentId,
      'date' => $appointment['date'],
      'time' => $appointment['time'],
    ]);

    clinica_json([
      'success' => true,
      'message' => 'Cita anulada correctamente. Ahora puede reservar otra cita en otro dia, hora o con otro doctor.',
      'appointmentId' => $appointmentId,
    ]);
  } catch (PDOException $e) {
    if ($pdo->inTransaction()) {
      $pdo->rollBack();
    }
    error_log('Appointment cancellation DB error: ' . $e->getMessage());
    clinica_json(['success' => false, 'error' => 'No se pudo anular la cita por un problema de la base de datos.'], 500);
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) {
      $pdo->rollBack();
    }
    error_log('Appointment cancellation error: ' . $e->getMessage());
    clinica_json(['success' => false, 'error' => 'No se pudo anular la cita. Intente nuevamente.'], 500);
  }
}

clinica_json(['success' => false, 'error' => 'Accion no valida.'], 400);

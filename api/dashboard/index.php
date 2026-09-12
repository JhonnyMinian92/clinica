<?php
require_once __DIR__ . '/../data.php';
require_once __DIR__ . '/../notifications/service.php';

clinica_ensure_session_started();
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
  if (!clinica_validate_csrf_from_request()) {
    clinica_json(['success' => false, 'error' => 'CSRF token invalido.'], 403);
  }
}

$user = clinica_current_user();

if (!is_array($user) || !in_array($user['role'] ?? '', clinica_dashboard_roles(), true)) {
  clinica_json(['success' => false, 'error' => 'Acceso no autorizado.'], 401);
}

$canSeeAllAppointments = clinica_can_see_all_appointments((string) ($user['role'] ?? ''));
clinica_maybe_run_reminder_sweep();

if ($method === 'GET' && (string) ($_GET['action'] ?? '') === 'patient_history') {
  $patientIdParam = (int) ($_GET['patient_id'] ?? 0);
  $email = trim((string) ($_GET['email'] ?? ''));

  if ($patientIdParam <= 0 && ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
    clinica_json(['success' => false, 'error' => 'Paciente no especificado.'], 422);
  }

  $pdo = clinica_get_db();
  if ($patientIdParam > 0) {
    $stmt = $pdo->prepare("SELECT id, name, email, phone FROM patients WHERE id = ?");
    $stmt->execute([$patientIdParam]);
  } else {
    $stmt = $pdo->prepare("SELECT id, name, email, phone FROM patients WHERE email = ?");
    $stmt->execute([$email]);
  }
  $patient = $stmt->fetch();

  if (!$patient) {
    clinica_json(['success' => true, 'patient' => null, 'appointments' => []]);
  }

  $where = ['a.patient_id = :patient_id'];
  $params = [':patient_id' => $patient['id']];
  if (!$canSeeAllAppointments) {
    $where[] = 'a.doctor_id = :user_id';
    $params[':user_id'] = $user['id'];
  }

  $sql = "SELECT a.id, a.date AS fecha, a.time AS hora, a.status AS estado, a.notes AS motivo_consulta,
                 s.name AS especialidad, d.name AS doctor_nombre
          FROM appointments a
          JOIN doctors d ON a.doctor_id = d.id
          LEFT JOIN specialties s ON a.specialty_id = s.id
          WHERE " . implode(' AND ', $where) . '
          ORDER BY a.date DESC, a.time DESC';
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $appointments = $stmt->fetchAll();

  clinica_json([
    'success' => true,
    'patient' => [
      'name' => $patient['name'],
      'email' => $patient['email'],
      'phone' => $patient['phone'],
    ],
    'appointments' => $appointments,
  ]);
}

if ($method === 'GET') {
  $doctorId = isset($_GET['doctor_id']) ? trim((string) $_GET['doctor_id']) : null;
  $doctorId = $doctorId !== '' ? $doctorId : null;
  $doctorId = $canSeeAllAppointments ? $doctorId : null;
  $dateScope = isset($_GET['date_scope']) ? trim((string) $_GET['date_scope']) : 'today';
  $exactDate = isset($_GET['date']) ? trim((string) $_GET['date']) : null;
  $exactDate = clinica_is_valid_dashboard_date($exactDate) ? $exactDate : null;
  $dateScope = clinica_normalize_date_scope($dateScope, $user['role']);
  $statusFilter = clinica_normalize_dashboard_status((string) ($_GET['status'] ?? 'all'));
  $search = trim((string) ($_GET['search'] ?? ''));
  $page = max(1, (int) ($_GET['page'] ?? 1));
  $perPage = max(6, min(48, (int) ($_GET['per_page'] ?? 6)));

  $pdo = clinica_get_db();
  $params = [];
  $baseJoinSql = " FROM appointments a JOIN patients p ON a.patient_id = p.id JOIN doctors d ON a.doctor_id = d.id LEFT JOIN specialties s ON a.specialty_id = s.id";
  $countSql = "SELECT COUNT(a.id)" . $baseJoinSql;
  $dataSql = "SELECT a.id, a.date AS fecha, a.time AS hora, a.patient_id, p.name AS nombre_paciente, p.email AS email_paciente, p.phone AS telefono_paciente, a.notes AS motivo_consulta, a.status AS estado, s.name AS especialidad, a.doctor_id, d.name AS doctor_nombre" . $baseJoinSql;
  $where = [];

  if (!$canSeeAllAppointments) {
    $where[] = "a.doctor_id = :user_id";
    $params[':user_id'] = $user['id'];
  } elseif ($doctorId) {
    $where[] = "a.doctor_id = :doctor_id";
    $params[':doctor_id'] = $doctorId;
  }

  if ($exactDate) {
    $where[] = "a.date = :exact_date";
    $params[':exact_date'] = $exactDate;
  } else {
    $today = date('Y-m-d');
    switch ($dateScope) {
      case 'tomorrow':
        $where[] = "a.date = DATE_ADD(:today, INTERVAL 1 DAY)";
        $params[':today'] = $today;
        break;
      case 'future':
        $where[] = "a.date >= :today";
        $params[':today'] = $today;
        break;
      case 'past':
        $where[] = "a.date < :today";
        $params[':today'] = $today;
        break;
      case 'today':
        $where[] = "a.date = :today";
        $params[':today'] = $today;
        break;
    }
  }

  if ($search !== '') {
    $searchLike = '%' . $search . '%';
    $searchFields = ['p.name', 'p.email', 'p.phone', 'a.notes', 's.name', 'd.name', 'CAST(a.id AS CHAR)'];
    $searchClauses = [];
    foreach ($searchFields as $index => $field) {
      $param = ':search_' . $index;
      $searchClauses[] = "$field LIKE $param";
      $params[$param] = $searchLike;
    }
    $where[] = '(' . implode(' OR ', $searchClauses) . ')';
  }

  $summarySql = $dataSql . (empty($where) ? '' : ' WHERE ' . implode(' AND ', $where)) . " ORDER BY a.date, a.time";
  $summaryStmt = $pdo->prepare($summarySql);
  $summaryStmt->execute($params);
  $summaryRecords = $summaryStmt->fetchAll();
  $statusCounts = clinica_dashboard_status_counts($summaryRecords);

  if ($statusFilter !== 'all') {
    $where[] = "a.status = :status";
    $params[':status'] = $statusFilter;
  }
  $whereClause = empty($where) ? '' : ' WHERE ' . implode(' AND ', $where);
  $totalStmt = $pdo->prepare($countSql . $whereClause);
  $totalStmt->execute($params);
  $totalRecords = (int) $totalStmt->fetchColumn();
  $totalPages = max(1, (int) ceil($totalRecords / $perPage));
  $page = min($page, $totalPages);
  $offset = ($page - 1) * $perPage;
  $dataSql .= $whereClause . " ORDER BY a.date, a.time LIMIT :limit OFFSET :offset";
  $dataStmt = $pdo->prepare($dataSql);
  $dataStmt->execute(array_merge($params, [':limit' => $perPage, ':offset' => $offset]));
  $pageRecords = $dataStmt->fetchAll();

  $notificationStatuses = clinica_notification_status_for_appointments(array_column($pageRecords, 'id'));
  $appointments = array_map(
    static function (array $appointment) use ($user, $notificationStatuses): array {
      $transformed = clinica_transform_appointment($appointment, $user);
      $appointmentId = (int) ($appointment['id'] ?? 0);
      $transformed['notifications'] = $notificationStatuses[$appointmentId] ?? clinica_notification_status_empty();
      return $transformed;
    },
    $pageRecords
  );
  $nextPending = null;
  foreach ($summaryRecords as $appointment) {
    if (($appointment['estado'] ?? '') === 'pendiente') {
      $nextPending = clinica_transform_appointment($appointment, $user);
      break;
    }
  }
  $doctors = $canSeeAllAppointments ? clinica_get_all_doctors_for_select() : [];

  clinica_json([
    'success' => true,
    'user' => clinica_public_user($user),
    'expires_at' => clinica_session_expires_at(),
    'appointments' => $appointments,
    'doctors' => $doctors,
    'filters' => [
      'date_scope' => $dateScope,
      'date' => $exactDate,
      'status' => $statusFilter,
      'search' => $search,
    ],
    'summary' => [
      'counts' => $statusCounts,
      'next_pending' => $nextPending,
      'filtered_total' => $totalRecords,
    ],
    'pagination' => [
      'page' => $page,
      'per_page' => $perPage,
      'total' => $totalRecords,
      'total_pages' => $totalPages,
      'from' => $totalRecords === 0 ? 0 : $offset + 1,
      'to' => min($offset + $perPage, $totalRecords),
    ],
  ]);
}

if ($method === 'POST') {
  $payload = clinica_request_json();

  if ((string) ($payload['action'] ?? '') === 'resend_notification') {
    $appointmentId = (int) ($payload['appointmentId'] ?? 0);
    $channel = trim((string) ($payload['channel'] ?? ''));
    $type = trim((string) ($payload['type'] ?? ''));
    $pdo = clinica_get_db();
    $stmt = $pdo->prepare("SELECT doctor_id FROM appointments WHERE id = ?");
    $stmt->execute([$appointmentId]);
    $targetAppointment = $stmt->fetch();
    if (!$targetAppointment) {
      clinica_json(['success' => false, 'error' => 'Cita no encontrada.'], 404);
    }
    if (!$canSeeAllAppointments && (string) $targetAppointment['doctor_id'] !== (string) $user['id']) {
      clinica_json(['success' => false, 'error' => 'No puede reenviar notificaciones de esta cita.'], 403);
    }
    $result = clinica_notify_resend($appointmentId, $channel, $type, clinica_get_user_id_from_session());
    clinica_json($result, $result['success'] ? 200 : 422);
  }

  $appointmentId = trim((string) ($payload['appointmentId'] ?? ''));
  $newStatus = trim((string) ($payload['newStatus'] ?? ''));
  if ($appointmentId === '' || !in_array($newStatus, clinica_statuses(), true)) {
    clinica_json(['success' => false, 'error' => 'Datos de actualizacion invalidos.'], 422);
  }

  $pdo = clinica_get_db();
  try {
    $pdo->beginTransaction();

    // Lock the appointment before evaluating and changing its state. This
    // prevents two dashboard requests from both observing "pendiente" and
    // creating conflicting status-history entries.
    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ? FOR UPDATE");
    $stmt->execute([$appointmentId]);
    $appointment = $stmt->fetch();
    if (!$appointment) {
      $pdo->rollBack();
      clinica_json(['success' => false, 'error' => 'Cita no encontrada.'], 404);
    }

    if (!$canSeeAllAppointments && (string) $appointment['doctor_id'] !== (string) $user['id']) {
      $pdo->rollBack();
      clinica_json(['success' => false, 'error' => 'No puede actualizar esta cita.'], 403);
    }

    $oldStatus = (string) ($appointment['status'] ?? '');
    $finalStatuses = ['atendida', 'no-presentado', 'rechazada', 'cancelada'];
    if ($oldStatus === $newStatus) {
      $pdo->rollBack();
      clinica_json(['success' => true, 'message' => 'La cita ya tenia ese estado.']);
    }
    if (in_array($oldStatus, $finalStatuses, true)) {
      $pdo->rollBack();
      clinica_json(['success' => false, 'error' => 'No puede cambiar una cita que ya esta cerrada.'], 422);
    }
    if ($oldStatus !== 'pendiente') {
      $pdo->rollBack();
      clinica_json(['success' => false, 'error' => 'Solo se pueden actualizar citas pendientes.'], 422);
    }

    if (in_array($newStatus, ['atendida', 'no-presentado'], true)) {
      $appointmentDateTime = clinica_dashboard_appointment_datetime($appointment);
      if ($appointmentDateTime !== null && $appointmentDateTime > new DateTimeImmutable('now')) {
        $pdo->rollBack();
        clinica_json(['success' => false, 'error' => 'Solo puede marcar como atendida o no presentada una cita que ya inicio.'], 422);
      }
    }

    $updateStmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ? AND status = 'pendiente'");
    $updateStmt->execute([$newStatus, $appointmentId]);
    if ($updateStmt->rowCount() !== 1) {
      $pdo->rollBack();
      clinica_json(['success' => false, 'error' => 'La cita cambio de estado mientras se procesaba. Intente nuevamente.'], 409);
    }

    $historyStmt = $pdo->prepare("INSERT INTO appointment_status_history (appointment_id, old_status, new_status, changed_by_user_id, reason) VALUES (?, ?, ?, ?, ?)");
    $historyStmt->execute([
      $appointmentId,
      $oldStatus,
      $newStatus,
      clinica_get_user_id_from_session(),
      trim((string) ($payload['reason'] ?? 'Cambio desde dashboard')),
    ]);
    $pdo->commit();

    clinica_log_audit_event(clinica_get_user_id_from_session(), 'appointment_status_updated', 'Estado de cita actualizado', [
      'appointment_id' => $appointmentId,
      'old_status' => $oldStatus,
      'new_status' => $newStatus,
    ], 'appointment', $appointmentId);
  } catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    clinica_json(['success' => false, 'error' => 'No se pudo actualizar el estado de la cita.'], 500);
  }

  clinica_json(['success' => true, 'message' => 'Estado de la cita actualizado.']);
}

function clinica_dashboard_appointment_datetime(array $appointment): ?DateTimeImmutable
{
  $date = trim((string) ($appointment['date'] ?? ''));
  $time = substr(trim((string) ($appointment['time'] ?? '00:00')), 0, 5);
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !preg_match('/^\d{2}:\d{2}$/', $time)) {
    return null;
  }
  try {
    return new DateTimeImmutable($date . ' ' . $time . ':00');
  } catch (Exception $exception) {
    return null;
  }
}

clinica_json(['success' => false, 'error' => 'Metodo no permitido.'], 405);

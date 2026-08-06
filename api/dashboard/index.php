<?php
require_once __DIR__ . '/../data.php';

// Ensure session started and CSRF cookie available
clinica_ensure_session_started();
header('Content-Type: application/json; charset=utf-8');

// Protect mutating requests with CSRF validation
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

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$canSeeAllAppointments = clinica_can_see_all_appointments((string) ($user['role'] ?? ''));

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
  $offset = ($page - 1) * $perPage;

  $pdo = clinica_get_db();
  $params = [];

  // Base query
  $countSql = "SELECT COUNT(a.id) FROM appointments a JOIN patients p ON a.patient_id = p.id JOIN doctors d ON a.doctor_id = d.id";
  $dataSql = "SELECT a.id, a.date AS fecha, a.time AS hora, p.name AS nombre_paciente, p.email AS email_paciente, p.phone AS telefono_paciente, a.notes AS motivo_consulta, a.status AS estado, a.doctor_id, d.name AS doctor_nombre FROM appointments a JOIN patients p ON a.patient_id = p.id JOIN doctors d ON a.doctor_id = d.id";

  // WHERE clauses
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
    if (!$canSeeAllAppointments && $exactDate < date('Y-m-d')) {
      $where[] = "1=0"; // Force no results for doctors on past dates
    }
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
    $where[] = "(p.name LIKE :search OR p.email LIKE :search OR a.notes LIKE :search OR a.id LIKE :search)";
    $params[':search'] = '%' . $search . '%';
  }

  // Build summary query before status filter
  $summarySql = $dataSql . (empty($where) ? '' : ' WHERE ' . implode(' AND ', $where));
  $summaryStmt = $pdo->prepare($summarySql);
  $summaryStmt->execute($params);
  $summaryRecords = $summaryStmt->fetchAll();
  $statusCounts = clinica_dashboard_status_counts($summaryRecords);

  if ($statusFilter !== 'all') {
    $where[] = "a.status = :status";
    $params[':status'] = $statusFilter;
  }

  $whereClause = empty($where) ? '' : ' WHERE ' . implode(' AND ', $where);

  // Get total count
  $totalStmt = $pdo->prepare($countSql . $whereClause);
  $totalStmt->execute($params);
  $totalRecords = (int) $totalStmt->fetchColumn();

  // Get paginated data
  $totalPages = max(1, (int) ceil($totalRecords / $perPage));
  $page = min($page, $totalPages);
  $dataSql .= $whereClause . " ORDER BY a.date, a.time LIMIT :limit OFFSET :offset";
  $dataStmt = $pdo->prepare($dataSql);
  $dataStmt->execute(array_merge($params, [':limit' => $perPage, ':offset' => $offset]));
  $pageRecords = $dataStmt->fetchAll();

  $appointments = array_map(
    static fn(array $appointment): array => clinica_transform_appointment($appointment, $user),
    $pageRecords,
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
  $appointmentId = trim((string) ($payload['appointmentId'] ?? ''));
  $newStatus = trim((string) ($payload['newStatus'] ?? ''));

  if ($appointmentId === '' || !in_array($newStatus, clinica_statuses(), true)) {
    clinica_json(['success' => false, 'error' => 'Datos de actualizacion invalidos.'], 422);
  }

  $pdo = clinica_get_db();
  $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ?");
  $stmt->execute([$appointmentId]);
  $appointment = $stmt->fetch();

  if (!$appointment) {
    clinica_json(['success' => false, 'error' => 'Cita no encontrada.'], 404);
  }

  if (!$canSeeAllAppointments && $appointment['doctor_id'] !== $user['id']) {
    clinica_json(['success' => false, 'error' => 'No puede actualizar esta cita.'], 403);
  }

  if (!$canSeeAllAppointments && ($appointment['date'] ?? '') < date('Y-m-d')) {
    clinica_json(['success' => false, 'error' => 'No puede actualizar citas anteriores.'], 403);
  }

  $updateStmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
  $updateStmt->execute([$newStatus, $appointmentId]);

  clinica_json(['success' => true, 'message' => 'Estado de la cita actualizado.']);
}

clinica_json(['success' => false, 'error' => 'Metodo no permitido.'], 405);

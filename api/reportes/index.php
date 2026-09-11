<?php
require_once __DIR__ . '/../data.php';

clinica_ensure_session_started();
header('Content-Type: application/json; charset=utf-8');

$user = clinica_current_user();
if (!is_array($user) || !in_array($user['role'] ?? '', clinica_dashboard_roles(), true)) {
  clinica_json(['success' => false, 'error' => 'Acceso no autorizado.'], 401);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
  clinica_json(['success' => false, 'error' => 'Metodo no permitido.'], 405);
}

$canSeeAllAppointments = clinica_can_see_all_appointments((string) ($user['role'] ?? ''));
$report = trim((string) ($_GET['report'] ?? ''));
$format = trim((string) ($_GET['format'] ?? 'json'));

$from = clinica_is_valid_dashboard_date($_GET['from'] ?? null) ? (string) $_GET['from'] : date('Y-m-01');
$to = clinica_is_valid_dashboard_date($_GET['to'] ?? null) ? (string) $_GET['to'] : date('Y-m-d');
if ($from > $to) {
  [$from, $to] = [$to, $from];
}

// Un DOCTOR solo puede ver sus propios reportes; admin/secretaria pueden
// filtrar por un doctor especifico o dejarlo vacio para ver todos.
$doctorId = isset($_GET['doctor_id']) ? trim((string) $_GET['doctor_id']) : null;
$doctorId = $doctorId !== '' ? $doctorId : null;
$doctorId = $canSeeAllAppointments ? $doctorId : (string) $user['id'];

$pdo = clinica_get_db();
$doctorsForSelect = $canSeeAllAppointments ? clinica_get_all_doctors_for_select() : [];

function clinica_reportes_stream_csv(string $filename, array $headers, array $rows): void
{
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="' . $filename . '"');
  $out = fopen('php://output', 'w');
  fwrite($out, "\xEF\xBB\xBF"); // BOM para que Excel detecte UTF-8 correctamente
  fputcsv($out, $headers);
  foreach ($rows as $row) {
    fputcsv($out, $row);
  }
  fclose($out);
  exit();
}

if ($report === 'by_doctor') {
  $where = ['a.date BETWEEN :from AND :to'];
  $params = [':from' => $from, ':to' => $to];
  if ($doctorId) {
    $where[] = 'a.doctor_id = :doctor_id';
    $params[':doctor_id'] = $doctorId;
  }

  $sql = "SELECT d.id AS doctor_id, d.name AS doctor_nombre,
                 SUM(a.status = 'pendiente') AS pendientes,
                 SUM(a.status = 'atendida') AS atendidas,
                 SUM(a.status = 'no-presentado') AS no_presentado,
                 SUM(a.status = 'rechazada') AS rechazadas,
                 SUM(a.status = 'cancelada') AS canceladas,
                 COUNT(*) AS total
          FROM appointments a
          JOIN doctors d ON a.doctor_id = d.id
          WHERE " . implode(' AND ', $where) . '
          GROUP BY d.id, d.name
          ORDER BY d.name';
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll();

  if ($format === 'csv') {
    clinica_reportes_stream_csv(
      "reporte_por_doctor_{$from}_a_{$to}.csv",
      ['Doctor', 'Pendientes', 'Atendidas', 'No presentado', 'Rechazadas', 'Canceladas', 'Total'],
      array_map(
        static fn(array $r): array => [$r['doctor_nombre'], $r['pendientes'], $r['atendidas'], $r['no_presentado'], $r['rechazadas'], $r['canceladas'], $r['total']],
        $rows
      )
    );
  }

  clinica_json(['success' => true, 'from' => $from, 'to' => $to, 'doctors' => $doctorsForSelect, 'rows' => $rows]);
}

if ($report === 'by_status') {
  $where = ['a.date BETWEEN :from AND :to'];
  $params = [':from' => $from, ':to' => $to];
  if ($doctorId) {
    $where[] = 'a.doctor_id = :doctor_id';
    $params[':doctor_id'] = $doctorId;
  }

  $sql = 'SELECT a.status, COUNT(*) AS c FROM appointments a WHERE ' . implode(' AND ', $where) . ' GROUP BY a.status';
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);

  $counts = array_fill_keys(clinica_statuses(), 0);
  foreach ($stmt->fetchAll() as $row) {
    $counts[$row['status']] = (int) $row['c'];
  }

  clinica_json(['success' => true, 'from' => $from, 'to' => $to, 'doctors' => $doctorsForSelect, 'counts' => $counts]);
}

if ($report === 'by_status_detail') {
  $status = trim((string) ($_GET['status'] ?? ''));
  $terminalStatuses = ['atendida', 'no-presentado', 'rechazada', 'cancelada'];
  if (!in_array($status, $terminalStatuses, true)) {
    clinica_json(['success' => false, 'error' => 'Estado invalido para el reporte.'], 422);
  }

  $where = ['a.status = :status', 'a.date BETWEEN :from AND :to'];
  $params = [':status' => $status, ':from' => $from, ':to' => $to];
  if ($doctorId) {
    $where[] = 'a.doctor_id = :doctor_id';
    $params[':doctor_id'] = $doctorId;
  }
  $whereSql = implode(' AND ', $where);

  // Cada cita solo puede tener, a lo sumo, una transicion hacia un estado
  // final (una vez cerrada no se puede volver a cambiar), por lo que este
  // LEFT JOIN nunca duplica filas.
  $baseSql = "SELECT a.id, a.date AS fecha, a.time AS hora, d.name AS doctor_nombre, p.name AS paciente,
                     h.changed_at AS fecha_cambio
              FROM appointments a
              JOIN doctors d ON a.doctor_id = d.id
              JOIN patients p ON a.patient_id = p.id
              LEFT JOIN appointment_status_history h ON h.appointment_id = a.id AND h.new_status = a.status
              WHERE $whereSql
              ORDER BY a.date DESC, a.time DESC";

  if ($format === 'csv') {
    $stmt = $pdo->prepare($baseSql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    clinica_reportes_stream_csv(
      "reporte_{$status}_{$from}_a_{$to}.csv",
      ['Paciente', 'Doctor', 'Fecha cita', 'Hora cita', 'Fecha y hora del cambio de estado'],
      array_map(
        static fn(array $r): array => [$r['paciente'], $r['doctor_nombre'], $r['fecha'], substr((string) $r['hora'], 0, 5), $r['fecha_cambio'] ?? 'No disponible'],
        $rows
      )
    );
  }

  $page = max(1, (int) ($_GET['page'] ?? 1));
  $perPage = max(10, min(200, (int) ($_GET['per_page'] ?? 25)));
  $offset = ($page - 1) * $perPage;

  $countStmt = $pdo->prepare("SELECT COUNT(*) FROM appointments a WHERE $whereSql");
  $countStmt->execute($params);
  $total = (int) $countStmt->fetchColumn();
  $totalPages = max(1, (int) ceil($total / $perPage));
  $page = min($page, $totalPages);
  $offset = ($page - 1) * $perPage;

  $stmt = $pdo->prepare($baseSql . ' LIMIT :limit OFFSET :offset');
  foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
  }
  $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
  $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
  $stmt->execute();
  $rows = $stmt->fetchAll();

  clinica_json([
    'success' => true,
    'from' => $from,
    'to' => $to,
    'status' => $status,
    'doctors' => $doctorsForSelect,
    'rows' => $rows,
    'pagination' => [
      'page' => $page,
      'per_page' => $perPage,
      'total' => $total,
      'total_pages' => $totalPages,
    ],
  ]);
}

clinica_json(['success' => false, 'error' => 'Reporte no especificado o invalido.'], 400);

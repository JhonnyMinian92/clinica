<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../data.php';

$user = $_SESSION['user'] ?? null;

if (!is_array($user) || !in_array($user['role'] ?? '', clinica_dashboard_roles(), true)) {
  clinica_json(['success' => false, 'error' => 'Acceso no autorizado.'], 401);
}

clinica_ensure_appointments();

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

  $dateRecords = clinica_filter_dashboard_appointment_records($_SESSION['appointments'], $user, $doctorId, $dateScope, $exactDate);
  $searchRecords = array_values(array_filter(
    $dateRecords,
    static fn(array $appointment): bool => clinica_matches_dashboard_search($appointment, $search),
  ));
  $statusCounts = clinica_dashboard_status_counts($searchRecords);
  $statusRecords = array_values(array_filter(
    $searchRecords,
    static fn(array $appointment): bool => clinica_matches_dashboard_status($appointment, $statusFilter),
  ));
  $totalRecords = count($statusRecords);
  $totalPages = max(1, (int) ceil($totalRecords / $perPage));
  $page = min($page, $totalPages);
  $offset = ($page - 1) * $perPage;
  $pageRecords = array_slice($statusRecords, $offset, $perPage);
  $appointments = array_map(
    static fn(array $appointment): array => clinica_transform_appointment($appointment, $user),
    $pageRecords,
  );
  $nextPending = null;
  foreach ($searchRecords as $appointment) {
    if (($appointment['estado'] ?? '') === 'pendiente') {
      $nextPending = clinica_transform_appointment($appointment, $user);
      break;
    }
  }
  $doctors = $canSeeAllAppointments ? clinica_doctors_from_appointments($_SESSION['appointments']) : [];

  clinica_json([
    'success' => true,
    'user' => clinica_public_user($user),
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

  foreach ($_SESSION['appointments'] as $index => $appointment) {
    if ($appointment['id'] !== $appointmentId) {
      continue;
    }

    if (!$canSeeAllAppointments && $appointment['doctor_id'] !== $user['id']) {
      clinica_json(['success' => false, 'error' => 'No puede actualizar esta cita.'], 403);
    }

    if (!$canSeeAllAppointments && ($appointment['fecha'] ?? '') < date('Y-m-d')) {
      clinica_json(['success' => false, 'error' => 'No puede actualizar citas anteriores.'], 403);
    }

    $_SESSION['appointments'][$index]['estado'] = $newStatus;
    $updatedAppointment = clinica_transform_appointment($_SESSION['appointments'][$index], $user);

    clinica_json([
      'success' => true,
      'appointment' => $updatedAppointment,
      'appointments' => clinica_filter_dashboard_appointments($_SESSION['appointments'], $user),
    ]);
  }

  clinica_json(['success' => false, 'error' => 'Cita no encontrada.'], 404);
}

clinica_json(['success' => false, 'error' => 'Metodo no permitido.'], 405);

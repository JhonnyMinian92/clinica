<?php
require_once __DIR__ . '/../adaptive.php';

clinica_ensure_session_started();
header('Content-Type: application/json; charset=utf-8');
$user = clinica_current_user();
if (!is_array($user) || !in_array((string) ($user['role'] ?? ''), ['ADMINISTRATOR', 'SECRETARIA'], true)) {
  clinica_json(['success' => false, 'error' => 'Acceso restringido.'], 403);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'GET' && !clinica_validate_csrf_from_request()) {
  clinica_json(['success' => false, 'error' => 'CSRF token invalido.'], 403);
}
$pdo = clinica_get_db();
$doctorId = trim((string) ($_GET['doctor_id'] ?? ''));
if ($doctorId === '') {
  clinica_json(['success' => false, 'error' => 'Doctor requerido.'], 422);
}

try {
  $stmt = $pdo->prepare('SELECT id FROM doctors WHERE id = ?');
  $stmt->execute([$doctorId]);
  if (!$stmt->fetch()) clinica_json(['success' => false, 'error' => 'Doctor no encontrado.'], 404);

  if ($method === 'GET') {
    clinica_json(['success' => true, 'doctorId' => $doctorId, 'specialtyIds' => clinica_doctor_specialties($pdo, $doctorId, false)]);
  }

  $payload = clinica_request_json();
  $ids = $payload['specialtyIds'] ?? [];
  if (!is_array($ids)) $ids = preg_split('/\s*,\s*/', (string) $ids, -1, PREG_SPLIT_NO_EMPTY);
  $ids = array_values(array_unique(array_filter(array_map(static fn($id) => trim((string) $id), $ids))));
  if (!$ids) clinica_json(['success' => false, 'error' => 'Seleccione al menos una especialidad.'], 422);

  $pdo->beginTransaction();
  $check = $pdo->prepare('SELECT id FROM specialties WHERE id = ? AND active = 1');
  $insert = $pdo->prepare('INSERT INTO doctor_specialties (doctor_id, specialty_id, active) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE active = 1, updated_at = CURRENT_TIMESTAMP');
  // Deactivate every relation first, then reactivate the requested set. This is safe and keeps history.
  $pdo->prepare('UPDATE doctor_specialties SET active = 0, updated_at = CURRENT_TIMESTAMP WHERE doctor_id = ?')->execute([$doctorId]);
  foreach ($ids as $specialtyId) {
    $check->execute([$specialtyId]);
    if (!$check->fetch()) {
      $pdo->rollBack();
      clinica_json(['success' => false, 'error' => 'Una de las especialidades no existe o esta inactiva.'], 422);
    }
    $insert->execute([$doctorId, $specialtyId]);
  }
  // Keep the legacy primary specialty synchronized with the first selected specialty.
  $primary = $ids[0];
  $pdo->prepare('UPDATE doctors SET specialty_id = ? WHERE id = ?')->execute([$primary, $doctorId]);
  $pdo->commit();

  clinica_log_audit_event(clinica_get_user_id_from_session(), 'doctor_specialties_updated', 'Especialidades del doctor actualizadas', ['doctor_id' => $doctorId, 'specialty_ids' => $ids], 'doctor', $doctorId);
  clinica_json(['success' => true, 'doctorId' => $doctorId, 'specialtyIds' => $ids]);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  error_log('Doctor specialties update error: ' . $e->getMessage());
  clinica_json(['success' => false, 'error' => 'No se pudieron actualizar las especialidades del doctor.'], 500);
}

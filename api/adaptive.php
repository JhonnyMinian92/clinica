<?php
/**
 * Adaptive clinic domain helpers.
 * Keeps the legacy doctors.specialty_id column usable while the
 * doctor_specialties relation is introduced incrementally.
 */
require_once __DIR__ . '/data.php';

function clinica_doctor_specialties(PDO $pdo, string $doctorId, bool $activeOnly = true): array
{
  $ids = [];
  try {
    $sql = 'SELECT specialty_id FROM doctor_specialties WHERE doctor_id = ?';
    if ($activeOnly) $sql .= ' AND active = 1';
    $sql .= ' ORDER BY specialty_id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$doctorId]);
    $ids = array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
  } catch (Throwable $e) {
    error_log('Adaptive specialty relation fallback: ' . $e->getMessage());
  }

  // The legacy column remains a compatibility bridge while existing CRUD
  // forms still write doctors.specialty_id. Include it only when its specialty
  // is active so disabled specialties can never leak into the public catalog.
  try {
    $sql = 'SELECT d.specialty_id FROM doctors d';
    if ($activeOnly) {
      $sql .= ' INNER JOIN specialties s ON s.id = d.specialty_id AND s.active = 1';
    }
    $sql .= ' WHERE d.id = ? AND d.specialty_id IS NOT NULL AND d.specialty_id <> ? LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$doctorId, '']);
    $legacy = $stmt->fetchColumn();
    if ($legacy !== false && $legacy !== null && $legacy !== '') {
      $ids[] = (string) $legacy;
    }
  } catch (Throwable $e) {
    // The relation result remains usable if the legacy column is unavailable.
  }

  return array_values(array_unique($ids));
}

function clinica_doctor_has_specialty(PDO $pdo, string $doctorId, string $specialtyId, bool $activeOnly = true): bool
{
  if ($doctorId === '' || $specialtyId === '') return false;
  try {
    $sql = 'SELECT 1 FROM doctor_specialties ds';
    if ($activeOnly) {
      $sql .= ' INNER JOIN specialties s ON s.id = ds.specialty_id AND s.active = 1';
    }
    $sql .= ' WHERE ds.doctor_id = ? AND ds.specialty_id = ?';
    if ($activeOnly) $sql .= ' AND ds.active = 1';
    $sql .= ' LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$doctorId, $specialtyId]);
    if ($stmt->fetchColumn()) return true;
  } catch (Throwable $e) {
    // Pre-migration fallback below.
  }

  // Compatibility bridge for legacy doctor CRUD writes. The fallback also
  // verifies specialty activity, matching the relation path above.
  $sql = 'SELECT 1 FROM doctors d';
  if ($activeOnly) {
    $sql .= ' INNER JOIN specialties s ON s.id = d.specialty_id AND s.active = 1';
  }
  $sql .= ' WHERE d.id = ? AND d.specialty_id = ? AND d.active = 1 LIMIT 1';
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$doctorId, $specialtyId]);
  return (bool) $stmt->fetchColumn();
}

function clinica_doctor_catalog(PDO $pdo, bool $activeOnly = true): array
{
  $doctors = clinica_get_doctors($activeOnly);
  $catalog = [];
  foreach ($doctors as $doctor) {
    $id = (string) ($doctor['id'] ?? '');
    if ($id === '') continue;
    $specialtyIds = clinica_doctor_specialties($pdo, $id, $activeOnly);
    if (!$specialtyIds) continue;
    $public = clinica_public_doctor($doctor);
    $public['especialidades'] = array_map(static function (string $specialtyId): array {
      return [
        'id' => $specialtyId,
        'nombre' => clinica_specialty_name_by_id($specialtyId),
      ];
    }, $specialtyIds);
    $catalog[] = $public;
  }
  return $catalog;
}

function clinica_adaptive_context(PDO $pdo): array
{
  $specialties = clinica_get_specialties(true);
  $doctors = clinica_doctor_catalog($pdo, true);
  $activeSpecialtyIds = [];
  foreach ($doctors as $doctor) {
    foreach (($doctor['especialidades'] ?? []) as $specialty) {
      $activeSpecialtyIds[(string) $specialty['id']] = true;
    }
  }
  $specialties = array_values(array_filter($specialties, static fn(array $s): bool => isset($activeSpecialtyIds[(string) ($s['id'] ?? '')])));

  $doctorCount = count($doctors);
  $specialtyCount = count($specialties);
  $singleDoctor = $doctorCount === 1 ? $doctors[0] : null;
  $singleDoctorSpecialtyCount = $singleDoctor ? count($singleDoctor['especialidades'] ?? []) : 0;

  if ($doctorCount === 1 && $singleDoctorSpecialtyCount === 1) {
    $mode = 'single_doctor_single_specialty';
    $steps = ['date_time', 'patient', 'confirm'];
  } elseif ($doctorCount === 1) {
    $mode = 'single_doctor_multi_specialty';
    $steps = ['specialty', 'date_time', 'patient', 'confirm'];
  } elseif ($specialtyCount === 1) {
    $mode = 'multi_doctor_single_specialty';
    $steps = ['doctor', 'date_time', 'patient', 'confirm'];
  } else {
    $mode = 'multi_doctor_multi_specialty';
    $steps = ['specialty', 'doctor', 'date_time', 'patient', 'confirm'];
  }

  return [
    'mode' => $mode,
    'doctor_count' => $doctorCount,
    'specialty_count' => $specialtyCount,
    'steps' => $steps,
    'doctors' => $doctors,
    'specialties' => array_map(static fn(array $s): array => [
      'id' => (string) ($s['id'] ?? ''),
      'titulo' => (string) ($s['name'] ?? ''),
      'descripcion' => (string) ($s['description'] ?? $s['summary'] ?? ''),
      'resumen' => (string) ($s['summary'] ?? ''),
      'icono' => (string) ($s['icon'] ?? ''),
    ], $specialties),
  ];
}

function clinica_user_can_manage_doctor(array $user, string $doctorId): bool
{
  $role = (string) ($user['role'] ?? '');
  if ($role === 'ADMINISTRATOR' || $role === 'SECRETARIA') return true;
  if ($role !== 'DOCTOR' || $doctorId === '') return false;
  $linkedDoctorId = (string) ($user['doctor_id'] ?? '');
  if ($linkedDoctorId !== '') return $linkedDoctorId === $doctorId;
  return (string) ($user['id'] ?? '') === $doctorId;
}

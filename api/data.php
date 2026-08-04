<?php

const CLINICA_APPOINTMENTS_SEED_VERSION = 2;

function clinica_demo_users(): array
{
  return [
    'admin' => [
      'id' => 'admin1',
      'name' => 'Valeria Andrade',
      'role' => 'ADMINISTRATOR',
      'label' => 'Administracion',
      'password' => 'password123',
    ],
    'secretaria' => [
      'id' => 'sec1',
      'name' => 'Mariana Rios',
      'role' => 'SECRETARIA',
      'label' => 'Secretaria',
      'password' => 'secretaria123',
    ],
    'dr.herrera' => [
      'id' => 'doc3',
      'name' => 'Dr. Mateo Herrera',
      'role' => 'DOCTOR',
      'label' => 'Doctor',
      'password' => 'doctor123',
    ],
  ];
}

function clinica_dashboard_roles(): array
{
  return ['DOCTOR', 'ADMINISTRATOR', 'SECRETARIA'];
}

function clinica_can_see_all_appointments(string $role): bool
{
  return in_array($role, ['ADMINISTRATOR', 'SECRETARIA'], true);
}

function clinica_statuses(): array
{
  return ['pendiente', 'atendida', 'no-presentado', 'rechazada'];
}

function clinica_seed_appointments(): array
{
  $today = date('Y-m-d');
  $yesterday = date('Y-m-d', strtotime('-1 day'));
  $tomorrow = date('Y-m-d', strtotime('+1 day'));
  $nextWeek = date('Y-m-d', strtotime('+5 days'));

  return [
    ['id' => 'CITA001', 'fecha' => $today, 'hora' => '09:00', 'nombre_paciente' => 'Ana Torres', 'email_paciente' => 'ana.torres@example.com', 'telefono_paciente' => '0991112222', 'motivo_consulta' => 'Chequeo anual', 'estado' => 'pendiente', 'doctor_id' => 'doc1', 'doctor_nombre' => 'Dr. Alejandro Vargas', 'especialidad' => 'Cardiologia'],
    ['id' => 'CITA002', 'fecha' => $today, 'hora' => '09:15', 'nombre_paciente' => 'Luis Mendez', 'email_paciente' => 'luis.mendez@example.com', 'telefono_paciente' => '0992223333', 'motivo_consulta' => 'Dolor de cabeza persistente', 'estado' => 'pendiente', 'doctor_id' => 'doc3', 'doctor_nombre' => 'Dr. Mateo Herrera', 'especialidad' => 'Neurologia'],
    ['id' => 'CITA003', 'fecha' => $today, 'hora' => '09:30', 'nombre_paciente' => 'Carla Solano', 'email_paciente' => 'carla.solano@example.com', 'telefono_paciente' => '0993334444', 'motivo_consulta' => 'Resultados de analisis', 'estado' => 'atendida', 'doctor_id' => 'doc3', 'doctor_nombre' => 'Dr. Mateo Herrera', 'especialidad' => 'Neurologia'],
    ['id' => 'CITA004', 'fecha' => $today, 'hora' => '10:00', 'nombre_paciente' => 'Pedro Jimenez', 'email_paciente' => 'pedro.jimenez@example.com', 'telefono_paciente' => '0994445555', 'motivo_consulta' => 'Control post-operatorio', 'estado' => 'pendiente', 'doctor_id' => 'doc5', 'doctor_nombre' => 'Dr. Lucas Jimenez', 'especialidad' => 'Traumatologia'],
    ['id' => 'CITA005', 'fecha' => $today, 'hora' => '10:30', 'nombre_paciente' => 'Sofia Castro', 'email_paciente' => 'sofia.castro@example.com', 'telefono_paciente' => '0995556666', 'motivo_consulta' => 'Consulta de seguimiento', 'estado' => 'pendiente', 'doctor_id' => 'doc3', 'doctor_nombre' => 'Dr. Mateo Herrera', 'especialidad' => 'Neurologia'],
    ['id' => 'CITA006', 'fecha' => $today, 'hora' => '11:00', 'nombre_paciente' => 'Elena Rojas', 'email_paciente' => 'elena.rojas@example.com', 'telefono_paciente' => '0996667777', 'motivo_consulta' => 'No especificado', 'estado' => 'no-presentado', 'doctor_id' => 'doc1', 'doctor_nombre' => 'Dr. Alejandro Vargas', 'especialidad' => 'Cardiologia'],
    ['id' => 'CITA007', 'fecha' => $yesterday, 'hora' => '14:00', 'nombre_paciente' => 'Marco Vera', 'email_paciente' => 'marco.vera@example.com', 'telefono_paciente' => '0997778888', 'motivo_consulta' => 'Revision dermatologica', 'estado' => 'rechazada', 'doctor_id' => 'doc4', 'doctor_nombre' => 'Dra. Valentina Morales', 'especialidad' => 'Dermatologia'],
    ['id' => 'CITA008', 'fecha' => $tomorrow, 'hora' => '14:30', 'nombre_paciente' => 'Julia Navarro', 'email_paciente' => 'julia.navarro@example.com', 'telefono_paciente' => '0998889999', 'motivo_consulta' => 'Vacunacion infantil', 'estado' => 'pendiente', 'doctor_id' => 'doc2', 'doctor_nombre' => 'Dra. Sofia Castillo', 'especialidad' => 'Pediatria'],
    ['id' => 'CITA009', 'fecha' => $nextWeek, 'hora' => '15:00', 'nombre_paciente' => 'Ricardo Ponce', 'email_paciente' => 'ricardo.ponce@example.com', 'telefono_paciente' => '0999990000', 'motivo_consulta' => 'Terapia', 'estado' => 'pendiente', 'doctor_id' => 'doc8', 'doctor_nombre' => 'Dra. Isabella Gomez', 'especialidad' => 'Psiquiatria'],
    ['id' => 'CITA010', 'fecha' => $tomorrow, 'hora' => '11:45', 'nombre_paciente' => 'Marta Aguilar', 'email_paciente' => 'marta.aguilar@example.com', 'telefono_paciente' => '0981112233', 'motivo_consulta' => 'Control neurologico', 'estado' => 'pendiente', 'doctor_id' => 'doc3', 'doctor_nombre' => 'Dr. Mateo Herrera', 'especialidad' => 'Neurologia'],
    ['id' => 'CITA011', 'fecha' => $yesterday, 'hora' => '08:30', 'nombre_paciente' => 'Diego Molina', 'email_paciente' => 'diego.molina@example.com', 'telefono_paciente' => '0983334455', 'motivo_consulta' => 'Seguimiento de migrana', 'estado' => 'atendida', 'doctor_id' => 'doc3', 'doctor_nombre' => 'Dr. Mateo Herrera', 'especialidad' => 'Neurologia'],
  ];
}

function clinica_ensure_appointments(): void
{
  if (!isset($_SESSION['appointments']) || !is_array($_SESSION['appointments'])) {
    $_SESSION['appointments'] = clinica_seed_appointments();
    $_SESSION['appointments_seed_version'] = CLINICA_APPOINTMENTS_SEED_VERSION;
    return;
  }

  if (($_SESSION['appointments_seed_version'] ?? 0) < CLINICA_APPOINTMENTS_SEED_VERSION) {
    clinica_upgrade_seed_appointments();
    $_SESSION['appointments_seed_version'] = CLINICA_APPOINTMENTS_SEED_VERSION;
  }
}

function clinica_upgrade_seed_appointments(): void
{
  $seedAppointments = clinica_seed_appointments();
  $sessionById = [];

  foreach ($_SESSION['appointments'] as $index => $appointment) {
    if (isset($appointment['id'])) {
      $sessionById[$appointment['id']] = $index;
    }
  }

  foreach ($seedAppointments as $seedAppointment) {
    $seedId = $seedAppointment['id'];

    if (!array_key_exists($seedId, $sessionById)) {
      $_SESSION['appointments'][] = $seedAppointment;
      continue;
    }

    $index = $sessionById[$seedId];
    $currentStatus = $_SESSION['appointments'][$index]['estado'] ?? $seedAppointment['estado'];
    $_SESSION['appointments'][$index] = array_merge($seedAppointment, [
      'estado' => $currentStatus,
    ]);
  }
}

function clinica_public_user(array $user): array
{
  return [
    'id' => $user['id'],
    'name' => $user['name'],
    'role' => $user['role'],
    'label' => $user['label'] ?? $user['role'],
  ];
}

function clinica_json(array $payload, int $statusCode = 200): never
{
  http_response_code($statusCode);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit();
}

function clinica_request_json(): array
{
  $rawInput = file_get_contents('php://input');
  if ($rawInput === false || trim($rawInput) === '') {
    return [];
  }

  $rawInput = preg_replace('/^\xEF\xBB\xBF/', '', $rawInput);
  $payload = json_decode($rawInput, true);
  return is_array($payload) ? $payload : [];
}

function clinica_transform_appointment(array $appointment, array $user): array
{
  $transformed = $appointment;

  if (clinica_can_see_all_appointments($user['role'])) {
    $transformed['doctor_info'] = [
      'id' => $appointment['doctor_id'],
      'nombre' => $appointment['doctor_nombre'],
    ];
  }

  unset($transformed['doctor_id'], $transformed['doctor_nombre']);
  return $transformed;
}

function clinica_filter_appointments_for_user(array $appointments, array $user, ?string $doctorId = null): array
{
  return clinica_filter_dashboard_appointments($appointments, $user, $doctorId);
}

function clinica_is_valid_dashboard_date(?string $date): bool
{
  if ($date === null || $date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    return false;
  }

  [$year, $month, $day] = array_map('intval', explode('-', $date));
  return checkdate($month, $day, $year);
}

function clinica_normalize_date_scope(string $dateScope, string $role): string
{
  $allowedScopes = ['today', 'tomorrow', 'future', 'past', 'all'];
  $dateScope = in_array($dateScope, $allowedScopes, true) ? $dateScope : 'today';

  if (!clinica_can_see_all_appointments($role) && in_array($dateScope, ['past', 'all'], true)) {
    return 'today';
  }

  return $dateScope;
}

function clinica_matches_date_filter(array $appointment, array $user, string $dateScope = 'today', ?string $exactDate = null): bool
{
  $appointmentDate = (string) ($appointment['fecha'] ?? '');
  $today = date('Y-m-d');

  if (clinica_is_valid_dashboard_date($exactDate)) {
    if (!clinica_can_see_all_appointments($user['role']) && $exactDate < $today) {
      return false;
    }

    return $appointmentDate === $exactDate;
  }

  $dateScope = clinica_normalize_date_scope($dateScope, $user['role']);

  return match ($dateScope) {
    'tomorrow' => $appointmentDate === date('Y-m-d', strtotime('+1 day')),
    'future' => $appointmentDate > $today,
    'past' => $appointmentDate < $today,
    'all' => true,
    default => $appointmentDate === $today,
  };
}

function clinica_filter_dashboard_appointments(array $appointments, array $user, ?string $doctorId = null, string $dateScope = 'today', ?string $exactDate = null): array
{
  $filtered = [];

  foreach ($appointments as $appointment) {
    if (!clinica_can_see_all_appointments($user['role']) && $appointment['doctor_id'] !== $user['id']) {
      continue;
    }

    if (clinica_can_see_all_appointments($user['role']) && $doctorId && $appointment['doctor_id'] !== $doctorId) {
      continue;
    }

    if (!clinica_matches_date_filter($appointment, $user, $dateScope, $exactDate)) {
      continue;
    }

    $filtered[] = clinica_transform_appointment($appointment, $user);
  }

  usort($filtered, static function (array $a, array $b): int {
    $dateCompare = strcmp($a['fecha'] ?? '', $b['fecha'] ?? '');
    return $dateCompare !== 0 ? $dateCompare : strcmp($a['hora'] ?? '', $b['hora'] ?? '');
  });

  return $filtered;
}

function clinica_doctors_from_appointments(array $appointments): array
{
  $doctors = [];

  foreach ($appointments as $appointment) {
    $doctors[$appointment['doctor_id']] = [
      'id' => $appointment['doctor_id'],
      'nombre' => $appointment['doctor_nombre'],
    ];
  }

  usort($doctors, static fn(array $a, array $b): int => strcmp($a['nombre'], $b['nombre']));
  return array_values($doctors);
}

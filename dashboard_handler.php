<?php
session_start();
header('Content-Type: application/json');

// --- SIMULACIÓN DE SESIÓN (para desarrollo) ---
// Descomentar para probar sin iniciar sesión. En producción, esto debe estar comentado.
/*
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'id' => 'doc3',
        'name' => 'Dr. Mateo Herrera',
        'role' => 'ADMINISTRATOR' // Cambiar a 'DOCTOR' para probar la vista de doctor
    ];
}
*/
// --- FIN DE SIMULACIÓN ---

// 1. VERIFICACIÓN DE SEGURIDAD
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['DOCTOR', 'ADMINISTRATOR'])) {
  http_response_code(401); // Unauthorized
  echo json_encode(['error' => 'Acceso no autorizado.']);
  exit();
}

$user = $_SESSION['user'];
$user_role = $user['role'];
$user_id = $user['id'];

// 2. SIMULACIÓN DE BASE DE DATOS DE CITAS DEL DÍA
// En un caso real, esto vendría de una consulta a la base de datos:
// "SELECT * FROM citas WHERE fecha = CURDATE()"
$todas_las_citas = [
  ['id' => 'CITA001', 'hora' => '09:00', 'nombre_paciente' => 'Ana Torres', 'motivo_consulta' => 'Chequeo anual', 'estado' => 'pendiente', 'doctor_id' => 'doc1', 'doctor_nombre' => 'Dr. Alejandro Vargas'],
  ['id' => 'CITA002', 'hora' => '09:15', 'nombre_paciente' => 'Luis Méndez', 'motivo_consulta' => 'Dolor de cabeza persistente', 'estado' => 'pendiente', 'doctor_id' => 'doc3', 'doctor_nombre' => 'Dr. Mateo Herrera'],
  ['id' => 'CITA003', 'hora' => '09:30', 'nombre_paciente' => 'Carla Solano', 'motivo_consulta' => 'Resultados de análisis', 'estado' => 'atendida', 'doctor_id' => 'doc3', 'doctor_nombre' => 'Dr. Mateo Herrera'],
  ['id' => 'CITA004', 'hora' => '10:00', 'nombre_paciente' => 'Pedro Jiménez', 'motivo_consulta' => 'Control post-operatorio', 'estado' => 'pendiente', 'doctor_id' => 'doc5', 'doctor_nombre' => 'Dr. Lucas Jiménez'],
  ['id' => 'CITA005', 'hora' => '10:30', 'nombre_paciente' => 'Sofía Castro', 'motivo_consulta' => 'Consulta de seguimiento', 'estado' => 'pendiente', 'doctor_id' => 'doc3', 'doctor_nombre' => 'Dr. Mateo Herrera'],
  ['id' => 'CITA006', 'hora' => '11:00', 'nombre_paciente' => 'Elena Rojas', 'motivo_consulta' => 'No especificado', 'estado' => 'no-presentado', 'doctor_id' => 'doc1', 'doctor_nombre' => 'Dr. Alejandro Vargas'],
  ['id' => 'CITA007', 'hora' => '14:00', 'nombre_paciente' => 'Marco Vera', 'motivo_consulta' => 'Revisión dermatológica', 'estado' => 'rechazada', 'doctor_id' => 'doc4', 'doctor_nombre' => 'Dra. Valentina Morales'],
  ['id' => 'CITA008', 'hora' => '14:30', 'nombre_paciente' => 'Julia Navarro', 'motivo_consulta' => 'Vacunación infantil', 'estado' => 'atendida', 'doctor_id' => 'doc2', 'doctor_nombre' => 'Dra. Sofía Castillo'],
  ['id' => 'CITA009', 'hora' => '15:00', 'nombre_paciente' => 'Ricardo Ponce', 'motivo_consulta' => 'Terapia', 'estado' => 'pendiente', 'doctor_id' => 'doc8', 'doctor_nombre' => 'Dra. Isabella Gómez'],
];


// 3. LÓGICA DE NEGOCIO Y FILTRADO
$citas_a_devolver = [];

if ($user_role === 'DOCTOR') {
  // Si es doctor, filtrar solo sus citas
  foreach ($todas_las_citas as $cita) {
    if ($cita['doctor_id'] === $user_id) {
      // No necesitamos devolver la info del doctor si es su propio dashboard
      unset($cita['doctor_id'], $cita['doctor_nombre']);
      $citas_a_devolver[] = $cita;
    }
  }
} elseif ($user_role === 'ADMINISTRATOR') {
  // Si es administrador, devolver todas las citas con la info del doctor anidada
  foreach ($todas_las_citas as $cita) {
    $cita['doctor_info'] = [
      'id' => $cita['doctor_id'],
      'nombre' => $cita['doctor_nombre']
    ];
    unset($cita['doctor_id'], $cita['doctor_nombre']);
    $citas_a_devolver[] = $cita;
  }
}

// 4. RESPUESTA JSON
// Simular un pequeño retardo de red para emular una carga real
usleep(500000); // 0.5 segundos

echo json_encode([
  'user' => $user,
  'appointments' => $citas_a_devolver
]);

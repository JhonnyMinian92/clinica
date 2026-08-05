<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../data.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$payload = clinica_request_json();
$action = $payload['action'] ?? ($_GET['action'] ?? ($method === 'GET' ? 'session' : ''));

if ($method === 'GET' && $action === 'session') {
  $user = clinica_current_user();

  clinica_json([
    'authenticated' => is_array($user),
    'user' => is_array($user) ? clinica_public_user($user) : null,
    'expires_at' => is_array($user) ? clinica_session_expires_at() : null,
  ]);
}

if ($method !== 'POST') {
  clinica_json(['success' => false, 'error' => 'Metodo no permitido.'], 405);
}

if ($action === 'login') {
  $username = strtolower(trim((string) ($payload['username'] ?? '')));
  $password = (string) ($payload['password'] ?? '');
  $users = clinica_demo_users();
  $user = $users[$username] ?? null;

  if (!$user || !hash_equals($user['password'], $password)) {
    clinica_json(['success' => false, 'error' => 'Usuario o contraseña incorrectos.'], 401);
  }

  if (!in_array($user['role'] ?? '', clinica_dashboard_roles(), true)) {
    clinica_json(['success' => false, 'error' => 'El usuario no tiene un rol operativo permitido.'], 403);
  }

  $redirect = clinica_resolve_login_redirect((string) ($payload['redirect'] ?? ''), $user);

  unset($user['password']);
  clinica_start_authenticated_session($user);
  clinica_ensure_appointments();

  clinica_json([
    'success' => true,
    'user' => clinica_public_user($user),
    'redirect' => $redirect,
    'expires_at' => clinica_session_expires_at(),
  ]);
}

if ($action === 'logout') {
  clinica_destroy_session();
  clinica_json(['success' => true, 'redirect' => '/clinica/pages/login/']);
}

if ($action === 'create_appointment') {
  clinica_ensure_appointments();

  $patientName = trim((string) ($payload['patientName'] ?? ''));
  $patientEmail = trim((string) ($payload['patientEmail'] ?? ''));
  $patientPhone = trim((string) ($payload['patientPhone'] ?? ''));
  $doctorId = trim((string) ($payload['doctorId'] ?? ''));
  $doctorName = trim((string) ($payload['doctorName'] ?? ''));
  $specialtyName = trim((string) ($payload['specialtyName'] ?? ''));
  $date = trim((string) ($payload['date'] ?? ''));
  $time = trim((string) ($payload['time'] ?? ''));
  $notes = trim((string) ($payload['patientNotes'] ?? ''));

  $errors = [];
  if ($patientName === '') $errors[] = 'El nombre del paciente es obligatorio.';
  if (!filter_var($patientEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'El correo del paciente no es valido.';
  if (!preg_match('/^\d{7,15}$/', $patientPhone)) $errors[] = 'El telefono del paciente no es valido.';
  if ($doctorId === '' || $doctorName === '') $errors[] = 'Debe seleccionar un doctor.';
  if ($specialtyName === '') $errors[] = 'Debe seleccionar una especialidad.';
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $errors[] = 'La fecha no es valida.';
  if (!preg_match('/^\d{2}:\d{2}$/', $time)) $errors[] = 'La hora no es valida.';

  if ($errors) {
    clinica_json(['success' => false, 'error' => implode(' ', $errors)], 422);
  }

  $appointment = [
    'id' => 'CITA' . date('YmdHis') . random_int(100, 999),
    'fecha' => $date,
    'hora' => $time,
    'nombre_paciente' => $patientName,
    'email_paciente' => $patientEmail,
    'telefono_paciente' => $patientPhone,
    'motivo_consulta' => $notes !== '' ? $notes : 'Reserva generada desde el sitio web',
    'estado' => 'pendiente',
    'doctor_id' => $doctorId,
    'doctor_nombre' => $doctorName,
    'especialidad' => $specialtyName,
  ];

  $_SESSION['appointments'][] = $appointment;

  clinica_json([
    'success' => true,
    'appointment' => $appointment,
  ], 201);
}

clinica_json(['success' => false, 'error' => 'Accion no reconocida.'], 400);

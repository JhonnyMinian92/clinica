<?php
require_once __DIR__ . '/../data.php';

// Ensure session and CSRF cookie are set up correctly before handling requests
clinica_ensure_session_started();

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$payload = [];

if ($method === 'POST') {
  $payload = clinica_request_json();
}

// Protect mutating endpoints (except login) with CSRF validation
if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true) && strtolower(trim((string) ($payload['action'] ?? $_GET['action'] ?? ''))) !== 'login') {
  if (!clinica_validate_csrf_from_request()) {
    clinica_json(['success' => false, 'error' => 'CSRF token invalido.'], 403);
  }
}

$action = strtolower(trim((string) ($payload['action'] ?? $_GET['action'] ?? '')));

if ($action === 'session') {
  if ($method !== 'GET') {
    clinica_json(['success' => false, 'error' => 'Metodo no permitido para esta accion.'], 405);
  }
  $user = clinica_current_user();
  if ($user) {
    clinica_json(['success' => true, 'authenticated' => true, 'user' => clinica_public_user($user), 'expires_at' => clinica_session_expires_at()]);
  }
  clinica_json(['success' => true, 'authenticated' => false]);
}

if ($action === 'login') {
  if ($method !== 'POST') {
    clinica_json(['success' => false, 'error' => 'Metodo no permitido para esta accion.'], 405);
  }
  $username = strtolower(trim((string) ($payload['username'] ?? '')));
  $password = (string) ($payload['password'] ?? '');
  $redirect = (string) ($payload['redirect'] ?? '');

  // Authenticate against the users table directly to avoid exposing password hashes in memory sent to the client.
  try {
    $pdo = clinica_get_db();
    $stmt = $pdo->prepare("SELECT id, username, password, name, role, doctor_id FROM users WHERE username = ? AND active = 1");
    $stmt->execute([$username]);
    $dbUser = $stmt->fetch(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
    clinica_json(['success' => false, 'error' => 'Error de base de datos al autenticar.'], 500);
  }

  if (!$dbUser || !password_verify($password, (string) ($dbUser['password'] ?? ''))) {
    clinica_json(['success' => false, 'error' => 'Usuario o contrasena incorrectos.'], 401);
  }

  // Map DB user to session user but DO NOT include password or hash.
  $sessionUserId = ($dbUser['role'] === 'DOCTOR' && !empty($dbUser['doctor_id'])) ? (string) $dbUser['doctor_id'] : (string) $dbUser['id'];
  $user = [
    'id' => $sessionUserId,
    'username' => strtolower(trim((string) ($dbUser['username'] ?? ''))),
    'name' => (string) ($dbUser['name'] ?? ''),
    'role' => (string) ($dbUser['role'] ?? ''),
    'label' => (string) ($dbUser['role'] ?? ''),
  ];

  clinica_start_authenticated_session($user);
  $target = clinica_resolve_login_redirect($redirect, $user);
  clinica_json(['success' => true, 'redirect' => $target]);
}

if ($action === 'logout') {
  if ($method !== 'POST') {
    clinica_json(['success' => false, 'error' => 'Metodo no permitido para esta accion.'], 405);
  }
  clinica_destroy_session();
  clinica_json(['success' => true, 'redirect' => '/clinica/pages/login/']);
}

if ($action === 'create_appointment') {
  if ($method !== 'POST') {
    clinica_json(['success' => false, 'error' => 'Metodo no permitido para esta accion.'], 405);
  }

  $specialtyId = trim((string) ($payload['specialtyId'] ?? ''));
  $doctorId = trim((string) ($payload['doctorId'] ?? ''));
  $date = trim((string) ($payload['date'] ?? ''));
  $time = trim((string) ($payload['time'] ?? ''));
  $patientName = trim((string) ($payload['patientName'] ?? ''));
  $patientEmail = trim((string) ($payload['patientEmail'] ?? ''));
  $patientPhone = trim((string) ($payload['patientPhone'] ?? ''));
  $patientNotes = trim((string) ($payload['patientNotes'] ?? ''));

  if (empty($specialtyId) || empty($doctorId) || empty($date) || empty($time) || empty($patientName) || empty($patientEmail)) {
    clinica_json(['success' => false, 'error' => 'Faltan datos requeridos para crear la cita.'], 422);
  }

  try {
    $pdo = clinica_get_db();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT id FROM patients WHERE email = ?");
    $stmt->execute([$patientEmail]);
    $patientId = $stmt->fetchColumn();

    if (!$patientId) {
      $stmt = $pdo->prepare("INSERT INTO patients (name, email, phone) VALUES (?, ?, ?)");
      $stmt->execute([$patientName, $patientEmail, $patientPhone]);
      $patientId = $pdo->lastInsertId();
    } else {
      $stmt = $pdo->prepare("UPDATE patients SET name = ?, phone = ? WHERE id = ?");
      $stmt->execute([$patientName, $patientPhone, $patientId]);
    }

    $stmt = $pdo->prepare("SELECT id FROM appointments WHERE doctor_id = ? AND date = ? AND time = ? AND status != 'rechazada'");
    $stmt->execute([$doctorId, $date, $time . ':00']);
    if ($stmt->fetch()) {
      $pdo->rollBack();
      clinica_json(['success' => false, 'error' => 'El horario seleccionado ya no está disponible. Por favor, elija otro.'], 409);
    }

    $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, specialty_id, date, time, notes, status) VALUES (?, ?, ?, ?, ?, ?, 'pendiente')");
    $stmt->execute([$patientId, $doctorId, $specialtyId, $date, $time . ':00', $patientNotes]);

    $pdo->commit();
    clinica_json(['success' => true, 'message' => 'Cita creada exitosamente.']);
  } catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
      $pdo->rollBack();
    }
    clinica_json(['success' => false, 'error' => 'Error de base de datos al crear la cita.'], 500);
  }
}

clinica_json(['success' => false, 'error' => 'Accion no especificada o no valida.'], 400);

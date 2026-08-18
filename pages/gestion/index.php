<?php
require_once __DIR__ . '/../../api/data.php';

clinica_ensure_session_started();

$user = clinica_current_user();
if (!is_array($user) || !in_array($user['role'] ?? '', clinica_dashboard_roles(), true)) {
  $redirect = urlencode('/clinica/pages/gestion/');
  header("Location: /clinica/pages/login/?redirect={$redirect}");
  exit();
}

if (!clinica_can_access_management((string) ($user['role'] ?? ''))) {
  header('Location: /clinica/pages/dashboard/');
  exit();
}

header('Location: /clinica/pages/gestion/clinic/');
exit();

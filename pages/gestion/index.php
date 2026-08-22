<?php
require_once __DIR__ . '/../../api/data.php';

clinica_ensure_session_started();

$user = clinica_current_user();
if (!is_array($user) || !in_array($user['role'] ?? '', clinica_dashboard_roles(), true)) {
  $redirect = urlencode(clinica_url('pages/gestion/'));
  header('Location: ' . clinica_url("pages/login/?redirect={$redirect}"));
  exit();
}

if (!clinica_can_access_management((string) ($user['role'] ?? ''))) {
  header('Location: ' . clinica_url('pages/dashboard/'));
  exit();
}

header('Location: ' . clinica_url('pages/gestion/clinic/'));
exit();

<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../data.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'GET') {
  clinica_json(['success' => false, 'error' => 'Metodo no permitido.'], 405);
}

$resource = strtolower(trim((string) ($_GET['resource'] ?? 'home')));
if ($resource !== 'home') {
  clinica_json(['success' => false, 'error' => 'Recurso no soportado.'], 404);
}

$clinic = clinica_get_clinic_settings();

clinica_json([
  'success' => true,
  'resource' => 'home',
  'data' => [
    'clinic' => $clinic,
    'about_cards' => array_values($clinic['about_cards'] ?? []),
    'stats' => array_values($clinic['stats'] ?? []),
    'services' => clinica_public_services_for_home(),
    'specialties' => array_slice(clinica_public_specialties_for_catalog(true), 0, 4),
    'doctors' => array_slice(clinica_public_doctors_for_home(), 0, 4),
    'comments' => clinica_public_comments_for_home(),
  ],
]);

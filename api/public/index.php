<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../data.php';
require_once __DIR__ . '/../adaptive.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'GET') {
  clinica_json(['success' => false, 'error' => 'Metodo no permitido.'], 405);
}

$resource = strtolower(trim((string) ($_GET['resource'] ?? 'home')));
if ($resource !== 'home') {
  clinica_json(['success' => false, 'error' => 'Recurso no soportado.'], 404);
}

$pdo = clinica_get_db();
$clinic = clinica_get_clinic_settings();
$adaptive_context = clinica_adaptive_context($pdo);

clinica_json([
  'success' => true,
  'resource' => 'home',
  'data' => [
    'clinic' => $clinic,
    'adaptive_context' => $adaptive_context,
    'about_cards' => array_values($clinic['about_cards'] ?? []),
    'stats' => array_values($clinic['stats'] ?? []),
    'services' => clinica_public_services_for_home(),
    'specialties' => array_slice($adaptive_context['specialties'] ?? [], 0, 4),
    'doctors' => array_slice($adaptive_context['doctors'] ?? [], 0, 4),
    'comments' => clinica_public_comments_for_home(),
  ],
]);

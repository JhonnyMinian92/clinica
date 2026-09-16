<?php
require_once __DIR__ . '/../adaptive.php';

clinica_ensure_session_started();
header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
  clinica_json(['success' => false, 'error' => 'Metodo no permitido.'], 405);
}

try {
  $pdo = clinica_get_db();
  $context = clinica_adaptive_context($pdo);
  clinica_json(['success' => true, 'context' => $context]);
} catch (Throwable $e) {
  error_log('Adaptive catalog error: ' . $e->getMessage());
  clinica_json(['success' => false, 'error' => 'No se pudo cargar el catalogo de la clinica.'], 500);
}

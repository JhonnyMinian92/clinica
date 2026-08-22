<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/api/db.php';

echo "Diagnostico Clinica\n";
echo "===================\n\n";

echo "PHP_VERSION: " . PHP_VERSION . "\n";
echo "PDO loaded: " . (extension_loaded('pdo') ? 'si' : 'no') . "\n";
echo "PDO MySQL loaded: " . (extension_loaded('pdo_mysql') ? 'si' : 'no') . "\n\n";

$credentials = clinica_db_credentials();

echo "DB_HOST: " . ($credentials['host'] ?? '') . "\n";
echo "DB_PORT: " . ($credentials['port'] ?? '') . "\n";
echo "DB_NAME: " . ($credentials['dbname'] ?? '') . "\n";
echo "DB_USER: " . ($credentials['user'] ?? '') . "\n";
echo "DB_PASS: " . (trim((string) ($credentials['password'] ?? '')) !== '' ? '[configurada]' : '[vacia]') . "\n\n";
echo "DB_PASS_LEN: " . strlen((string) ($credentials['password'] ?? '')) . "\n";
echo "DB_PASS_LAST_CHAR: " . (strlen((string) ($credentials['password'] ?? '')) > 0 ? substr((string) $credentials['password'], -1) : '[vacia]') . "\n";
echo "ENV_FILE_EXISTS: " . (is_file(__DIR__ . '/.env') ? 'si' : 'no') . "\n";
echo "LOCAL_DB_CONFIG_EXISTS: " . (is_file(__DIR__ . '/api/db.local.php') ? 'si' : 'no') . "\n\n";

try {
  $pdo = clinica_get_db();
  echo "Conexion DB: OK\n";
  echo "Base activa: " . (string) $pdo->query('SELECT DATABASE()')->fetchColumn() . "\n\n";

  $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
  echo "Tablas encontradas: " . count($tables) . "\n";
  foreach ($tables as $table) {
    echo "- " . $table . "\n";
  }

  echo "\nPrueba tabla users\n";
  echo "==================\n";

  $columns = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_ASSOC);
  echo "Columnas users: " . count($columns) . "\n";
  foreach ($columns as $column) {
    echo "- " . $column['Field'] . " | " . $column['Type'] . " | Null: " . $column['Null'] . "\n";
  }

  echo "\nUsuarios activos\n";
  $stmt = $pdo->query("SELECT id, username, name, role, doctor_id, active, CHAR_LENGTH(password) AS password_length, LEFT(password, 4) AS password_prefix FROM users ORDER BY id ASC");
  $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo "Filas users: " . count($users) . "\n";
  foreach ($users as $user) {
    echo "- ID {$user['id']} | {$user['username']} | {$user['name']} | {$user['role']} | active={$user['active']} | doctor_id=" . ($user['doctor_id'] ?? 'NULL') . " | hash_len={$user['password_length']} | prefix={$user['password_prefix']}\n";
  }

  echo "\nVerificacion de claves demo\n";
  $demoPasswords = [
    'admin' => 'password123',
    'secretaria' => 'secretaria123',
    'dr.herrera' => 'doctor123',
    'dr.vargas' => 'doctor123',
  ];
  $stmt = $pdo->prepare("SELECT password FROM users WHERE username = ? AND active = 1");
  foreach ($demoPasswords as $username => $password) {
    $stmt->execute([$username]);
    $hash = (string) ($stmt->fetchColumn() ?: '');
    $status = $hash !== '' && password_verify($password, $hash) ? 'OK' : 'FALLA';
    echo "- {$username}: {$status}\n";
  }
} catch (Throwable $e) {
  echo "Conexion DB: ERROR\n";
  echo "Mensaje: " . $e->getMessage() . "\n";
}

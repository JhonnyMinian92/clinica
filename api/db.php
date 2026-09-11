<?php

require_once __DIR__ . '/environment.php';

if (!function_exists('str_starts_with')) {
  function str_starts_with(string $haystack, string $needle): bool
  {
    return $needle === '' || strpos($haystack, $needle) === 0;
  }
}

if (!function_exists('str_contains')) {
  function str_contains(string $haystack, string $needle): bool
  {
    return $needle === '' || strpos($haystack, $needle) !== false;
  }
}

/**
 * Loads the environment selected only in api/environment.php.
 * The application never needs to be edited when switching environments.
 */
function clinica_db_credentials(): array
{
  clinica_load_env_file(clinica_environment_file());

  return [
    'environment' => clinica_env('APP_ENV', 'local'),
    'host' => clinica_env('DB_HOST', '127.0.0.1'),
    'port' => clinica_env('DB_PORT', '3306'),
    'dbname' => clinica_env('DB_NAME', 'clinica'),
    'user' => clinica_env('DB_USER', 'root'),
    'password' => clinica_env('DB_PASS', ''),
    'charset' => clinica_env('DB_CHARSET', 'utf8mb4'),
  ];
}

function clinica_load_env_file(string $path): void
{
  static $loadedPath = null;
  if ($loadedPath === $path) {
    return;
  }

  if (!is_file($path) || !is_readable($path)) {
    throw new RuntimeException('No se encontro el archivo de configuracion del ambiente: ' . basename($path));
  }

  foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
      continue;
    }

    [$key, $value] = array_map('trim', explode('=', $line, 2));
    $value = trim($value, "\"'");
    if ($key !== '') {
      putenv($key . '=' . $value);
      $_ENV[$key] = $value;
    }
  }

  $loadedPath = $path;
}

function clinica_env(string $key, string $default = ''): string
{
  $value = getenv($key);
  return $value === false ? $default : (string) $value;
}

/**
 * Forces the clinic timezone for web and CLI execution.
 */
function clinica_bootstrap_timezone(): void
{
  static $done = false;
  if ($done) {
    return;
  }

  clinica_load_env_file(clinica_environment_file());
  date_default_timezone_set(clinica_env('APP_TIMEZONE', 'America/Guayaquil'));
  $done = true;
}

clinica_bootstrap_timezone();

/**
 * Establishes and returns a singleton PDO database connection.
 */
function clinica_get_db(): PDO
{
  static $pdo = null;

  if ($pdo === null) {
    $credentials = clinica_db_credentials();
    $port = trim((string) ($credentials['port'] ?? ''));
    $portSegment = $port !== '' ? ";port={$port}" : '';
    $dsn = "mysql:host={$credentials['host']}{$portSegment};dbname={$credentials['dbname']};charset={$credentials['charset']}";
    $options = [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, $credentials['user'], $credentials['password'], $options);
  }

  return $pdo;
}

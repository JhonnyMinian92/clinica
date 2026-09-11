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

function clinica_db_credentials(): array
{
  $environmentFile = clinica_environment_file();
  clinica_load_env_file($environmentFile);

  $environment = clinica_env('APP_ENV');
  $host = clinica_env('DB_HOST');
  $port = clinica_env('DB_PORT');
  $dbname = clinica_env('DB_NAME');
  $user = clinica_env('DB_USER');
  $password = clinica_env('DB_PASS');
  $charset = clinica_env('DB_CHARSET');

  $required = [
    'APP_ENV' => $environment,
    'DB_HOST' => $host,
    'DB_PORT' => $port,
    'DB_NAME' => $dbname,
    'DB_USER' => $user,
    'DB_CHARSET' => $charset,
  ];

  foreach ($required as $key => $value) {
    if ($value === '') {
      throw new RuntimeException('Falta la configuracion obligatoria ' . $key . ' en ' . basename($environmentFile));
    }
  }

  return [
    'environment' => $environment,
    'host' => $host,
    'port' => $port,
    'dbname' => $dbname,
    'user' => $user,
    'password' => $password,
    'charset' => $charset,
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
  if ($value !== false) {
    return (string) $value;
  }

  // Some shared hosts disable getenv()/putenv() while still allowing the
  // PHP environment array. Use $_ENV as a portable fallback.
  if (isset($_ENV[$key])) {
    return (string) $_ENV[$key];
  }

  return $default;
}

function clinica_bootstrap_timezone(): void
{
  static $done = false;
  if ($done) {
    return;
  }

  $environmentFile = clinica_environment_file();
  if (is_file($environmentFile) && is_readable($environmentFile)) {
    clinica_load_env_file($environmentFile);
  }

  date_default_timezone_set(clinica_env('APP_TIMEZONE', 'America/Guayaquil'));
  $done = true;
}

clinica_bootstrap_timezone();

function clinica_get_db(): PDO
{
  static $pdo = null;

  if ($pdo === null) {
    $credentials = clinica_db_credentials();
    $port = trim((string) $credentials['port']);
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

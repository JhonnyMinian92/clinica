<?php

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
 * Returns database credentials for the deployed hosting.
 *
 * Priority:
 * 1. Environment variables or values loaded from /.env
 * 2. Optional untracked api/db.local.php file
 * 3. Hosting defaults from InfinityFree
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
 * Returns database credentials for the deployed hosting.
 *
 * Priority:
 * 1. Environment variables or values loaded from /.env
 * 2. Optional untracked api/db.local.php file
 * 3. Hosting defaults from InfinityFree
 */
function clinica_db_credentials(): array
{
  clinica_load_env_file(__DIR__ . '/../.env');

  $defaults = [
    'host' => 'sql206.infinityfree.com',
    'port' => '3306',
    'dbname' => 'if0_42682038_clinical',
    'user' => 'if0_42682038',
    'password' => 'L46SDkSCmP',
    'charset' => 'utf8mb4',
  ];

  $localConfig = clinica_load_local_db_config();
  $credentials = array_merge($defaults, array_intersect_key($localConfig, $defaults));

  return [
    'host' => clinica_env('DB_HOST', $credentials['host']),
    'port' => clinica_env('DB_PORT', $credentials['port']),
    'dbname' => clinica_env('DB_NAME', $credentials['dbname']),
    'user' => clinica_env('DB_USER', $credentials['user']),
    'password' => clinica_env('DB_PASS', $credentials['password']),
    'charset' => clinica_env('DB_CHARSET', $credentials['charset']),
  ];
}

function clinica_load_env_file(string $path): void
{
  static $loaded = false;
  if ($loaded || !is_file($path) || !is_readable($path)) {
    $loaded = true;
    return;
  }

  foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
      continue;
    }

    [$key, $value] = array_map('trim', explode('=', $line, 2));
    $value = trim($value, "\"'");
    if ($key !== '' && getenv($key) === false) {
      putenv($key . '=' . $value);
      $_ENV[$key] = $value;
    }
  }

  $loaded = true;
}

function clinica_load_local_db_config(): array
{
  $path = __DIR__ . '/db.local.php';
  if (!is_file($path)) {
    return [];
  }

  $config = require $path;
  return is_array($config) ? $config : [];
}

function clinica_env(string $key, string $default = ''): string
{
  $value = getenv($key);
  return $value === false || $value === '' ? $default : (string) $value;
}

/**
 * Forces a consistent timezone across every entry point (Apache/PHP-FPM
 * requests AND the CLI-run reminders cron), regardless of what php.ini says
 * for each SAPI. This matters because appointment reminders compare
 * appointment date/time against "now" - if the cron's CLI php.ini used a
 * different default timezone than the web SAPI, reminders would fire at the
 * wrong real-world moment relative to the stored appointment time.
 */
function clinica_bootstrap_timezone(): void
{
  static $done = false;
  if ($done) {
    return;
  }

  clinica_load_env_file(__DIR__ . '/../.env');
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
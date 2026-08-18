<?php

/**
 * Returns the database credentials.
 * In a production environment, these should come from environment variables (.env file)
 * and not be hardcoded in the source code.
 */
function clinica_db_credentials(): array
{
  return [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'dbname' => getenv('DB_NAME') ?: 'clinica_base',
    'user' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') ?: '',
    'charset' => 'utf8mb4',
  ];
}

/**
 * Establishes and returns a singleton PDO database connection.
 */
function clinica_get_db(): PDO
{
  static $pdo = null;

  if ($pdo === null) {
    $credentials = clinica_db_credentials();
    $dsn = "mysql:host={$credentials['host']};dbname={$credentials['dbname']};charset={$credentials['charset']}";
    $options = [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, $credentials['user'], $credentials['password'], $options);
  }

  return $pdo;
}

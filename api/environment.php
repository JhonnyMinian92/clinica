<?php

/**
 * Single environment selector for Clinica.
 *
 * The application can be deployed from the same Git repository to both
 * XAMPP/local and InfinityFree/demo without editing this file per deployment.
 *
 * Available files:
 * - /.env.local       -> XAMPP / phpMyAdmin
 * - /.env.development -> InfinityFree demo
 * - /.env.production  -> production (manual/explicit selection only)
 *
 * Set this to 'auto' for normal deployments. The runtime selects local when
 * executed from localhost/loopback and development when executed through a
 * public HTTP host. Production is never selected automatically.
 */
const CLINICA_ENV_FILE = 'auto';

function clinica_environment_file(): string
{
  $allowed = [
    '.env.local',
    '.env.development',
    '.env.production',
  ];

  $selected = CLINICA_ENV_FILE;

  if ($selected === 'auto') {
    $host = '';

    if (isset($_SERVER['HTTP_HOST'])) {
      $host = strtolower(trim((string) $_SERVER['HTTP_HOST']));
      $host = preg_replace('/:\\d+$/', '', $host) ?: $host;
    }

    $isCli = PHP_SAPI === 'cli';
    $isLocalHost = in_array($host, [
      '',
      'localhost',
      '127.0.0.1',
      '::1',
    ], true);

    // CLI is treated as local unless an explicit environment is selected.
    // This avoids selecting demo credentials from a local terminal.
    $selected = ($isCli || $isLocalHost)
      ? '.env.local'
      : '.env.development';
  }

  if (!in_array($selected, $allowed, true)) {
    throw new RuntimeException('Ambiente no permitido.');
  }

  return dirname(__DIR__) . DIRECTORY_SEPARATOR . $selected;
}

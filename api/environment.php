<?php

/**
 * Single environment selector for Clinica.
 *
 * The same repository can run in XAMPP/local and InfinityFree/demo without
 * editing this file for each deployment.
 *
 * Environment files:
 * - /.env.local       -> XAMPP / phpMyAdmin
 * - /.env.development -> InfinityFree demo
 * - /.env.production  -> production (explicit selection only)
 *
 * In automatic mode, a local loopback request uses .env.local and any
 * non-loopback web request uses .env.development. CLI remains local.
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
    $hosts = [];

    foreach (['HTTP_HOST', 'SERVER_NAME', 'SERVER_ADDR'] as $serverKey) {
      if (isset($_SERVER[$serverKey])) {
        $value = strtolower(trim((string) $_SERVER[$serverKey]));
        $value = preg_replace('/:\\d+$/', '', $value) ?: $value;
        if ($value !== '') {
          $hosts[] = $value;
        }
      }
    }

    $isCli = PHP_SAPI === 'cli';
    $localHosts = [
      'localhost',
      '127.0.0.1',
      '::1',
    ];

    $isLocalRequest = false;
    foreach ($hosts as $host) {
      if (in_array($host, $localHosts, true)) {
        $isLocalRequest = true;
        break;
      }
    }

    // A public HTTP request is development/demo. Only an explicit loopback
    // request or CLI execution selects the local XAMPP environment.
    $selected = ($isCli || $isLocalRequest)
      ? '.env.local'
      : '.env.development';
  }

  if (!in_array($selected, $allowed, true)) {
    throw new RuntimeException('Ambiente no permitido.');
  }

  return dirname(__DIR__) . DIRECTORY_SEPARATOR . $selected;
}

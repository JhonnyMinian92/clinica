<?php

/**
 * Single environment selector for Clinica.
 *
 * Change ONLY this value when switching between environments.
 * Do not edit database credentials in application files.
 *
 * Available files:
 * - /.env.local       -> XAMPP / phpMyAdmin
 * - /.env.development -> InfinityFree demo
 */

const CLINICA_ENV_FILE = '.env.local';

function clinica_environment_file(): string
{
  $allowed = [
    '.env.local',
    '.env.development',
  ];

  $selected = CLINICA_ENV_FILE;
  if (!in_array($selected, $allowed, true)) {
    throw new RuntimeException('Ambiente no permitido.');
  }

  return dirname(__DIR__) . DIRECTORY_SEPARATOR . $selected;
}

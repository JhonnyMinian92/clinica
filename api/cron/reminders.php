<?php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../data.php';
require_once __DIR__ . '/../notifications/service.php';

/**
 * Sweeps pending appointments and sends the "15 minutes before" reminder.
 * Meant to run every minute via cPanel Cron Jobs (CLI, recommended - bypasses
 * the shared-secret check below) or, for local XAMPP dev, Windows Task
 * Scheduler / a manual `php api/cron/reminders.php` run.
 *
 * Idempotency lives in clinica_notify_appointment_reminder() (per channel),
 * not in the query below - the query is only a coarse time-window filter.
 */

$isCli = PHP_SAPI === 'cli';

if (!$isCli) {
  header('Content-Type: text/plain; charset=utf-8');

  $secret = trim(clinica_env('CRON_SECRET', ''));
  $provided = (string) ($_GET['secret'] ?? $_SERVER['HTTP_X_CRON_SECRET'] ?? '');

  if ($secret === '' || !hash_equals($secret, $provided)) {
    http_response_code(403);
    echo "Forbidden\n";
    exit(1);
  }
}

$processed = clinica_run_reminder_sweep();

echo $processed . " cita(s) procesada(s) para recordatorio.\n";

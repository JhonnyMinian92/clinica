<?php

require_once __DIR__ . '/../data.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/whatsapp.php';
require_once __DIR__ . '/templates.php';

/**
 * Orchestrates patient notifications (confirmation on booking, reminder before
 * the appointment, manual resend from the dashboard). Every entry point here
 * is safe to call from a request that must not fail because of a notification
 * problem: failures are logged to notification_log and to error_log, never
 * thrown back to the caller.
 */

function clinica_notification_appointment_data(PDO $pdo, int $appointmentId): ?array
{
  $stmt = $pdo->prepare("
    SELECT a.id, a.date, a.time, a.status,
           p.name AS patient_name, p.email AS patient_email, p.phone AS patient_phone,
           p.email_opt_in, p.whatsapp_opt_in,
           d.name AS doctor_name, s.name AS specialty_name
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    JOIN doctors d ON a.doctor_id = d.id
    LEFT JOIN specialties s ON a.specialty_id = s.id
    WHERE a.id = ?
  ");
  $stmt->execute([$appointmentId]);
  $row = $stmt->fetch();
  return $row ?: null;
}

function clinica_notification_already_sent(PDO $pdo, int $appointmentId, string $channel, string $type): bool
{
  $stmt = $pdo->prepare("SELECT 1 FROM notification_log WHERE appointment_id = ? AND channel = ? AND type = ? AND status = 'sent' LIMIT 1");
  $stmt->execute([$appointmentId, $channel, $type]);
  return (bool) $stmt->fetchColumn();
}

function clinica_log_notification(PDO $pdo, int $appointmentId, string $channel, string $type, string $status, ?string $provider, ?string $recipient, ?string $errorMessage): void
{
  try {
    $stmt = $pdo->prepare("INSERT INTO notification_log (appointment_id, channel, type, status, provider, recipient, error_message) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$appointmentId, $channel, $type, $status, $provider, $recipient, $errorMessage]);
  } catch (PDOException $e) {
    error_log('Failed to log notification: ' . $e->getMessage());
  }
}

/**
 * @return array{status: string, provider: ?string, error: ?string}
 */
function clinica_notification_send_channel(string $channel, string $type, array $ctx, string $recipient): array
{
  if ($channel === 'email') {
    $content = $type === 'reminder' ? clinica_email_reminder_content($ctx) : clinica_email_confirmation_content($ctx);
    $result = clinica_mail_send($recipient, $ctx['patient_name'], $content['subject'], $content['html']);
    return [
      'status' => $result['success'] ? 'sent' : 'failed',
      'provider' => 'smtp',
      'error' => $result['error'],
    ];
  }

  if ($channel === 'whatsapp') {
    $message = $type === 'reminder' ? clinica_whatsapp_reminder_text($ctx) : clinica_whatsapp_confirmation_text($ctx);
    $result = clinica_whatsapp_send($recipient, $message, $type, $ctx);
    return [
      'status' => $result['status'],
      'provider' => $result['provider'],
      'error' => $result['error'],
    ];
  }

  return ['status' => 'failed', 'provider' => null, 'error' => 'Canal de notificacion desconocido.'];
}

function clinica_notification_channel_config(string $channel, array $appointment, array $clinic): array
{
  if ($channel === 'email') {
    return [
      'enabled' => (bool) ($clinic['notify_email_enabled'] ?? true),
      'opt_in' => (bool) ($appointment['email_opt_in'] ?? true),
      'recipient' => (string) ($appointment['patient_email'] ?? ''),
    ];
  }

  return [
    'enabled' => (bool) ($clinic['notify_whatsapp_enabled'] ?? false),
    'opt_in' => (bool) ($appointment['whatsapp_opt_in'] ?? false),
    'recipient' => (string) ($appointment['patient_phone'] ?? ''),
  ];
}

function clinica_dispatch_notification(PDO $pdo, array $appointment, array $clinic, string $type, bool $checkIdempotency): void
{
  $ctx = clinica_notification_context($appointment, $clinic);
  $appointmentId = (int) $appointment['id'];

  foreach (['email', 'whatsapp'] as $channel) {
    $config = clinica_notification_channel_config($channel, $appointment, $clinic);

    if (!$config['enabled'] || !$config['opt_in'] || trim($config['recipient']) === '') {
      continue;
    }

    if ($checkIdempotency && clinica_notification_already_sent($pdo, $appointmentId, $channel, $type)) {
      continue;
    }

    $result = clinica_notification_send_channel($channel, $type, $ctx, $config['recipient']);
    clinica_log_notification($pdo, $appointmentId, $channel, $type, $result['status'], $result['provider'], $config['recipient'], $result['error']);
  }
}

/**
 * Called right after an appointment is created. Must never throw or block
 * the booking response.
 */
function clinica_notify_appointment_created(int $appointmentId): void
{
  try {
    $pdo = clinica_get_db();
    $appointment = clinica_notification_appointment_data($pdo, $appointmentId);
    if ($appointment === null) {
      return;
    }

    $clinic = clinica_get_clinic_settings();
    clinica_dispatch_notification($pdo, $appointment, $clinic, 'confirmation', false);
  } catch (Throwable $e) {
    error_log('clinica_notify_appointment_created failed: ' . $e->getMessage());
  }
}

/**
 * Called by the reminders cron. Skips appointments that are no longer
 * pending (cancelled/attended/etc.) and, per channel, appointments that
 * already have a 'sent' reminder logged (idempotency).
 */
function clinica_notify_appointment_reminder(int $appointmentId): void
{
  try {
    $pdo = clinica_get_db();
    $appointment = clinica_notification_appointment_data($pdo, $appointmentId);
    if ($appointment === null || (string) ($appointment['status'] ?? '') !== 'pendiente') {
      return;
    }

    $clinic = clinica_get_clinic_settings();
    clinica_dispatch_notification($pdo, $appointment, $clinic, 'reminder', true);
  } catch (Throwable $e) {
    error_log('clinica_notify_appointment_reminder failed: ' . $e->getMessage());
  }
}

/**
 * Finds pending appointments whose start time falls within the clinic's
 * reminder window and sends the reminder for each. Shared by the real cron
 * script (api/cron/reminders.php) and by clinica_maybe_run_reminder_sweep()
 * below, so both paths use identical logic.
 *
 * @return int number of appointments processed (not necessarily all sent -
 *   clinica_notify_appointment_reminder() applies its own idempotency/opt-in checks).
 */
function clinica_run_reminder_sweep(): int
{
  $pdo = clinica_get_db();
  $clinic = clinica_get_clinic_settings();
  $leadMinutes = max(1, (int) ($clinic['reminder_minutes_before'] ?? 15));

  $windowStart = new DateTimeImmutable('now');
  $windowEnd = $windowStart->modify("+{$leadMinutes} minutes");

  $stmt = $pdo->prepare("
    SELECT id FROM appointments
    WHERE status = 'pendiente'
      AND TIMESTAMP(date, time) BETWEEN :start AND :end
  ");
  $stmt->execute([
    ':start' => $windowStart->format('Y-m-d H:i:s'),
    ':end' => $windowEnd->format('Y-m-d H:i:s'),
  ]);
  $appointmentIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

  foreach ($appointmentIds as $appointmentId) {
    clinica_notify_appointment_reminder((int) $appointmentId);
  }

  return count($appointmentIds);
}

/**
 * Best-effort automatic trigger for the reminder sweep from a normal request
 * path (the dashboard). This means reminders fire without depending on the
 * user having configured an OS-level cron (Windows Task Scheduler / cPanel
 * Cron Jobs) - as long as someone has the dashboard open, it polls often
 * enough to catch every reminder window. A real cron (api/cron/reminders.php)
 * is still recommended for production so reminders fire even if nobody has
 * the dashboard open; this is a safety net, not a replacement.
 *
 * Cheap on the common case (0 matching appointments) and never throws.
 */
function clinica_maybe_run_reminder_sweep(): void
{
  try {
    clinica_run_reminder_sweep();
  } catch (Throwable $e) {
    error_log('clinica_maybe_run_reminder_sweep failed: ' . $e->getMessage());
  }
}

/**
 * Manual resend triggered from the dashboard. Unlike the automatic paths,
 * this intentionally does not check "already sent" (it's an explicit
 * override) but still respects the clinic-wide toggle and the patient's
 * opt-in - staff cannot force a channel the patient declined.
 *
 * @return array{success: bool, error?: string, message?: string}
 */
function clinica_notify_resend(int $appointmentId, string $channel, string $type, ?int $actingUserId): array
{
  if (!in_array($channel, ['email', 'whatsapp'], true) || !in_array($type, ['confirmation', 'reminder'], true)) {
    return ['success' => false, 'error' => 'Canal o tipo de notificacion invalido.'];
  }

  $pdo = clinica_get_db();
  $appointment = clinica_notification_appointment_data($pdo, $appointmentId);
  if ($appointment === null) {
    return ['success' => false, 'error' => 'Cita no encontrada.'];
  }

  $clinic = clinica_get_clinic_settings();
  $config = clinica_notification_channel_config($channel, $appointment, $clinic);

  if (!$config['enabled']) {
    return ['success' => false, 'error' => 'Este canal no esta habilitado para la clinica.'];
  }

  if (!$config['opt_in']) {
    return ['success' => false, 'error' => 'El paciente no ha aceptado este canal.'];
  }

  if (trim($config['recipient']) === '') {
    return ['success' => false, 'error' => 'El paciente no tiene un dato de contacto valido para este canal.'];
  }

  $ctx = clinica_notification_context($appointment, $clinic);
  $result = clinica_notification_send_channel($channel, $type, $ctx, $config['recipient']);
  clinica_log_notification($pdo, $appointmentId, $channel, $type, $result['status'], $result['provider'], $config['recipient'], $result['error']);

  clinica_log_audit_event($actingUserId, 'notification_resent', 'Notificacion reenviada manualmente', [
    'appointment_id' => $appointmentId,
    'channel' => $channel,
    'type' => $type,
    'status' => $result['status'],
  ], 'appointment', (string) $appointmentId);

  if ($result['status'] === 'sent') {
    return ['success' => true];
  }

  if ($result['status'] === 'skipped') {
    return ['success' => true, 'message' => 'Canal en modo simulado (mock): no se realizo un envio real.'];
  }

  return ['success' => false, 'error' => $result['error'] ?? 'No se pudo reenviar la notificacion.'];
}

function clinica_notification_status_empty(): array
{
  return [
    'email' => ['confirmation' => null, 'reminder' => null],
    'whatsapp' => ['confirmation' => null, 'reminder' => null],
  ];
}

/**
 * Batched status lookup for the dashboard list (avoids N+1 queries).
 *
 * @return array<int, array{email: array{confirmation: ?string, reminder: ?string}, whatsapp: array{confirmation: ?string, reminder: ?string}}>
 */
function clinica_notification_status_for_appointments(array $appointmentIds): array
{
  $appointmentIds = array_values(array_unique(array_map('intval', $appointmentIds)));
  if (count($appointmentIds) === 0) {
    return [];
  }

  $pdo = clinica_get_db();
  $placeholders = implode(',', array_fill(0, count($appointmentIds), '?'));
  $stmt = $pdo->prepare("
    SELECT appointment_id, channel, type, status
    FROM notification_log
    WHERE appointment_id IN ({$placeholders})
    ORDER BY appointment_id, created_at DESC
  ");
  $stmt->execute($appointmentIds);

  $result = [];
  foreach ($stmt->fetchAll() as $row) {
    $id = (int) $row['appointment_id'];
    if (!isset($result[$id])) {
      $result[$id] = clinica_notification_status_empty();
    }
    // ORDER BY created_at DESC means the first row seen per (channel,type) is the latest.
    if ($result[$id][$row['channel']][$row['type']] === null) {
      $result[$id][$row['channel']][$row['type']] = $row['status'];
    }
  }

  foreach ($appointmentIds as $id) {
    if (!isset($result[$id])) {
      $result[$id] = clinica_notification_status_empty();
    }
  }

  return $result;
}

function clinica_notification_status_for_appointment(int $appointmentId): array
{
  $all = clinica_notification_status_for_appointments([$appointmentId]);
  return $all[$appointmentId] ?? clinica_notification_status_empty();
}

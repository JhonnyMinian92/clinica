<?php

require_once __DIR__ . '/../data.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/whatsapp.php';
require_once __DIR__ . '/templates.php';

const CLINICA_NOTIFICATION_RETRY_COOLDOWN_MINUTES = 10;
const CLINICA_REMINDER_SWEEP_LOCK_TIMEOUT_SECONDS = 0;

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

/** Prevents a failed provider from being hammered once per dashboard poll. */
function clinica_notification_should_skip_retry(PDO $pdo, int $appointmentId, string $channel, string $type): bool
{
  $stmt = $pdo->prepare("\n    SELECT status, created_at\n    FROM notification_log\n    WHERE appointment_id = ? AND channel = ? AND type = ?\n    ORDER BY created_at DESC, id DESC\n    LIMIT 1\n  ");
  $stmt->execute([$appointmentId, $channel, $type]);
  $last = $stmt->fetch();

  if (!$last) return false;
  if ((string) ($last['status'] ?? '') === 'sent') return true;
  if ((string) ($last['status'] ?? '') !== 'failed') return false;

  try {
    $lastAttempt = new DateTimeImmutable((string) $last['created_at']);
    return new DateTimeImmutable('now') < $lastAttempt->modify('+' . CLINICA_NOTIFICATION_RETRY_COOLDOWN_MINUTES . ' minutes');
  } catch (Throwable $e) {
    return false;
  }
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

function clinica_notification_send_channel(string $channel, string $type, array $ctx, string $recipient): array
{
  if ($channel === 'email') {
    $content = $type === 'reminder' ? clinica_email_reminder_content($ctx) : clinica_email_confirmation_content($ctx);
    $result = clinica_mail_send($recipient, $ctx['patient_name'], $content['subject'], $content['html']);
    return ['status' => $result['success'] ? 'sent' : 'failed', 'provider' => 'smtp', 'error' => $result['error']];
  }

  if ($channel === 'whatsapp') {
    $message = $type === 'reminder' ? clinica_whatsapp_reminder_text($ctx) : clinica_whatsapp_confirmation_text($ctx);
    $result = clinica_whatsapp_send($recipient, $message, $type, $ctx);
    return ['status' => $result['status'], 'provider' => $result['provider'], 'error' => $result['error']];
  }

  return ['status' => 'failed', 'provider' => null, 'error' => 'Canal de notificacion desconocido.'];
}

function clinica_notification_channel_config(string $channel, array $appointment, array $clinic): array
{
  if ($channel === 'email') {
    // Even if an old demo row has notify_email_enabled=1, do not attempt SMTP
    // until a sender address is configured. This prevents 8-second connection
    // attempts during every booking on demo hosting.
    $smtpConfigured = trim(clinica_env('SMTP_HOST', '')) !== ''
      && trim(clinica_env('MAIL_FROM_EMAIL', '')) !== '';

    return [
      'enabled' => (bool) ($clinic['notify_email_enabled'] ?? false) && $smtpConfigured,
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
    if (!$config['enabled'] || !$config['opt_in'] || trim($config['recipient']) === '') continue;
    if ($checkIdempotency && clinica_notification_should_skip_retry($pdo, $appointmentId, $channel, $type)) continue;

    $result = clinica_notification_send_channel($channel, $type, $ctx, $config['recipient']);
    clinica_log_notification($pdo, $appointmentId, $channel, $type, $result['status'], $result['provider'], $config['recipient'], $result['error']);
  }
}

function clinica_notify_appointment_created(int $appointmentId): void
{
  try {
    $pdo = clinica_get_db();
    $appointment = clinica_notification_appointment_data($pdo, $appointmentId);
    if ($appointment === null) return;

    $clinic = clinica_get_clinic_settings();
    clinica_dispatch_notification($pdo, $appointment, $clinic, 'confirmation', false);
  } catch (Throwable $e) {
    error_log('clinica_notify_appointment_created failed: ' . $e->getMessage());
  }
}

function clinica_notify_appointment_reminder(int $appointmentId): void
{
  try {
    $pdo = clinica_get_db();
    $appointment = clinica_notification_appointment_data($pdo, $appointmentId);
    if ($appointment === null || (string) ($appointment['status'] ?? '') !== 'pendiente') return;

    $clinic = clinica_get_clinic_settings();
    clinica_dispatch_notification($pdo, $appointment, $clinic, 'reminder', true);
  } catch (Throwable $e) {
    error_log('clinica_notify_appointment_reminder failed: ' . $e->getMessage());
  }
}

/**
 * Reminder sweep with a non-blocking advisory lock and a sargable date/time
 * predicate. This prevents duplicate concurrent sweeps and avoids scanning
 * TIMESTAMP(date,time) over every appointment row.
 */
function clinica_run_reminder_sweep(): int
{
  $pdo = clinica_get_db();
  $lockName = 'clinica_reminder_sweep';
  $lockAcquired = false;

  try {
    $lockStmt = $pdo->prepare('SELECT GET_LOCK(?, ?)');
    $lockStmt->execute([$lockName, CLINICA_REMINDER_SWEEP_LOCK_TIMEOUT_SECONDS]);
    $lockAcquired = (bool) $lockStmt->fetchColumn();
    if (!$lockAcquired) return 0;

    $clinic = clinica_get_clinic_settings();
    $leadMinutes = max(1, min(1440, (int) ($clinic['reminder_minutes_before'] ?? 15)));
    $windowStart = new DateTimeImmutable('now');
    $windowEnd = $windowStart->modify("+{$leadMinutes} minutes");
    $startDate = $windowStart->format('Y-m-d');
    $endDate = $windowEnd->format('Y-m-d');

    $stmt = $pdo->prepare("\n      SELECT id\n      FROM appointments\n      WHERE status = 'pendiente'\n        AND (\n          (date = :start_date AND time >= :start_time)\n          OR (date > :start_date_mid AND date < :end_date_mid)\n          OR (date = :end_date AND time <= :end_time)\n        )\n      ORDER BY date, time\n    ");
    $stmt->execute([
      ':start_date' => $startDate,
      ':start_time' => $windowStart->format('H:i:s'),
      ':start_date_mid' => $startDate,
      ':end_date_mid' => $endDate,
      ':end_date' => $endDate,
      ':end_time' => $windowEnd->format('H:i:s'),
    ]);
    $appointmentIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($appointmentIds as $appointmentId) clinica_notify_appointment_reminder((int) $appointmentId);
    return count($appointmentIds);
  } catch (Throwable $e) {
    error_log('clinica_run_reminder_sweep failed: ' . $e->getMessage());
    return 0;
  } finally {
    if ($lockAcquired) {
      try {
        $releaseStmt = $pdo->prepare('SELECT RELEASE_LOCK(?)');
        $releaseStmt->execute([$lockName]);
      } catch (Throwable $e) {
        error_log('Failed to release reminder sweep lock: ' . $e->getMessage());
      }
    }
  }
}

function clinica_maybe_run_reminder_sweep(): void
{
  try {
    clinica_run_reminder_sweep();
  } catch (Throwable $e) {
    error_log('clinica_maybe_run_reminder_sweep failed: ' . $e->getMessage());
  }
}

function clinica_notify_resend(int $appointmentId, string $channel, string $type, ?int $actingUserId): array
{
  if (!in_array($channel, ['email', 'whatsapp'], true) || !in_array($type, ['confirmation', 'reminder'], true)) {
    return ['success' => false, 'error' => 'Canal o tipo de notificacion invalido.'];
  }

  $pdo = clinica_get_db();
  $appointment = clinica_notification_appointment_data($pdo, $appointmentId);
  if ($appointment === null) return ['success' => false, 'error' => 'Cita no encontrada.'];

  $clinic = clinica_get_clinic_settings();
  $config = clinica_notification_channel_config($channel, $appointment, $clinic);
  if (!$config['enabled']) return ['success' => false, 'error' => 'Este canal no esta habilitado para la clinica.'];
  if (!$config['opt_in']) return ['success' => false, 'error' => 'El paciente no ha aceptado este canal.'];
  if (trim($config['recipient']) === '') return ['success' => false, 'error' => 'El paciente no tiene un dato de contacto valido para este canal.'];

  $ctx = clinica_notification_context($appointment, $clinic);
  $result = clinica_notification_send_channel($channel, $type, $ctx, $config['recipient']);
  clinica_log_notification($pdo, $appointmentId, $channel, $type, $result['status'], $result['provider'], $config['recipient'], $result['error']);
  clinica_log_audit_event($actingUserId, 'notification_resent', 'Notificacion reenviada manualmente', [
    'appointment_id' => $appointmentId,
    'channel' => $channel,
    'type' => $type,
    'status' => $result['status'],
  ], 'appointment', (string) $appointmentId);

  if ($result['status'] === 'sent') return ['success' => true];
  if ($result['status'] === 'skipped') return ['success' => true, 'message' => 'Canal en modo simulado (mock): no se realizo un envio real.'];
  return ['success' => false, 'error' => $result['error'] ?? 'No se pudo reenviar la notificacion.'];
}

function clinica_notification_status_empty(): array
{
  return [
    'email' => ['confirmation' => null, 'reminder' => null],
    'whatsapp' => ['confirmation' => null, 'reminder' => null],
  ];
}

function clinica_notification_status_for_appointments(array $appointmentIds): array
{
  $appointmentIds = array_values(array_unique(array_map('intval', $appointmentIds)));
  if (count($appointmentIds) === 0) return [];

  $pdo = clinica_get_db();
  $placeholders = implode(',', array_fill(0, count($appointmentIds), '?'));
  $stmt = $pdo->prepare("\n    SELECT appointment_id, channel, type, status\n    FROM notification_log\n    WHERE appointment_id IN ({$placeholders})\n    ORDER BY appointment_id, created_at DESC, id DESC\n  ");
  $stmt->execute($appointmentIds);

  $result = [];
  foreach ($stmt->fetchAll() as $row) {
    $id = (int) $row['appointment_id'];
    if (!isset($result[$id])) $result[$id] = clinica_notification_status_empty();
    if (isset($result[$id][$row['channel']][$row['type']]) && $result[$id][$row['channel']][$row['type']] === null) {
      $result[$id][$row['channel']][$row['type']] = $row['status'];
    }
  }

  foreach ($appointmentIds as $id) {
    if (!isset($result[$id])) $result[$id] = clinica_notification_status_empty();
  }
  return $result;
}

function clinica_notification_status_for_appointment(int $appointmentId): array
{
  $all = clinica_notification_status_for_appointments([$appointmentId]);
  return $all[$appointmentId] ?? clinica_notification_status_empty();
}

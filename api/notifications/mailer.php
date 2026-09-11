<?php

require_once __DIR__ . '/../db.php';

/**
 * Minimal dependency-free SMTP client (no Composer/PHPMailer available in this project).
 * Supports both encryption modes offered by Gmail and standard cPanel mail servers:
 * STARTTLS on port 587 (SMTP_SECURE=tls) and implicit SSL on port 465 (SMTP_SECURE=ssl),
 * plus AUTH LOGIN for authentication.
 */

function clinica_smtp_config(): array
{
  return [
    'host' => clinica_env('SMTP_HOST', ''),
    'port' => (int) clinica_env('SMTP_PORT', '587'),
    'secure' => strtolower(clinica_env('SMTP_SECURE', 'tls')),
    'user' => clinica_env('SMTP_USER', ''),
    'pass' => clinica_env('SMTP_PASS', ''),
    'from_email' => clinica_env('MAIL_FROM_EMAIL', ''),
    'from_name' => clinica_env('MAIL_FROM_NAME', 'Clinica Estandar'),
  ];
}

/**
 * Sends an HTML email. Never throws - always returns a result array so callers
 * (appointment creation, reminders) can safely ignore failures without breaking
 * the surrounding business flow.
 *
 * @return array{success: bool, error: ?string}
 */
function clinica_mail_send(string $toEmail, string $toName, string $subject, string $htmlBody): array
{
  $config = clinica_smtp_config();

  if (trim($config['host']) === '' || trim($config['from_email']) === '') {
    return ['success' => false, 'error' => 'SMTP no configurado (SMTP_HOST/MAIL_FROM_EMAIL vacios).'];
  }

  if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
    return ['success' => false, 'error' => 'Direccion de destino invalida.'];
  }

  try {
    clinica_smtp_transaction($config, $toEmail, $toName, $subject, $htmlBody);
    return ['success' => true, 'error' => null];
  } catch (Throwable $e) {
    error_log('SMTP send failed: ' . $e->getMessage());
    return ['success' => false, 'error' => $e->getMessage()];
  }
}

function clinica_smtp_transaction(array $config, string $toEmail, string $toName, string $subject, string $htmlBody): void
{
  // 'ssl' = implicit TLS from the first byte (classic port 465, still the
  // default/recommended mode on most cPanel mail servers). 'tls' = plaintext
  // connect then STARTTLS upgrade (port 587). Anything else connects plain.
  $isImplicitSsl = $config['secure'] === 'ssl';
  $transport = $isImplicitSsl ? 'ssl' : 'tcp';

  $fp = @stream_socket_client(
    "{$transport}://{$config['host']}:{$config['port']}",
    $errno,
    $errstr,
    8,
    STREAM_CLIENT_CONNECT
  );

  if ($fp === false) {
    throw new RuntimeException("No se pudo conectar a {$config['host']}:{$config['port']} ({$errstr})");
  }

  stream_set_timeout($fp, 8);

  try {
    clinica_smtp_expect($fp, ['220']);

    clinica_smtp_command($fp, 'EHLO localhost', ['250']);

    if ($config['secure'] === 'tls') {
      clinica_smtp_command($fp, 'STARTTLS', ['220']);

      $cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT;
      if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
        $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
      }

      if (!@stream_socket_enable_crypto($fp, true, $cryptoMethod)) {
        throw new RuntimeException('No se pudo iniciar TLS con el servidor SMTP.');
      }

      // RFC 3207: EHLO must be re-issued after STARTTLS.
      clinica_smtp_command($fp, 'EHLO localhost', ['250']);
    }

    if (trim($config['user']) !== '') {
      clinica_smtp_command($fp, 'AUTH LOGIN', ['334']);
      clinica_smtp_command($fp, base64_encode($config['user']), ['334']);
      clinica_smtp_command($fp, base64_encode($config['pass']), ['235']);
    }

    clinica_smtp_command($fp, "MAIL FROM:<{$config['from_email']}>", ['250']);
    clinica_smtp_command($fp, "RCPT TO:<{$toEmail}>", ['250', '251']);
    clinica_smtp_command($fp, 'DATA', ['354']);

    $message = clinica_smtp_build_message($config, $toEmail, $toName, $subject, $htmlBody);
    fwrite($fp, $message . "\r\n.\r\n");
    clinica_smtp_expect($fp, ['250']);

    clinica_smtp_command($fp, 'QUIT', ['221'], false);
  } finally {
    fclose($fp);
  }
}

function clinica_smtp_build_message(array $config, string $toEmail, string $toName, string $subject, string $htmlBody): string
{
  $fromHeader = clinica_smtp_encode_address($config['from_name'], $config['from_email']);
  $toHeader = clinica_smtp_encode_address($toName, $toEmail);
  $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

  $headers = [
    "From: {$fromHeader}",
    "To: {$toHeader}",
    "Subject: {$encodedSubject}",
    'MIME-Version: 1.0',
    'Content-Type: text/html; charset=UTF-8',
    'Content-Transfer-Encoding: 8bit',
    'Date: ' . date('r'),
  ];

  // SMTP requires CRLF line endings; normalize before dot-stuffing.
  $normalizedBody = str_replace(["\r\n", "\r"], "\n", $htmlBody);
  $normalizedBody = str_replace("\n", "\r\n", $normalizedBody);

  // Dot-stuffing per RFC 5321: lines starting with '.' must be escaped as '..'.
  $body = preg_replace('/^\./m', '..', $normalizedBody);

  return implode("\r\n", $headers) . "\r\n\r\n" . $body;
}

function clinica_smtp_encode_address(string $name, string $email): string
{
  $name = trim($name);
  if ($name === '') {
    return $email;
  }

  return '=?UTF-8?B?' . base64_encode($name) . "?= <{$email}>";
}

function clinica_smtp_command($fp, string $command, array $expectedCodes, bool $expectResponse = true): void
{
  fwrite($fp, $command . "\r\n");
  if ($expectResponse) {
    clinica_smtp_expect($fp, $expectedCodes);
  }
}

function clinica_smtp_expect($fp, array $expectedCodes): string
{
  $response = clinica_smtp_read($fp);
  $code = substr($response, 0, 3);

  if (!in_array($code, $expectedCodes, true)) {
    throw new RuntimeException("Respuesta SMTP inesperada: {$response}");
  }

  return $response;
}

function clinica_smtp_read($fp): string
{
  $lines = [];

  while (!feof($fp)) {
    $line = fgets($fp, 515);
    if ($line === false) {
      break;
    }

    $lines[] = $line;

    // Multi-line responses use a hyphen after the code ("250-"); the final
    // line uses a space ("250 ").
    if (strlen($line) >= 4 && $line[3] === ' ') {
      break;
    }
  }

  $meta = stream_get_meta_data($fp);
  if (!empty($meta['timed_out'])) {
    throw new RuntimeException('Tiempo de espera agotado esperando respuesta del servidor SMTP.');
  }

  return trim(implode('', $lines));
}

<?php

require_once __DIR__ . '/../db.php';

/**
 * Pluggable WhatsApp sender. Two real providers are wired in (Twilio, Meta
 * Cloud API) plus a "mock" that makes no network call at all. Switching
 * providers is a matter of setting WHATSAPP_PROVIDER + that provider's env
 * vars - no caller of clinica_whatsapp_send() needs to change.
 *
 * Both real providers send WhatsApp *template* messages, not free text:
 * WhatsApp Business requires business-initiated messages (which confirmations
 * and reminders are) to use a template pre-approved by Meta. The template's
 * placeholders ({{1}}, {{2}}, ...) must be created in this exact order:
 *   1) nombre del paciente
 *   2) nombre de la clinica
 *   3) fecha y hora de la cita
 *   4) nombre del doctor
 *   5) especialidad
 */

function clinica_whatsapp_provider(): string
{
  return strtolower(trim(clinica_env('WHATSAPP_PROVIDER', 'mock'))) ?: 'mock';
}

/**
 * @param string $type 'confirmation' or 'reminder' - selects which approved template to use.
 * @param array $ctx shared notification context from clinica_notification_context().
 * @return array{success: bool, status: string, provider: string, error: ?string}
 */
function clinica_whatsapp_send(string $toPhone, string $message, string $type = 'confirmation', array $ctx = []): array
{
  $provider = clinica_whatsapp_provider();

  switch ($provider) {
    case 'twilio':
      return clinica_whatsapp_send_twilio($toPhone, $type, $ctx);

    case 'meta':
      return clinica_whatsapp_send_meta($toPhone, $type, $ctx);

    case 'mock':
    default:
      return clinica_whatsapp_send_mock($toPhone, $message);
  }
}

function clinica_whatsapp_send_mock(string $toPhone, string $message): array
{
  // Intentionally makes no network call. The "not really sent" fact is
  // reported via status=skipped so callers can log it distinctly from a
  // real failed send.
  return [
    'success' => true,
    'status' => 'skipped',
    'provider' => 'mock',
    'error' => null,
  ];
}

/**
 * Normalizes a locally-collected phone number (digits only, per the
 * reservation form's validation) into E.164 for WhatsApp. Best-effort: the
 * booking form does not collect a country code today, so this assumes the
 * clinic's own country (WHATSAPP_DEFAULT_COUNTRY_CODE) for local-looking
 * numbers.
 */
function clinica_whatsapp_normalize_phone(string $phone): string
{
  $digits = preg_replace('/\D+/', '', $phone) ?? '';
  if ($digits === '') {
    return '';
  }

  $countryCode = preg_replace('/\D+/', '', clinica_env('WHATSAPP_DEFAULT_COUNTRY_CODE', '593')) ?: '593';

  if (str_starts_with($digits, $countryCode) && strlen($digits) > 10) {
    return '+' . $digits;
  }

  // Formato local con 0 inicial (ej. Ecuador: 0991112222) -> reemplazar por codigo de pais.
  if (str_starts_with($digits, '0')) {
    return '+' . $countryCode . substr($digits, 1);
  }

  if (strlen($digits) <= 10) {
    return '+' . $countryCode . $digits;
  }

  return '+' . $digits;
}

/**
 * Ordered template variables shared by both real providers. Keep the order
 * in sync with the placeholders configured in the Twilio Content Template /
 * Meta message template.
 */
function clinica_whatsapp_template_variables(array $ctx): array
{
  return [
    '1' => (string) ($ctx['patient_name'] ?? ''),
    '2' => (string) ($ctx['clinic_name'] ?? ''),
    '3' => (string) ($ctx['datetime_label'] ?? ''),
    '4' => (string) ($ctx['doctor_name'] ?? ''),
    '5' => (string) ($ctx['specialty_name'] ?? ''),
  ];
}

function clinica_whatsapp_http_post(string $url, array $headers, string $body, ?string $basicAuthUserPass = null): array
{
  if (!function_exists('curl_init')) {
    return ['ok' => false, 'httpCode' => 0, 'response' => null, 'error' => 'La extension cURL de PHP no esta habilitada.'];
  }

  $ch = curl_init($url);
  $options = [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_POSTFIELDS => $body,
    CURLOPT_TIMEOUT => 12,
    CURLOPT_CONNECTTIMEOUT => 8,
  ];

  if ($basicAuthUserPass !== null) {
    $options[CURLOPT_USERPWD] = $basicAuthUserPass;
  }

  curl_setopt_array($ch, $options);
  $response = curl_exec($ch);
  $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $curlError = curl_error($ch);
  curl_close($ch);

  if ($response === false) {
    return ['ok' => false, 'httpCode' => $httpCode, 'response' => null, 'error' => $curlError ?: 'Error de conexion desconocido.'];
  }

  return ['ok' => $httpCode >= 200 && $httpCode < 300, 'httpCode' => $httpCode, 'response' => $response, 'error' => null];
}

/**
 * Twilio WhatsApp API (active provider). Requires a Twilio Content Template
 * (WhatsApp template) approved by Meta for each message type - free-text
 * "Body" messages are not allowed for business-initiated messages like
 * these outside a 24h customer session.
 */
function clinica_whatsapp_send_twilio(string $toPhone, string $type, array $ctx): array
{
  $accountSid = trim(clinica_env('TWILIO_ACCOUNT_SID', ''));
  $authToken = trim(clinica_env('TWILIO_AUTH_TOKEN', ''));
  $fromNumber = trim(clinica_env('TWILIO_WHATSAPP_FROM', ''));
  $contentSid = trim(clinica_env(
    $type === 'reminder' ? 'TWILIO_CONTENT_SID_REMINDER' : 'TWILIO_CONTENT_SID_CONFIRMATION',
    ''
  ));

  if ($accountSid === '' || $authToken === '' || $fromNumber === '' || $contentSid === '') {
    return [
      'success' => false,
      'status' => 'failed',
      'provider' => 'twilio',
      'error' => 'Twilio no esta configurado (TWILIO_ACCOUNT_SID / TWILIO_AUTH_TOKEN / TWILIO_WHATSAPP_FROM / TWILIO_CONTENT_SID_*).',
    ];
  }

  $normalizedPhone = clinica_whatsapp_normalize_phone($toPhone);
  if ($normalizedPhone === '') {
    return ['success' => false, 'status' => 'failed', 'provider' => 'twilio', 'error' => 'Numero de telefono invalido para WhatsApp.'];
  }

  $from = str_starts_with($fromNumber, 'whatsapp:') ? $fromNumber : "whatsapp:{$fromNumber}";

  $body = http_build_query([
    'From' => $from,
    'To' => "whatsapp:{$normalizedPhone}",
    'ContentSid' => $contentSid,
    'ContentVariables' => json_encode(clinica_whatsapp_template_variables($ctx), JSON_UNESCAPED_UNICODE),
  ]);

  $result = clinica_whatsapp_http_post(
    "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json",
    ['Content-Type: application/x-www-form-urlencoded'],
    $body,
    "{$accountSid}:{$authToken}"
  );

  return clinica_whatsapp_interpret_response('twilio', $result, static function (array $decoded): string {
    return (string) ($decoded['message'] ?? 'Error desconocido de Twilio.');
  });
}

/**
 * Meta WhatsApp Cloud API - implemented and ready to activate by setting
 * WHATSAPP_PROVIDER=meta plus the META_* env vars below, once a Meta
 * Business/WhatsApp Cloud API account and approved templates exist.
 */
function clinica_whatsapp_send_meta(string $toPhone, string $type, array $ctx): array
{
  $phoneNumberId = trim(clinica_env('META_WHATSAPP_PHONE_NUMBER_ID', ''));
  $accessToken = trim(clinica_env('META_WHATSAPP_ACCESS_TOKEN', ''));
  $templateName = trim(clinica_env(
    $type === 'reminder' ? 'META_TEMPLATE_NAME_REMINDER' : 'META_TEMPLATE_NAME_CONFIRMATION',
    ''
  ));
  $languageCode = trim(clinica_env('META_TEMPLATE_LANGUAGE', 'es'));

  if ($phoneNumberId === '' || $accessToken === '' || $templateName === '') {
    return [
      'success' => false,
      'status' => 'failed',
      'provider' => 'meta',
      'error' => 'Meta Cloud API no esta configurado (META_WHATSAPP_PHONE_NUMBER_ID / META_WHATSAPP_ACCESS_TOKEN / META_TEMPLATE_NAME_*).',
    ];
  }

  $normalizedPhone = clinica_whatsapp_normalize_phone($toPhone);
  if ($normalizedPhone === '') {
    return ['success' => false, 'status' => 'failed', 'provider' => 'meta', 'error' => 'Numero de telefono invalido para WhatsApp.'];
  }

  $variables = array_values(clinica_whatsapp_template_variables($ctx));

  $payload = json_encode([
    'messaging_product' => 'whatsapp',
    'to' => ltrim($normalizedPhone, '+'),
    'type' => 'template',
    'template' => [
      'name' => $templateName,
      'language' => ['code' => $languageCode],
      'components' => [[
        'type' => 'body',
        'parameters' => array_map(static function (string $value): array {
          return ['type' => 'text', 'text' => $value];
        }, $variables),
      ]],
    ],
  ], JSON_UNESCAPED_UNICODE);

  $result = clinica_whatsapp_http_post(
    "https://graph.facebook.com/v20.0/{$phoneNumberId}/messages",
    ['Content-Type: application/json', "Authorization: Bearer {$accessToken}"],
    (string) $payload
  );

  return clinica_whatsapp_interpret_response('meta', $result, static function (array $decoded): string {
    return (string) ($decoded['error']['message'] ?? 'Error desconocido de Meta.');
  });
}

/**
 * @param callable(array): string $extractError
 */
function clinica_whatsapp_interpret_response(string $provider, array $result, callable $extractError): array
{
  if ($result['error'] !== null) {
    return ['success' => false, 'status' => 'failed', 'provider' => $provider, 'error' => $result['error']];
  }

  if ($result['ok']) {
    return ['success' => true, 'status' => 'sent', 'provider' => $provider, 'error' => null];
  }

  $decoded = json_decode((string) $result['response'], true);
  $errorMessage = is_array($decoded) ? $extractError($decoded) : (string) $result['response'];

  return ['success' => false, 'status' => 'failed', 'provider' => $provider, 'error' => $errorMessage];
}

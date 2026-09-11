<?php

/**
 * Builds the shared content used by both the email and WhatsApp messages for
 * appointment confirmations and reminders. Deliberately does not include the
 * patient's consultation notes (privacy: notes may contain sensitive info and
 * are not needed to identify the appointment).
 */

function clinica_notification_spanish_date_label(string $date, string $time): string
{
  static $weekdays = [1 => 'lunes', 2 => 'martes', 3 => 'miercoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sabado', 7 => 'domingo'];
  static $months = [
    1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio',
    7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
  ];

  try {
    $dateTime = new DateTimeImmutable("{$date} {$time}");
  } catch (Exception $e) {
    return trim("{$date} {$time}");
  }

  $weekday = $weekdays[(int) $dateTime->format('N')] ?? '';
  $month = $months[(int) $dateTime->format('n')] ?? '';
  $day = (int) $dateTime->format('j');
  $year = $dateTime->format('Y');

  return trim("{$weekday}, {$day} de {$month} de {$year}") . ' a las ' . $dateTime->format('H:i');
}

/**
 * @param array $appointment associative row with patient_name, doctor_name, specialty_name, date, time
 * @param array $clinic clinic settings row (from clinica_get_clinic_settings())
 */
function clinica_notification_context(array $appointment, array $clinic): array
{
  return [
    'patient_name' => (string) ($appointment['patient_name'] ?? ''),
    'doctor_name' => (string) ($appointment['doctor_name'] ?? ''),
    'specialty_name' => (string) ($appointment['specialty_name'] ?? ''),
    'datetime_label' => clinica_notification_spanish_date_label(
      (string) ($appointment['date'] ?? ''),
      substr((string) ($appointment['time'] ?? '00:00:00'), 0, 5)
    ),
    'clinic_name' => (string) ($clinic['logo_label'] ?? $clinic['name'] ?? 'la clinica'),
    'clinic_address' => (string) ($clinic['address'] ?? ''),
    'clinic_phone' => (string) ($clinic['phone_display'] ?? ''),
  ];
}

/**
 * @return array{subject: string, html: string}
 */
function clinica_email_confirmation_content(array $ctx): array
{
  $subject = "Cita confirmada - {$ctx['clinic_name']}";
  $html = clinica_email_layout(
    $ctx,
    'Su cita fue agendada',
    'Hola ' . htmlspecialchars($ctx['patient_name']) . ', confirmamos su cita en <strong>' . htmlspecialchars($ctx['clinic_name']) . '</strong>.'
  );

  return ['subject' => $subject, 'html' => $html];
}

/**
 * @return array{subject: string, html: string}
 */
function clinica_email_reminder_content(array $ctx): array
{
  $subject = "Recordatorio de su cita - {$ctx['clinic_name']}";
  $html = clinica_email_layout(
    $ctx,
    'Su cita esta por comenzar',
    'Hola ' . htmlspecialchars($ctx['patient_name']) . ', le recordamos que su cita en <strong>' . htmlspecialchars($ctx['clinic_name']) . '</strong> esta por comenzar. Por favor, llegue con anticipacion.'
  );

  return ['subject' => $subject, 'html' => $html];
}

// $introHtml is pre-built HTML (patient/clinic names already escaped by the
// callers above); every other $ctx value interpolated here is escaped inline
// since it comes straight from patient/staff-entered data (patient name,
// doctor name, specialty) and must never break or inject into the email markup.
function clinica_email_layout(array $ctx, string $heading, string $introHtml): string
{
  $heading = htmlspecialchars($heading);
  $datetimeLabel = htmlspecialchars($ctx['datetime_label']);
  $doctorName = htmlspecialchars($ctx['doctor_name']);
  $specialtyName = htmlspecialchars($ctx['specialty_name']);
  $address = htmlspecialchars($ctx['clinic_address']);
  $phone = htmlspecialchars($ctx['clinic_phone']);
  $addressLine = $address !== '' ? "<p style=\"margin:4px 0;color:#555;\">{$address}</p>" : '';
  $phoneLine = $phone !== '' ? "<p style=\"margin:4px 0;color:#555;\">Tel: {$phone}</p>" : '';

  return <<<HTML
  <div style="font-family:Arial,Helvetica,sans-serif;max-width:480px;margin:0 auto;color:#1f2933;">
    <h2 style="color:#0f172a;">{$heading}</h2>
    <p>{$introHtml}</p>
    <table style="width:100%;border-collapse:collapse;margin:16px 0;">
      <tr>
        <td style="padding:8px 0;color:#555;">Fecha y hora</td>
        <td style="padding:8px 0;font-weight:bold;text-align:right;">{$datetimeLabel}</td>
      </tr>
      <tr>
        <td style="padding:8px 0;color:#555;">Doctor</td>
        <td style="padding:8px 0;font-weight:bold;text-align:right;">{$doctorName}</td>
      </tr>
      <tr>
        <td style="padding:8px 0;color:#555;">Especialidad</td>
        <td style="padding:8px 0;font-weight:bold;text-align:right;">{$specialtyName}</td>
      </tr>
    </table>
    <p style="color:#555;">Por favor, llegue 15 minutos antes de su cita.</p>
    {$addressLine}
    {$phoneLine}
  </div>
  HTML;
}

function clinica_whatsapp_confirmation_text(array $ctx): string
{
  return "Hola {$ctx['patient_name']}, tu cita en {$ctx['clinic_name']} quedo agendada para el {$ctx['datetime_label']} con {$ctx['doctor_name']} ({$ctx['specialty_name']}). Te avisaremos 15 minutos antes.";
}

function clinica_whatsapp_reminder_text(array $ctx): string
{
  return "Hola {$ctx['patient_name']}, tu cita en {$ctx['clinic_name']} con {$ctx['doctor_name']} esta por comenzar ({$ctx['datetime_label']}). Te esperamos.";
}

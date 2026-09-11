<?php
require_once __DIR__ . '/../../api/data.php';

// The management API requires the CSRF token issued by the shared session.
// Start the session here so the XSRF-TOKEN cookie exists before the browser submits the lookup.
clinica_ensure_session_started();

$clinic = clinica_get_clinic_settings();
$page_title = 'Mis citas | ' . clinica_get_display_name($clinic);
$page_description = 'Consulte y gestione sus citas futuras de forma rapida y segura.';
$page_specific_css = 'my-appointment.css';
$page_specific_js = 'my-appointment.js';

include __DIR__ . '/../../public/shared/header/header.php';
?>

<main id="main-content">
  <section class="my-appointment-page">
    <div class="my-appointment-container">
      <div class="my-appointment-hero">
        <div class="my-appointment-hero-copy">
          <span class="my-appointment-eyebrow">Portal de citas</span>
          <h1>Gestiona tu cita sin complicaciones</h1>
          <p>Consulta tus próximas citas con tu cédula o documento. Si tus planes cambiaron, puedes anular una cita antes de su hora de atención y volver a agendar.</p>
        </div>
        <div class="my-appointment-hero-icon" aria-hidden="true">✓</div>
      </div>

      <div class="appointment-steps" aria-label="Pasos para gestionar una cita">
        <div class="appointment-step"><span>1</span><strong>Identifícate</strong><small>Ingresa tu documento</small></div>
        <div class="appointment-step"><span>2</span><strong>Consulta</strong><small>Revisa tus citas futuras</small></div>
        <div class="appointment-step"><span>3</span><strong>Gestiona</strong><small>Anula o vuelve a agendar</small></div>
      </div>

      <div class="appointment-lookup-card">
        <div class="lookup-card-heading">
          <div class="lookup-card-icon" aria-hidden="true">⌕</div>
          <div>
            <span class="lookup-card-kicker">Acceso seguro</span>
            <h2>Consulta tus citas</h2>
            <p>Usaremos tu documento únicamente para localizar tus citas futuras.</p>
          </div>
        </div>
        <form id="appointment-lookup-form" novalidate>
          <div class="lookup-field">
            <label for="lookup-cedula">Cédula / Documento</label>
            <div class="lookup-input-wrap">
              <input id="lookup-cedula" name="cedula" type="text" maxlength="20" autocomplete="off" inputmode="text" pattern="[A-Za-z0-9]{6,20}" required placeholder="Ej. 0912345678" aria-describedby="lookup-help">
            </div>
          </div>
          <button type="submit" id="lookup-submit" class="btn btn-primary lookup-submit"><span>Consultar mis citas</span><span aria-hidden="true">→</span></button>
        </form>
        <p id="lookup-help" class="lookup-help"><span aria-hidden="true">🔒</span> No mostramos información clínica ni datos médicos en esta consulta.</p>
      </div>

      <div id="appointment-feedback" class="appointment-feedback" role="status" aria-live="polite" hidden></div>
      <div id="appointment-results" class="appointment-results" hidden></div>
    </div>
  </section>
</main>

<script>
const appointmentManagementConfig = <?php echo json_encode([
  'apiUrl' => clinica_url('api/appointments/manage.php'),
  'reservationUrl' => clinica_url('pages/reservations/'),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>

<?php include __DIR__ . '/../../public/shared/footer/footer.php'; ?>

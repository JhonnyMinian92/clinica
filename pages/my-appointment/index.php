<?php
require_once __DIR__ . '/../../api/data.php';

$clinic = clinica_get_clinic_settings();
$page_title = 'Consultar mi cita | ' . clinica_get_display_name($clinic);
$page_description = 'Consulte sus citas futuras con su cedula o documento y anule una cita antes de su hora de atencion.';
$page_specific_css = 'my-appointment.css';
$page_specific_js = 'my-appointment.js';

include __DIR__ . '/../../public/shared/header/header.php';
?>

<main id="main-content">
  <section class="my-appointment-page">
    <div class="my-appointment-container">
      <div class="my-appointment-intro">
        <span class="my-appointment-eyebrow">Gestion de citas</span>
        <h1>Consultar mi cita</h1>
        <p>Ingrese su cedula o documento para consultar sus citas futuras. Si necesita cambiar de dia, hora o doctor, primero puede anular una cita que aun no haya llegado a su hora de atencion.</p>
      </div>

      <div class="appointment-lookup-card">
        <form id="appointment-lookup-form" novalidate>
          <div class="lookup-field">
            <label for="lookup-cedula">Cedula / Documento</label>
            <input id="lookup-cedula" name="cedula" type="text" maxlength="20" autocomplete="off" inputmode="text" pattern="[A-Za-z0-9]{6,20}" required placeholder="Ingrese su cedula o documento">
          </div>
          <button type="submit" id="lookup-submit" class="btn btn-primary">Consultar cita</button>
        </form>
        <p class="lookup-help">Por seguridad, solo mostraremos los datos necesarios para identificar su cita. No mostramos informacion clinica.</p>
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

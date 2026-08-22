<?php
require_once __DIR__ . '/../../api/data.php';

$clinic = clinica_get_clinic_settings();
$especialidades = clinica_public_specialties_for_catalog();
$doctores = clinica_public_doctors_grouped_by_specialty();
$citas_reservadas = clinica_public_booked_slots();

$especialidad_seleccionada_id = '';
$doctor_seleccionado_id = isset($_GET['doctor_id']) ? trim((string) $_GET['doctor_id']) : '';
$specialty_param = isset($_GET['specialty']) ? trim((string) $_GET['specialty']) : '';
$especialidad_param = isset($_GET['especialidad']) ? trim((string) $_GET['especialidad']) : '';

if ($specialty_param !== '') {
  $especialidad_seleccionada_id = clinica_reservation_specialty_id_from_param($specialty_param, $especialidades);
}

if ($especialidad_seleccionada_id === '' && $especialidad_param !== '') {
  $especialidad_seleccionada_id = clinica_reservation_specialty_id_from_param($especialidad_param, $especialidades);
}

if ($doctor_seleccionado_id !== '') {
  foreach ($doctores as $especialidad_id => $lista_doctores) {
    foreach ($lista_doctores as $doctor) {
      if ($doctor['id'] === $doctor_seleccionado_id) {
        $especialidad_seleccionada_id = $especialidad_seleccionada_id ?: $especialidad_id;
        break 2;
      }
    }
  }
}

$page_specific_css = 'reservations.css';
$page_specific_js = 'reservations.js';

function clinica_reservation_specialty_id_from_param(string $param, array $especialidades): string
{
  $param = trim($param);
  if ($param === '') {
    return '';
  }

  $normalizedParam = clinica_slugify($param);

  foreach ($especialidades as $especialidad) {
    $id = (string) ($especialidad['id'] ?? '');
    $title = (string) ($especialidad['titulo'] ?? '');

    if (
      $id === $param ||
      strcasecmp($title, $param) === 0 ||
      clinica_slugify($id) === $normalizedParam ||
      clinica_slugify($title) === $normalizedParam
    ) {
      return $id;
    }
  }

  return '';
}

include __DIR__ . '/../../public/shared/header/header.php';
?>

<main id="main-content">
  <div class="reservation-container">
    <div class="reservation-form-wrapper">
      <h1 class="form-main-title"><?php echo htmlspecialchars($clinic['reservation_title'] ?? 'Agendar una Cita'); ?>
      </h1>
      <p class="form-main-subtitle">
        <?php echo htmlspecialchars($clinic['reservation_subtitle'] ?? 'Siga los pasos para encontrar un horario conveniente para usted.'); ?>
      </p>

      <div class="progress-bar" role="list" aria-label="Progreso de agendamiento">
        <div class="progress-step active" role="listitem" aria-current="step" data-step-name="Especialidad">1</div>
        <div class="progress-step" role="listitem" data-step-name="Doctor">2</div>
        <div class="progress-step" role="listitem" data-step-name="Fecha y Hora">3</div>
        <div class="progress-step" role="listitem" data-step-name="Sus Datos">4</div>
        <div class="progress-step" role="listitem" data-step-name="Confirmar">5</div>
      </div>

      <form id="reservation-form" class="reservation-form" novalidate
        data-selected-specialty-id="<?php echo htmlspecialchars($especialidad_seleccionada_id); ?>"
        data-selected-doctor-id="<?php echo htmlspecialchars($doctor_seleccionado_id); ?>">

        <div class="form-step active">
          <h2 class="step-title">Seleccione una especialidad</h2>
          <div class="filter-wrapper">
            <svg class="filter-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
              fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="search" id="specialty-filter" class="filter-input" placeholder="Buscar especialidad"
              aria-label="Buscar especialidad" autocomplete="off">
          </div>

          <div class="selection-grid" id="specialty-grid">
            <?php foreach ($especialidades as $item): ?>
            <button type="button" class="selectable-card" data-value="<?php echo htmlspecialchars($item['id']); ?>"
              data-name="<?php echo htmlspecialchars($item['titulo']); ?>" aria-pressed="false">
              <div class="card-icon">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                  stroke-linecap="round" stroke-linejoin="round"><?php echo $item['icono']; ?></svg>
              </div>
              <span class="card-title"><?php echo htmlspecialchars($item['titulo']); ?></span>
            </button>
            <?php endforeach; ?>
          </div>
          <div class="no-results-message" style="display: none;">
            No se encontraron especialidades que coincidan con su busqueda.
          </div>
        </div>

        <div class="form-step">
          <h2 class="step-title">Seleccione un doctor</h2>
          <div class="filter-wrapper">
            <svg class="filter-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
              fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="search" id="doctor-filter" class="filter-input" placeholder="Buscar por nombre del doctor..."
              aria-label="Buscar por nombre del doctor" autocomplete="off">
          </div>
          <div id="doctor-selection-grid" class="selection-grid doctor-grid">
            <div class="skeleton-card"></div>
            <div class="skeleton-card"></div>
          </div>
          <div id="doctor-no-results" class="no-results-message" style="display: none;">
            No se encontraron doctores que coincidan con su busqueda.
          </div>
        </div>

        <div class="form-step">
          <div class="schedule-step-header">
            <h2 class="step-title">Seleccione fecha y hora</h2>
            <div class="schedule-summary" aria-live="polite">
              <div class="schedule-summary-item">
                <span class="schedule-summary-label">Doctor</span>
                <strong id="schedule-doctor-label">Pendiente</strong>
              </div>
              <div class="schedule-summary-item">
                <span class="schedule-summary-label">Fecha</span>
                <strong id="schedule-date-label">Pendiente</strong>
              </div>
              <div class="schedule-summary-item">
                <span class="schedule-summary-label">Hora</span>
                <strong id="schedule-time-label">Pendiente</strong>
              </div>
            </div>
          </div>

          <div class="date-time-container">
            <div class="calendar-container">
              <div class="calendar-header">
                <button type="button" id="prev-month-btn" class="calendar-nav-btn"
                  aria-label="Mes anterior">&lt;</button>
                <h3 id="calendar-month-year" class="calendar-title"></h3>
                <button type="button" id="next-month-btn" class="calendar-nav-btn"
                  aria-label="Mes siguiente">&gt;</button>
              </div>
              <div class="calendar-weekdays">
                <span>Lun</span><span>Mar</span><span>Mie</span><span>Jue</span><span>Vie</span><span>Sab</span><span>Dom</span>
              </div>
              <div id="calendar-grid" class="calendar-grid"></div>
              <div class="calendar-legend" aria-hidden="true">
                <span><i class="legend-mark is-available"></i>Disponible</span>
                <span><i class="legend-mark is-today"></i>Hoy</span>
                <span><i class="legend-mark is-disabled"></i>No disponible</span>
              </div>
            </div>

            <div class="time-slot-wrapper">
              <div class="time-slot-heading">
                <div>
                  <p class="time-slot-title">Horarios disponibles</p>
                  <span id="selected-date-label" class="time-slot-date">Seleccione una fecha</span>
                </div>
                <span id="available-slots-count" class="time-slot-count">--</span>
              </div>
              <div class="time-slot-loader">
                <div class="spinner"></div>
              </div>
              <div id="time-slots" class="selection-grid time-grid" aria-live="polite">
                <div class="no-time-selected">
                  <p>Seleccione una fecha en el calendario para ver los horarios.</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="form-step">
          <h2 class="step-title">Complete sus datos</h2>
          <div class="patient-info-grid">
            <div class="form-group">
              <input type="text" id="patient-name" name="patient-name" required placeholder=" ">
              <label for="patient-name">Nombre Completo</label>
            </div>
            <div class="form-group">
              <input type="email" id="patient-email" name="patient-email" required placeholder=" ">
              <label for="patient-email">Correo Electronico <span class="required-mark" aria-hidden="true">*</span></label>
              <div class="form-error-message">Por favor, ingrese un correo valido. Es obligatorio para enviarle la confirmacion.</div>
            </div>
            <div class="form-group">
              <input type="tel" id="patient-phone" name="patient-phone" required placeholder=" " inputmode="numeric"
                pattern="[0-9]{7,15}" title="Ingrese solo numeros (de 7 a 15 digitos).">
              <label for="patient-phone">Telefono (solo numeros) <span class="required-mark" aria-hidden="true">*</span></label>
              <div class="form-error-message">Debe ser un numero valido de 7 a 15 digitos. Es obligatorio para que la clinica pueda contactarlo.</div>
            </div>
            <div class="form-group">
              <textarea id="patient-notes" name="patient-notes" rows="3" placeholder=" "></textarea>
              <label for="patient-notes">Notas adicionales (opcional)</label>
            </div>
          </div>

          <div class="notification-preferences">
            <p class="notification-preferences-title">Como desea recibir la confirmacion y el recordatorio de su cita</p>
            <label class="checkbox-field">
              <input type="checkbox" id="notify-email-optin" name="notify-email-optin" checked>
              <span>Correo electronico</span>
            </label>
            <label class="checkbox-field <?php echo ($clinic['notify_whatsapp_enabled'] ?? false) ? '' : 'is-unavailable'; ?>">
              <input type="checkbox" id="notify-whatsapp-optin" name="notify-whatsapp-optin"
                <?php echo ($clinic['notify_whatsapp_enabled'] ?? false) ? '' : 'disabled'; ?>>
              <span>
                WhatsApp
                <?php if (!($clinic['notify_whatsapp_enabled'] ?? false)): ?>
                  <em class="form-hint">(canal aun no disponible)</em>
                <?php endif; ?>
              </span>
            </label>
          </div>
        </div>

        <div class="form-step">
          <h2 class="step-title">Confirme los detalles de su cita</h2>
          <div class="summary-card">
            <h3>Resumen de la Cita</h3>
            <ul class="summary-list">
              <li><strong>Especialidad:</strong> <span id="summary-specialty"></span></li>
              <li><strong>Doctor:</strong> <span id="summary-doctor"></span></li>
              <li><strong>Fecha:</strong> <span id="summary-date"></span></li>
              <li><strong>Hora:</strong> <span id="summary-time"></span></li>
              <li><strong>Paciente:</strong> <span id="summary-name"></span></li>
              <li><strong>Email:</strong> <span id="summary-email"></span></li>
              <li><strong>Telefono:</strong> <span id="summary-phone"></span></li>
            </ul>
            <p class="summary-footer">
              <?php echo htmlspecialchars($clinic['reservation_summary_note'] ?? 'Recibira una confirmacion por correo electronico. Por favor, llegue 15 minutos antes de su cita.'); ?>
            </p>
          </div>
        </div>

        <div class="form-navigation">
          <button type="button" id="prev-btn"
            class="btn btn-tertiary"><?php echo htmlspecialchars($clinic['reservation_previous_label'] ?? 'Anterior'); ?></button>
          <button type="button" id="next-btn"
            class="btn btn-primary"><?php echo htmlspecialchars($clinic['reservation_next_label'] ?? 'Siguiente'); ?></button>
        </div>
      </form>
    </div>
  </div>
</main>

<script>
const doctorsData = <?php echo json_encode($doctores, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
const bookedAppointmentsData =
  <?php echo json_encode($citas_reservadas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
const reservationConfig = <?php echo json_encode([
    'generalApi' => clinica_url('api/general/'),
    'homeUrl' => clinica_url(),
    // Use clinic label (preferred for header/footer) then clinic name, fallback to seed label
    'clinicName' => $clinic['logo_label'] ?? $clinic['name'] ?? 'Clinica Estandar',
    'successTitle' => $clinic['reservation_success_title'] ?? 'Cita Agendada',
    'successText' => $clinic['reservation_success_text'] ?? 'Su cita ha sido confirmada exitosamente. Recibira un correo electronico con todos los detalles. Gracias por confiar en {clinic}.',
    'backHomeLabel' => $clinic['reservation_back_home_label'] ?? 'Volver al Inicio',
    'nextLabel' => $clinic['reservation_next_label'] ?? 'Siguiente',
    'confirmLabel' => $clinic['reservation_confirm_label'] ?? 'Confirmar Cita',
    'previousLabel' => $clinic['reservation_previous_label'] ?? 'Anterior',
    'notifyWhatsappAvailable' => (bool) ($clinic['notify_whatsapp_enabled'] ?? false),
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>

<?php include __DIR__ . '/../../public/shared/footer/footer.php'; ?>

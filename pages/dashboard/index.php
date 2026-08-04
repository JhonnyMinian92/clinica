<?php
session_start();
require_once __DIR__ . '/../../api/data.php';

$user = $_SESSION['user'] ?? null;

if (!is_array($user) || !in_array($user['role'] ?? '', clinica_dashboard_roles(), true)) {
  $redirect = urlencode('/clinica/pages/dashboard/');
  header("Location: /clinica/pages/login/?redirect={$redirect}");
  exit();
}

$page_specific_css = 'dashboard.css';
$page_specific_js = 'dashboard.js';
$can_see_all = clinica_can_see_all_appointments($user['role']);
$can_manage = clinica_can_access_management((string) ($user['role'] ?? ''));
$default_date_scope = $can_see_all ? 'all' : 'future';
$dashboard_subtitle = match ($user['role']) {
  'ADMINISTRATOR' => 'Administracion: acceso a gestion y a todas las citas presentes, pasadas y futuras.',
  'SECRETARIA' => 'Secretaria: agenda completa de todos los doctores en fechas pasadas, presentes y futuras.',
  'DOCTOR' => 'Doctor: solo sus citas presentes y futuras, protegidas por su perfil medico.',
  default => 'Panel de citas programadas segun los permisos del rol.',
};
$name_parts = preg_split('/\s+/', trim($user['name']));
$display_name = $name_parts[0] ?? $user['name'];
$role_label = $user['label'] ?? $user['role'];
$today_iso = date('Y-m-d');

include __DIR__ . '/../../public/shared/header/header.php';
?>

<main id="main-content" class="dashboard-container">
  <div class="container">
    <div class="dashboard-header">
      <div>
        <span class="dashboard-role-badge"><?php echo htmlspecialchars($role_label); ?></span>
        <h1 class="dashboard-title">Bienvenido, <?php echo htmlspecialchars($display_name); ?></h1>
        <p class="dashboard-subtitle"><?php echo htmlspecialchars($dashboard_subtitle); ?></p>
      </div>
      <div class="dashboard-header-actions">
        <?php if ($can_manage): ?>
          <a href="/clinica/pages/gestion/clinic/" class="btn btn-primary dashboard-management-link">Gestion</a>
        <?php endif; ?>
        <button type="button" id="logout-btn" class="btn btn-tertiary dashboard-logout">Cerrar sesion</button>
      </div>
    </div>

    <div class="kpi-grid" id="kpi-grid" aria-label="Resumen rapido de citas">
      <button type="button" class="kpi-card is-active" id="kpi-total" data-kpi-filter="all" aria-label="Ver todas las citas" aria-pressed="true">
        <div class="kpi-icon-wrapper total">
          <div class="kpi-icon total"></div>
        </div>
        <div class="kpi-content"><span class="kpi-value">0</span><span class="kpi-label">Citas Totales</span></div>
      </button>
      <button type="button" class="kpi-card" id="kpi-pending" data-kpi-filter="pendiente" aria-label="Ver citas pendientes" aria-pressed="false">
        <div class="kpi-icon-wrapper pending">
          <div class="kpi-icon pending"></div>
        </div>
        <div class="kpi-content"><span class="kpi-value">0</span><span class="kpi-label">Pendientes</span></div>
      </button>
      <button type="button" class="kpi-card" id="kpi-attended" data-kpi-filter="atendida" aria-label="Ver citas atendidas" aria-pressed="false">
        <div class="kpi-icon-wrapper attended">
          <div class="kpi-icon attended"></div>
        </div>
        <div class="kpi-content"><span class="kpi-value">0</span><span class="kpi-label">Atendidas</span></div>
      </button>
      <button type="button" class="kpi-card" id="kpi-absent" data-kpi-filter="no-presentado" aria-label="Ver citas no presentadas" aria-pressed="false">
        <div class="kpi-icon-wrapper absent">
          <div class="kpi-icon absent"></div>
        </div>
        <div class="kpi-content"><span class="kpi-value">0</span><span class="kpi-label">No Presentado</span></div>
      </button>
      <button type="button" class="kpi-card" id="kpi-rejected" data-kpi-filter="rechazada" aria-label="Ver citas rechazadas" aria-pressed="false">
        <div class="kpi-icon-wrapper rejected">
          <div class="kpi-icon rejected"></div>
        </div>
        <div class="kpi-content"><span class="kpi-value">0</span><span class="kpi-label">Rechazadas</span></div>
      </button>
    </div>

    <div class="agenda-insights" id="agenda-insights" aria-live="polite">
      <div class="agenda-insight">
        <span class="insight-kicker">Siguiente cita</span>
        <strong data-insight="next-title">Cargando...</strong>
        <span data-insight="next-detail">Agenda en revision</span>
      </div>
      <div class="agenda-insight">
        <span class="insight-kicker">En cola</span>
        <strong data-insight="queue-title">0 pendientes</strong>
        <span data-insight="queue-detail">Listas para atencion</span>
      </div>
      <div class="agenda-insight">
        <span class="insight-kicker">Resolucion</span>
        <strong data-insight="resolution-title">0%</strong>
        <span data-insight="resolution-detail">Citas cerradas del rango</span>
      </div>
    </div>

    <section class="dashboard-content" aria-labelledby="appointments-title">
      <div class="appointments-panel-header">
        <div>
          <h2 id="appointments-title">Citas</h2>
          <p class="panel-subtitle">Agenda operativa con acciones directas de atencion.</p>
        </div>
        <div class="dashboard-tools">
          <?php if ($can_see_all): ?>
            <div class="control-field">
              <label class="control-label" for="doctor-filter-select">Doctor</label>
              <select id="doctor-filter-select" class="doctor-filter-select">
                <option value="">Todos los doctores</option>
              </select>
            </div>
          <?php endif; ?>
          <div class="control-field">
            <label class="control-label" for="patient-search-input">Paciente</label>
            <div class="search-group">
            <span class="search-icon" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
            </span>
            <input type="search" id="patient-search-input" placeholder="Buscar por paciente..." class="search-input"
              aria-label="Buscar por paciente">
            </div>
          </div>
        </div>
      </div>

      <div class="date-filter-row">
        <div class="date-filter-controls" id="date-filter-controls" aria-label="Filtrar citas por fecha">
          <button type="button" class="btn date-filter-btn <?php echo $default_date_scope === 'today' ? 'active' : ''; ?>" data-date-scope="today">Hoy</button>
          <button type="button" class="btn date-filter-btn <?php echo $default_date_scope === 'tomorrow' ? 'active' : ''; ?>" data-date-scope="tomorrow">Manana</button>
          <button type="button" class="btn date-filter-btn <?php echo $default_date_scope === 'future' ? 'active' : ''; ?>" data-date-scope="future">Proximas</button>
          <?php if ($can_see_all): ?>
            <button type="button" class="btn date-filter-btn" data-date-scope="past">Anteriores</button>
            <button type="button" class="btn date-filter-btn <?php echo $default_date_scope === 'all' ? 'active' : ''; ?>" data-date-scope="all">Todas</button>
          <?php endif; ?>
        </div>

        <div class="control-field date-control-field">
          <label class="control-label" for="date-filter-input">Fecha exacta</label>
          <input type="date" id="date-filter-input" class="date-filter-input" aria-label="Buscar citas por fecha exacta"
            <?php echo $can_see_all ? '' : 'min="' . htmlspecialchars($today_iso) . '"'; ?>>
        </div>
      </div>

      <div class="filter-controls" id="filter-controls" aria-label="Filtrar citas por estado">
        <button type="button" class="btn btn-sm btn-filter active" data-filter="all" aria-pressed="true">
          <span class="filter-label">Todas</span>
          <span class="filter-count" data-filter-count="all">0</span>
        </button>
        <button type="button" class="btn btn-sm btn-filter" data-filter="pendiente" aria-pressed="false">
          <span class="filter-label">Pendientes</span>
          <span class="filter-count" data-filter-count="pendiente">0</span>
        </button>
        <button type="button" class="btn btn-sm btn-filter" data-filter="atendida" aria-pressed="false">
          <span class="filter-label">Atendidas</span>
          <span class="filter-count" data-filter-count="atendida">0</span>
        </button>
        <button type="button" class="btn btn-sm btn-filter" data-filter="no-presentado" aria-pressed="false">
          <span class="filter-label">No Presentado</span>
          <span class="filter-count" data-filter-count="no-presentado">0</span>
        </button>
        <button type="button" class="btn btn-sm btn-filter" data-filter="rechazada" aria-pressed="false">
          <span class="filter-label">Rechazadas</span>
          <span class="filter-count" data-filter-count="rechazada">0</span>
        </button>
      </div>

      <div id="dashboard-toast" class="dashboard-toast" role="status" aria-live="polite"></div>

      <div id="appointments-list" class="appointments-list" aria-live="polite">
        <div class="appointments-loading">
          <div class="spinner"></div>
          <span>Cargando citas...</span>
        </div>
      </div>
    </section>
  </div>
</main>

<div id="confirmation-modal" class="modal-overlay" aria-hidden="true" role="dialog" aria-modal="true"
  aria-labelledby="modal-title">
  <div class="modal-content" role="document">
    <button type="button" class="modal-close-btn" aria-label="Cerrar">&times;</button>
    <h3 id="modal-title" class="modal-title">Confirmar Accion</h3>
    <p id="modal-message" class="modal-message">Esta accion actualizara la cita seleccionada.</p>
    <div class="modal-actions">
      <button type="button" id="modal-cancel-btn" class="btn btn-tertiary">Cancelar</button>
      <button type="button" id="modal-confirm-btn" class="btn btn-primary">Confirmar</button>
    </div>
  </div>
</div>

<?php
include __DIR__ . '/../../public/shared/footer/footer.php';
?>

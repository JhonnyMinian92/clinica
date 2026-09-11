<?php
require_once __DIR__ . '/../../api/data.php';
clinica_ensure_session_started();

$user = clinica_current_user();

if (!is_array($user) || !in_array($user['role'] ?? '', clinica_dashboard_roles(), true)) {
  $redirect = urlencode(clinica_url('pages/reportes/'));
  header('Location: ' . clinica_url("pages/login/?redirect={$redirect}"));
  exit();
}

$page_specific_css = 'reportes.css';
$page_specific_js = 'reportes.js';
$can_see_all = clinica_can_see_all_appointments($user['role']);
$today_iso = date('Y-m-d');
$month_start_iso = date('Y-m-01');

include __DIR__ . '/../../public/shared/header/header.php';
?>

<main id="main-content" class="reportes-container" data-can-see-all="<?php echo $can_see_all ? '1' : '0'; ?>">
  <div class="container">
    <div class="reportes-header">
      <div>
        <h1 class="reportes-title">Reportes</h1>
        <p class="reportes-subtitle">
          <?php echo $can_see_all
            ? 'Consulte la actividad de todos los doctores por rango de fechas.'
            : 'Consulte su propia actividad por rango de fechas.'; ?>
        </p>
      </div>
      <div class="reportes-header-actions">
        <a href="<?php echo htmlspecialchars(clinica_url('pages/dashboard/')); ?>" class="btn btn-tertiary reportes-back-link" aria-label="Volver al dashboard">
          <span class="reportes-back-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M19 12H5"></path>
              <path d="m12 19-7-7 7-7"></path>
            </svg>
          </span>
          <span>Volver al dashboard</span>
        </a>
      </div>
    </div>

    <div class="reportes-tabs" role="tablist" aria-label="Tipo de reporte">
      <button type="button" class="reportes-tab active" id="tab-by-doctor" data-tab="by-doctor" role="tab" aria-selected="true">Por doctor</button>
      <button type="button" class="reportes-tab" id="tab-by-status" data-tab="by-status" role="tab" aria-selected="false">Por estado de cita</button>
    </div>

    <section class="reportes-filters" aria-label="Filtros del reporte">
      <div class="control-field">
        <label class="control-label" for="report-from">Desde</label>
        <input type="date" id="report-from" class="report-date-input" value="<?php echo htmlspecialchars($month_start_iso); ?>" max="<?php echo htmlspecialchars($today_iso); ?>">
      </div>
      <div class="control-field">
        <label class="control-label" for="report-to">Hasta</label>
        <input type="date" id="report-to" class="report-date-input" value="<?php echo htmlspecialchars($today_iso); ?>" max="<?php echo htmlspecialchars($today_iso); ?>">
      </div>
      <?php if ($can_see_all): ?>
        <div class="control-field">
          <label class="control-label" for="report-doctor-select">Doctor</label>
          <select id="report-doctor-select" class="doctor-filter-select">
            <option value="">Todos los doctores</option>
          </select>
        </div>
      <?php endif; ?>
      <div class="control-field report-status-field" id="report-status-field" hidden>
        <label class="control-label" for="report-status-select">Estado</label>
        <select id="report-status-select" class="doctor-filter-select">
          <option value="atendida">Atendidas</option>
          <option value="no-presentado">No presentado</option>
          <option value="rechazada">Rechazadas</option>
          <option value="cancelada">Canceladas</option>
        </select>
      </div>
      <button type="button" id="report-export-btn" class="btn btn-tertiary report-export-btn">Exportar CSV</button>
    </section>

    <section id="by-doctor-panel" class="reportes-panel" role="tabpanel" aria-labelledby="tab-by-doctor">
      <div id="by-doctor-table-wrapper" class="reportes-table-wrapper">
        <div class="reportes-loading"><div class="spinner"></div><span>Cargando...</span></div>
      </div>
    </section>

    <section id="by-status-panel" class="reportes-panel" role="tabpanel" aria-labelledby="tab-by-status" hidden>
      <div class="status-kpi-grid" id="status-kpi-grid" aria-label="Resumen por estado">
        <button type="button" class="status-kpi-card active" data-status="atendida">
          <span class="status-kpi-value">0</span>
          <span class="status-kpi-label">Atendidas</span>
        </button>
        <button type="button" class="status-kpi-card" data-status="no-presentado">
          <span class="status-kpi-value">0</span>
          <span class="status-kpi-label">No presentado</span>
        </button>
        <button type="button" class="status-kpi-card" data-status="rechazada">
          <span class="status-kpi-value">0</span>
          <span class="status-kpi-label">Rechazadas</span>
        </button>
        <button type="button" class="status-kpi-card" data-status="cancelada">
          <span class="status-kpi-value">0</span>
          <span class="status-kpi-label">Canceladas</span>
        </button>
      </div>

      <div id="by-status-table-wrapper" class="reportes-table-wrapper">
        <div class="reportes-loading"><div class="spinner"></div><span>Cargando...</span></div>
      </div>

      <div id="status-pagination" class="reportes-pagination" hidden>
        <p id="status-pagination-summary" class="pagination-summary">Mostrando 0 registros</p>
        <div class="pagination-controls">
          <button type="button" class="pagination-btn" id="status-pagination-prev" aria-label="Pagina anterior">Anterior</button>
          <span id="status-pagination-pages" class="pagination-pages">1 / 1</span>
          <button type="button" class="pagination-btn" id="status-pagination-next" aria-label="Pagina siguiente">Siguiente</button>
        </div>
      </div>
    </section>
  </div>
</main>

<?php
include __DIR__ . '/../../public/shared/footer/footer.php';
?>

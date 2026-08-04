<?php
/*session_start();


if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['DOCTOR', 'ADMINISTRATOR'])) {
  header('Location: /clinica/pages/login/');
  exit();
}

$user = $_SESSION['user'];*/
$user = [];
// Definir assets específicos para esta página.
$page_specific_css = 'dashboard.css';
$page_specific_js = 'dashboard.js';

// Incluir el header
include __DIR__ . '/../../public/shared/header/header.php';
?>

<main id="main-content" class="dashboard-container">
  <div class="container">
    <div class="dashboard-header">
      <h1 class="dashboard-title">Bienvenido, <?php echo htmlspecialchars(explode(' ', $user['name'])[1]); ?></h1>
      <p class="dashboard-subtitle">Panel de control de citas para hoy, <?php echo date("d \d\\e F \d\\e Y"); ?>.</p>
    </div>

    <!-- Tarea 15.2: Maquetación de KPIs -->
    <div class="kpi-grid">
      <div class="kpi-card" id="kpi-total">
        <div class="kpi-icon-wrapper">
          <div class="kpi-icon total"></div>
        </div>
        <div class="kpi-content"><span class="kpi-value">0</span><span class="kpi-label">Citas Totales</span></div>
      </div>
      <div class="kpi-card" id="kpi-pending">
        <div class="kpi-icon-wrapper">
          <div class="kpi-icon pending"></div>
        </div>
        <div class="kpi-content"><span class="kpi-value">0</span><span class="kpi-label">Pendientes</span></div>
      </div>
      <div class="kpi-card" id="kpi-attended">
        <div class="kpi-icon-wrapper">
          <div class="kpi-icon attended"></div>
        </div>
        <div class="kpi-content"><span class="kpi-value">0</span><span class="kpi-label">Atendidas</span></div>
      </div>
      <div class="kpi-card" id="kpi-absent">
        <div class="kpi-icon-wrapper">
          <div class="kpi-icon absent"></div>
        </div>
        <div class="kpi-content"><span class="kpi-value">0</span><span class="kpi-label">No Presentado</span></div>
      </div>
    </div>

    <!-- Tarea 15.2: Maquetación de la tabla de citas -->
    <div class="dashboard-content">
      <div class="table-header">
        <div class="search-group">
          <span class="search-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
          </span>
          <input type="search" id="patient-search-input" placeholder="Buscar por paciente..." class="search-input">
        </div>
        <!-- Tarea 15.3: Añadir estructura para filtros -->
        <div class="filter-controls" id="filter-controls">
          <button class="btn btn-sm btn-filter active" data-filter="all">Todas</button>
          <button class="btn btn-sm btn-filter" data-filter="pendiente">Pendientes</button>
          <button class="btn btn-sm btn-filter" data-filter="atendida">Atendidas</button>
          <button class="btn btn-sm btn-filter" data-filter="no-presentado">No Presentado</button>
        </div>
      </div>
      <div class="table-responsive-wrapper">
        <table class="appointments-table">
          <thead>
            <tr>
              <th>Hora</th>
              <th>Paciente</th>
              <?php if ($user['role'] === 'ADMINISTRATOR'): ?>
                <th>Doctor</th>
              <?php endif; ?>
              <th>Motivo</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody id="appointments-tbody">
            <!-- Las filas se insertarán aquí con JavaScript -->
            <!-- Placeholder de carga inicial -->
            <tr>
              <td colspan="<?php echo ($user['role'] === 'ADMINISTRATOR') ? '6' : '5'; ?>" class="loading-cell">
                <div class="spinner"></div>
                <span>Cargando citas...</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<!-- Tarea 15.5: Modal de Confirmación -->
<div id="confirmation-modal" class="modal-overlay" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="modal-title">
  <div class="modal-content" role="document">
    <button class="modal-close-btn" aria-label="Cerrar">&times;</button>
    <h3 id="modal-title" class="modal-title">Confirmar Acción</h3>
    <p id="modal-message" class="modal-message">¿Está seguro de que desea realizar esta acción?</p>
    <div class="modal-actions">
      <button id="modal-cancel-btn" class="btn btn-tertiary">Cancelar</button>
      <button id="modal-confirm-btn" class="btn btn-primary">Confirmar</button>
    </div>
  </div>
</div>

<?php
// Incluir el footer
include __DIR__ . '/../../public/shared/footer/footer.php';
?>
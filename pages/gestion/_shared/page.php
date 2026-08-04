<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

require_once __DIR__ . '/../../../api/data.php';

$user = $_SESSION['user'] ?? null;
if (!is_array($user) || !in_array($user['role'] ?? '', clinica_dashboard_roles(), true)) {
  $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '/clinica/pages/gestion/clinic/');
  header("Location: /clinica/pages/login/?redirect={$redirect}");
  exit();
}

if (!clinica_can_access_management((string) ($user['role'] ?? ''))) {
  header('Location: /clinica/pages/dashboard/');
  exit();
}

$gestion_title = $gestion_title ?? 'Gestion';
$gestion_subtitle = $gestion_subtitle ?? 'Administre la informacion operativa de la clinica.';
$gestion_resource = $gestion_resource ?? '';
$gestion_icon = $gestion_icon ?? 'grid';
$gestion_actions = [
  ['href' => '/clinica/pages/gestion/clinic/', 'label' => 'Clinica', 'resource' => 'clinic'],
  ['href' => '/clinica/pages/gestion/comment/', 'label' => 'Resenas', 'resource' => 'comments'],
  ['href' => '/clinica/pages/gestion/doctors/', 'label' => 'Doctores', 'resource' => 'doctors'],
  ['href' => '/clinica/pages/gestion/specialties/', 'label' => 'Especialidades', 'resource' => 'specialties'],
  ['href' => '/clinica/pages/gestion/users/', 'label' => 'Usuarios', 'resource' => 'users'],
  ['href' => '/clinica/pages/gestion/services/', 'label' => 'Servicios', 'resource' => 'services'],
  ['href' => '/clinica/pages/gestion/calendar/', 'label' => 'Horarios', 'resource' => 'schedules'],
];

include __DIR__ . '/../../../public/shared/header/header.php';
?>

<main id="main-content" class="gestion-shell" data-resource="<?php echo htmlspecialchars($gestion_resource); ?>">
  <div class="container">
    <div class="gestion-layout">
      <aside class="gestion-sidebar" aria-label="Navegacion de gestion">
        <div class="gestion-sidebar-header">
          <span class="gestion-kicker">Base de datos</span>
          <strong>Gestion Clinica</strong>
        </div>
        <nav class="gestion-nav">
          <?php foreach ($gestion_actions as $action): ?>
            <a class="gestion-nav-link <?php echo $action['resource'] === $gestion_resource ? 'is-active' : ''; ?>"
              href="<?php echo htmlspecialchars($action['href']); ?>">
              <span aria-hidden="true"></span>
              <?php echo htmlspecialchars($action['label']); ?>
            </a>
          <?php endforeach; ?>
        </nav>
      </aside>

      <section class="gestion-main" aria-labelledby="gestion-page-title">
        <header class="gestion-header">
          <div>
            <span class="gestion-kicker">Modulo</span>
            <h1 id="gestion-page-title"><?php echo htmlspecialchars($gestion_title); ?></h1>
            <p><?php echo htmlspecialchars($gestion_subtitle); ?></p>
          </div>
          <div class="gestion-header-actions">
            <a href="/clinica/pages/dashboard/" class="btn btn-tertiary">Dashboard</a>
            <button type="button" class="btn btn-primary" id="gestion-new-btn">Nuevo</button>
          </div>
        </header>

        <div class="gestion-stats" id="gestion-stats" aria-live="polite"></div>

        <div class="gestion-workspace">
          <section class="gestion-list-panel" aria-labelledby="gestion-list-title">
            <div class="gestion-panel-header">
              <div>
                <h2 id="gestion-list-title">Registros</h2>
                <p id="gestion-list-summary">Cargando informacion...</p>
              </div>
              <button type="button" class="btn btn-tertiary btn-sm" id="gestion-reload-btn">Actualizar</button>
            </div>

            <div class="gestion-filters">
              <input type="search" id="gestion-search" class="gestion-search" placeholder="Buscar..." aria-label="Buscar registros">
              <select id="gestion-status-filter" class="gestion-select" aria-label="Filtrar por estado">
                <option value="all">Todos</option>
                <option value="active">Activos</option>
                <option value="inactive">Inactivos</option>
              </select>
            </div>

            <div id="gestion-list" class="gestion-list" aria-live="polite"></div>
          </section>

          <section class="gestion-editor-panel" aria-labelledby="gestion-editor-title">
            <div class="gestion-panel-header">
              <div>
                <h2 id="gestion-editor-title">Editor</h2>
                <p id="gestion-editor-summary">Seleccione un registro o cree uno nuevo.</p>
              </div>
            </div>
            <form id="gestion-form" class="gestion-form" novalidate></form>
          </section>
        </div>
      </section>
    </div>
  </div>
</main>

<div id="gestion-toast" class="gestion-toast" role="status" aria-live="polite"></div>

<script src="/clinica/pages/gestion/gestion.js" defer></script>

<?php include __DIR__ . '/../../../public/shared/footer/footer.php'; ?>

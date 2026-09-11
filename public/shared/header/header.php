<?php
require_once __DIR__ . '/../../../api/data.php';

clinica_ensure_session_started();

$clinic_header = clinica_get_clinic_settings();
$page_title = $page_title ?? ($clinic_header['meta_title'] ?? 'Clinica Estandar');
$page_description = $page_description ?? ($clinic_header['meta_description'] ?? '');
$page_canonical = $page_canonical ?? ($clinic_header['canonical_url'] ?? '');
$clinic_name = clinica_get_display_name($clinic_header);
$logo_image = $clinic_header['logo_image'] ?? '';
$home_url = clinica_url();

// El acceso a "Mi cita" se mantiene como un único CTA flotante en las páginas públicas.
// Nunca se muestra en login ni en ninguna ruta perteneciente al dashboard.
$clinic_request_path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
$clinic_request_path = str_replace('\\', '/', (string) $clinic_request_path);
$clinic_is_login = (bool) preg_match('#/pages/login(?:/|$)#i', $clinic_request_path);
$clinic_is_dashboard = (bool) preg_match('#/pages/dashboard(?:/|$)#i', $clinic_request_path);
$clinic_show_appointment_floating = !$clinic_is_login && !$clinic_is_dashboard;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($page_title); ?></title>
  <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
  <?php if ($page_canonical !== ''): ?>
    <link rel="canonical" href="<?php echo htmlspecialchars($page_canonical); ?>">
  <?php endif; ?>
  <link rel="icon" href="<?php echo htmlspecialchars(clinica_url('favicon.ico')); ?>" type="image/x-icon">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo htmlspecialchars(clinica_versioned_url('public/css/style.css')); ?>">
  <link rel="stylesheet" href="<?php echo htmlspecialchars(clinica_versioned_url('public/shared/header/header.css')); ?>">
  <link rel="stylesheet" href="<?php echo htmlspecialchars(clinica_versioned_url('public/shared/footer/footer.css')); ?>">
  <link rel="stylesheet" href="<?php echo htmlspecialchars(clinica_versioned_url('public/css/components.css')); ?>">
  <script>window.CLINICA_BASE_PATH = <?php echo json_encode(clinica_base_path()); ?>;</script>
  <?php
  if (isset($page_specific_css) && !empty($page_specific_css)) {
    $css_path = dirname($_SERVER['PHP_SELF']) . '/' . $page_specific_css;
    $css_fs_path = dirname($_SERVER['SCRIPT_FILENAME']) . '/' . $page_specific_css;
    $css_mtime = is_file($css_fs_path) ? @filemtime($css_fs_path) : false;
    if ($css_mtime !== false) {
      $css_path .= '?v=' . $css_mtime;
    }
    echo '<link rel="stylesheet" href="' . htmlspecialchars($css_path) . '">';
  }
  ?>
</head>
<body>
  <header class="main-header" id="main-header">
    <div class="container header-container">
      <a href="<?php echo htmlspecialchars($home_url); ?>" class="logo" aria-label="Página de inicio de <?php echo htmlspecialchars($clinic_name); ?>">
        <?php clinica_render_logo_element($clinic_header); ?>
        <span class="logo-label"><?php echo htmlspecialchars($clinic_name); ?></span>
      </a>
      <nav class="main-nav" id="main-nav" aria-label="Navegacion principal">
        <ul class="nav-list">
          <li><a href="<?php echo htmlspecialchars(clinica_url('#specialties')); ?>" class="nav-link">Especialidades</a></li>
          <li><a href="<?php echo htmlspecialchars(clinica_url('#services')); ?>" class="nav-link">Servicios</a></li>
          <li><a href="<?php echo htmlspecialchars(clinica_url('#doctors')); ?>" class="nav-link">Doctores</a></li>
          <li><a href="<?php echo htmlspecialchars(clinica_url('#about')); ?>" class="nav-link">Nosotros</a></li>
        </ul>
      </nav>
      <div class="header-actions">
        <a href="<?php echo htmlspecialchars(clinica_url('pages/reservations/')); ?>" class="btn btn-primary header-book-button">Agendar cita</a>
        <button class="mobile-nav-toggle" id="mobile-nav-toggle" aria-controls="main-nav" aria-expanded="false" aria-label="Abrir menu de navegacion">
          <span class="hamburger-line"></span><span class="hamburger-line"></span><span class="hamburger-line"></span>
        </button>
      </div>
    </div>
  </header>

  <?php if ($clinic_show_appointment_floating): ?>
    <a href="<?php echo htmlspecialchars(clinica_url('pages/my-appointment/')); ?>" class="floating-appointment-link" aria-label="Consultar y gestionar mi cita">
      <span class="floating-appointment-icon" aria-hidden="true">✓</span>
      <span class="floating-appointment-text">
        <small>¿Ya tienes una cita?</small>
        <strong>Consultar mi cita</strong>
      </span>
    </a>
  <?php endif; ?>

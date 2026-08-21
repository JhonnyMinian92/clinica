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

  <link rel="stylesheet" href="<?php echo htmlspecialchars(clinica_url('public/css/style.css')); ?>">
  <link rel="stylesheet" href="<?php echo htmlspecialchars(clinica_url('public/shared/header/header.css')); ?>">
  <link rel="stylesheet" href="<?php echo htmlspecialchars(clinica_url('public/shared/footer/footer.css')); ?>">
  <link rel="stylesheet" href="<?php echo htmlspecialchars(clinica_url('public/css/components.css')); ?>">
  <script>
    window.CLINICA_BASE_PATH = <?php echo json_encode(clinica_base_path()); ?>;
  </script>

  <?php
  if (isset($page_specific_css) && !empty($page_specific_css)) {
    $css_path = dirname($_SERVER['PHP_SELF']) . '/' . $page_specific_css;
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
        <a href="<?php echo htmlspecialchars(clinica_url('pages/reservations/')); ?>" class="btn btn-primary">Agendar Cita</a>
        <button class="mobile-nav-toggle" id="mobile-nav-toggle" aria-controls="main-nav" aria-expanded="false"
          aria-label="Abrir menu de navegacion">
          <span class="hamburger-line"></span><span class="hamburger-line"></span><span class="hamburger-line"></span>
        </button>
      </div>
    </div>
  </header>

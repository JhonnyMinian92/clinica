<?php
require_once __DIR__ . '/../../../api/data.php';

$clinic_header = clinica_get_clinic_settings();
$page_title = $page_title ?? ($clinic_header['meta_title'] ?? 'Clinica Estandar');
$page_description = $page_description ?? ($clinic_header['meta_description'] ?? '');
$page_canonical = $page_canonical ?? ($clinic_header['canonical_url'] ?? '');
$clinic_name = $clinic_header['logo_label'] ?? $clinic_header['name'] ?? 'Clinica Estandar';
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

  <link rel="icon" href="/clinica/favicon.ico" type="image/x-icon">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="/clinica/public/css/style.css">
  <link rel="stylesheet" href="/clinica/public/shared/header/header.css">
  <link rel="stylesheet" href="/clinica/public/shared/footer/footer.css">
  <link rel="stylesheet" href="/clinica/public/css/components.css">

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
      <a href="/clinica/" class="logo" aria-label="Pagina de inicio de <?php echo htmlspecialchars($clinic_name); ?>">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="logo-svg" aria-hidden="true">
          <path d="M12 5C12 5 9 2 6 2C3 2 2 4 2 6C2 9 5 12 12 19C19 12 22 9 22 6C22 4 21 2 18 2C15 2 12 5 12 5Z"
            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" opacity="0.4" />
          <path d="M12 8V16M8 12H16" stroke="currentColor" stroke-width="2" stroke-linecap="round"
            stroke-linejoin="round" />
        </svg>
        <span class="clinic-name-header"><?php echo htmlspecialchars($clinic_name); ?></span>
      </a>
      <nav class="main-nav" id="main-nav" aria-label="Navegacion principal">
        <ul class="nav-list">
          <li><a href="/clinica/#specialties" class="nav-link">Especialidades</a></li>
          <li><a href="/clinica/#services" class="nav-link">Servicios</a></li>
          <li><a href="/clinica/#doctors" class="nav-link">Doctores</a></li>
          <li><a href="/clinica/#about" class="nav-link">Nosotros</a></li>
        </ul>
      </nav>
      <div class="header-actions">
        <a href="/clinica/pages/reservations/" class="btn btn-primary">Agendar Cita</a>
        <button class="mobile-nav-toggle" id="mobile-nav-toggle" aria-controls="main-nav" aria-expanded="false"
          aria-label="Abrir menu de navegacion">
          <span class="hamburger-line"></span><span class="hamburger-line"></span><span class="hamburger-line"></span>
        </button>
      </div>
    </div>
  </header>

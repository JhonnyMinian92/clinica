<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Clínica Estándar | Medicina de Vanguardia y Cuidado Humano</title>
  <meta name="description"
    content="Clínica Estándar: líder en atención médica especializada, con tecnología de punta y un equipo de profesionales comprometidos con su bienestar.">
  <link rel="canonical" href="https://www.clinicaestandar.com">

  <!-- Favicon & Fonts -->
  <link rel="icon" href="/clinica/favicon.ico" type="image/x-icon">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Global & Component Styles -->
  <link rel="stylesheet" href="/clinica/public/css/style.css">
  <link rel="stylesheet" href="/clinica/public/shared/header/header.css">
  <link rel="stylesheet" href="/clinica/public/shared/footer/footer.css">
  <link rel="stylesheet" href="/clinica/public/css/components.css">

  <?php
  // Carga condicional de CSS para páginas específicas
  if (strpos($_SERVER['REQUEST_URI'], '/pages/doctors/') !== false) {
    echo '<link rel="stylesheet" href="/clinica/pages/doctors/doctors.css">';
  }
  if (strpos($_SERVER['REQUEST_URI'], '/pages/specialties/') !== false) {
    echo '<link rel="stylesheet" href="/clinica/pages/specialties/specialties.css">';
  }
  ?>
</head>

<body>

  <!-- Header -->
  <header class="main-header" id="main-header">
    <div class="container header-container">
      <a href="/clinica/" class="logo" aria-label="Página de inicio de Clínica Estándar">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="logo-svg">
          <path d="M12 5C12 5 9 2 6 2C3 2 2 4 2 6C2 9 5 12 12 19C19 12 22 9 22 6C22 4 21 2 18 2C15 2 12 5 12 5Z"
            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" opacity="0.4" />
          <path d="M12 8V16M8 12H16" stroke="currentColor" stroke-width="2" stroke-linecap="round"
            stroke-linejoin="round" />
        </svg>
        <span class="clinic-name-header">Clínica Estándar</span>
      </a>
      <nav class="main-nav" id="main-nav" aria-label="Navegación principal">
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
          aria-label="Abrir menú de navegación">
          <span class="hamburger-line"></span><span class="hamburger-line"></span><span class="hamburger-line"></span>
        </button>
      </div>
    </div>
  </header>
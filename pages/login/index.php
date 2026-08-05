<?php
session_start();
require_once __DIR__ . '/../../api/data.php';

$user = clinica_current_user();
if (is_array($user) && in_array($user['role'] ?? '', clinica_dashboard_roles(), true)) {
  $target = clinica_resolve_login_redirect((string) ($_GET['redirect'] ?? ''), $user);
  header('Location: ' . $target);
  exit();
}
?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <title>Ingreso | Clínica Estándar</title>
  <link rel="icon" href="/clinica/favicon.ico" type="image/x-icon" />
  <meta name="description"
    content="Acceso seguro al portal de pacientes y personal de Clínica Estándar. Tecnología y cuidado a su servicio." />

  <!-- Preconnect for future font optimizations if needed, though we'll aim for system fonts first -->
  <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin />

  <!-- Links to our assets -->
  <link rel="stylesheet" href="login.css" />
  <script src="login.js" defer></script>

  <!-- Theme color for browser UI -->
  <meta name="theme-color" content="#F0F8FF" />
</head>

<body>
  <!-- 
        The ambient background container. 
        This will hold all the purely decorative, animated shapes 
        created with CSS to achieve the "work of art" background effect.
        They are separated from the main content for performance and semantic clarity.
    -->
  <div class="ambient-background" aria-hidden="true">
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
    <div class="shape shape-3"></div>
    <div class="shape shape-4"></div>
  </div>

  <!-- Main container for the entire login experience -->
  <main class="login-container">
    <!-- 
            Branding & Welcome Panel.
            This section establishes the clinic's identity. It's designed to be visually
            striking and convey a sense of premium quality and trust.
        -->
    <header class="brand-panel">
      <div class="logo" aria-label="Logo de Clínica Estándar">
        <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <linearGradient id="logo-gradient" x1="0" y1="0" x2="64" y2="64" gradientUnits="userSpaceOnUse">
              <stop stop-color="var(--color-primary)" />
              <stop offset="1" stop-color="var(--color-secondary)" />
            </linearGradient>
          </defs>
          <circle cx="32" cy="32" r="32" fill="url(#logo-gradient)" />
          <path d="M35 21H29V29H21V35H29V43H35V35H43V29H35V21Z" fill="white" />
        </svg>
      </div>
      <h1 class="clinic-name">Clínica Estándar</h1>
      <p class="tagline">Innovación y calidez al servicio de su salud.</p>
    </header>

    <!-- 
            Login Form Panel.
            This is the functional heart of the page. The structure is optimized for
            accessibility (WCAG AAA), advanced styling, and microinteractions.
        -->
    <section class="form-panel">
      <h2 class="form-title">Acceso al Portal</h2>

      <form id="login-form" novalidate>
        <!-- Username Field -->
        <div class="form-group">
          <input type="text" id="username" name="username" class="form-input" required autocomplete="username"
            aria-required="true" />
          <label for="username" class="form-label">Usuario</label>
          <div class="input-border"></div>
        </div>

        <!-- Password Field -->
        <div class="form-group password-group">
          <input type="password" id="password" name="password" class="form-input" required
            autocomplete="current-password" aria-required="true" />
          <label for="password" class="form-label">Contraseña</label>
          <div class="input-border"></div>
          <button type="button" id="toggle-password" class="toggle-password-btn" aria-label="Mostrar contraseña"
            aria-pressed="false">
            <!-- Icons will be created with CSS on these spans -->
            <span class="icon-eye" aria-hidden="true"></span>
            <span class="icon-eye-slash" aria-hidden="true"></span>
          </button>
        </div>

        <!-- Form Options: Remember Me & Forgot Password -->
        <div class="form-options">
          <div class="checkbox-group">
            <input type="checkbox" id="remember-me" name="remember-me" class="form-checkbox" />
            <label for="remember-me" class="checkbox-label">Recordarme</label>
          </div>
        </div>

        <!-- Submit Button with states -->
        <div class="form-group submit-group">
          <button type="submit" id="submit-button" class="submit-btn">
            <span class="btn-text">Ingresar</span>
            <!-- Elements for loading and success states, hidden by default -->
            <span class="btn-loader" aria-hidden="true"></span>
            <span class="btn-success-icon" aria-hidden="true"></span>
          </button>
        </div>

        <!-- Area for displaying form-level error messages, crucial for accessibility -->
        <div id="form-error-message" class="form-error" role="alert" aria-live="assertive"></div>
      </form>
    </section>
  </main>
</body>

</html>

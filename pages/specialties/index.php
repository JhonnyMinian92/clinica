<?php
// Incluimos el header reutilizable.
// Usamos __DIR__ para construir una ruta absoluta al archivo, lo que hace el código más robusto.
include __DIR__ . '/../../public/shared/header/header.php';
?>

<main id="main-content">
  <section class="section">
    <div class="container">
      <div class="section-header">
        <h1 class="section-title">Nuestras Especialidades</h1>
        <p class="section-subtitle">
          Ofrecemos una cobertura médica integral con un equipo de especialistas de primer nivel y tecnología de
          vanguardia.
        </p>
      </div>

      <div class="grid-3-col">

        <div class="specialty-card" style="animation-delay: 200ms;">
          <div class="specialty-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path
                d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z">
              </path>
            </svg>
          </div>
          <h3 class="specialty-title">Cardiología</h3>
          <p class="specialty-text">Cuidado integral de su corazón con la tecnología más avanzada.</p>
          <a href="#" class="specialty-link">Saber más</a>
        </div>

        <div class="specialty-card" style="animation-delay: 300ms;">
          <div class="specialty-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
            </svg>
          </div>
          <h3 class="specialty-title">Neurología</h3>
          <p class="specialty-text">Diagnóstico y tratamiento de enfermedades del sistema nervioso.</p>
          <a href="#" class="specialty-link">Saber más</a>
        </div>

        <div class="specialty-card" style="animation-delay: 400ms;">
          <div class="specialty-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
          </div>
          <h3 class="specialty-title">Pediatría</h3>
          <p class="specialty-text">Cuidamos la salud de los más pequeños con dedicación y cariño.</p>
          <a href="#" class="specialty-link">Saber más</a>
        </div>

        <!-- Aquí se podrían añadir más especialidades dinámicamente con PHP -->

      </div>
    </div>
  </section>
</main>

<?php
// Incluimos el footer reutilizable
include __DIR__ . '/../../public/shared/footer/footer.php';
?>
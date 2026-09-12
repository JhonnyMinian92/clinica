<?php
require_once __DIR__ . '/api/data.php';
require_once __DIR__ . '/api/adaptive.php';

$clinic = clinica_get_clinic_settings();
$adaptive_context = clinica_adaptive_context($pdo);
$page_title = $clinic['meta_title'] ?? null;
$page_description = $clinic['meta_description'] ?? null;
$page_canonical = $clinic['canonical_url'] ?? null;
$page_specific_css = 'adaptive-home.css';

include __DIR__ . '/public/shared/header/header.php';
?>

<main id="main-content" class="clinic-mode-<?php echo htmlspecialchars($adaptive_context['mode'] ?? 'multi_doctor_multi_specialty'); ?>" data-home-api="<?php echo htmlspecialchars(clinica_url('api/public/?resource=home')); ?>">
  <section id="hero" class="hero lazy-bg skeleton skeleton-dark">
    <div class="hero-overlay"></div>
    <div class="container">
      <div class="hero-content animate-on-scroll fade-in-up" id="home-hero-content">
        <h1 class="hero-title">Cargando informacion...</h1>
        <p class="hero-subtitle">Preparando la experiencia de la clinica.</p>
        <div class="hero-cta">
          <a href="pages/reservations/" class="btn btn-primary btn-lg">Agendar una Cita</a>
          <a href="pages/doctors/" class="btn btn-secondary btn-lg">Encontrar un Doctor</a>
        </div>
      </div>
    </div>
  </section>

  <section id="about" class="section">
    <div class="container">
      <div class="section-header">
        <h2 class="section-title animate-on-scroll fade-in-up" data-home-text="about_title">Cargando...</h2>
        <p class="section-subtitle animate-on-scroll fade-in-up" style="--animation-delay: 100ms;"
          data-home-text="about_subtitle"></p>
      </div>
      <div class="grid-3-col" id="home-about-cards"></div>
    </div>
  </section>

  <section class="stats-section section">
    <div class="container">
      <div class="grid-4-col" id="home-stats"></div>
    </div>
  </section>

  <section id="services" class="section section-light">
    <div class="container">
      <div class="section-header">
        <h2 class="section-title animate-on-scroll fade-in-up" data-home-text="services_title">Cargando...</h2>
        <p class="section-subtitle animate-on-scroll fade-in-up" style="--animation-delay: 100ms;"
          data-home-text="services_subtitle"></p>
      </div>
      <div class="grid-3-col" id="home-services"></div>
    </div>
  </section>

  <section id="specialties" class="section section-light">
    <div class="container">
      <div class="section-header">
        <h2 class="section-title animate-on-scroll fade-in-up" data-home-text="specialties_title">Cargando...</h2>
        <p class="section-subtitle animate-on-scroll fade-in-up" style="--animation-delay: 100ms;"
          data-home-text="specialties_subtitle"></p>
      </div>
      <div class="grid-4-col" id="home-specialties"></div>
      <div class="section-footer animate-on-scroll fade-in-up">
        <a href="pages/specialties/" class="btn btn-primary btn-lg">Ver Todas las Especialidades</a>
      </div>
    </div>
  </section>

  <section id="doctors" class="section">
    <div class="container">
      <div class="section-header">
        <h2 class="section-title animate-on-scroll fade-in-up" data-home-text="doctors_title">Cargando...</h2>
        <p class="section-subtitle animate-on-scroll fade-in-up" style="--animation-delay: 100ms;"
          data-home-text="doctors_subtitle"></p>
      </div>
      <div class="grid-4-col" id="home-doctors"></div>
      <div class="section-footer animate-on-scroll fade-in-up">
        <a href="pages/doctors/" class="btn btn-primary btn-lg">Ver Todos los Doctores</a>
      </div>
    </div>
  </section>

  <section id="testimonials" class="section section-light">
    <div class="container">
      <div class="section-header">
        <h2 class="section-title animate-on-scroll fade-in-up" data-home-text="comments_title">Cargando...</h2>
        <p class="section-subtitle animate-on-scroll fade-in-up" style="--animation-delay: 100ms;"
          data-home-text="comments_subtitle"></p>
      </div>
      <div id="testimonial-carousel" class="testimonial-carousel animate-on-scroll fade-in-up"
        style="--animation-delay: 200ms;">
        <div class="testimonial-wrapper" id="home-testimonials"></div>
        <div class="testimonial-controls">
          <button id="testimonial-prev" class="carousel-btn prev" aria-label="Testimonio anterior">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
          </button>
          <button id="testimonial-next" class="carousel-btn next" aria-label="Siguiente testimonio">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
          </button>
        </div>
        <div id="testimonial-dots" class="testimonial-dots"></div>
      </div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/public/shared/footer/footer.php'; ?>

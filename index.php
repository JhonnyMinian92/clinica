<?php
require_once __DIR__ . '/api/data.php';

$clinic = clinica_get_clinic_settings();
$especialidades_destacadas = array_slice(clinica_public_specialties_for_catalog(true), 0, 4);
$doctores_destacados = array_slice(clinica_public_doctors_for_home(), 0, 4);
$servicios = clinica_public_services_for_home();
$testimonios = clinica_public_comments_for_home();
$clinic_stats = $clinic['stats'] ?? [];
$clinic_about_cards = $clinic['about_cards'] ?? [];
$page_title = $clinic['meta_title'] ?? null;
$page_description = $clinic['meta_description'] ?? null;
$page_canonical = $clinic['canonical_url'] ?? null;

include __DIR__ . '/public/shared/header/header.php';
?>

<main id="main-content">
  <section id="hero" class="hero lazy-bg skeleton skeleton-dark" data-bg="<?php echo htmlspecialchars($clinic['hero_image'] ?? 'public/assets/banner.jpg'); ?>">
    <div class="hero-overlay"></div>
    <div class="container">
      <div class="hero-content animate-on-scroll fade-in-up">
        <h1 class="hero-title"><?php echo htmlspecialchars($clinic['hero_title'] ?? 'Medicina de Vanguardia, Cuidado Humano'); ?></h1>
        <p class="hero-subtitle"><?php echo htmlspecialchars($clinic['hero_subtitle'] ?? 'Combinamos alta tecnologia con especialistas comprometidos con su salud y bienestar.'); ?></p>
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
        <h2 class="section-title animate-on-scroll fade-in-up"><?php echo htmlspecialchars($clinic['about_title'] ?? 'Su Salud, Nuestra Prioridad'); ?></h2>
        <p class="section-subtitle animate-on-scroll fade-in-up" style="--animation-delay: 100ms;">
          <?php echo htmlspecialchars($clinic['about_subtitle'] ?? 'Descubra por que somos la eleccion de confianza para miles de pacientes.'); ?>
        </p>
      </div>
      <div class="grid-3-col">
        <?php foreach ($clinic_about_cards as $i => $card): ?>
          <div class="card animate-on-scroll fade-in-up" style="--animation-delay: <?php echo ($i * 100) + 200; ?>ms;">
            <div class="card-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <?php echo $card['icon'] ?? ''; ?>
              </svg>
            </div>
            <h3 class="card-title"><?php echo htmlspecialchars($card['title'] ?? ''); ?></h3>
            <p class="card-text"><?php echo htmlspecialchars($card['text'] ?? ''); ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="stats-section section">
    <div class="container">
      <div class="grid-4-col">
        <?php foreach ($clinic_stats as $i => $stat): ?>
          <div class="stat-item animate-on-scroll fade-in-up" style="--animation-delay: <?php echo $i * 100; ?>ms;">
            <span class="stat-number" data-target="<?php echo (int) ($stat['value'] ?? 0); ?>">0</span><span class="stat-suffix"><?php echo htmlspecialchars($stat['suffix'] ?? ''); ?></span>
            <p class="stat-label"><?php echo htmlspecialchars($stat['label'] ?? ''); ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section id="services" class="section section-light">
    <div class="container">
      <div class="section-header">
        <h2 class="section-title animate-on-scroll fade-in-up"><?php echo htmlspecialchars($clinic['services_title'] ?? 'Nuestros Servicios'); ?></h2>
        <p class="section-subtitle animate-on-scroll fade-in-up" style="--animation-delay: 100ms;">
          <?php echo htmlspecialchars($clinic['services_subtitle'] ?? 'Ofrecemos una gama completa de servicios medicos para cuidar de usted en cada etapa.'); ?>
        </p>
      </div>
      <div class="grid-3-col">
        <?php foreach ($servicios as $i => $servicio): ?>
          <div class="service-card animate-on-scroll fade-in-up" style="--animation-delay: <?php echo ($i * 100) + 200; ?>ms;">
            <div class="service-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <?php echo $servicio['icono']; ?>
              </svg>
            </div>
            <h3 class="service-title"><?php echo htmlspecialchars($servicio['titulo']); ?></h3>
            <p class="service-text"><?php echo htmlspecialchars($servicio['descripcion']); ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section id="specialties" class="section section-light">
    <div class="container">
      <div class="section-header">
        <h2 class="section-title animate-on-scroll fade-in-up"><?php echo htmlspecialchars($clinic['specialties_title'] ?? 'Nuestras Especialidades'); ?></h2>
        <p class="section-subtitle animate-on-scroll fade-in-up" style="--animation-delay: 100ms;">
          <?php echo htmlspecialchars($clinic['specialties_subtitle'] ?? 'Cobertura medica integral para todas sus necesidades de salud.'); ?>
        </p>
      </div>
      <div class="grid-4-col">
        <?php foreach ($especialidades_destacadas as $i => $especialidad): ?>
          <div class="specialty-card animate-on-scroll fade-in-up" style="--animation-delay: <?php echo ($i * 100) + 200; ?>ms;">
            <div class="specialty-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <?php echo $especialidad['icono']; ?>
              </svg>
            </div>
            <h3 class="specialty-title"><?php echo htmlspecialchars($especialidad['titulo']); ?></h3>
            <p class="specialty-text"><?php echo htmlspecialchars($especialidad['resumen'] ?: $especialidad['descripcion']); ?></p>
            <a href="pages/specialties/?specialty=<?php echo urlencode($especialidad['id']); ?>" class="specialty-link">Saber mas</a>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="section-footer animate-on-scroll fade-in-up">
        <a href="pages/specialties/" class="btn btn-primary btn-lg">Ver Todas las Especialidades</a>
      </div>
    </div>
  </section>

  <section id="doctors" class="section">
    <div class="container">
      <div class="section-header">
        <h2 class="section-title animate-on-scroll fade-in-up"><?php echo htmlspecialchars($clinic['doctors_title'] ?? 'Conozca a Nuestro Equipo'); ?></h2>
        <p class="section-subtitle animate-on-scroll fade-in-up" style="--animation-delay: 100ms;">
          <?php echo htmlspecialchars($clinic['doctors_subtitle'] ?? 'Profesionales que combinan conocimiento, experiencia y vocacion.'); ?>
        </p>
      </div>
      <div class="grid-4-col">
        <?php foreach ($doctores_destacados as $i => $doctor): ?>
          <div class="doctor-card animate-on-scroll fade-in-up" style="--animation-delay: <?php echo ($i * 100) + 200; ?>ms;">
            <div class="doctor-photo skeleton">
              <img data-src="<?php echo htmlspecialchars($doctor['foto']); ?>"
                alt="Fotografia de <?php echo htmlspecialchars($doctor['nombre']); ?>" loading="lazy" class="lazy-img">
            </div>
            <div class="doctor-card-overlay">
              <div class="doctor-card-content">
                <h3 class="doctor-name"><?php echo htmlspecialchars($doctor['nombre']); ?></h3>
                <p class="doctor-specialty"><?php echo htmlspecialchars($doctor['especialidad']); ?></p>
                <div class="doctor-card-details">
                  <p class="doctor-exp"><?php echo htmlspecialchars($doctor['experiencia']); ?></p>
                  <a href="pages/doctors/" class="btn btn-primary btn-sm">Ver Perfil</a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="section-footer animate-on-scroll fade-in-up">
        <a href="pages/doctors/" class="btn btn-primary btn-lg">Ver Todos los Doctores</a>
      </div>
    </div>
  </section>

  <section id="testimonials" class="section section-light">
    <div class="container">
      <div class="section-header">
        <h2 class="section-title animate-on-scroll fade-in-up"><?php echo htmlspecialchars($clinic['comments_title'] ?? 'La Voz de Nuestros Pacientes'); ?></h2>
        <p class="section-subtitle animate-on-scroll fade-in-up" style="--animation-delay: 100ms;">
          <?php echo htmlspecialchars($clinic['comments_subtitle'] ?? 'Historias reales de quienes han confiado su salud en nuestras manos.'); ?>
        </p>
      </div>
      <div id="testimonial-carousel" class="testimonial-carousel animate-on-scroll fade-in-up" style="--animation-delay: 200ms;">
        <div class="testimonial-wrapper">
          <?php foreach ($testimonios as $testimonio): ?>
            <?php $rating = max(0, min(5, (int) ($testimonio['valoracion'] ?? 0))); ?>
            <div class="testimonial-slide">
              <img src="<?php echo htmlspecialchars($testimonio['avatar']); ?>"
                alt="Avatar de <?php echo htmlspecialchars($testimonio['autor']); ?>" class="testimonial-avatar">
              <div class="testimonial-rating" aria-label="Valoracion: <?php echo $rating; ?> de 5 estrellas">
                <?php echo str_repeat('&#9733;', $rating) . str_repeat('&#9734;', 5 - $rating); ?>
              </div>
              <p class="testimonial-text">"<?php echo htmlspecialchars($testimonio['texto']); ?>"</p>
              <p class="testimonial-author"><?php echo htmlspecialchars($testimonio['autor']); ?></p>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="testimonial-controls">
          <button id="testimonial-prev" class="carousel-btn prev" aria-label="Testimonio anterior">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
          </button>
          <button id="testimonial-next" class="carousel-btn next" aria-label="Siguiente testimonio">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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

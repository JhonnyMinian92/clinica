<?php
// Datos para la página principal
$especialidades_destacadas = [
  [
    'titulo' => 'Cardiología',
    'descripcion' => 'Cuidado integral de su corazón con la tecnología más avanzada.',
    'icono' => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>'
  ],
  [
    'titulo' => 'Neurología',
    'descripcion' => 'Diagnóstico y tratamiento de enfermedades del sistema nervioso.',
    'icono' => '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>'
  ],
  [
    'titulo' => 'Pediatría',
    'descripcion' => 'Cuidamos la salud de los más pequeños con dedicación y cariño.',
    'icono' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>'
  ],
  [
    'titulo' => 'Oncología',
    'descripcion' => 'Tratamientos de vanguardia y apoyo continuo en la lucha contra el cáncer.',
    'icono' => '<path d="M12 2a2 2 0 0 0-2 2v2H8a2 2 0 0 0-2 2v2a2 2 0 0 0 2 2h2v2a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2v-2h2a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-2V4a2 2 0 0 0-2-2h-2z"></path>'
  ],
];

$doctores_destacados = [
  [
    'nombre' => 'Dr. Alejandro Vargas',
    'especialidad' => 'Cardiólogo Intervencionista',
    'experiencia' => '15+ años de experiencia',
    'foto' => 'public\assets\doctor_avatar.jpg',
  ],
  [
    'nombre' => 'Dra. Sofía Castillo',
    'especialidad' => 'Pediatra Neonatóloga',
    'experiencia' => '12+ años de experiencia',
    'foto' => 'public\assets\doctora_avatar.png',
  ],
  [
    'nombre' => 'Dr. Mateo Herrera',
    'especialidad' => 'Neurólogo Vascular',
    'experiencia' => '18+ años de experiencia',
    'foto' => 'public\assets\doctor_avatar.jpg',
  ],
  [
    'nombre' => 'Dra. Valentina Morales',
    'especialidad' => 'Dermatóloga Estética',
    'experiencia' => '10+ años de experiencia',
    'foto' => 'public\assets\doctora_avatar.png',
  ],
];

$servicios = [
  [
    'titulo' => 'Consulta Externa',
    'descripcion' => 'Acceso a especialistas de todas las áreas para diagnóstico y seguimiento.',
    'icono' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>'
  ],
  [
    'titulo' => 'Emergencias 24/7',
    'descripcion' => 'Atención inmediata y especializada para urgencias médicas a cualquier hora.',
    'icono' => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>'
  ],
  [
    'titulo' => 'Hospitalización',
    'descripcion' => 'Habitaciones cómodas y monitoreo constante para una recuperación segura.',
    'icono' => '<path d="M2 22h20"></path><path d="M4 14v-2a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v2"></path><path d="M4 14h16"></path><path d="M12 4v6"></path><path d="M10 6h4"></path>'
  ],
  [
    'titulo' => 'Centro Quirúrgico',
    'descripcion' => 'Quirófanos inteligentes equipados para procedimientos de alta complejidad.',
    'icono' => '<path d="M14.5 2.5a2.5 2.5 0 0 0-5 0V6H8a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-1.5V2.5z"></path><path d="M9 14h6"></path><path d="M12 11v6"></path>'
  ],
  [
    'titulo' => 'Diagnóstico por Imagen',
    'descripcion' => 'Resonancia magnética, tomografía y radiología digital de última generación.',
    'icono' => '<rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line>'
  ],
  [
    'titulo' => 'Laboratorio Clínico',
    'descripcion' => 'Análisis precisos y resultados rápidos para un diagnóstico confiable.',
    'icono' => '<path d="M14.5 2.5a2.5 2.5 0 0 0-5 0V6H8a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-1.5V2.5z"></path><path d="M10.5 13.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0z"></path><path d="M14.5 13.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0z"></path><path d="m11 17 2 2"></path>'
  ],
];

$testimonios = [
  [
    'texto' => 'El nivel de profesionalismo y la calidad humana del Dr. Vargas y su equipo son excepcionales. Me sentí seguro y bien atendido durante todo mi procedimiento coronario.',
    'autor' => 'Carlos Rodríguez',
    'detalle' => 'Paciente de Cardiología',
    'valoracion' => 5,
    'avatar' => 'public/assets/avatar.png'
  ],
  [
    'texto' => 'Llevar a mi hijo recién nacido a la Dra. Castillo fue la mejor decisión. Su paciencia y conocimiento nos dieron una tranquilidad inmensa en momentos de incertidumbre.',
    'autor' => 'Ana Sofía Paredes',
    'detalle' => 'Madre de paciente de Pediatría',
    'valoracion' => 5,
    'avatar' => 'public/assets/avatar.png'
  ],
  [
    'texto' => 'Después de años buscando una solución para mi problema de piel, la Dra. Morales me dio un diagnóstico preciso y un tratamiento que realmente funcionó. ¡Increíble!',
    'autor' => 'Javier Mendoza',
    'detalle' => 'Paciente de Dermatología',
    'valoracion' => 5,
    'avatar' => 'public/assets/avatar.png'
  ],
  [
    'texto' => 'La tecnología que utilizan para los diagnósticos por imagen es de otro nivel. Obtuve mis resultados de resonancia magnética rápidamente y con una claridad impresionante.',
    'autor' => 'Lucía Fernández',
    'detalle' => 'Paciente de Diagnóstico por Imagen',
    'valoracion' => 5,
    'avatar' => 'public/assets/avatar.png'
  ]
];

include __DIR__ . '/public/shared/header/header.php';
?>

<main id="main-content">

  <!-- ============================ -->
  <!-- ========= HERO ============= -->
  <!-- ============================ -->
  <section id="hero" class="hero lazy-bg skeleton skeleton-dark" data-bg="public/assets/banner.jpg">
    <div class="hero-overlay"></div>
    <div class="container">
      <div class="hero-content animate-on-scroll fade-in-up">
        <h1 class="hero-title">Medicina de Vanguardia, Cuidado Humano</h1>
        <p class="hero-subtitle">Combinamos la más alta tecnología con un equipo de especialistas comprometidos con su
          salud y bienestar.</p>
        <div class="hero-cta">
          <a href="pages/reservations/" class="btn btn-primary btn-lg">Agendar una Cita</a>
          <a href="pages/doctors/" class="btn btn-secondary btn-lg">Encontrar un Doctor</a>
        </div>
      </div>
    </div>
  </section>

  <!-- ============================ -->
  <!-- ===== POR QUÉ ELEGIRNOS ==== -->
  <!-- ============================ -->
  <section id="about" class="section">
    <div class="container">
      <div class="section-header">
        <h2 class="section-title animate-on-scroll fade-in-up">Su Salud, Nuestra Prioridad</h2>
        <p class="section-subtitle animate-on-scroll fade-in-up" style="--animation-delay: 100ms;">
          Descubra por qué somos la elección de confianza para miles de pacientes.
        </p>
      </div>
      <div class="grid-3-col">
        <div class="card animate-on-scroll fade-in-up" style="--animation-delay: 200ms;">
          <div class="card-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
              <line x1="8" y1="21" x2="16" y2="21"></line>
              <line x1="12" y1="17" x2="12" y2="21"></line>
            </svg>
          </div>
          <h3 class="card-title">Tecnología de Punta</h3>
          <p class="card-text">Equipos de última generación para diagnósticos precisos y tratamientos efectivos.</p>
        </div>
        <div class="card animate-on-scroll fade-in-up" style="--animation-delay: 300ms;">
          <div class="card-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
          </div>
          <h3 class="card-title">Especialistas de Élite</h3>
          <p class="card-text">Un equipo médico de renombre, con amplia experiencia y reconocimiento internacional.
          </p>
        </div>
        <div class="card animate-on-scroll fade-in-up" style="--animation-delay: 400ms;">
          <div class="card-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <path
                d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z">
              </path>
            </svg>
          </div>
          <h3 class="card-title">Atención Humana</h3>
          <p class="card-text">Cuidado cálido y personalizado, porque entendemos que detrás de cada paciente hay una
            historia.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ============================ -->
  <!-- ========= ESTADÍSTICAS ========= -->
  <!-- ============================ -->
  <section class="stats-section section">
    <div class="container">
      <div class="grid-4-col">
        <div class="stat-item animate-on-scroll fade-in-up" style="--animation-delay: 0ms;">
          <span class="stat-number" data-target="50000">0</span><span class="stat-suffix">+</span>
          <p class="stat-label">Pacientes Atendidos</p>
        </div>
        <div class="stat-item animate-on-scroll fade-in-up" style="--animation-delay: 100ms;">
          <span class="stat-number" data-target="25">0</span><span class="stat-suffix">+</span>
          <p class="stat-label">Años de Experiencia</p>
        </div>
        <div class="stat-item animate-on-scroll fade-in-up" style="--animation-delay: 200ms;">
          <span class="stat-number" data-target="100">0</span><span class="stat-suffix">+</span>
          <p class="stat-label">Especialistas Certificados</p>
        </div>
        <div class="stat-item animate-on-scroll fade-in-up" style="--animation-delay: 300ms;">
          <span class="stat-number" data-target="98">0</span><span class="stat-suffix">%</span>
          <p class="stat-label">Tasa de Satisfacción</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ============================ -->
  <!-- ========= SERVICIOS ======== -->
  <!-- ============================ -->
  <section id="services" class="section section-light">
    <div class="container">
      <div class="section-header">
        <h2 class="section-title animate-on-scroll fade-in-up">Nuestros Servicios</h2>
        <p class="section-subtitle animate-on-scroll fade-in-up" style="--animation-delay: 100ms;">
          Ofrecemos una gama completa de servicios médicos para cuidar de usted en cada etapa.
        </p>
      </div>
      <div class="grid-3-col">
        <?php foreach ($servicios as $i => $servicio): ?>
          <div class="service-card animate-on-scroll fade-in-up"
            style="--animation-delay: <?php echo ($i * 100) + 200; ?>ms;">
            <div class="service-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                aria-hidden="true">
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

  <!-- ============================ -->
  <!-- ====== ESPECIALIDADES ====== -->
  <!-- ============================ -->
  <section id="specialties" class="section section-light">
    <div class="container">
      <div class="section-header">
        <h2 class="section-title animate-on-scroll fade-in-up">Nuestras Especialidades</h2>
        <p class="section-subtitle animate-on-scroll fade-in-up" style="--animation-delay: 100ms;">
          Cobertura médica integral para todas sus necesidades de salud.
        </p>
      </div>
      <div class="grid-4-col">
        <?php foreach ($especialidades_destacadas as $i => $especialidad): ?>
          <div class="specialty-card animate-on-scroll fade-in-up"
            style="--animation-delay: <?php echo ($i * 100) + 200; ?>ms;">
            <div class="specialty-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <?php echo $especialidad['icono']; ?>
              </svg>
            </div>
            <h3 class="specialty-title"><?php echo htmlspecialchars($especialidad['titulo']); ?></h3>
            <p class="specialty-text"><?php echo htmlspecialchars($especialidad['descripcion']); ?></p>
            <a href="pages/specialties/?especialidad=<?php echo urlencode($especialidad['titulo']); ?>"
              class="specialty-link">Saber más</a>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="section-footer animate-on-scroll fade-in-up">
        <a href="pages/specialties/" class="btn btn-primary btn-lg">Ver Todas las Especialidades</a>
      </div>
    </div>
  </section>

  <!-- ============================ -->
  <!-- ========= DOCTORES ========= -->
  <!-- ============================ -->
  <section id="doctors" class="section">
    <div class="container">
      <div class="section-header">
        <h2 class="section-title animate-on-scroll fade-in-up">Conozca a Nuestro Equipo</h2>
        <p class="section-subtitle animate-on-scroll fade-in-up" style="--animation-delay: 100ms;">Profesionales que
          combinan conocimiento, experiencia y vocación.</p>
      </div>
      <div class="grid-4-col">
        <?php foreach ($doctores_destacados as $i => $doctor): ?>
          <div class="doctor-card animate-on-scroll fade-in-up"
            style="--animation-delay: <?php echo ($i * 100) + 200; ?>ms;">
            <div class="doctor-photo skeleton">
              <img data-src="<?php echo htmlspecialchars($doctor['foto']); ?>"
                alt="Fotografía de <?php echo htmlspecialchars($doctor['nombre']); ?>" loading="lazy" class="lazy-img">
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

  <!-- ============================ -->
  <!-- ======== TESTIMONIOS ========= -->
  <!-- ============================ -->
  <section id="testimonials" class="section section-light">
    <div class="container">
      <div class="section-header">
        <h2 class="section-title animate-on-scroll fade-in-up">La Voz de Nuestros Pacientes</h2>
        <p class="section-subtitle animate-on-scroll fade-in-up" style="--animation-delay: 100ms;">
          Historias reales de quienes han confiado su salud en nuestras manos.
        </p>
      </div>
      <div id="testimonial-carousel" class="testimonial-carousel animate-on-scroll fade-in-up"
        style="--animation-delay: 200ms;">
        <div class="testimonial-wrapper">
          <?php foreach ($testimonios as $testimonio): ?>
            <div class="testimonial-slide">
              <img src="<?php echo htmlspecialchars($testimonio['avatar']); ?>"
                alt="Avatar de <?php echo htmlspecialchars($testimonio['autor']); ?>" class="testimonial-avatar">
              <div class="testimonial-rating"
                aria-label="Valoración: <?php echo $testimonio['valoracion']; ?> de 5 estrellas">
                <?php echo str_repeat('★', $testimonio['valoracion']) . str_repeat('☆', 5 - $testimonio['valoracion']); ?>
              </div>
              <p class="testimonial-text">"<?php echo htmlspecialchars($testimonio['texto']); ?>"</p>
              <p class="testimonial-author"><?php echo htmlspecialchars($testimonio['autor']); ?></p>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="testimonial-controls">
          <button id="testimonial-prev" class="carousel-btn prev" aria-label="Testimonio anterior">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
          </button>
          <button id="testimonial-next" class="carousel-btn next" aria-label="Siguiente testimonio">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
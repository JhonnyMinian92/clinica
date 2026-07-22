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
    'foto' => 'https://images.unsplash.com/photo-1622253692010-333f2da60710?q=80&w=400&auto=format&fit=crop',
  ],
  [
    'nombre' => 'Dra. Sofía Castillo',
    'especialidad' => 'Pediatra Neonatóloga',
    'experiencia' => '12+ años de experiencia',
    'foto' => 'https://images.unsplash.com/photo-1559839734-2b71ea197ec2?q=80&w=400&auto=format&fit=crop',
  ],
  [
    'nombre' => 'Dr. Mateo Herrera',
    'especialidad' => 'Neurólogo Vascular',
    'experiencia' => '18+ años de experiencia',
    'foto' => 'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?q=80&w=400&auto=format&fit=crop',
  ],
  [
    'nombre' => 'Dra. Valentina Morales',
    'especialidad' => 'Dermatóloga Estética',
    'experiencia' => '10+ años de experiencia',
    'foto' => 'https://images.unsplash.com/photo-1537368910025-700350fe46c7?q=80&w=400&auto=format&fit=crop',
  ],
];

$testimonios = [
  [
    'texto' => 'El nivel de profesionalismo y la calidad humana del Dr. Vargas y su equipo son excepcionales. Me sentí seguro y bien atendido durante todo mi procedimiento coronario.',
    'autor' => 'Carlos Rodríguez',
    'detalle' => 'Paciente de Cardiología',
    'valoracion' => 5,
    'avatar' => 'https://images.unsplash.com/photo-1560250097-0b93528c311a?q=80&w=200&auto=format&fit=crop'
  ],
  [
    'texto' => 'Llevar a mi hijo recién nacido a la Dra. Castillo fue la mejor decisión. Su paciencia y conocimiento nos dieron una tranquilidad inmensa en momentos de incertidumbre.',
    'autor' => 'Ana Sofía Paredes',
    'detalle' => 'Madre de paciente de Pediatría',
    'valoracion' => 5,
    'avatar' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?q=80&w=200&auto=format&fit=crop'
  ],
  [
    'texto' => 'Después de años buscando una solución para mi problema de piel, la Dra. Morales me dio un diagnóstico preciso y un tratamiento que realmente funcionó. ¡Increíble!',
    'autor' => 'Javier Mendoza',
    'detalle' => 'Paciente de Dermatología',
    'valoracion' => 5,
    'avatar' => 'https://images.unsplash.com/photo-1557862921-37829c790f19?q=80&w=200&auto=format&fit=crop'
  ],
  [
    'texto' => 'La tecnología que utilizan para los diagnósticos por imagen es de otro nivel. Obtuve mis resultados de resonancia magnética rápidamente y con una claridad impresionante.',
    'autor' => 'Lucía Fernández',
    'detalle' => 'Paciente de Diagnóstico por Imagen',
    'valoracion' => 5,
    'avatar' => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?q=80&w=200&auto=format&fit=crop'
  ]
];
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Clínica Estándar | Medicina de Vanguardia y Cuidado Humano</title>
  <meta name="description"
    content="Clínica Estándar: líder en atención médica especializada, con tecnología de punta y un equipo de profesionales comprometidos con su bienestar.">
  <link rel="canonical" href="https://www.clinicaestandar.com">

  <!-- Favicon, Fonts, Global CSS -->
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="public/css/style.css">
</head>

<body>

  <!-- Header -->
  <header class="main-header" id="main-header">
    <div class="container header-container">
      <a href="" class="logo" aria-label="Página de inicio de Clínica Estándar">
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
          <li><a href="#specialties" class="nav-link">Especialidades</a></li>
          <li><a href="#services" class="nav-link">Servicios</a></li>
          <li><a href="#doctors" class="nav-link">Doctores</a></li>
          <li><a href="#about" class="nav-link">Nosotros</a></li>
        </ul>
      </nav>
      <div class="header-actions">
        <a href="pages/reservations/" class="btn btn-primary">Agendar Cita</a>
        <button class="mobile-nav-toggle" id="mobile-nav-toggle" aria-controls="main-nav" aria-expanded="false"
          aria-label="Abrir menú de navegación">
          <span class="hamburger-line"></span><span class="hamburger-line"></span><span class="hamburger-line"></span>
        </button>
      </div>
    </div>
  </header>

  <main id="main-content">

    <!-- ============================ -->
    <!-- ========= HERO ============= -->
    <!-- ============================ -->
    <section id="hero" class="hero lazy-bg skeleton skeleton-dark"
      data-bg="https://images.unsplash.com/photo-1584982751601-C59f3299e690?q=80&w=2070&auto=format&fit=crop">
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
    <!-- ========= SERVICIOS ======== -->
    <!-- ============================ -->
    <section id="services" class="section">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title animate-on-scroll fade-in-up">Nuestros Servicios</h2>
          <p class="section-subtitle animate-on-scroll fade-in-up" style="--animation-delay: 100ms;">
            Atención médica completa para usted y su familia. (Sección en desarrollo)
          </p>
        </div>
        <!-- El contenido de las tarjetas de servicios se añadirá aquí en una futura tarea. -->
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
              <div class="doctor-info">
                <h3 class="doctor-name"><?php echo htmlspecialchars($doctor['nombre']); ?></h3>
                <p class="doctor-specialty"><?php echo htmlspecialchars($doctor['especialidad']); ?></p>
                <p class="doctor-exp"><?php echo htmlspecialchars($doctor['experiencia']); ?></p>
                <a href="pages/doctors/" class="btn btn-tertiary">Ver Perfil</a>
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

  <!-- Footer -->
  <footer class="main-footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-col">
          <a href="/" class="logo footer-logo" aria-label="Página de inicio de Clínica Estándar">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
              class="logo-svg">
              <path d="M12 5C12 5 9 2 6 2C3 2 2 4 2 6C2 9 5 12 12 19C19 12 22 9 22 6C22 4 21 2 18 2C15 2 12 5 12 5Z"
                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" opacity="0.4" />
              <path d="M12 8V16M8 12H16" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round" />
            </svg>
            <span class="clinic-name-header">Clínica Estándar</span>
          </a>
          <p>Medicina de vanguardia con un enfoque humano y personalizado.</p>
        </div>
        <div class="footer-col">
          <h4 class="footer-title">Navegación</h4>
          <ul class="footer-list">
            <li><a href="#about">Nosotros</a></li>
            <li><a href="pages/specialties/">Especialidades</a></li>
            <li><a href="pages/doctors/">Doctores</a></li>
            <li><a href="pages/reservations/">Agendar Cita</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4 class="footer-title">Contacto</h4>
          <ul class="footer-list contact-info">
            <li>Av. Principal 123, Ciudad Capital</li>
            <li>(123) 456-7890</li>
            <li><span class="emergency-info">Emergencias: (123) 456-7899</span></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4 class="footer-title">Síganos</h4>
          <div class="social-links">
            <a href="#" aria-label="Facebook"><svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
              </svg></a>
            <a href="#" aria-label="Instagram"><svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
              </svg></a>
            <a href="#" aria-label="LinkedIn"><svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path>
                <rect x="2" y="9" width="4" height="12"></rect>
                <circle cx="4" cy="4" r="2"></circle>
              </svg></a>
          </div>
        </div>
      </div>
      <div class="footer-bottom">
        <p>&copy; <?php echo date("Y"); ?> Clínica Estándar. Todos los derechos reservados.</p>
        <ul class="footer-legal-links">
          <li><a href="#">Aviso Legal</a></li>
          <li><a href="#">Política de Privacidad</a></li>
        </ul>
      </div>
    </div>
  </footer>

  <!-- JavaScript -->
  <script src="public/js/app.js" defer></script>

</body>

</html>
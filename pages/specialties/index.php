<?php
// Simulación de una base de datos de especialidades
$especialidades = [
  [
    'id' => 'cardiologia',
    'titulo' => 'Cardiología',
    'descripcion' => 'Cuidado integral de su corazón con la tecnología más avanzada para diagnóstico y tratamiento.',
    'icono' => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>'
  ],
  [
    'id' => 'neurologia',
    'titulo' => 'Neurología',
    'descripcion' => 'Diagnóstico y tratamiento de enfermedades del sistema nervioso central y periférico.',
    'icono' => '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>'
  ],
  [
    'id' => 'pediatria',
    'titulo' => 'Pediatría',
    'descripcion' => 'Cuidamos la salud de los más pequeños con dedicación, cariño y un enfoque preventivo.',
    'icono' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>'
  ],
  [
    'id' => 'oncologia',
    'titulo' => 'Oncología',
    'descripcion' => 'Tratamientos de vanguardia y apoyo continuo en la lucha integral contra el cáncer.',
    'icono' => '<path d="M12 2a2 2 0 0 0-2 2v2H8a2 2 0 0 0-2 2v2a2 2 0 0 0 2 2h2v2a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2v-2h2a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-2V4a2 2 0 0 0-2-2h-2z"></path>'
  ],
  [
    'id' => 'dermatologia',
    'titulo' => 'Dermatología',
    'descripcion' => 'Soluciones avanzadas para la salud de su piel, cabello y uñas, con enfoque estético y clínico.',
    'icono' => '<path d="M12 12.5a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/><path d="M12 2a10 10 0 0 0-9.94 9.34c.06 5.44 4.44 9.82 9.88 9.88a10 10 0 1 0 0-19.22zm0 17.22c-4.3 0-7.8-3.5-7.8-7.8s3.5-7.8 7.8-7.8 7.8 3.5 7.8 7.8-3.5 7.8-7.8 7.8z"/>'
  ],
  [
    'id' => 'ginecologia',
    'titulo' => 'Ginecología',
    'descripcion' => 'Atención integral de la salud femenina en todas las etapas de la vida, desde la adolescencia a la menopausia.',
    'icono' => '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v4h4v2h-4v4h-2v-4H7v-2h4V7z"/>'
  ],
  [
    'id' => 'traumatologia',
    'titulo' => 'Traumatología',
    'descripcion' => 'Especialistas en lesiones del aparato locomotor, desde fracturas hasta medicina deportiva.',
    'icono' => '<path d="M12 2L4 5v6.09c0 5.05 3.41 9.76 8 10.91 4.59-1.15 8-5.86 8-10.91V5l-8-3zm-1 14.09V13h2v3.09c-.33.05-.66.09-1 .09s-.67-.04-1-.09zm1-10.09c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5-1.5-.67-1.5-1.5.67-1.5 1.5-1.5z"/>'
  ],
  [
    'id' => 'psiquiatria',
    'titulo' => 'Psiquiatría',
    'descripcion' => 'Cuidado de la salud mental con terapias modernas y un enfoque humano y confidencial.',
    'icono' => '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/><path d="M15.5 12c0-1.93-1.57-3.5-3.5-3.5S8.5 10.07 8.5 12s1.57 3.5 3.5 3.5 3.5-1.57 3.5-3.5zm-3.5-2c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5-1.5-.67-1.5-1.5.67-1.5 1.5-1.5z"/>'
  ]
];

require_once __DIR__ . '/../../api/data.php';

$especialidades = clinica_public_specialties_for_catalog();

// Definir assets específicos para esta página.
// El header y footer compartidos los usarán para cargar los archivos correctos.
$page_specific_css = 'specialties.css';
$page_specific_js = 'specialties.js';

// Incluimos el header reutilizable.
// Usamos __DIR__ para construir una ruta absoluta al archivo, lo que hace el código más robusto.
include __DIR__ . '/../../public/shared/header/header.php';
?>

<main id="main-content">
  <!-- Page Header -->
  <section class="page-header">
    <div class="container">
      <h1 class="page-title">Nuestras Especialidades</h1>
      <p class="page-subtitle">
        Ofrecemos una cobertura médica integral con un equipo de especialistas de primer nivel y tecnología de
        vanguardia.
      </p>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <!-- Search Filter -->
      <div class="filter-wrapper">
        <div class="filter-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
        </div>
        <input type="search" id="specialty-search" class="filter-input"
          placeholder="Buscar por nombre de especialidad..." aria-label="Buscar especialidad">
      </div>

      <div class="grid-4-col" id="specialties-grid">
        <?php foreach ($especialidades as $i => $especialidad): ?>
          <div class="specialty-catalog-card animate-on-scroll fade-in-up"
            style="--animation-delay: <?php echo ($i * 50); ?>ms;" data-title="<?php echo strtolower(htmlspecialchars($especialidad['titulo'])); ?>">
            <div class="specialty-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <?php echo $especialidad['icono']; ?>
              </svg>
            </div>
            <h3 class="specialty-title"><?php echo htmlspecialchars($especialidad['titulo']); ?></h3>
            <p class="specialty-text"><?php echo htmlspecialchars($especialidad['descripcion']); ?></p>
            <a href="/clinica/pages/reservations/?specialty=<?php echo urlencode($especialidad['id']); ?>"
              class="btn btn-primary btn-sm">Agendar Cita</a>
          </div>
        <?php endforeach; ?>

      </div>
      <div id="no-results-message" class="no-results-message">
        <p>No se encontraron especialidades que coincidan con su búsqueda.</p>
      </div>
    </div>
  </section>
</main>

<?php
// Incluimos el footer reutilizable
include __DIR__ . '/../../public/shared/footer/footer.php';
?>

<?php
// Simulación de una base de datos de doctores
$doctores = [
  [
    'id' => 'doc1',
    'nombre' => 'Dr. Alejandro Vargas',
    'foto' => 'https://images.unsplash.com/photo-1622253692010-333f2da60710?q=80&w=400&auto=format&fit=crop',
    'especialidad' => 'Cardiología',
    'subespecialidad' => 'Cardiología Intervencionista',
    'experiencia_anos' => 15,
    'universidad' => 'Universidad de Buenos Aires',
    'reconocimientos' => ['Miembro Titular de la SAC'],
    'idiomas' => ['Español', 'Inglés'],
    'valoracion' => 5,
    'pacientes' => 3200,
    'disponibilidad' => 'Mañanas'
  ],
  [
    'id' => 'doc2',
    'nombre' => 'Dra. Sofía Castillo',
    'foto' => 'https://images.unsplash.com/photo-1559839734-2b71ea197ec2?q=80&w=400&auto=format&fit=crop',
    'especialidad' => 'Pediatría',
    'subespecialidad' => 'Neonatología',
    'experiencia_anos' => 12,
    'universidad' => 'Universidad Nacional de Colombia',
    'reconocimientos' => ['Certificada por la SCP'],
    'idiomas' => ['Español', 'Portugués'],
    'valoracion' => 5,
    'pacientes' => 2800,
    'disponibilidad' => 'Tardes'
  ],
  [
    'id' => 'doc3',
    'nombre' => 'Dr. Mateo Herrera',
    'foto' => 'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?q=80&w=400&auto=format&fit=crop',
    'especialidad' => 'Neurología',
    'subespecialidad' => 'Neurofisiología Clínica',
    'experiencia_anos' => 18,
    'universidad' => 'Universidad de Chile',
    'reconocimientos' => ['Fellowship en Clínica Mayo'],
    'idiomas' => ['Español', 'Inglés'],
    'valoracion' => 5,
    'pacientes' => 4100,
    'disponibilidad' => 'Completa'
  ],
  [
    'id' => 'doc4',
    'nombre' => 'Dra. Valentina Morales',
    'foto' => 'https://images.unsplash.com/photo-1537368910025-700350fe46c7?q=80&w=400&auto=format&fit=crop',
    'especialidad' => 'Dermatología',
    'subespecialidad' => 'Dermatología Cosmética',
    'experiencia_anos' => 10,
    'universidad' => 'Universidad Autónoma de México',
    'reconocimientos' => ['Miembro de la AAD'],
    'idiomas' => ['Español'],
    'valoracion' => 4,
    'pacientes' => 3500,
    'disponibilidad' => 'Tardes'
  ],
  [
    'id' => 'doc5',
    'nombre' => 'Dr. Lucas Jiménez',
    'foto' => 'https://plus.unsplash.com/premium_photo-1661764878654-3d0fc6eefd5f?q=80&w=400&auto=format&fit=crop',
    'especialidad' => 'Traumatología',
    'subespecialidad' => 'Cirugía de Rodilla y Hombro',
    'experiencia_anos' => 20,
    'universidad' => 'Universidad Complutense de Madrid',
    'reconocimientos' => [],
    'idiomas' => ['Español', 'Inglés'],
    'valoracion' => 5,
    'pacientes' => 5000,
    'disponibilidad' => 'Mañanas'
  ],
  [
    'id' => 'doc6',
    'nombre' => 'Dra. Camila Ríos',
    'foto' => 'https://images.unsplash.com/photo-1594824476967-48c8b964273f?q=80&w=400&auto=format&fit=crop',
    'especialidad' => 'Ginecología',
    'subespecialidad' => 'Medicina Reproductiva',
    'experiencia_anos' => 14,
    'universidad' => 'Universidad de São Paulo',
    'reconocimientos' => ['Certificación en Laparoscopia'],
    'idiomas' => ['Español', 'Portugués', 'Inglés'],
    'valoracion' => 5,
    'pacientes' => 3900,
    'disponibilidad' => 'Completa'
  ],
  [
    'id' => 'doc7',
    'nombre' => 'Dr. Sebastián Peña',
    'foto' => 'https://images.unsplash.com/photo-1625498542638-85712b6b71a3?q=80&w=400&auto=format&fit=crop',
    'especialidad' => 'Oncología',
    'subespecialidad' => 'Oncología Torácica',
    'experiencia_anos' => 22,
    'universidad' => 'Johns Hopkins University',
    'reconocimientos' => ['Premio Nacional de Medicina'],
    'idiomas' => ['Español', 'Inglés'],
    'valoracion' => 5,
    'pacientes' => 2100,
    'disponibilidad' => 'Tardes'
  ],
  [
    'id' => 'doc8',
    'nombre' => 'Dra. Isabella Gómez',
    'foto' => 'https://images.unsplash.com/photo-1607990281513-2c110a25bd8c?q=80&w=400&auto=format&fit=crop',
    'especialidad' => 'Psiquiatría',
    'subespecialidad' => 'Psicoterapia',
    'experiencia_anos' => 9,
    'universidad' => 'Universidad de Barcelona',
    'reconocimientos' => [],
    'idiomas' => ['Español', 'Catalán'],
    'valoracion' => 4,
    'pacientes' => 1500,
    'disponibilidad' => 'Mañanas'
  ],
];

// Extraer datos para los filtros
$especialidades_filtro = array_unique(array_column($doctores, 'especialidad'));
$idiomas_filtro = array_unique(array_merge(...array_column($doctores, 'idiomas')));
$disponibilidades_filtro = array_unique(array_column($doctores, 'disponibilidad'));
sort($especialidades_filtro);
sort($idiomas_filtro);
sort($disponibilidades_filtro);
?>
<?php
include __DIR__ . '/../../public/shared/header/header.php';
?>

<main id="main-content">

  <!-- Page Header -->
  <section class="page-header">
    <div class="container">
      <h1 class="page-title">Nuestro Equipo Médico</h1>
      <p class="page-subtitle">Profesionales de élite comprometidos con su salud. Encuentre a su especialista ideal.
      </p>
    </div>
  </section>

  <!-- Doctors Catalog -->
  <section class="section" id="doctors-catalog">
    <div class="container">
      <!-- Filters -->
      <div class="doctors-filters" id="doctors-filters">
        <div class="filter-group animate-on-scroll fade-in-up" style="--animation-delay: 100ms;">
          <span class="filter-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
          </span>
          <input type="search" id="name-filter" placeholder="Buscar por nombre..." class="filter-input"
            style="padding-left: 40px;">
        </div>
        <div class="filter-group animate-on-scroll fade-in-up" style="--animation-delay: 200ms;">
          <span class="filter-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
              <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
          </span>
          <select id="specialty-filter" class="filter-select">
            <option value="">Todas las especialidades</option>
            <?php foreach ($especialidades_filtro as $item): ?>
              <option value="<?php echo htmlspecialchars($item); ?>"><?php echo htmlspecialchars($item); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="filter-group animate-on-scroll fade-in-up" style="--animation-delay: 300ms;">
          <span class="filter-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
          </span>
          <select id="availability-filter" class="filter-select">
            <option value="">Toda disponibilidad</option>
            <?php foreach ($disponibilidades_filtro as $item): ?>
              <option value="<?php echo htmlspecialchars($item); ?>"><?php echo htmlspecialchars($item); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="filter-group animate-on-scroll fade-in-up" style="--animation-delay: 400ms;">
          <span class="filter-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <path d="M2 12h20"></path>
              <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z">
              </path>
            </svg>
          </span>
          <select id="language-filter" class="filter-select">
            <option value="">Todos los idiomas</option>
            <?php foreach ($idiomas_filtro as $item): ?>
              <option value="<?php echo htmlspecialchars($item); ?>"><?php echo htmlspecialchars($item); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="animate-on-scroll fade-in-up" style="--animation-delay: 500ms;">
          <button id="filter-reset-btn" class="filter-reset-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
              style="vertical-align: middle; margin-right: 4px;">
              <polyline points="23 4 23 10 17 10"></polyline>
              <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
            </svg>
            Limpiar
          </button>
        </div>
      </div>

      <!-- Doctors Grid -->
      <div class="doctors-grid" id="doctors-grid">
        <?php foreach ($doctores as $doctor): ?>
          <div class="doctor-profile-card animate-on-scroll fade-in-up"
            data-name="<?php echo htmlspecialchars(strtolower($doctor['nombre'])); ?>"
            data-specialty="<?php echo htmlspecialchars($doctor['especialidad']); ?>"
            data-availability="<?php echo htmlspecialchars($doctor['disponibilidad']); ?>"
            data-languages="<?php echo htmlspecialchars(implode(',', $doctor['idiomas'])); ?>">
            <div class="doctor-profile-photo-wrapper">
              <img src="<?php echo htmlspecialchars($doctor['foto']); ?>"
                alt="Foto de <?php echo htmlspecialchars($doctor['nombre']); ?>" class="doctor-profile-photo">
            </div>
            <div class="doctor-profile-content">
              <div class="doctor-profile-header">
                <h3 class="doctor-profile-name"><?php echo htmlspecialchars($doctor['nombre']); ?></h3>
                <div class="doctor-profile-rating"
                  aria-label="Valoración: <?php echo $doctor['valoracion']; ?> de 5 estrellas">
                  <?php echo str_repeat('★', $doctor['valoracion']) . str_repeat('☆', 5 - $doctor['valoracion']); ?>
                </div>
              </div>
              <p class="doctor-profile-specialty"><?php echo htmlspecialchars($doctor['especialidad']); ?> |
                <strong><?php echo htmlspecialchars($doctor['subespecialidad']); ?></strong>
              </p>
              <div class="doctor-profile-details">
                <div class="detail-item">
                  <span class="detail-label">Experiencia</span>
                  <span class="detail-value"><?php echo $doctor['experiencia_anos']; ?> años</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Pacientes</span>
                  <span class="detail-value"><?php echo number_format($doctor['pacientes']); ?>+</span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Universidad</span>
                  <span class="detail-value"><?php echo htmlspecialchars($doctor['universidad']); ?></span>
                </div>
              </div>
              <div class="doctor-profile-tags">
                <?php foreach ($doctor['idiomas'] as $idioma): ?>
                  <span class="tag language-tag"><?php echo htmlspecialchars($idioma); ?></span>
                <?php endforeach; ?>
                <?php foreach ($doctor['reconocimientos'] as $rec): ?>
                  <span class="tag recognition-tag"><?php echo htmlspecialchars($rec); ?></span>
                <?php endforeach; ?>
              </div>
              <div class="doctor-profile-footer">
                <span
                  class="availability-tag availability-<?php echo strtolower(htmlspecialchars($doctor['disponibilidad'])); ?>">
                  Disponible: <strong><?php echo htmlspecialchars($doctor['disponibilidad']); ?></strong>
                </span>
                <a href="../reservations/?doctor_id=<?php echo htmlspecialchars($doctor['id']); ?>"
                  class="btn btn-primary">Agendar Cita</a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <div id="no-results-message" class="no-results-message" style="display: none;">
        <p>No se encontraron especialistas que coincidan con su búsqueda.</p>
      </div>
    </div>
  </section>

</main>

<?php
include __DIR__ . '/../../public/shared/footer/footer.php';
?>
<?php
$dias_semana = [
  1 => 'Lunes',
  2 => 'Martes',
  3 => 'Miércoles',
  4 => 'Jueves',
  5 => 'Viernes',
  6 => 'Sábado',
  7 => 'Domingo',
];
$dia_actual = $dias_semana[(int) date('N')];

// Simulación de una base de datos de doctores
$doctores = [
  [
    'id' => 'doc1',
    'nombre' => 'Dr. Alejandro Vargas',
    'foto' => '../../public/assets/avatar.png',
    'especialidad' => 'Cardiología',
    'subespecialidad' => 'Cardiología Intervencionista',
    'experiencia_anos' => 15,
    'universidad' => 'Universidad de Buenos Aires',
    'reconocimientos' => ['Miembro Titular de la SAC'],
    'idiomas' => ['Español', 'Inglés'],
    'valoracion' => 5,
    'pacientes' => 3200,
    'horario_semanal' => ['Lunes' => 'Mañana', 'Miércoles' => 'Mañana', 'Viernes' => 'Tarde']
  ],
  [
    'id' => 'doc2',
    'nombre' => 'Dra. Sofía Castillo',
    'foto' => '../../public/assets/avatar.png',
    'especialidad' => 'Pediatría',
    'subespecialidad' => 'Neonatología',
    'experiencia_anos' => 12,
    'universidad' => 'Universidad Nacional de Colombia',
    'reconocimientos' => ['Certificada por la SCP'],
    'idiomas' => ['Español', 'Portugués'],
    'valoracion' => 5,
    'pacientes' => 2800,
    'horario_semanal' => ['Martes' => 'Tarde', 'Jueves' => 'Tarde', 'Sábado' => 'Mañana']
  ],
  [
    'id' => 'doc3',
    'nombre' => 'Dr. Mateo Herrera',
    'foto' => '../../public/assets/avatar.png',
    'especialidad' => 'Neurología',
    'subespecialidad' => 'Neurofisiología Clínica',
    'experiencia_anos' => 18,
    'universidad' => 'Universidad de Chile',
    'reconocimientos' => ['Fellowship en Clínica Mayo'],
    'idiomas' => ['Español', 'Inglés'],
    'valoracion' => 5,
    'pacientes' => 4100,
    'horario_semanal' => ['Lunes' => 'Completo', 'Martes' => 'Completo', 'Miércoles' => 'Completo', 'Jueves' => 'Completo', 'Viernes' => 'Completo']
  ],
  [
    'id' => 'doc4',
    'nombre' => 'Dra. Valentina Morales',
    'foto' => '../../public/assets/avatar.png',
    'especialidad' => 'Dermatología',
    'subespecialidad' => 'Dermatología Cosmética',
    'experiencia_anos' => 10,
    'universidad' => 'Universidad Autónoma de México',
    'reconocimientos' => ['Miembro de la AAD'],
    'idiomas' => ['Español'],
    'valoracion' => 4,
    'pacientes' => 3500,
    'horario_semanal' => ['Lunes' => 'Tarde', 'Miércoles' => 'Tarde', 'Viernes' => 'Tarde']
  ],
  [
    'id' => 'doc5',
    'nombre' => 'Dr. Lucas Jiménez',
    'foto' => '../../public/assets/avatar.png',
    'especialidad' => 'Traumatología',
    'subespecialidad' => 'Cirugía de Rodilla y Hombro',
    'experiencia_anos' => 20,
    'universidad' => 'Universidad Complutense de Madrid',
    'reconocimientos' => [],
    'idiomas' => ['Español', 'Inglés'],
    'valoracion' => 5,
    'pacientes' => 5000,
    'horario_semanal' => ['Martes' => 'Mañana', 'Jueves' => 'Mañana']
  ],
  [
    'id' => 'doc6',
    'nombre' => 'Dra. Camila Ríos',
    'foto' => '../../public/assets/avatar.png',
    'especialidad' => 'Ginecología',
    'subespecialidad' => 'Medicina Reproductiva',
    'experiencia_anos' => 14,
    'universidad' => 'Universidad de São Paulo',
    'reconocimientos' => ['Certificación en Laparoscopia'],
    'idiomas' => ['Español', 'Portugués', 'Inglés'],
    'valoracion' => 5,
    'pacientes' => 3900,
    'horario_semanal' => ['Lunes' => 'Tarde', 'Miércoles' => 'Completo', 'Viernes' => 'Mañana']
  ],
  [
    'id' => 'doc7',
    'nombre' => 'Dr. Sebastián Peña',
    'foto' => '../../public/assets/avatar.png',
    'especialidad' => 'Oncología',
    'subespecialidad' => 'Oncología Torácica',
    'experiencia_anos' => 22,
    'universidad' => 'Johns Hopkins University',
    'reconocimientos' => ['Premio Nacional de Medicina'],
    'idiomas' => ['Español', 'Inglés'],
    'valoracion' => 5,
    'pacientes' => 2100,
    'horario_semanal' => ['Martes' => 'Tarde', 'Jueves' => 'Tarde']
  ],
  [
    'id' => 'doc8',
    'nombre' => 'Dra. Isabella Gómez',
    'foto' => '../../public/assets/avatar.png',
    'especialidad' => 'Psiquiatría',
    'subespecialidad' => 'Psicoterapia',
    'experiencia_anos' => 9,
    'universidad' => 'Universidad de Barcelona',
    'reconocimientos' => [],
    'idiomas' => ['Español', 'Catalán'],
    'valoracion' => 4,
    'pacientes' => 1500,
    'horario_semanal' => ['Lunes' => 'Mañana', 'Jueves' => 'Mañana']
  ],
];

require_once __DIR__ . '/../../api/data.php';

$dia_actual = (string) date('N');
$doctores = clinica_public_doctors_for_catalog();

// Extraer datos para los filtros
$especialidades_filtro = array_unique(array_column($doctores, 'especialidad'));
$idiomas_filtro = array_unique(array_merge(...array_column($doctores, 'idiomas')));
sort($especialidades_filtro);
sort($idiomas_filtro);
?>
<?php
// Definir assets específicos para esta página.
$page_specific_css = 'doctors.css';
$page_specific_js = 'doctors.js';

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
        <div class="filter-group animate-on-scroll fade-in-up" style="--animation-delay: 400ms;">
          <!-- Filtro de disponibilidad mejorado: Checkbox -->
          <label class="filter-switch">
            <input type="checkbox" id="availability-filter">
            <span class="switch-slider"></span>
            <span class="switch-label">Mostrar solo disponibles hoy</span>
          </label>
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
          <?php
          // Lógica para determinar la disponibilidad del día actual
          $horario_hoy = $doctor['horario_semanal'][$dia_actual] ?? 'No disponible';
          $disponible_hoy = $horario_hoy !== 'No disponible';
          $texto_disponibilidad = 'No disponible hoy';
          if ($disponible_hoy) {
            switch ($horario_hoy) {
              case 'Mañana':
                $texto_disponibilidad = 'Disponible por la mañana';
                break;
              case 'Tarde':
                $texto_disponibilidad = 'Disponible por la tarde';
                break;
              case 'Completo':
                $texto_disponibilidad = 'Disponible todo el día';
                break;
            }
          }
          ?>
          <div class="doctor-profile-card animate-on-scroll fade-in-up"
            data-name="<?php echo htmlspecialchars(strtolower($doctor['nombre'])); ?>"
            data-specialty="<?php echo htmlspecialchars(strtolower($doctor['especialidad'])); ?>"
            data-languages="|<?php echo htmlspecialchars(implode('|', array_map('strtolower', $doctor['idiomas']))); ?>|"
            data-available-today="<?php echo $disponible_hoy ? 'true' : 'false'; ?>">
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
                  <svg class="detail-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M8 21h8a2 2 0 0 0 2-2V7H6v12a2 2 0 0 0 2 2z"></path>
                    <line x1="10" y1="3" x2="14" y2="3"></line>
                    <line x1="12" y1="3" x2="12" y2="7"></line>
                  </svg>
                  <span class="detail-value"><?php echo $doctor['experiencia_anos']; ?> años</span>
                  <span class="detail-label">de experiencia</span>
                </div>
                <div class="detail-item">
                  <svg class="detail-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                  </svg>
                  <span class="detail-value"><?php echo number_format($doctor['pacientes']); ?>+</span>
                  <span class="detail-label">pacientes</span>
                </div>
                <div class="detail-item">
                  <svg class="detail-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path
                      d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z">
                    </path>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                  </svg>
                  <span class="detail-value"><?php echo htmlspecialchars($doctor['universidad']); ?></span>
                  <span class="detail-label">formación</span>
                </div>
              </div>
              <div class="doctor-profile-tags">
                <?php foreach ($doctor['idiomas'] as $idioma): ?>
                  <span class="tag language-tag">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                      stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <circle cx="12" cy="12" r="10"></circle>
                      <line x1="2" y1="12" x2="22" y2="12"></line>
                      <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z">
                      </path>
                    </svg>
                    <?php echo htmlspecialchars($idioma); ?>
                  </span>
                <?php endforeach; ?>
                <?php foreach ($doctor['reconocimientos'] as $rec): ?>
                  <span class="tag recognition-tag">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                      stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path
                        d="M12 8.21l-2.12 4.29-4.7.68 3.4 3.31-.8 4.69L12 18.27l4.22 2.21-.8-4.69 3.4-3.31-4.7-.68L12 8.21z">
                      </path>
                    </svg>
                    <?php echo htmlspecialchars($rec); ?>
                  </span>
                <?php endforeach; ?>
              </div>
              <div class="doctor-profile-footer">
                <div class="availability-status <?php echo $disponible_hoy ? 'is-available' : ''; ?>">
                  <span class="availability-dot"></span>
                  <span class="availability-text"><?php echo htmlspecialchars($texto_disponibilidad); ?></span>
                </div>
                <a href="../reservations/?doctor_id=<?php echo htmlspecialchars($doctor['id']); ?>"
                  class="btn btn-primary btn-sm">Agendar Cita</a>
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

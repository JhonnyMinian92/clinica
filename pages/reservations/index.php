<?php
// Simulación de datos que vendrían de una base de datos
$especialidades = [
  ['id' => 'cardiologia', 'titulo' => 'Cardiología', 'icono' => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>'],
  ['id' => 'neurologia', 'titulo' => 'Neurología', 'icono' => '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>'],
  ['id' => 'pediatria', 'titulo' => 'Pediatría', 'icono' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>'],
  ['id' => 'oncologia', 'titulo' => 'Oncología', 'icono' => '<path d="M12 2a2 2 0 0 0-2 2v2H8a2 2 0 0 0-2 2v2a2 2 0 0 0 2 2h2v2a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2v-2h2a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-2V4a2 2 0 0 0-2-2h-2z"></path>'],
  ['id' => 'dermatologia', 'titulo' => 'Dermatología', 'icono' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>'],
  ['id' => 'ginecologia', 'titulo' => 'Ginecología', 'icono' => '<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line>'],
  ['id' => 'traumatologia', 'titulo' => 'Traumatología', 'icono' => '<path d="M12 2L4 5v6.09c0 5.05 3.41 9.76 8 10.91 4.59-1.15 8-5.86 8-10.91V5l-8-3z"></path>'],
  ['id' => 'psiquiatria', 'titulo' => 'Psiquiatría', 'icono' => '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"></path>'],
];

$doctores = [
  'cardiologia' => [
    ['id' => 'doc1', 'nombre' => 'Dr. Alejandro Vargas', 'foto' => '/clinica/public/assets/avatar.png', 'valoracion' => 5, 'horario_semanal' => ['Lunes' => 'Mañana', 'Miércoles' => 'Mañana', 'Viernes' => 'Tarde']]
  ],
  'neurologia' => [
    ['id' => 'doc3', 'nombre' => 'Dr. Mateo Herrera', 'foto' => '/clinica/public/assets/avatar.png', 'valoracion' => 5, 'horario_semanal' => ['Lunes' => 'Completo', 'Martes' => 'Completo', 'Miércoles' => 'Completo', 'Jueves' => 'Completo', 'Viernes' => 'Completo']]
  ],
  'pediatria' => [
    ['id' => 'doc2', 'nombre' => 'Dra. Sofía Castillo', 'foto' => '/clinica/public/assets/avatar.png', 'valoracion' => 5, 'horario_semanal' => ['Martes' => 'Tarde', 'Jueves' => 'Tarde', 'Sábado' => 'Mañana']]
  ],
  'oncologia' => [
    ['id' => 'doc7', 'nombre' => 'Dr. Sebastián Peña', 'foto' => '/clinica/public/assets/avatar.png', 'valoracion' => 5, 'horario_semanal' => ['Martes' => 'Tarde', 'Jueves' => 'Tarde']]
  ],
  'dermatologia' => [
    ['id' => 'doc4', 'nombre' => 'Dra. Valentina Morales', 'foto' => '/clinica/public/assets/avatar.png', 'valoracion' => 4, 'horario_semanal' => ['Lunes' => 'Tarde', 'Miércoles' => 'Tarde', 'Viernes' => 'Tarde']]
  ],
  'ginecologia' => [
    ['id' => 'doc6', 'nombre' => 'Dra. Camila Ríos', 'foto' => '/clinica/public/assets/avatar.png', 'valoracion' => 5, 'horario_semanal' => ['Lunes' => 'Tarde', 'Miércoles' => 'Completo', 'Viernes' => 'Mañana']]
  ],
  'traumatologia' => [
    ['id' => 'doc5', 'nombre' => 'Dr. Lucas Jiménez', 'foto' => '/clinica/public/assets/avatar.png', 'valoracion' => 5, 'horario_semanal' => ['Martes' => 'Mañana', 'Jueves' => 'Mañana']]
  ],
  'psiquiatria' => [
    ['id' => 'doc8', 'nombre' => 'Dra. Isabella Gómez', 'foto' => '/clinica/public/assets/avatar.png', 'valoracion' => 4, 'horario_semanal' => ['Lunes' => 'Mañana', 'Jueves' => 'Mañana']]
  ],
];

// Simulación de citas ya reservadas para la demo
$citas_reservadas = [
  'doc1' => [
    // Usamos una fecha futura dinámica para que la demo siempre funcione
    date('Y-m-d', strtotime('next monday')) => ['09:00', '09:15', '10:30'],
    date('Y-m-d', strtotime('next wednesday')) => ['11:00'],
  ],
  'doc3' => [
    date('Y-m-d', strtotime('next tuesday')) => ['14:00', '14:15', '16:00', '16:30'],
  ],
  'doc6' => [
    date('Y-m-d', strtotime('next wednesday')) => ['09:00', '09:15', '09:30', '09:45', '10:00', '10:15', '10:30', '10:45', '11:00'],
  ]
];

$especialidad_seleccionada_id = '';
$doctor_seleccionado_id = isset($_GET['doctor_id']) ? trim((string) $_GET['doctor_id']) : '';
$specialty_param = isset($_GET['specialty']) ? trim((string) $_GET['specialty']) : '';
$especialidad_param = isset($_GET['especialidad']) ? trim((string) $_GET['especialidad']) : '';
$aliases_especialidad = [
  'cardio' => 'cardiologia',
  'neuro' => 'neurologia',
  'derma' => 'dermatologia',
  'gine' => 'ginecologia',
  'onco' => 'oncologia',
];

if ($specialty_param !== '') {
  $especialidad_seleccionada_id = $aliases_especialidad[$specialty_param] ?? $specialty_param;
}

if ($especialidad_seleccionada_id === '' && $especialidad_param !== '') {
  foreach ($especialidades as $especialidad) {
    if (strcasecmp($especialidad['titulo'], $especialidad_param) === 0 || $especialidad['id'] === $especialidad_param) {
      $especialidad_seleccionada_id = $especialidad['id'];
      break;
    }
  }
}

if ($doctor_seleccionado_id !== '') {
  foreach ($doctores as $especialidad_id => $lista_doctores) {
    foreach ($lista_doctores as $doctor) {
      if ($doctor['id'] === $doctor_seleccionado_id) {
        $especialidad_seleccionada_id = $especialidad_seleccionada_id ?: $especialidad_id;
        break 2;
      }
    }
  }
}

// Definir assets específicos para esta página para que sean cargados por los componentes de header/footer.
$page_specific_css = 'reservations.css';
$page_specific_js = 'reservations.js';

include __DIR__ . '/../../public/shared/header/header.php';
?>

<main id="main-content">
  <div class="reservation-container">
    <div class="reservation-form-wrapper">
      <h1 class="form-main-title">Agendar una Cita</h1>
      <p class="form-main-subtitle">Siga los pasos para encontrar un horario conveniente para usted.</p>

      <!-- Barra de Progreso -->
      <div class="progress-bar">
        <div class="progress-step active" data-step-name="Especialidad">1</div>
        <div class="progress-step" data-step-name="Doctor">2</div>
        <div class="progress-step" data-step-name="Fecha y Hora">3</div>
        <div class="progress-step" data-step-name="Sus Datos">4</div>
        <div class="progress-step" data-step-name="Confirmar">5</div>
      </div>

      <form id="reservation-form" class="reservation-form" novalidate
        data-selected-specialty-id="<?php echo htmlspecialchars($especialidad_seleccionada_id); ?>"
        data-selected-doctor-id="<?php echo htmlspecialchars($doctor_seleccionado_id); ?>">

        <!-- Paso 1: Seleccionar Especialidad -->
        <div class="form-step active">
          <h2 class="step-title">Seleccione una especialidad</h2>

          <div class="filter-wrapper">
            <svg class="filter-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
              fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" id="specialty-filter" class="filter-input"
              placeholder="Buscar especialidad (ej. Cardiología)">
          </div>

          <div class="selection-grid" id="specialty-grid">
            <?php foreach ($especialidades as $item): ?>
              <div class="selectable-card" data-value="<?php echo $item['id']; ?>"
                data-name="<?php echo $item['titulo']; ?>">
                <div class="card-icon">
                  <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round"><?php echo $item['icono']; ?></svg>
                </div>
                <span class="card-title"><?php echo $item['titulo']; ?></span>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="no-results-message" style="display: none;">
            No se encontraron especialidades que coincidan con su búsqueda.
          </div>
        </div>

        <!-- Paso 2: Seleccionar Doctor -->
        <div class="form-step">
          <h2 class="step-title">Seleccione un doctor</h2>
          <div class="filter-wrapper">
            <svg class="filter-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
              fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" id="doctor-filter" class="filter-input" placeholder="Buscar por nombre del doctor...">
          </div>
          <div id="doctor-selection-grid" class="selection-grid doctor-grid">
            <!-- Los doctores se cargarán aquí con JS -->
            <div class="skeleton-card"></div>
            <div class="skeleton-card"></div>
          </div>
          <div id="doctor-no-results" class="no-results-message" style="display: none;">
            No se encontraron doctores que coincidan con su búsqueda.
          </div>
        </div>

        <!-- Paso 3: Seleccionar Fecha y Hora -->
        <div class="form-step">
          <h2 class="step-title">Seleccione fecha y hora</h2>
          <div class="date-time-container">
            <!-- Calendario Interactivo -->
            <div class="calendar-container">
              <div class="calendar-header">
                <button type="button" id="prev-month-btn" class="calendar-nav-btn" aria-label="Mes anterior">‹</button>
                <h3 id="calendar-month-year" class="calendar-title"></h3>
                <button type="button" id="next-month-btn" class="calendar-nav-btn" aria-label="Mes siguiente">›</button>
              </div>
              <div class="calendar-weekdays">
                <span>Lun</span><span>Mar</span><span>Mié</span><span>Jue</span><span>Vie</span><span>Sáb</span><span>Dom</span>
              </div>
              <div id="calendar-grid" class="calendar-grid">
                <!-- Los días se generarán aquí con JS -->
              </div>
            </div>

            <!-- Horarios Disponibles -->
            <div class="time-slot-wrapper">
              <p class="time-slot-title">Horarios disponibles</p>
              <div class="time-slot-loader">
                <div class="spinner"></div>
              </div>
              <div id="time-slots" class="selection-grid time-grid">
                <div class="no-time-selected">
                  <p>Seleccione una fecha en el calendario para ver los horarios.</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Paso 4: Información del Paciente -->
        <div class="form-step">
          <h2 class="step-title">Complete sus datos</h2>
          <div class="patient-info-grid">
            <div class="form-group-styled">
              <input type="text" id="patient-name" name="patient-name" required placeholder=" ">
              <label for="patient-name">Nombre Completo</label>
            </div>
            <div class="form-group-styled">
              <input type="email" id="patient-email" name="patient-email" required placeholder=" ">
              <label for="patient-email">Correo Electrónico</label>
              <div class="form-error-message">Por favor, ingrese un correo válido.</div>
            </div>
            <div class="form-group-styled">
              <input type="tel" id="patient-phone" name="patient-phone" required placeholder=" " inputmode="numeric"
                pattern="[0-9]{7,10}" title="Ingrese solo números (de 7 a 10 dígitos).">
              <label for="patient-phone">Teléfono (solo números)</label>
              <div class="form-error-message">Debe ser un número válido de 7 a 10 dígitos.</div>
            </div>
            <div class="form-group-styled">
              <textarea id="patient-notes" name="patient-notes" rows="3" placeholder=" "></textarea>
              <label for="patient-notes">Notas adicionales (opcional)</label>
            </div>
          </div>
        </div>

        <!-- Paso 5: Confirmación -->
        <div class="form-step">
          <h2 class="step-title">Confirme los detalles de su cita</h2>
          <div class="summary-card">
            <h3>Resumen de la Cita</h3>
            <ul class="summary-list">
              <li><strong>Especialidad:</strong> <span id="summary-specialty"></span></li>
              <li><strong>Doctor:</strong> <span id="summary-doctor"></span></li>
              <li><strong>Fecha:</strong> <span id="summary-date"></span></li>
              <li><strong>Hora:</strong> <span id="summary-time"></span></li>
              <li><strong>Paciente:</strong> <span id="summary-name"></span></li>
              <li><strong>Email:</strong> <span id="summary-email"></span></li>
              <li><strong>Teléfono:</strong> <span id="summary-phone"></span></li>
            </ul>
            <p class="summary-footer">Recibirá una confirmación por correo electrónico. Por favor, llegue 15 minutos
              antes de su cita.</p>
          </div>
        </div>

        <!-- Botones de Navegación -->
        <div class="form-navigation">
          <button type="button" id="prev-btn" class="btn btn-tertiary">Anterior</button>
          <button type="button" id="next-btn" class="btn btn-primary">Siguiente</button>
        </div>

      </form>
    </div>
  </div>
</main>

<!-- Datos de doctores para JS, debe estar antes del footer que carga el script -->
<script>
  const doctorsData = <?php echo json_encode($doctores); ?>;
  const bookedAppointmentsData = <?php echo json_encode($citas_reservadas); ?>;
</script>

<?php
include __DIR__ . '/../../public/shared/footer/footer.php';
?>

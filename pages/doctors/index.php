<?php
require_once __DIR__ . '/../../api/data.php';
require_once __DIR__ . '/../../api/adaptive.php';

$pdo = clinica_get_db();
$clinic = clinica_get_clinic_settings();
$dia_actual = (string) date('N');
$adaptive_context = clinica_adaptive_context($pdo);
$doctores = $adaptive_context['doctors'] ?? [];

foreach ($doctores as &$doctor) {
  $doctor_specialties = array_values(array_filter($doctor['especialidades'] ?? [], static fn($item) => is_array($item) && ($item['nombre'] ?? '') !== ''));
  if ($doctor_specialties) {
    $doctor['especialidad'] = implode(' · ', array_map(static fn($item) => (string) $item['nombre'], $doctor_specialties));
  }
  $doctor['idiomas'] = is_array($doctor['idiomas'] ?? null) ? $doctor['idiomas'] : [];
  $doctor['reconocimientos'] = is_array($doctor['reconocimientos'] ?? null) ? $doctor['reconocimientos'] : [];
}
unset($doctor);

$especialidades_filtro = [];
foreach ($doctores as $doctor) {
  foreach (($doctor['especialidades'] ?? []) as $especialidad) {
    $nombre = trim((string) ($especialidad['nombre'] ?? ''));
    if ($nombre !== '') $especialidades_filtro[$nombre] = true;
  }
}
$especialidades_filtro = array_keys($especialidades_filtro);
$idiomas_columnas = array_values(array_filter(array_column($doctores, 'idiomas'), 'is_array'));
$idiomas_filtro = $idiomas_columnas ? array_values(array_unique(array_merge(...$idiomas_columnas))) : [];
sort($especialidades_filtro);
sort($idiomas_filtro);

$page_specific_css = 'adaptive-doctors.css';
$page_specific_js = 'doctors.js';

include __DIR__ . '/../../public/shared/header/header.php';
?>

<main id="main-content" class="clinic-mode-<?php echo htmlspecialchars($adaptive_context['mode'] ?? 'multi_doctor_multi_specialty'); ?>">
  <section class="page-header">
    <div class="container">
      <h1 class="page-title"><?php echo htmlspecialchars($clinic['doctors_title'] ?? 'Nuestro Equipo Medico'); ?></h1>
      <p class="page-subtitle"><?php echo htmlspecialchars($clinic['doctors_subtitle'] ?? 'Profesionales comprometidos con su salud. Encuentre a su especialista ideal.'); ?></p>
    </div>
  </section>

  <section class="section" id="doctors-catalog">
    <div class="container">
      <?php if (count($doctores) > 1): ?>
        <div class="doctors-filters" id="doctors-filters">
          <div class="filter-group animate-on-scroll fade-in-up" style="--animation-delay: 100ms;"><span class="filter-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg></span><input type="search" id="name-filter" placeholder="Buscar por nombre..." class="filter-input" style="padding-left: 40px;"></div>
          <div class="filter-group animate-on-scroll fade-in-up" style="--animation-delay: 200ms;"><span class="filter-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg></span><select id="specialty-filter" class="filter-select"><option value="">Todas las especialidades</option><?php foreach ($especialidades_filtro as $item): ?><option value="<?php echo htmlspecialchars(strtolower($item)); ?>"><?php echo htmlspecialchars($item); ?></option><?php endforeach; ?></select></div>
          <div class="filter-group animate-on-scroll fade-in-up" style="--animation-delay: 300ms;"><span class="filter-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M2 12h20"></path><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg></span><select id="language-filter" class="filter-select"><option value="">Todos los idiomas</option><?php foreach ($idiomas_filtro as $item): ?><option value="<?php echo htmlspecialchars(strtolower($item)); ?>"><?php echo htmlspecialchars($item); ?></option><?php endforeach; ?></select></div>
          <div class="filter-group animate-on-scroll fade-in-up" style="--animation-delay: 400ms;"><label class="filter-switch"><input type="checkbox" id="availability-filter"><span class="switch-slider"></span><span class="switch-label">Mostrar solo disponibles hoy</span></label></div>
          <div class="animate-on-scroll fade-in-up" style="--animation-delay: 500ms;"><button id="filter-reset-btn" class="filter-reset-btn">Limpiar</button></div>
        </div>
      <?php endif; ?>

      <div class="doctors-grid" id="doctors-grid">
        <?php foreach ($doctores as $doctor): ?>
          <?php
          $horario_hoy = $doctor['horario_semanal'][$dia_actual] ?? '';
          $disponible_hoy = $horario_hoy !== '';
          $texto_disponibilidad = $disponible_hoy ? 'Disponible hoy (' . $horario_hoy . ')' : 'No disponible hoy';
          $rating = max(0, min(5, (int) ($doctor['valoracion'] ?? 0)));
          $doctor_specialty_names = array_values(array_filter(array_map(static fn($item) => trim((string) ($item['nombre'] ?? '')), $doctor['especialidades'] ?? [])));
          $doctor_specialty_text = $doctor_specialty_names ? implode(' · ', $doctor_specialty_names) : (string) ($doctor['especialidad'] ?? '');
          ?>
          <div class="doctor-profile-card animate-on-scroll fade-in-up" data-id="<?php echo htmlspecialchars($doctor['id']); ?>" data-name="<?php echo htmlspecialchars(strtolower($doctor['nombre'])); ?>" data-specialty="<?php echo htmlspecialchars(strtolower($doctor_specialty_text)); ?>" data-languages="|<?php echo htmlspecialchars(implode('|', array_map('strtolower', $doctor['idiomas']))); ?>|" data-available-today="<?php echo $disponible_hoy ? 'true' : 'false'; ?>">
            <div class="doctor-profile-photo-wrapper"><img src="<?php echo htmlspecialchars($doctor['foto']); ?>" alt="Foto de <?php echo htmlspecialchars($doctor['nombre']); ?>" class="doctor-profile-photo"></div>
            <div class="doctor-profile-content">
              <div class="doctor-profile-header"><h3 class="doctor-profile-name"><?php echo htmlspecialchars($doctor['nombre']); ?></h3><div class="doctor-profile-rating" aria-label="Valoracion: <?php echo $rating; ?> de 5 estrellas"><?php echo str_repeat('&#9733;', $rating) . str_repeat('&#9734;', 5 - $rating); ?></div></div>
              <p class="doctor-profile-specialty"><?php echo htmlspecialchars($doctor_specialty_text); ?><?php if (!empty($doctor['subespecialidad'])): ?> | <strong><?php echo htmlspecialchars($doctor['subespecialidad']); ?></strong><?php endif; ?></p>
              <div class="doctor-profile-details"><div class="detail-item"><span class="detail-value"><?php echo (int) $doctor['experiencia_anos']; ?> anos</span><span class="detail-label">de experiencia</span></div><div class="detail-item"><span class="detail-value"><?php echo number_format((int) $doctor['pacientes']); ?>+</span><span class="detail-label">pacientes</span></div><div class="detail-item"><span class="detail-value"><?php echo htmlspecialchars($doctor['universidad']); ?></span><span class="detail-label">formacion</span></div></div>
              <div class="doctor-profile-tags"><?php foreach ($doctor['idiomas'] as $idioma): ?><span class="tag language-tag"><?php echo htmlspecialchars($idioma); ?></span><?php endforeach; ?><?php foreach ($doctor['reconocimientos'] as $rec): ?><span class="tag recognition-tag"><?php echo htmlspecialchars($rec); ?></span><?php endforeach; ?></div>
              <div class="doctor-profile-footer"><div class="availability-status <?php echo $disponible_hoy ? 'is-available' : ''; ?>"><span class="availability-dot"></span><span class="availability-text"><?php echo htmlspecialchars($texto_disponibilidad); ?></span></div><a href="../reservations/?doctor_id=<?php echo htmlspecialchars($doctor['id']); ?>" class="btn btn-primary btn-sm">Agendar Cita</a></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <div id="no-results-message" class="no-results-message" style="display: none;"><p>No se encontraron especialistas que coincidan con su busqueda.</p></div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/../../public/shared/footer/footer.php'; ?>

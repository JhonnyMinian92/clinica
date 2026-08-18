<?php
require_once __DIR__ . '/../../api/data.php';

$clinic = clinica_get_clinic_settings();
$especialidades = clinica_public_specialties_for_catalog();
$page_specific_css = 'specialties.css';
$page_specific_js = 'specialties.js';

include __DIR__ . '/../../public/shared/header/header.php';
?>

<main id="main-content">
  <section class="page-header">
    <div class="container">
      <h1 class="page-title"><?php echo htmlspecialchars($clinic['specialties_title'] ?? 'Nuestras Especialidades'); ?></h1>
      <p class="page-subtitle">
        <?php echo htmlspecialchars($clinic['specialties_subtitle'] ?? 'Cobertura medica integral para todas sus necesidades de salud.'); ?>
      </p>
    </div>
  </section>

  <section class="section">
    <div class="container">
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
            style="--animation-delay: <?php echo ($i * 50); ?>ms;"
            data-id="<?php echo htmlspecialchars(strtolower($especialidad['id'])); ?>"
            data-title="<?php echo strtolower(htmlspecialchars($especialidad['titulo'])); ?>">
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
        <p>No se encontraron especialidades que coincidan con su busqueda.</p>
      </div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/../../public/shared/footer/footer.php'; ?>

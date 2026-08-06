/*
 * doctors.js
 * Lógica para el catálogo de doctores
 * Sitio publico
 */

(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', () => {
    const filters = {
      name: document.getElementById('name-filter'),
      specialty: document.getElementById('specialty-filter'),
      availability: document.getElementById('availability-filter'),
      language: document.getElementById('language-filter'),
      reset: document.getElementById('filter-reset-btn'),
    };
    const doctorsGrid = document.getElementById('doctors-grid');
    const noResultsMessage = document.getElementById('no-results-message');

    if (!doctorsGrid) return; // Exit if main grid is not found

    // Defensive check: ensure all filter elements exist before proceeding.
    const allFiltersExist = Object.values(filters).every((el) => el !== null);
    if (!allFiltersExist) {
      console.error('One or more filter elements are missing from the DOM. Filtering cannot be initialized.');
      return;
    }

    const allCards = Array.from(doctorsGrid.children);

    // If a doctor id is provided in the URL (e.g. ?doctor=doc1), pre-filter to show only that doctor.
    try {
      const urlParams = new URLSearchParams(window.location.search);
      const doctorParam = (urlParams.get('doctor') || '').trim();
      if (doctorParam !== '') {
        let found = false;
        allCards.forEach((card) => {
          const id = card.dataset.id || '';
          const shouldShow = id === doctorParam;
          if (shouldShow) found = true;
          card.style.display = shouldShow ? '' : 'none';
        });
        const noResultsMessage = document.getElementById('no-results-message');
        if (noResultsMessage) noResultsMessage.classList.toggle('is-visible', !found);
        // Skip initializing filters when prefiltered to a single doctor
        return;
      }
    } catch (e) {
      // ignore URL parsing errors and continue with normal filtering
      console.error('Error parsing URL params for doctor filtering', e);
    }

    function applyFilters() {
      const nameTerm = filters.name.value.toLowerCase().trim();
      const specialtyTerm = filters.specialty.value.toLowerCase();
      const availabilityOnly = filters.availability.checked;
      const languageTerm = filters.language.value.toLowerCase();
      let visibleCount = 0;

      allCards.forEach((card, index) => {
        const nameMatch = card.dataset.name.includes(nameTerm);
        const specialtyMatch = !specialtyTerm || card.dataset.specialty === specialtyTerm;
        const availabilityMatch = !availabilityOnly || card.dataset.availableToday === 'true';
        const languageMatch = !languageTerm || card.dataset.languages.includes('|' + languageTerm + '|');

        const isVisible = nameMatch && specialtyMatch && availabilityMatch && languageMatch;

        // Se cambia la atenuación por ocultar completamente para mayor claridad, según feedback.
        card.style.display = isVisible ? '' : 'none';

        if (isVisible) {
          visibleCount++;
        }
      });

      noResultsMessage.classList.toggle('is-visible', visibleCount === 0);
    }

    function resetFilters() {
      filters.name.value = '';
      filters.specialty.value = ''; // Resets to the option with value=""
      filters.availability.checked = false;
      filters.language.value = '';
      applyFilters();
    }

    // Aplicar debounce solo al campo de texto libre
    filters.name.addEventListener('input', window.ClinicaUtils.debounce(applyFilters));

    // Los selects usan 'input' para una respuesta inmediata.
    [filters.specialty, filters.language].forEach((filter) => {
      filter.addEventListener('input', applyFilters);
    });
    // El checkbox usa 'change' para mayor compatibilidad.
    filters.availability.addEventListener('change', applyFilters);

    filters.reset.addEventListener('click', resetFilters);
  });
})();

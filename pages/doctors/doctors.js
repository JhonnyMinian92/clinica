/*
 * doctors.js
 * Lógica para el catálogo de doctores
 * Clínica Estándar
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

    /**
     * Retrasa la ejecución de una función. Esencial para no sobrecargar
     * el navegador en eventos que se disparan rápidamente, como 'input'.
     * @param {Function} func - La función a ejecutar.
     * @param {number} delay - El tiempo de espera en milisegundos.
     * @returns {Function}
     */
    function debounce(func, delay = 300) {
      let timeoutId;
      return function (...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => func.apply(this, args), delay);
      };
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
    filters.name.addEventListener('input', debounce(applyFilters));

    // Los selects usan 'input' para una respuesta inmediata.
    [filters.specialty, filters.language].forEach((filter) => {
      filter.addEventListener('input', applyFilters);
    });
    // El checkbox usa 'change' para mayor compatibilidad.
    filters.availability.addEventListener('change', applyFilters);

    filters.reset.addEventListener('click', resetFilters);
  });
})();

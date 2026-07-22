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

    if (!doctorsGrid) return;

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
      const specialtyTerm = filters.specialty.value;
      const availabilityTerm = filters.availability.value;
      const languageTerm = filters.language.value;
      let visibleCount = 0;

      allCards.forEach((card) => {
        const nameMatch = card.dataset.name.includes(nameTerm);
        const specialtyMatch = !specialtyTerm || card.dataset.specialty === specialtyTerm;
        const availabilityMatch = !availabilityTerm || card.dataset.availability === availabilityTerm;
        const languageMatch = !languageTerm || card.dataset.languages.toLowerCase().split(',').includes(languageTerm.toLowerCase());

        const isVisible = nameMatch && specialtyMatch && availabilityMatch && languageMatch;

        // En lugar de ocultar, atenuamos las tarjetas que no coinciden para una mejor UX
        card.classList.toggle('is-filtered-out', !isVisible);

        if (isVisible) {
          visibleCount++;
        }
      });

      noResultsMessage.classList.toggle('is-visible', visibleCount === 0);
    }

    function resetFilters() {
      filters.name.value = '';
      filters.specialty.value = '';
      filters.availability.value = '';
      filters.language.value = '';
      applyFilters();
    }

    // Aplicar debounce solo al campo de texto libre
    filters.name.addEventListener('input', debounce(applyFilters));

    // Los selects pueden aplicar el filtro inmediatamente
    [filters.specialty, filters.availability, filters.language].forEach((filter) => {
      filter.addEventListener('input', applyFilters);
    });

    filters.reset.addEventListener('click', resetFilters);
  });
})();

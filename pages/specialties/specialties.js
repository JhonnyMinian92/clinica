/*
 * specialties.js
 * Lógica para el filtro interactivo del catálogo de especialidades.
 * Sitio publico
 */

(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('specialty-search');
    const specialtiesGrid = document.getElementById('specialties-grid');
    const noResultsMessage = document.getElementById('no-results-message');

    if (!searchInput || !specialtiesGrid || !noResultsMessage) {
      return; // No ejecutar si los elementos no existen
    }

    const cards = Array.from(specialtiesGrid.children);

    /**
     * Retrasa la ejecución de una función. Esencial para no sobrecargar
     * el navegador en eventos que se disparan rápidamente, como 'input' o 'resize'.
     * @param {Function} func - La función a ejecutar.
     * @param {number} delay - El tiempo de espera en milisegundos.
     * @returns {Function}
     */
    function debounce(func, delay = 250) {
      let timeoutId;
      return function (...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => func.apply(this, args), delay);
      };
    }

    function filterSpecialties() {
      const searchTerm = searchInput.value.toLowerCase().trim();
      let visibleCount = 0;

      cards.forEach((card) => {
        const title = card.dataset.title || '';
        const isVisible = title.includes(searchTerm);

        card.style.display = isVisible ? '' : 'none';

        if (isVisible) {
          visibleCount++;
        }
      });

      noResultsMessage.style.display = visibleCount === 0 ? 'block' : 'none';
    }

    // Usamos debounce para optimizar el rendimiento del filtro
    searchInput.addEventListener('input', debounce(filterSpecialties, 300));
  });
})();

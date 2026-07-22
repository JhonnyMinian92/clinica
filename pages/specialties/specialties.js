/*
 * specialties.js
 * Lógica para el filtro interactivo del catálogo de especialidades.
 * Clínica Estándar
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

      noResultsMessage.style.display = visibleCount === 0 ? '' : 'none';
    }

    searchInput.addEventListener('input', filterSpecialties);
  });
})();

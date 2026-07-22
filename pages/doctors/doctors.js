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
    };
    const doctorsGrid = document.getElementById('doctors-grid');
    const noResultsMessage = document.getElementById('no-results-message');

    if (!doctorsGrid) return;

    const allCards = Array.from(doctorsGrid.children);

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

        card.style.display = isVisible ? 'flex' : 'none';
        if (isVisible) {
          visibleCount++;
        }
      });

      noResultsMessage.style.display = visibleCount === 0 ? 'block' : 'none';
    }

    Object.values(filters).forEach((filter) => {
      filter.addEventListener('input', applyFilters);
    });
  });
})();

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

    if (!doctorsGrid) return;

    const allFiltersExist = Object.values(filters).every((el) => el !== null);
    if (!allFiltersExist) return;

    const allCards = Array.from(doctorsGrid.children);

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
        if (noResultsMessage) noResultsMessage.classList.toggle('is-visible', !found);
        return;
      }
    } catch (e) {
      console.error('Error parsing URL params for doctor filtering', e);
    }

    function applyFilters() {
      const nameTerm = filters.name.value.toLowerCase().trim();
      const specialtyTerm = filters.specialty.value.toLowerCase();
      const availabilityOnly = filters.availability.checked;
      const languageTerm = filters.language.value.toLowerCase();
      let visibleCount = 0;

      allCards.forEach((card) => {
        const nameMatch = card.dataset.name.includes(nameTerm);
        const specialties = (card.dataset.specialty || '').split(' · ').map((item) => item.trim());
        const specialtyMatch = !specialtyTerm || specialties.includes(specialtyTerm);
        const availabilityMatch = !availabilityOnly || card.dataset.availableToday === 'true';
        const languageMatch = !languageTerm || card.dataset.languages.includes('|' + languageTerm + '|');

        const isVisible = nameMatch && specialtyMatch && availabilityMatch && languageMatch;
        card.style.display = isVisible ? '' : 'none';
        if (isVisible) visibleCount++;
      });

      noResultsMessage.classList.toggle('is-visible', visibleCount === 0);
    }

    function resetFilters() {
      filters.name.value = '';
      filters.specialty.value = '';
      filters.availability.checked = false;
      filters.language.value = '';
      applyFilters();
    }

    filters.name.addEventListener('input', window.ClinicaUtils.debounce(applyFilters));
    [filters.specialty, filters.language].forEach((filter) => filter.addEventListener('input', applyFilters));
    filters.availability.addEventListener('change', applyFilters);
    filters.reset.addEventListener('click', resetFilters);
  });
})();

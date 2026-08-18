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
    const urlParams = new URLSearchParams(window.location.search);
    const specialtyParam = normalizeTerm(urlParams.get('specialty') || urlParams.get('especialidad') || '');

    function normalizeTerm(value) {
      return String(value || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();
    }

    function applyFilter(searchTerm, exactMatch = false) {
      let visibleCount = 0;

      cards.forEach((card) => {
        const title = normalizeTerm(card.dataset.title || '');
        const id = normalizeTerm(card.dataset.id || '');
        const isVisible = exactMatch
          ? id === searchTerm || title === searchTerm
          : title.includes(searchTerm) || id.includes(searchTerm);

        card.style.display = isVisible ? '' : 'none';
        card.classList.toggle('is-filtered-match', searchTerm !== '' && isVisible);

        if (isVisible) {
          visibleCount++;
        }
      });

      noResultsMessage.style.display = visibleCount === 0 ? 'block' : 'none';
    }

    function filterSpecialties() {
      cards.forEach((card) => card.classList.remove('is-url-selected'));
      applyFilter(normalizeTerm(searchInput.value));
    }

    if (specialtyParam !== '') {
      const matchingCard = cards.find((card) => {
        const title = normalizeTerm(card.dataset.title || '');
        const id = normalizeTerm(card.dataset.id || '');
        return id === specialtyParam || title === specialtyParam;
      });

      if (matchingCard) {
        searchInput.value = matchingCard.dataset.title || specialtyParam;
        applyFilter(normalizeTerm(matchingCard.dataset.id || matchingCard.dataset.title || specialtyParam), true);
        matchingCard.classList.add('is-url-selected');
      } else {
        searchInput.value = specialtyParam;
        filterSpecialties();
      }
    }

    // Usamos debounce para optimizar el rendimiento del filtro
    searchInput.addEventListener('input', window.ClinicaUtils.debounce(filterSpecialties, 300));
  });
})();

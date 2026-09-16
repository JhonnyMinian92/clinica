(function () {
  'use strict';

  // Compatibility renderer for the dashboard agenda. The dashboard owns the
  // pagination state, while this shared fallback keeps the UI functional if
  // the page-specific renderer is unavailable after a frontend refactor.
  if (typeof window.renderPagination !== 'function') {
    window.renderPagination = function renderPagination(pageData) {
      const data = pageData || {};
      const pagination = document.getElementById('appointments-pagination');
      const summary = document.getElementById('pagination-summary');
      const pages = document.getElementById('pagination-pages');
      const previous = document.getElementById('pagination-prev');
      const next = document.getElementById('pagination-next');
      if (!pagination) return;

      const total = Math.max(0, Number(data.total) || 0);
      const page = Math.max(1, Number(data.page) || 1);
      const totalPages = Math.max(1, Number(data.totalPages) || 1);
      const from = Math.max(0, Number(data.from) || 0);
      const to = Math.max(0, Number(data.to) || 0);

      pagination.hidden = total === 0;
      if (summary) {
        summary.textContent = total === 0
          ? 'Mostrando 0 citas'
          : `Mostrando ${from + 1}-${Math.min(to, total)} de ${total} citas`;
      }
      if (pages) pages.textContent = `${page} / ${totalPages}`;
      if (previous) {
        previous.disabled = page <= 1;
        previous.setAttribute('aria-disabled', previous.disabled ? 'true' : 'false');
      }
      if (next) {
        next.disabled = page >= totalPages;
        next.setAttribute('aria-disabled', next.disabled ? 'true' : 'false');
      }
    };
  }

  document.addEventListener('DOMContentLoaded', () => {
    const dom = {
      header: document.getElementById('main-header'),
      mobileNavToggle: document.getElementById('mobile-nav-toggle'),
      navLinks: document.querySelectorAll('.nav-link'),
    };

    function init() {
      if (!dom.header || !dom.mobileNavToggle) {
        console.warn('Componentes del header no encontrados. El script del header no se ejecutará.');
        return;
      }
      attachEventListeners();
    }

    function attachEventListeners() {
      window.addEventListener('scroll', handleHeaderScroll, { passive: true });
      dom.mobileNavToggle.addEventListener('click', toggleMobileNav);
      dom.navLinks.forEach((link) => {
        link.addEventListener('click', () => {
          if (document.body.classList.contains('mobile-nav-open')) {
            toggleMobileNav();
          }
        });
      });
    }

    function handleHeaderScroll() {
      if (window.scrollY > 50) {
        dom.header.classList.add('scrolled');
      } else {
        dom.header.classList.remove('scrolled');
      }
    }

    function toggleMobileNav() {
      const isExpanded = dom.mobileNavToggle.getAttribute('aria-expanded') === 'true';
      document.body.classList.toggle('mobile-nav-open');
      dom.mobileNavToggle.setAttribute('aria-expanded', !isExpanded);
    }

    init();
  });

})();
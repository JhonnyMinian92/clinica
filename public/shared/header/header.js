(function () {
  'use strict';

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
      // Listener para el encogimiento del header al hacer scroll
      window.addEventListener('scroll', handleHeaderScroll, { passive: true });

      // Listener para el botón de navegación móvil
      dom.mobileNavToggle.addEventListener('click', toggleMobileNav);

      // Cerrar menú móvil al hacer clic en un enlace
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
/*
 * APP.JS
 * Clínica Estándar - Lógica del Sitio Principal
 *
 * Principios:
 * 1. Modular y Contenido: Sin polución del scope global.
 * 2. Rendimiento por Defecto: Manejo eficiente del DOM y eventos.
 * 3. Feedback Significativo: Cada acción del usuario tiene una reacción clara.
 * 4. Interacciones Accesibles: Atributos ARIA como ciudadano de primera clase.
 * 5. Artesanía Pura: Vanilla JS para control total y rendimiento.
 */

(function () {
  'use strict';

  /**
   * Espera a que el DOM esté completamente cargado antes de inicializar el script.
   */
  document.addEventListener('DOMContentLoaded', () => {
    // Almacén de referencias a elementos del DOM para eficiencia
    const dom = {
      animatedElements: document.querySelectorAll('.animate-on-scroll'),
      statNumbers: document.querySelectorAll('.stat-number'),
      lazyImages: document.querySelectorAll('.lazy-img'),
      lazyBackgrounds: document.querySelectorAll('.lazy-bg'),
      testimonialCarousel: document.getElementById('testimonial-carousel'),
      testimonialWrapper: document.querySelector('.testimonial-wrapper'),
      testimonialPrev: document.getElementById('testimonial-prev'),
      testimonialNext: document.getElementById('testimonial-next'),
      testimonialDots: document.getElementById('testimonial-dots'),
    };

    /**
     * Función principal de inicialización.
     */
    function init() {
      // La lógica de eventos del header se ha movido a /public/shared/header/header.js
      initScrollAnimations();
      initStatCounters();
      initLazyLoading();
      initTestimonialCarousel();
    }

    /**
     * Inicializa el IntersectionObserver para las animaciones al hacer scroll.
     * Es mucho más performante que escuchar el evento 'scroll'.
     */
    function initScrollAnimations() {
      const observerOptions = {
        root: null, // viewport
        rootMargin: '0px',
        threshold: 0.1, // 10% del elemento visible
      };

      const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target); // Dejar de observar una vez animado
          }
        });
      }, observerOptions);

      dom.animatedElements.forEach((el) => observer.observe(el));
    }

    /**
     * Inicializa los contadores de estadísticas usando IntersectionObserver.
     */
    function initStatCounters() {
      const observer = new IntersectionObserver(
        (entries, observer) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              const el = entry.target;
              const target = +el.getAttribute('data-target');
              animateCounter(el, target);
              observer.unobserve(el);
            }
          });
        },
        { threshold: 0.8 },
      );

      dom.statNumbers.forEach((num) => observer.observe(num));
    }

    /**
     * Anima un número desde 0 hasta un valor objetivo.
     * @param {HTMLElement} el - El elemento que contiene el número.
     * @param {number} target - El número final.
     */
    function animateCounter(el, target) {
      let current = 0;
      const duration = 2000; // 2 seconds
      // Update roughly 60 times per second for smooth animation
      const updatesPerSecond = 60;
      const intervalTime = 1000 / updatesPerSecond;
      const increment = target / (duration / intervalTime);

      if (target === 0) {
        el.textContent = 0;
        return;
      }

      const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
          el.textContent = target.toLocaleString('es-ES');
          clearInterval(timer);
        } else {
          el.textContent = Math.ceil(current).toLocaleString('es-ES');
        }
      }, intervalTime);
    }

    /**
     * Initializes lazy loading for images and background images.
     * Uses IntersectionObserver for performance.
     */
    function initLazyLoading() {
      const MIN_SKELETON_TIME = 800; // ms. Garantiza que el skeleton sea visible.

      const lazyImageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            const img = entry.target;
            const parent = img.parentElement;

            const imageLoadPromise = new Promise((resolve) => {
              img.src = img.dataset.src;
              img.addEventListener('load', resolve, { once: true });
            });

            const minTimePromise = new Promise((resolve) => {
              setTimeout(resolve, MIN_SKELETON_TIME);
            });

            Promise.all([imageLoadPromise, minTimePromise]).then(() => {
              img.classList.add('is-loaded');
              parent.classList.remove('skeleton');
            });

            img.removeAttribute('data-src');
            observer.unobserve(img);
          }
        });
      });
      dom.lazyImages.forEach((img) => lazyImageObserver.observe(img));

      const lazyBgObserver = new IntersectionObserver(
        (entries, observer) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              const el = entry.target;
              const bgUrl = el.dataset.bg;

              const imageLoadPromise = new Promise((resolve) => {
                const img = new Image();
                img.src = bgUrl;
                // Solo pre-cargamos la imagen, el CSS se encarga de mostrarla.
                img.addEventListener('load', resolve, { once: true });
              });

              const minTimePromise = new Promise((resolve) => {
                setTimeout(resolve, MIN_SKELETON_TIME);
              });

              Promise.all([imageLoadPromise, minTimePromise]).then(() => {
                el.classList.add('is-loaded');
                el.classList.remove('skeleton', 'skeleton-dark');
              });

              el.removeAttribute('data-bg');
              observer.unobserve(el);
            }
          });
        },
        {
          // Start loading the hero image a bit before it's fully in view
          rootMargin: '100px',
        },
      );
      dom.lazyBackgrounds.forEach((el) => lazyBgObserver.observe(el));
    }

    /**
     * Initializes the testimonial carousel functionality.
     */
    function initTestimonialCarousel() {
      if (!dom.testimonialCarousel) return;

      const slides = Array.from(dom.testimonialWrapper.children);
      if (slides.length === 0) return;

      let currentIndex = 0;
      const totalSlides = slides.length;
      let autoPlayInterval = null;
      const autoPlayDelay = 5000; // 5 segundos

      // --- Touch/Swipe Logic ---
      let touchStartX = 0;
      let touchEndX = 0;

      // Create dots
      for (let i = 0; i < totalSlides; i++) {
        const dot = document.createElement('button');
        dot.classList.add('testimonial-dot');
        dot.setAttribute('aria-label', `Ir al testimonio ${i + 1}`);
        dot.addEventListener('click', () => {
          goToSlide(i);
          stopAutoPlay();
        });
        dom.testimonialDots.appendChild(dot);
      }

      const dots = dom.testimonialDots.children;

      function updateCarousel() {
        const activeSlide = slides[currentIndex];
        if (!activeSlide) return;

        // UX Improvement: Adjust wrapper height to match the active slide's height
        dom.testimonialWrapper.style.height = `${activeSlide.offsetHeight}px`;

        // Toggle active class for fade effect
        slides.forEach((slide, index) => {
          slide.classList.toggle('is-active', index === currentIndex);
        });

        // Update dots
        Array.from(dots).forEach((dot, i) => {
          dot.classList.toggle('active', i === currentIndex);
        });
      }

      function goToSlide(index) {
        if (index < 0 || index >= totalSlides) return;
        currentIndex = index;
        updateCarousel();
      }

      function nextSlide() {
        currentIndex = (currentIndex + 1) % totalSlides;
        updateCarousel();
      }

      function prevSlide() {
        currentIndex = (currentIndex - 1 + totalSlides) % totalSlides;
        updateCarousel();
      }

      function handleTouchStart(e) {
        touchStartX = e.changedTouches[0].screenX;
        stopAutoPlay();
      }

      function handleTouchEnd(e) {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
        startAutoPlay(); // Reinicia el autoplay después del swipe
      }

      function handleSwipe() {
        if (touchEndX < touchStartX - 50) nextSlide(); // Swipe left
        if (touchEndX > touchStartX + 50) prevSlide(); // Swipe right
      }

      function startAutoPlay() {
        if (autoPlayInterval) clearInterval(autoPlayInterval);
        autoPlayInterval = setInterval(nextSlide, autoPlayDelay);
      }

      function stopAutoPlay() {
        clearInterval(autoPlayInterval);
      }

      // Attach Event Listeners
      dom.testimonialNext.addEventListener('click', () => {
        nextSlide();
        stopAutoPlay();
      });
      dom.testimonialPrev.addEventListener('click', () => {
        prevSlide();
        stopAutoPlay();
      });
      dom.testimonialWrapper.addEventListener('touchstart', handleTouchStart, { passive: true });
      dom.testimonialWrapper.addEventListener('touchend', handleTouchEnd, { passive: true });

      // Initialize first slide
      updateCarousel();
      startAutoPlay();
    }

    // Iniciar la aplicación
    init();
  });
})();

/*
 * APP.JS
 * Logica del sitio publico.
 */

(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', () => {
    init();
  });

  const dom = {};

  async function init() {
    refreshDomReferences();
    await hydrateHomeFromApi();
    refreshDomReferences();
    initScrollAnimations();
    initStatCounters();
    initLazyLoading();
    initTestimonialCarousel();
  }

  function refreshDomReferences() {
    dom.animatedElements = document.querySelectorAll('.animate-on-scroll');
    dom.statNumbers = document.querySelectorAll('.stat-number');
    dom.lazyImages = document.querySelectorAll('.lazy-img');
    dom.lazyBackgrounds = document.querySelectorAll('.lazy-bg');
    dom.testimonialCarousel = document.getElementById('testimonial-carousel');
    dom.testimonialWrapper = document.querySelector('.testimonial-wrapper');
    dom.testimonialPrev = document.getElementById('testimonial-prev');
    dom.testimonialNext = document.getElementById('testimonial-next');
    dom.testimonialDots = document.getElementById('testimonial-dots');
  }

  async function hydrateHomeFromApi() {
    const homeRoot = document.querySelector('[data-home-api]');
    if (!homeRoot) return;

    const endpoint = homeRoot.dataset.homeApi || '/clinica/api/public/?resource=home';

    try {
      const response = await fetch(endpoint, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
      });
      const result = await response.json().catch(() => ({}));

      if (!response.ok || !result.success || !result.data) {
        throw new Error(result.error || 'No se pudo cargar la informacion publica.');
      }

      renderHome(result.data);
      homeRoot.dataset.homeLoaded = 'true';
    } catch (error) {
      renderHomeError(error.message || 'No se pudo cargar la informacion publica.');
    }
  }

  function renderHome(data) {
    const clinic = data.clinic || {};

    renderHero(clinic);
    renderSectionText(clinic);
    renderAboutCards(data.about_cards || []);
    renderStats(data.stats || []);
    renderServices(data.services || []);
    renderSpecialties(data.specialties || []);
    renderDoctors(data.doctors || []);
    renderTestimonials(data.comments || []);
  }

  function renderHero(clinic) {
    const hero = document.getElementById('hero');
    const heroTitle = document.querySelector('.hero-title');
    const heroSubtitle = document.querySelector('.hero-subtitle');

    if (hero) {
      hero.dataset.bg = clinic.hero_image || 'public/assets/banner.jpg';
    }

    if (heroTitle) {
      heroTitle.textContent = clinic.hero_title || 'Medicina de Vanguardia, Cuidado Humano';
    }

    if (heroSubtitle) {
      heroSubtitle.textContent = clinic.hero_subtitle || 'Combinamos alta tecnologia con especialistas comprometidos con su salud y bienestar.';
    }
  }

  function renderSectionText(clinic) {
    document.querySelectorAll('[data-home-text]').forEach((element) => {
      const key = element.dataset.homeText;
      element.textContent = clinic[key] || '';
    });
  }

  function renderAboutCards(cards) {
    const target = document.getElementById('home-about-cards');
    if (!target) return;

    target.innerHTML = cards
      .map((card, index) => `
        <div class="card animate-on-scroll fade-in-up" style="--animation-delay: ${index * 100 + 200}ms;">
          <div class="card-icon">
            ${renderSvg(card.icon || '', 40, 1.5)}
          </div>
          <h3 class="card-title">${escapeHtml(card.title || '')}</h3>
          <p class="card-text">${escapeHtml(card.text || '')}</p>
        </div>
      `)
      .join('');
  }

  function renderStats(stats) {
    const target = document.getElementById('home-stats');
    if (!target) return;

    target.innerHTML = stats
      .map((stat, index) => `
        <div class="stat-item animate-on-scroll fade-in-up" style="--animation-delay: ${index * 100}ms;">
          <span class="stat-number" data-target="${Number(stat.value || 0)}">0</span><span class="stat-suffix">${escapeHtml(stat.suffix || '')}</span>
          <p class="stat-label">${escapeHtml(stat.label || '')}</p>
        </div>
      `)
      .join('');
  }

  function renderServices(services) {
    const target = document.getElementById('home-services');
    if (!target) return;

    target.innerHTML = services
      .map((service, index) => `
        <div class="service-card animate-on-scroll fade-in-up" style="--animation-delay: ${index * 100 + 200}ms;">
          <div class="service-icon">
            ${renderSvg(service.icono || '', 48, 1.5)}
          </div>
          <h3 class="service-title">${escapeHtml(service.titulo || '')}</h3>
          <p class="service-text">${escapeHtml(service.descripcion || '')}</p>
        </div>
      `)
      .join('');
  }

  function renderSpecialties(specialties) {
    const target = document.getElementById('home-specialties');
    if (!target) return;

    target.innerHTML = specialties
      .map((specialty, index) => `
        <div class="specialty-card animate-on-scroll fade-in-up" style="--animation-delay: ${index * 100 + 200}ms;">
          <div class="specialty-icon">
            ${renderSvg(specialty.icono || '', 32, 2)}
          </div>
          <h3 class="specialty-title">${escapeHtml(specialty.titulo || '')}</h3>
          <p class="specialty-text">${escapeHtml(specialty.resumen || specialty.descripcion || '')}</p>
          <a href="pages/specialties/?specialty=${encodeURIComponent(specialty.id || '')}" class="specialty-link">Saber mas</a>
        </div>
      `)
      .join('');
  }

  function renderDoctors(doctors) {
    const target = document.getElementById('home-doctors');
    if (!target) return;

    target.innerHTML = doctors
      .map((doctor, index) => `
        <div class="doctor-card animate-on-scroll fade-in-up" style="--animation-delay: ${index * 100 + 200}ms;" data-id="${escapeAttribute(doctor.id || '')}">
          <div class="doctor-photo skeleton">
            <img data-src="${escapeAttribute(doctor.foto || 'public/assets/avatar.png')}"
              alt="Fotografia de ${escapeAttribute(doctor.nombre || 'Doctor')}" loading="lazy" class="lazy-img">
          </div>
          <div class="doctor-card-overlay">
            <div class="doctor-card-content">
              <h3 class="doctor-name">${escapeHtml(doctor.nombre || '')}</h3>
              <p class="doctor-specialty">${escapeHtml(doctor.especialidad || '')}</p>
              <div class="doctor-card-details">
                <p class="doctor-exp">${escapeHtml(doctor.experiencia || '')}</p>
            <a href="pages/doctors/?doctor=${encodeURIComponent(doctor.id || '')}" class="btn btn-primary btn-sm">Ver Perfil</a>
              </div>
            </div>
          </div>
        </div>
      `)
      .join('');
  }

  function renderTestimonials(comments) {
    const target = document.getElementById('home-testimonials');
    if (!target) return;

    target.innerHTML = comments
      .map((comment) => {
        const rating = Math.max(0, Math.min(5, Number(comment.valoracion || 0)));
        return `
          <div class="testimonial-slide">
            <img src="${escapeAttribute(comment.avatar || 'public/assets/avatar.png')}"
              alt="Avatar de ${escapeAttribute(comment.autor || 'paciente')}" class="testimonial-avatar">
            <div class="testimonial-rating" aria-label="Valoracion: ${rating} de 5 estrellas">
              ${'&#9733;'.repeat(rating)}${'&#9734;'.repeat(5 - rating)}
            </div>
            <p class="testimonial-text">"${escapeHtml(comment.texto || '')}"</p>
            <p class="testimonial-author">${escapeHtml(comment.autor || '')}</p>
          </div>
        `;
      })
      .join('');
  }

  function renderHomeError(message) {
    const heroTitle = document.querySelector('.hero-title');
    const heroSubtitle = document.querySelector('.hero-subtitle');

    if (heroTitle) heroTitle.textContent = 'Informacion no disponible';
    if (heroSubtitle) heroSubtitle.textContent = message;
  }

  function renderSvg(content, size, strokeWidth) {
    return `
      <svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size}" viewBox="0 0 24 24" fill="none"
        stroke="currentColor" stroke-width="${strokeWidth}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        ${content}
      </svg>
    `;
  }

  function initScrollAnimations() {
    if (!('IntersectionObserver' in window)) {
      dom.animatedElements.forEach((el) => el.classList.add('is-visible'));
      return;
    }

    const observerOptions = {
      root: null,
      rootMargin: '0px',
      threshold: 0.1,
    };

    const observer = new IntersectionObserver((entries, activeObserver) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          activeObserver.unobserve(entry.target);
        }
      });
    }, observerOptions);

    dom.animatedElements.forEach((el) => observer.observe(el));
  }

  function initStatCounters() {
    if (!('IntersectionObserver' in window)) {
      dom.statNumbers.forEach((num) => animateCounter(num, Number(num.dataset.target || 0)));
      return;
    }

    const observer = new IntersectionObserver(
      (entries, activeObserver) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            const el = entry.target;
            animateCounter(el, Number(el.getAttribute('data-target') || 0));
            activeObserver.unobserve(el);
          }
        });
      },
      { threshold: 0.8 },
    );

    dom.statNumbers.forEach((num) => observer.observe(num));
  }

  function animateCounter(el, target) {
    let current = 0;
    const duration = 2000;
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

  function initLazyLoading() {
    const minSkeletonTime = 800;

    if (!('IntersectionObserver' in window)) {
      dom.lazyImages.forEach(loadLazyImage);
      dom.lazyBackgrounds.forEach(loadLazyBackground);
      return;
    }

    const lazyImageObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        loadLazyImage(entry.target, minSkeletonTime);
        observer.unobserve(entry.target);
      });
    });
    dom.lazyImages.forEach((img) => lazyImageObserver.observe(img));

    const lazyBgObserver = new IntersectionObserver(
      (entries, observer) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;
          loadLazyBackground(entry.target, minSkeletonTime);
          observer.unobserve(entry.target);
        });
      },
      { rootMargin: '100px' },
    );
    dom.lazyBackgrounds.forEach((el) => lazyBgObserver.observe(el));
  }

  function loadLazyImage(img, minSkeletonTime = 0) {
    const src = img.dataset.src;
    if (!src) return;

    const parent = img.parentElement;
    const imageLoadPromise = new Promise((resolve) => {
      img.src = src;
      img.addEventListener('load', resolve, { once: true });
      img.addEventListener('error', resolve, { once: true });
    });
    const minTimePromise = new Promise((resolve) => setTimeout(resolve, minSkeletonTime));

    Promise.all([imageLoadPromise, minTimePromise]).then(() => {
      img.classList.add('is-loaded');
      parent?.classList.remove('skeleton');
    });

    img.removeAttribute('data-src');
  }

  function loadLazyBackground(el, minSkeletonTime = 0) {
    const bgUrl = el.dataset.bg;
    if (!bgUrl) return;

    const imageLoadPromise = new Promise((resolve) => {
      const img = new Image();
      img.src = bgUrl;
      img.addEventListener('load', resolve, { once: true });
      img.addEventListener('error', resolve, { once: true });
    });
    const minTimePromise = new Promise((resolve) => setTimeout(resolve, minSkeletonTime));

    Promise.all([imageLoadPromise, minTimePromise]).then(() => {
      el.style.backgroundImage = `url("${bgUrl}")`;
      el.classList.add('is-loaded');
      el.classList.remove('skeleton', 'skeleton-dark');
    });

    el.removeAttribute('data-bg');
  }

  function initTestimonialCarousel() {
    if (!dom.testimonialCarousel || !dom.testimonialWrapper || !dom.testimonialDots) return;

    const slides = Array.from(dom.testimonialWrapper.children);
    dom.testimonialDots.innerHTML = '';
    if (slides.length === 0) return;

    let currentIndex = 0;
    const totalSlides = slides.length;
    let autoPlayInterval = null;
    const autoPlayDelay = 5000;
    let touchStartX = 0;
    let touchEndX = 0;

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

      dom.testimonialWrapper.style.height = `${activeSlide.offsetHeight}px`;
      slides.forEach((slide, index) => {
        slide.classList.toggle('is-active', index === currentIndex);
      });
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

    function handleSwipe() {
      if (touchEndX < touchStartX - 50) nextSlide();
      if (touchEndX > touchStartX + 50) prevSlide();
    }

    function startAutoPlay() {
      if (autoPlayInterval) clearInterval(autoPlayInterval);
      autoPlayInterval = setInterval(nextSlide, autoPlayDelay);
    }

    function stopAutoPlay() {
      clearInterval(autoPlayInterval);
    }

    dom.testimonialNext?.addEventListener('click', () => {
      nextSlide();
      stopAutoPlay();
    });
    dom.testimonialPrev?.addEventListener('click', () => {
      prevSlide();
      stopAutoPlay();
    });
    dom.testimonialWrapper.addEventListener('touchstart', (event) => {
      touchStartX = event.changedTouches[0].screenX;
      stopAutoPlay();
    }, { passive: true });
    dom.testimonialWrapper.addEventListener('touchend', (event) => {
      touchEndX = event.changedTouches[0].screenX;
      handleSwipe();
      startAutoPlay();
    }, { passive: true });

    updateCarousel();
    startAutoPlay();
  }

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (match) => {
      return {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
      }[match];
    });
  }

  function escapeAttribute(value) {
    return escapeHtml(value).replace(/`/g, '&#96;');
  }
})();

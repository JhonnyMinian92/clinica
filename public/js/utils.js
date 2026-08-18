/*
 * utils.js
 * Funciones de utilidad compartidas para el sitio de la Clinica.
 */

(function () {
  'use strict';

  function debounce(func, delay = 300) {
    let timeoutId;
    return function (...args) {
      clearTimeout(timeoutId);
      timeoutId = setTimeout(() => func.apply(this, args), delay);
    };
  }

  /**
   * Safely set innerHTML using DOMPurify when available.
   * Accepts an options object to pass to DOMPurify.sanitize, default enables SAFE_FOR_SVG.
   */
  function safeSetInnerHTML(target, htmlString, options = { SAFE_FOR_SVG: true }) {
    if (!target) return;
    try {
      if (window.DOMPurify && typeof window.DOMPurify.sanitize === 'function') {
        const clean = window.DOMPurify.sanitize(htmlString || '', options);
        target.innerHTML = clean;
        return;
      }
    } catch (err) {
      // If DOMPurify fails, fall back to a conservative escape + setting textContent
      console.error('DOMPurify error:', err);
    }
    // Conservative fallback: strip tags by creating a temporary div and using textContent
    const tmp = document.createElement('div');
    tmp.textContent = htmlString || '';
    target.innerHTML = tmp.innerHTML;
  }

  window.ClinicaUtils = { debounce, safeSetInnerHTML };
})();
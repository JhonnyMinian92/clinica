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

  window.ClinicaUtils = { debounce };
})();
/*
 * APP.JS
 * Clínica Estándar - Lógica del Portal de Acceso
 *
 * Version: 1.0 (2027 Vision)
 * Lead Engineer: Senior Frontend Engineer
 * UX Specialist: Especialista en Experiencia de Usuario
 *
 * Principles:
 * 1. Modular and Contained: No global scope pollution.
 * 2. Performant by Default: Efficient DOM handling and event delegation.
 * 3. Meaningful Feedback: Every user action has a clear, immediate reaction.
 * 4. Accessible Interactions: ARIA attributes are a first-class citizen.
 * 5. Pure Craftsmanship: Vanilla JS for full control and performance.
 */

(function () {
  'use strict';

  /**
   * Waits for the DOM to be fully loaded before initializing the script.
   */
  document.addEventListener('DOMContentLoaded', () => {
    // Object to hold all necessary DOM element references
    const dom = {
      loginForm: document.getElementById('login-form'),
      usernameInput: document.getElementById('username'),
      passwordInput: document.getElementById('password'),
      togglePasswordBtn: document.getElementById('toggle-password'),
      submitBtn: document.getElementById('submit-button'),
      errorMessage: document.getElementById('form-error-message'),
      rememberMeCheckbox: document.getElementById('remember-me'),
    };

    /**
     * Main initialization function.
     * Checks for the existence of the form before attaching any logic.
     */
    function init() {
      if (!dom.loginForm) {
        console.warn('Login form not found. Script will not run.');
        return;
      }
      attachEventListeners();
      loadRememberedUser();
    }

    /**
     * Attaches all event listeners to the relevant DOM elements.
     */
    function attachEventListeners() {
      dom.loginForm.addEventListener('submit', handleFormSubmit);
      dom.togglePasswordBtn.addEventListener('click', togglePasswordVisibility);

      // Clear form-level errors as the user types
      [dom.usernameInput, dom.passwordInput].forEach((input) => {
        input.addEventListener('input', clearFormError);
      });
    }

    /**
     * Toggles the visibility of the password field and updates ARIA attributes
     * for accessibility.
     */
    function togglePasswordVisibility() {
      const isPressed = dom.togglePasswordBtn.getAttribute('aria-pressed') === 'true';
      const newPressedState = !isPressed;

      dom.togglePasswordBtn.setAttribute('aria-pressed', newPressedState);
      dom.passwordInput.type = newPressedState ? 'text' : 'password';

      // Update the button's label for screen readers to reflect the new action
      const newLabel = newPressedState ? 'Ocultar contraseña' : 'Mostrar contraseña';
      dom.togglePasswordBtn.setAttribute('aria-label', newLabel);
    }

    /**
     * Handles the form submission event.
     * @param {Event} e The submission event.
     */
    async function handleFormSubmit(e) {
      e.preventDefault();

      if (isButtonBusy()) return;

      if (!validateForm()) {
        triggerErrorShake();
        return;
      }

      setButtonState('loading');

      try {
        const response = await fetch('/clinica/api/general/', {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-Token': (function(){
              // Read XSRF-TOKEN cookie inserted by server
              const match = document.cookie.match(new RegExp('(^|;)\\s*' + 'XSRF-TOKEN' + '\\s*=\\s*([^;]+)'));
              return match ? decodeURIComponent(match.pop()) : '';
            })(),
          },

          body: JSON.stringify({
            action: 'login',
            username: dom.usernameInput.value.trim(),
            password: dom.passwordInput.value,
            redirect: getRequestedRedirect(),
          }),
        });

        const result = await response.json().catch(() => ({}));

        if (!response.ok || !result.success) {
          throw new Error(result.error || 'No se pudo iniciar sesión.');
        }

        setButtonState('success');
        handleRememberMe();

        setTimeout(() => {
          window.location.href = getSafeRedirect(result.redirect);
        }, 450);
      } catch (error) {
        displayFormError(error.message || 'No se pudo iniciar sesión.');
        setButtonState('default');
        triggerErrorShake();
      }
    }

    function getSafeRedirect(apiRedirect) {
      const requestedRedirect = apiRedirect || '/clinica/pages/dashboard/';

      try {
        const target = new URL(requestedRedirect, window.location.origin);
        if (target.origin === window.location.origin && target.pathname.startsWith('/clinica/')) {
          return `${target.pathname}${target.search}${target.hash}`;
        }
      } catch (error) {
        return '/clinica/pages/dashboard/';
      }

      return '/clinica/pages/dashboard/';
    }

    function getRequestedRedirect() {
      return new URLSearchParams(window.location.search).get('redirect') || '';
    }

    /**
     * Validates the form fields and displays the first encountered error.
     * @returns {boolean} True if the form is valid, false otherwise.
     */
    function validateForm() {
      clearFormError();
      const username = dom.usernameInput.value.trim();
      const password = dom.passwordInput.value.trim();

      if (username === '') {
        displayFormError('Por favor, ingrese su usuario o correo.');
        return false;
      }
      if (password === '') {
        displayFormError('Por favor, ingrese su contraseña.');
        return false;
      }
      // Example of a more specific validation
      if (password.length < 8) {
        displayFormError('La contraseña debe tener al menos 8 caracteres.');
        return false;
      }

      return true;
    }

    /**
     * Displays a form-level error message.
     * @param {string} message The error message to display.
     */
    function displayFormError(message) {
      dom.errorMessage.textContent = message;
    }

    /**
     * Clears the form-level error message.
     */
    function clearFormError() {
      if (dom.errorMessage.textContent !== '') {
        dom.errorMessage.textContent = '';
      }
    }

    /**
     * Triggers a shake animation on the form to provide visual feedback for an error.
     */
    function triggerErrorShake() {
      dom.loginForm.classList.add('shake');
      dom.loginForm.addEventListener(
        'animationend',
        () => {
          dom.loginForm.classList.remove('shake');
        },
        { once: true },
      );
    }

    /**
     * Checks localStorage for a remembered username and populates the field if found.
     */
    function loadRememberedUser() {
      const rememberedUser = localStorage.getItem('rememberedUsername');
      if (rememberedUser) {
        dom.usernameInput.value = rememberedUser;
        dom.rememberMeCheckbox.checked = true;
      }
    }

    /**
     * Saves or removes the username from localStorage based on the "Remember me" checkbox.
     */
    function handleRememberMe() {
      if (dom.rememberMeCheckbox.checked) {
        localStorage.setItem('rememberedUsername', dom.usernameInput.value);
      } else {
        localStorage.removeItem('rememberedUsername');
      }
    }

    /**
     * Checks if the button is in a 'loading' or 'success' state.
     * @returns {boolean}
     */
    function isButtonBusy() {
      return dom.submitBtn.classList.contains('loading') || dom.submitBtn.classList.contains('success');
    }

    /**
     * Manages the visual and functional state of the submit button.
     * @param {'default' | 'loading' | 'success'} state The target state.
     */
    function setButtonState(state) {
      dom.submitBtn.classList.remove('loading', 'success');
      // By default, the button is enabled.
      dom.submitBtn.disabled = false;
      dom.submitBtn.setAttribute('aria-busy', 'false');

      switch (state) {
        case 'loading':
          dom.submitBtn.classList.add('loading');
          dom.submitBtn.disabled = true;
          dom.submitBtn.setAttribute('aria-busy', 'true');
          break;
        case 'success':
          dom.submitBtn.classList.add('success');
          dom.submitBtn.disabled = true;
          break;
        case 'default':
        default:
          // No special class needed for the default state.
          break;
      }
    }

    // Start the application
    init();
  });
})();

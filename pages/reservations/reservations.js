/*
 * reservations.js
 * Lógica para el formulario de agendamiento de citas por pasos.
 * Clínica Estándar
 */

(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('reservation-form');
    if (!form) {
      console.warn('Formulario de reservación no encontrado. El script no se ejecutará.');
      return;
    }

    // --- DOM Elements ---
    const nextBtn = document.getElementById('next-btn');
    const prevBtn = document.getElementById('prev-btn');
    const formSteps = Array.from(form.querySelectorAll('.form-step'));
    const progressSteps = Array.from(document.querySelectorAll('.progress-step'));
    const progressBar = document.querySelector('.progress-bar');
    const specialtyFilterInput = document.getElementById('specialty-filter');
    const specialtyGrid = document.getElementById('specialty-grid');
    const doctorSelectionGrid = document.getElementById('doctor-selection-grid');
    const noResultsMessage = form.querySelector('.no-results-message');
    const timeSlotsGrid = document.getElementById('time-slots');
    const patientNameInput = document.getElementById('patient-name');
    const patientEmailInput = document.getElementById('patient-email');
    // Calendar elements
    const calendarMonthYear = document.getElementById('calendar-month-year');
    const calendarGrid = document.getElementById('calendar-grid');
    const prevMonthBtn = document.getElementById('prev-month-btn');
    const nextMonthBtn = document.getElementById('next-month-btn');

    const patientPhoneInput = document.getElementById('patient-phone');
    const summarySpecialty = document.getElementById('summary-specialty');
    const summaryDoctor = document.getElementById('summary-doctor');
    const summaryDate = document.getElementById('summary-date');
    const summaryTime = document.getElementById('summary-time');
    const summaryName = document.getElementById('summary-name');
    const summaryEmail = document.getElementById('summary-email');
    const summaryPhone = document.getElementById('summary-phone');
    const totalSteps = formSteps.length;

    // --- State ---
    let currentStep = 0;
    const selection = {
      specialtyId: null,
      specialtyName: null,
      doctorId: null,
      doctorName: null,
      date: null,
      time: null,
      patientName: '',
      patientEmail: '',
      patientPhone: '',
      patientNotes: '',
    };
    const calendarState = {
      today: new Date(),
      currentMonth: new Date().getMonth(),
      currentYear: new Date().getFullYear(),
    };

    // --- Functions ---

    /**
     * Retrasa la ejecución de una función para optimizar el rendimiento en eventos frecuentes.
     * @param {Function} func La función a ejecutar.
     * @param {number} delay El tiempo de espera en milisegundos.
     * @returns {Function}
     */
    function debounce(func, delay = 300) {
      let timeoutId;
      return function (...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => func.apply(this, args), delay);
      };
    }

    /**
     * Valida un formato de correo electrónico simple.
     * @param {string} email
     * @returns {boolean}
     */
    function isValidEmail(email) {
      const re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
      return re.test(String(email).toLowerCase());
    }

    /**
     * Valida el paso actual y habilita/deshabilita el botón 'Siguiente'.
     */
    function validateStep() {
      let isValid = false;
      switch (currentStep) {
        case 0:
          isValid = !!selection.specialtyId;
          break;
        case 1:
          isValid = !!selection.doctorId;
          break;
        case 2:
          isValid = !!selection.date && !!selection.time;
          break;
        case 3:
          isValid = selection.patientName.trim() !== '' && isValidEmail(selection.patientEmail) && selection.patientPhone.trim() !== '';
          break;
        case 4:
          isValid = true; // El último paso siempre es válido para confirmar.
          break;
      }
      nextBtn.disabled = !isValid;
    }

    /**
     * Actualiza toda la UI del formulario para reflejar el paso actual.
     */
    function updateFormUI() {
      // 1. Actualizar la visibilidad de los pasos del formulario
      formSteps.forEach((step, index) => {
        step.classList.toggle('active', index === currentStep);
      });

      // 2. Actualizar la barra de progreso
      progressSteps.forEach((step, index) => {
        step.classList.toggle('active', index <= currentStep);
      });

      // 3. Actualizar el ancho de la línea de progreso
      const progressPercentage = totalSteps > 1 ? (currentStep / (totalSteps - 1)) * 100 : 0;
      progressBar.style.setProperty('--progress-width', `${progressPercentage}%`);

      // 4. Actualizar la visibilidad y texto de los botones
      prevBtn.style.visibility = currentStep > 0 ? 'visible' : 'hidden';
      nextBtn.textContent = currentStep === totalSteps - 1 ? 'Confirmar Cita' : 'Siguiente';

      // 5. Ajustar la altura del contenedor para una transición suave
      adjustFormHeight();

      // 6. Validar el paso actual para el botón 'Siguiente'
      validateStep();
    }

    /**
     * Ajusta la altura del contenedor del formulario al del paso activo.
     */
    function adjustFormHeight() {
      const activeStepElement = formSteps[currentStep];
      if (activeStepElement) {
        form.style.height = `${activeStepElement.offsetHeight}px`;
      }
    }

    /**
     * Avanza al siguiente paso.
     */
    function goToNextStep() {
      if (nextBtn.disabled) return;

      // If we are on the last step, the button is "Confirmar Cita"
      if (currentStep === totalSteps - 1) {
        submitForm();
        return;
      }

      if (currentStep < totalSteps - 1) {
        currentStep++;

        // Si hemos avanzado al paso 2 (índice 1), renderizamos los doctores.
        if (currentStep === 1) {
          renderDoctors(selection.specialtyId);
        }

        // Si hemos avanzado al paso 5 (índice 4), poblamos el resumen.
        if (currentStep === 4) {
          populateSummary();
        }

        // Si hemos avanzado al paso 3 (índice 2), renderizamos el calendario.
        if (currentStep === 2) {
          renderCalendar(calendarState.currentYear, calendarState.currentMonth);
        }

        updateFormUI();
      }
    }

    /**
     * Retrocede al paso anterior.
     */
    function goToPrevStep() {
      if (currentStep > 0) {
        // Si retrocedemos desde el paso de fecha/hora (2) al de doctor (1)
        if (currentStep === 2) {
          selection.doctorId = null;
          selection.doctorName = null;
        }
        // Si retrocedemos desde el paso de datos (3) al de fecha/hora (2)
        if (currentStep === 3) {
          selection.date = null;
          selection.time = null;
          timeSlotsGrid.innerHTML = '<div class="no-time-selected"><p>Seleccione una fecha en el calendario para ver los horarios.</p></div>';
        }
        // Si retrocedemos desde el paso de confirmación (4) al de datos (3)
        if (currentStep === 4) {
          selection.patientName = patientNameInput.value;
          selection.patientEmail = patientEmailInput.value;
          selection.patientPhone = patientPhoneInput.value;
        }

        currentStep--;
        updateFormUI();
      }
    }

    /**
     * Filtra las tarjetas de especialidad según el texto introducido.
     */
    function filterSpecialties() {
      const searchTerm = specialtyFilterInput.value.toLowerCase().trim();
      const cards = specialtyGrid.querySelectorAll('.selectable-card');
      let visibleCount = 0;

      cards.forEach((card) => {
        const name = card.dataset.name.toLowerCase();
        const isVisible = name.includes(searchTerm);
        card.style.display = isVisible ? 'flex' : 'none';
        if (isVisible) {
          visibleCount++;
        }
      });

      noResultsMessage.style.display = visibleCount === 0 ? 'block' : 'none';
      adjustFormHeight(); // La altura puede cambiar si se ocultan elementos
    }

    /**
     * Maneja la selección de una tarjeta de especialidad.
     * @param {Event} e - El evento de clic.
     */
    function handleSpecialtySelection(e) {
      const selectedCard = e.target.closest('.selectable-card');
      if (!selectedCard) return;

      specialtyGrid.querySelectorAll('.selectable-card').forEach((card) => card.classList.remove('active'));
      selectedCard.classList.add('active');

      selection.specialtyId = selectedCard.dataset.value;
      selection.specialtyName = selectedCard.dataset.name;

      // Validate the step to enable the next button internally
      validateStep();

      // Automatically proceed to the next step for a smoother UX.
      // A short delay allows the user to see their selection.
      setTimeout(goToNextStep, 400);
    }

    /**
     * Renderiza las tarjetas de los doctores para la especialidad seleccionada.
     * @param {string} specialtyId - El ID de la especialidad seleccionada.
     */
    function renderDoctors(specialtyId) {
      const doctors = doctorsData[specialtyId] || [];
      doctorSelectionGrid.innerHTML = ''; // Limpiar contenido previo (skeletons)

      if (doctors.length === 0) {
        doctorSelectionGrid.innerHTML = `<p class="no-results-message" style="display: block; grid-column: 1 / -1;">No hay doctores disponibles para esta especialidad.</p>`;
      } else {
        const doctorCardsHTML = doctors
          .map((doctor) => {
            const rating = doctor.valoracion || 0;
            const ratingStars = '★'.repeat(rating) + '☆'.repeat(5 - rating);
            return `
              <div class="selectable-card" data-value="${doctor.id}" data-name="${doctor.nombre}">
                <img src="${doctor.foto}" alt="Foto de ${doctor.nombre}" class="doctor-photo">
                <div class="doctor-info">
                  <span class="card-title">${doctor.nombre}</span>
                  <div class="doctor-rating" aria-label="Valoración: ${rating} de 5 estrellas">${ratingStars}</div>
                </div>
              </div>
            `;
          })
          .join('');
        doctorSelectionGrid.innerHTML = doctorCardsHTML;
      }

      // Esperar un instante para que el DOM se actualice antes de ajustar la altura
      setTimeout(adjustFormHeight, 50);
    }

    /**
     * Maneja la selección de una tarjeta de doctor.
     * @param {Event} e - El evento de clic.
     */
    function handleDoctorSelection(e) {
      const selectedCard = e.target.closest('.selectable-card');
      if (!selectedCard) return;

      doctorSelectionGrid.querySelectorAll('.selectable-card').forEach((card) => card.classList.remove('active'));
      selectedCard.classList.add('active');

      selection.doctorId = selectedCard.dataset.value;
      selection.doctorName = selectedCard.dataset.name;

      // Validate and automatically proceed
      validateStep();
      setTimeout(goToNextStep, 400);
    }

    /**
     * Renderiza el calendario para un mes y año específicos.
     * @param {number} year
     * @param {number} month - 0-indexed (0 for January)
     */
    function renderCalendar(year, month) {
      calendarState.currentYear = year;
      calendarState.currentMonth = month;

      const firstDayOfMonth = new Date(year, month, 1);
      const daysInMonth = new Date(year, month + 1, 0).getDate();
      const startDayOfWeek = (firstDayOfMonth.getDay() + 6) % 7; // 0=Monday, 6=Sunday

      calendarMonthYear.textContent = firstDayOfMonth.toLocaleDateString('es-ES', {
        month: 'long',
        year: 'numeric',
      });
      calendarGrid.innerHTML = '';

      // Add empty cells for days before the 1st of the month
      for (let i = 0; i < startDayOfWeek; i++) {
        calendarGrid.innerHTML += `<div class="calendar-day is-other-month"></div>`;
      }

      // Add days of the month
      for (let day = 1; day <= daysInMonth; day++) {
        const date = new Date(year, month, day);
        const dateString = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;

        let classes = 'calendar-day';
        if (date < calendarState.today.setHours(0, 0, 0, 0)) {
          classes += ' is-disabled';
        }
        if (date.toDateString() === calendarState.today.toDateString()) {
          classes += ' is-today';
        }
        if (dateString === selection.date) {
          classes += ' is-selected';
        }

        calendarGrid.innerHTML += `<button type="button" class="${classes}" data-date="${dateString}">${day}</button>`;
      }

      // Adjust height after rendering
      adjustFormHeight();
    }

    /**
     * Maneja la selección de una fecha en el calendario.
     * @param {Event} e
     */
    function handleDateSelection(e) {
      const selectedDay = e.target.closest('.calendar-day:not(.is-disabled)');
      if (!selectedDay) return;

      selection.date = selectedDay.dataset.date;

      // Update UI
      calendarGrid.querySelectorAll('.calendar-day.is-selected').forEach((el) => el.classList.remove('is-selected'));
      selectedDay.classList.add('is-selected');

      // Render time slots for the selected date
      renderTimeSlots();
    }

    /**
     * Simula y renderiza los horarios disponibles para la fecha seleccionada.
     */
    function renderTimeSlots() {
      selection.time = null; // Reset time on new date selection
      validateStep();

      timeSlotsGrid.innerHTML = '<div class="skeleton-time"></div><div class="skeleton-time"></div><div class="skeleton-time"></div><div class="skeleton-time"></div>';
      adjustFormHeight();

      // Simulación de una llamada a la API para obtener horarios
      setTimeout(() => {
        // Generar horarios de 7:00 a 21:30 en intervalos de 30 minutos.
        const allTimes = [];
        for (let hour = 7; hour < 22; hour++) {
          // from 7:00 up to (but not including) 22:00
          allTimes.push(`${String(hour).padStart(2, '0')}:00`);
          allTimes.push(`${String(hour).padStart(2, '0')}:30`);
        }

        let filteredTimes = allTimes;

        // Si la fecha seleccionada es hoy, filtrar los horarios que ya pasaron
        const selectedDate = new Date(selection.date + 'T00:00:00');
        if (selectedDate.toDateString() === calendarState.today.toDateString()) {
          const now = new Date();
          const currentTime = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
          filteredTimes = allTimes.filter((time) => time > currentTime);
        }

        if (filteredTimes.length === 0) {
          timeSlotsGrid.innerHTML = `<p class="no-results-message" style="display: block;">No hay horarios disponibles para esta fecha.</p>`;
        } else {
          const timeSlotsHTML = filteredTimes
            .map(
              (time) => `
            <button type="button" class="selectable-card" data-value="${time}">
              <span class="card-title">${time}</span>
            </button>
          `,
            )
            .join('');
          timeSlotsGrid.innerHTML = timeSlotsHTML;
        }
        adjustFormHeight();
      }, 600); // Simular retardo de red
    }

    /**
     * Navega al mes anterior o siguiente en el calendario.
     * @param {number} direction - 1 para siguiente, -1 para anterior.
     */
    function navigateMonth(direction) {
      let newMonth = calendarState.currentMonth + direction;
      let newYear = calendarState.currentYear;
      if (newMonth < 0) {
        newMonth = 11;
        newYear--;
      } else if (newMonth > 11) {
        newMonth = 0;
        newYear++;
      }
      renderCalendar(newYear, newMonth);
    }

    /**
     * Maneja la selección de un horario.
     * @param {Event} e - El evento de clic.
     */
    function handleTimeSelection(e) {
      const selectedCard = e.target.closest('.selectable-card:not(.is-disabled)');
      if (!selectedCard) return;

      timeSlotsGrid.querySelectorAll('.selectable-card').forEach((card) => card.classList.remove('active'));
      selectedCard.classList.add('active');

      selection.time = selectedCard.dataset.value;

      // Validate and automatically proceed
      validateStep();
      setTimeout(goToNextStep, 400);
    }

    /**
     * Valida los campos de datos del paciente en tiempo real.
     * @param {Event} e
     */
    function handlePatientDataInput(e) {
      const input = e.target;
      const value = input.value;
      let fieldIsValid = true;

      switch (input.id) {
        case 'patient-name':
          selection.patientName = value;
          fieldIsValid = value.trim() !== '';
          break;
        case 'patient-email':
          selection.patientEmail = value;
          fieldIsValid = isValidEmail(value);
          break;
        case 'patient-phone':
          selection.patientPhone = value;
          fieldIsValid = value.trim() !== '';
          break;
      }

      input.parentElement.classList.toggle('has-error', !fieldIsValid);
      validateStep();
    }

    /**
     * Rellena la tarjeta de resumen con los datos seleccionados.
     */
    function populateSummary() {
      summarySpecialty.textContent = selection.specialtyName || 'No seleccionado';
      summaryDoctor.textContent = selection.doctorName || 'No seleccionado';
      summaryDate.textContent = selection.date
        ? new Date(selection.date + 'T00:00:00').toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
          })
        : 'No seleccionado';
      summaryTime.textContent = selection.time ? `${selection.time} hs.` : 'No seleccionado';
      summaryName.textContent = selection.patientName || 'No ingresado';
      summaryEmail.textContent = selection.patientEmail || 'No ingresado';
      summaryPhone.textContent = selection.patientPhone || 'No ingresado';
    }

    /**
     * Simulates the form submission and displays the success message.
     */
    function submitForm() {
      nextBtn.disabled = true;
      prevBtn.style.visibility = 'hidden';
      nextBtn.innerHTML = '<span class="btn-loader"></span>'; // Add a loader
      nextBtn.classList.add('is-loading');

      // Simulate an API call
      setTimeout(() => {
        showSuccessMessage();
      }, 2000); // Simulate 2-second network delay
    }

    /**
     * Replaces the form content with a success message.
     */
    function showSuccessMessage() {
      const formWrapper = document.querySelector('.reservation-form-wrapper');
      const successHTML = `
        <div class="success-message-container">
          <div class="success-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
              <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
          </div>
          <h2 class="success-title">¡Cita Agendada!</h2>
          <p class="success-text">Su cita ha sido confirmada exitosamente. Recibirá un correo electrónico con todos los detalles. Gracias por confiar en Clínica Estándar.</p>
          <a href="/clinica/" class="btn btn-primary">Volver al Inicio</a>
        </div>`;
      formWrapper.innerHTML = successHTML;
      formWrapper.style.height = 'auto'; // Let the content define the height
    }

    // --- Event Listeners ---
    nextBtn.addEventListener('click', goToNextStep);
    prevBtn.addEventListener('click', goToPrevStep);
    specialtyFilterInput.addEventListener('input', debounce(filterSpecialties, 300));
    specialtyGrid.addEventListener('click', handleSpecialtySelection);
    doctorSelectionGrid.addEventListener('click', handleDoctorSelection);
    calendarGrid.addEventListener('click', handleDateSelection);
    prevMonthBtn.addEventListener('click', () => navigateMonth(-1));
    nextMonthBtn.addEventListener('click', () => navigateMonth(1));
    timeSlotsGrid.addEventListener('click', handleTimeSelection);
    patientNameInput.addEventListener('input', handlePatientDataInput);
    patientEmailInput.addEventListener('input', handlePatientDataInput);
    patientPhoneInput.addEventListener('input', handlePatientDataInput);

    // --- Initialization ---

    // Ajustar la altura inicial al cargar la página
    adjustFormHeight();

    // Reajustar la altura si cambia el tamaño de la ventana (responsive)
    window.addEventListener('resize', adjustFormHeight);

    // Iniciar con el botón 'Siguiente' deshabilitado
    validateStep();

    // TODO: Implementar lógica de pre-selección si viene de URL
    // const preselectedSpecialty = form.dataset.selectedSpecialtyId;
    // if (preselectedSpecialty) { ... }
  });
})();

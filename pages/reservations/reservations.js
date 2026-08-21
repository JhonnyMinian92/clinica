/*
 * reservations.js
 * Lógica para el formulario de agendamiento de citas por pasos.
 * Sitio publico
 */

(function () {
  'use strict';

  function appUrl(path) {
    const basePath = String(window.CLINICA_BASE_PATH || '').replace(/\/$/, '');
    return `${basePath}/${String(path || '').replace(/^\/+/, '')}`;
  }

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
    const doctorFilterInput = document.getElementById('doctor-filter');
    const doctorSelectionGrid = document.getElementById('doctor-selection-grid');
    const noResultsMessage = form.querySelector('.no-results-message');
    const doctorNoResultsMessage = document.getElementById('doctor-no-results');
    const timeSlotWrapper = document.querySelector('.time-slot-wrapper');
    const timeSlotsGrid = document.getElementById('time-slots');
    const selectedDateLabel = document.getElementById('selected-date-label');
    const availableSlotsCount = document.getElementById('available-slots-count');
    const scheduleDoctorLabel = document.getElementById('schedule-doctor-label');
    const scheduleDateLabel = document.getElementById('schedule-date-label');
    const scheduleTimeLabel = document.getElementById('schedule-time-label');
    const patientNameInput = document.getElementById('patient-name');
    const patientEmailInput = document.getElementById('patient-email');
    const patientNotesInput = document.getElementById('patient-notes');
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
    const config = typeof reservationConfig !== 'undefined' ? reservationConfig : {};
    const GENERAL_API = config.generalApi || appUrl('api/general/');

    // --- State ---
    let currentStep = 0;
    const selection = {
      specialtyId: null,
      specialtyName: null,
      doctorId: null,
      doctorName: null,
      doctorScheduleRules: {},
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

    function findCardByValue(container, value) {
      return Array.from(container.querySelectorAll('.selectable-card')).find((card) => card.dataset.value === value) || null;
    }

    function markSelectedCard(container, selectedCard) {
      container.querySelectorAll('.selectable-card').forEach((card) => {
        const isSelected = card === selectedCard;
        card.classList.toggle('active', isSelected);
        if (card.hasAttribute('aria-pressed')) {
          card.setAttribute('aria-pressed', String(isSelected));
        }
      });
    }

    function formatDateLabel(dateString, options = {}) {
      if (!dateString) return 'Pendiente';

      return new Date(dateString + 'T00:00:00').toLocaleDateString('es-ES', {
        weekday: options.weekday || 'short',
        day: 'numeric',
        month: options.month || 'short',
      });
    }

    function updateScheduleSummary(slotCountText = null) {
      if (scheduleDoctorLabel) {
        scheduleDoctorLabel.textContent = selection.doctorName || 'Pendiente';
      }
      if (scheduleDateLabel) {
        scheduleDateLabel.textContent = selection.date ? formatDateLabel(selection.date) : 'Pendiente';
      }
      if (scheduleTimeLabel) {
        scheduleTimeLabel.textContent = selection.time || 'Pendiente';
      }
      if (selectedDateLabel) {
        selectedDateLabel.textContent = selection.date
          ? formatDateLabel(selection.date, { weekday: 'long', month: 'long' })
          : 'Seleccione una fecha';
      }
      if (availableSlotsCount) {
        availableSlotsCount.textContent = slotCountText ?? (selection.date ? 'Buscando' : '--');
      }
      if (timeSlotWrapper) {
        timeSlotWrapper.classList.toggle('has-date', !!selection.date);
        if (!selection.date) {
          timeSlotWrapper.classList.remove('has-slots', 'has-no-slots');
        }
      }
    }

    function getTimePeriod(time) {
      const minutes = timeToMinutes(time);
      if (minutes === null) return 'other';
      if (minutes < 12 * 60) return 'morning';
      if (minutes < 18 * 60) return 'afternoon';
      return 'evening';
    }

    function getTimePeriodLabel(period) {
      return {
        morning: 'Mañana',
        afternoon: 'Tarde',
        evening: 'Noche',
        other: 'Otros',
      }[period] || 'Otros';
    }

    function getSlotCountLabel(count) {
      if (count === 0) return 'Sin cupos';
      return count === 1 ? '1 cupo' : `${count} cupos`;
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
     * Valida un número de teléfono (solo dígitos, 7-15 caracteres).
     * @param {string} phone
     * @returns {boolean}
     */
    function isValidPhone(phone) {
      const re = /^\d{7,15}$/;
      return re.test(String(phone).trim());
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
          isValid = selection.patientName.trim() !== '' && isValidEmail(selection.patientEmail) && isValidPhone(selection.patientPhone);
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
        if (index === currentStep) {
          step.setAttribute('aria-current', 'step');
        } else {
          step.removeAttribute('aria-current');
        }
      });

      // 3. Actualizar el ancho de la línea de progreso
      const progressPercentage = totalSteps > 1 ? (currentStep / (totalSteps - 1)) * 100 : 0;
      progressBar.style.setProperty('--progress-width', `${progressPercentage}%`);

      // 4. Actualizar la visibilidad y texto de los botones
      prevBtn.style.visibility = currentStep > 0 ? 'visible' : 'hidden';
      nextBtn.textContent = currentStep === totalSteps - 1 ? getConfigText('confirmLabel', 'Confirmar Cita') : getConfigText('nextLabel', 'Siguiente');

      // 5. Ajustar la altura del contenedor para una transición suave
      adjustFormHeight();

      // 6. Validar el paso actual para el botón 'Siguiente'
      validateStep();
    }

    /**
     * Ajusta la altura del contenedor del formulario al del paso activo.
     */
    function adjustFormHeight() {
      form.style.height = 'auto';
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
        // Dejando el Paso 2 (Doctor) para ir al Paso 1 (Especialidad)
        if (currentStep === 1) {
          selection.doctorId = null;
          selection.doctorName = null;
          if (doctorFilterInput) doctorFilterInput.value = '';
          updateScheduleSummary();
        }
        // Dejando el Paso 3 (Fecha/Hora) para ir al Paso 2 (Doctor)
        else if (currentStep === 2) {
          selection.date = null;
          selection.time = null;
          timeSlotsGrid.innerHTML = '<div class="no-time-selected"><p>Seleccione una fecha en el calendario para ver los horarios.</p></div>';
          updateScheduleSummary();
        }
        // Dejando el Paso 4 (Datos) para ir al Paso 3 (Fecha/Hora)
        else if (currentStep === 3) {
          // Preservar los datos del paciente al retroceder
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
        card.style.display = isVisible ? '' : 'none';
        if (isVisible) {
          visibleCount++;
        }
      });

      noResultsMessage.style.display = visibleCount === 0 ? 'block' : 'none';
      adjustFormHeight(); // La altura puede cambiar si se ocultan elementos
    }

    /**
     * Filtra las tarjetas de doctores según el texto introducido.
     */
    function filterDoctors() {
      const searchTerm = doctorFilterInput.value.toLowerCase().trim();
      const cards = doctorSelectionGrid.querySelectorAll('.selectable-card');
      let visibleCount = 0;

      cards.forEach((card) => {
        const name = card.dataset.name.toLowerCase();
        const isVisible = name.includes(searchTerm);
        card.style.display = isVisible ? '' : 'none';
        if (isVisible) {
          visibleCount++;
        }
      });

      const hasContent = cards.length > 0;
      doctorNoResultsMessage.style.display = visibleCount === 0 && hasContent ? 'block' : 'none';

      adjustFormHeight();
    }

    /**
     * Maneja la selección de una tarjeta de especialidad.
     * @param {Event} e - El evento de clic.
     */
    function handleSpecialtySelection(e) {
      const selectedCard = e.target.closest('.selectable-card');
      if (!selectedCard) return;

      selectSpecialtyCard(selectedCard, true);
    }

    function selectSpecialtyCard(selectedCard, autoAdvance = false) {
      markSelectedCard(specialtyGrid, selectedCard);

      const specialtyId = selectedCard.dataset.value;
      const specialtyName = selectedCard.dataset.name;

      // Si el usuario cambia de especialidad, reseteamos la selección de doctor
      if (selection.specialtyId && selection.specialtyId !== specialtyId) {
        selection.doctorId = null;
        selection.doctorName = null;
        selection.date = null;
        selection.time = null;
        updateScheduleSummary();
      }

      selection.specialtyId = specialtyId;
      selection.specialtyName = specialtyName;

      // Validate the step to enable the next button internally
      validateStep();

      if (autoAdvance) {
        setTimeout(goToNextStep, 400);
      }

      return true;
    }

    /**
     * Renderiza las tarjetas de los doctores para la especialidad seleccionada.
     * @param {string} specialtyId - El ID de la especialidad seleccionada.
     */
    function renderDoctors(specialtyId) {
      const doctors = doctorsData[specialtyId] || [];
      doctorSelectionGrid.innerHTML = ''; // Limpiar contenido previo (skeletons)
      // Resetear el estado del filtro de doctores
      doctorFilterInput.value = '';
      doctorNoResultsMessage.style.display = 'none';

      if (doctors.length === 0) {
        doctorSelectionGrid.innerHTML = `<p class="no-results-message" style="display: block; grid-column: 1 / -1;">No hay doctores disponibles para esta especialidad.</p>`;
      } else {
        const doctorCardsHTML = doctors
          .map((doctor) => {
            const rating = doctor.valoracion || 0;
            const ratingStars = '★'.repeat(rating) + '☆'.repeat(5 - rating);
            return `
              <button type="button" class="selectable-card" data-value="${escapeHtml(doctor.id)}" data-name="${escapeHtml(doctor.nombre)}" aria-pressed="false">
                <img src="${escapeHtml(doctor.foto)}" alt="Foto de ${escapeHtml(doctor.nombre)}" class="doctor-photo">
                <div class="doctor-info">
                  <span class="card-title">${escapeHtml(doctor.nombre)}</span>
                  <div class="doctor-rating" aria-label="Valoración: ${rating} de 5 estrellas">${ratingStars}</div>
                </div>
              </button>
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

      selectDoctorCard(selectedCard, true);
    }

    function selectDoctorCard(selectedCard, autoAdvance = false) {
      markSelectedCard(doctorSelectionGrid, selectedCard);

      const doctorId = selectedCard.dataset.value;
      const selectedDoctor = doctorsData[selection.specialtyId]?.find((doc) => doc.id === doctorId);

      if (selectedDoctor) {
        selection.doctorId = selectedDoctor.id;
        selection.doctorName = selectedDoctor.nombre;
        selection.doctorScheduleRules = selectedDoctor.horarios_detallados || {};
      } else {
        // Fallback si el doctor no se encuentra (no debería ocurrir)
        selection.doctorId = doctorId;
        selection.doctorName = selectedCard.dataset.name;
        selection.doctorScheduleRules = {};
      }

      selection.date = null;
      selection.time = null;
      timeSlotsGrid.innerHTML = '<div class="no-time-selected"><p>Seleccione una fecha en el calendario para ver los horarios.</p></div>';
      updateScheduleSummary();

      // Validate and automatically proceed
      validateStep();
      if (autoAdvance) {
        setTimeout(goToNextStep, 400);
      }

      return true;
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

        // Obtener el nombre del día en español para verificar el horario
        const isoWeekday = String(date.getDay() === 0 ? 7 : date.getDay());

        // Verificar si el doctor trabaja en este día de la semana
        const worksOnThisDay = doctorWorksOnDate(dateString, isoWeekday);

        let classes = 'calendar-day';
        const todayMidnight = new Date(calendarState.today);
        todayMidnight.setHours(0, 0, 0, 0);
        if (date < todayMidnight || !worksOnThisDay) {
          classes += ' is-disabled';
        }
        if (date.toDateString() === calendarState.today.toDateString()) {
          classes += ' is-today';
        }
        if (dateString === selection.date) {
          classes += ' is-selected';
        }

        const dayLabel = date.toLocaleDateString('es-ES', {
          weekday: 'long',
          day: 'numeric',
          month: 'long',
          year: 'numeric',
        });
        const disabledAttr = classes.includes('is-disabled') ? 'disabled aria-disabled="true"' : '';
        const pressedAttr = dateString === selection.date ? 'true' : 'false';
        calendarGrid.innerHTML += `<button type="button" class="${classes}" data-date="${dateString}" aria-label="${escapeHtml(dayLabel)}" aria-pressed="${pressedAttr}" ${disabledAttr}>${day}</button>`;
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
      calendarGrid.querySelectorAll('.calendar-day.is-selected').forEach((el) => {
        el.classList.remove('is-selected');
        el.setAttribute('aria-pressed', 'false');
      });
      selectedDay.classList.add('is-selected');
      selectedDay.setAttribute('aria-pressed', 'true');
      updateScheduleSummary('Buscando');

      // Render time slots for the selected date
      renderTimeSlots();
    }

    /**
     * Simula y renderiza los horarios disponibles para la fecha seleccionada.
     */
    function buildTimeSlotButton(time, index, isBooked) {
      const bookedClass = isBooked ? 'is-booked is-disabled' : '';
      const disabledAttr = isBooked ? 'disabled aria-disabled="true"' : '';
      const timeLabel = isBooked ? `Horario ${time} no disponible` : `Seleccionar horario ${time}`;

      return `
        <button type="button" class="selectable-card ${bookedClass}" data-value="${time}" aria-label="${timeLabel}" aria-pressed="false" style="--animation-order: ${index};" ${disabledAttr}>
          <span class="card-title">${time}</span>
        </button>
      `;
    }

    function renderTimeSlots() {
      selection.time = null; // Reset time on new date selection
      validateStep();
      updateScheduleSummary('Buscando');

      // Mostrar el estado de carga
      timeSlotWrapper.classList.add('is-loading');
      timeSlotWrapper.classList.remove('has-slots', 'has-no-slots');

      // Simulación de una llamada a la API para obtener horarios
      setTimeout(() => {
        // 1. Determinar el turno del doctor para el día seleccionado
        const selectedDateObj = new Date(selection.date + 'T00:00:00');
        const isoWeekday = String(selectedDateObj.getDay() === 0 ? 7 : selectedDateObj.getDay());
        const rules = getScheduleRulesForDate(selection.date, isoWeekday);

        const availableTimes = generateTimesFromRules(rules);
        let filteredTimes = availableTimes;

        // Si la fecha seleccionada es hoy, filtrar los horarios que ya pasaron
        if (selectedDateObj.toDateString() === calendarState.today.toDateString()) {
          const now = new Date();
          const currentTime = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
          filteredTimes = availableTimes.filter((time) => time > currentTime);
        }

        // 3. Obtener las citas ya reservadas para este doctor y fecha
        const bookedSlots = bookedAppointmentsData[selection.doctorId]?.[selection.date] || [];
        const availableSlotCount = filteredTimes.filter((time) => !bookedSlots.includes(time)).length;
        timeSlotWrapper.classList.toggle('has-slots', availableSlotCount > 0);
        timeSlotWrapper.classList.toggle('has-no-slots', availableSlotCount === 0);
        updateScheduleSummary(getSlotCountLabel(availableSlotCount));

        if (filteredTimes.length === 0) {
          timeSlotsGrid.innerHTML = `<div class="no-time-selected"><p>No hay horarios disponibles para este turno.</p></div>`;
        } else {
          const groupedTimes = filteredTimes.reduce((groups, time) => {
            const period = getTimePeriod(time);
            groups[period] = groups[period] || [];
            groups[period].push(time);
            return groups;
          }, {});

          let slotIndex = 0;
          const timeSlotsHTML = ['morning', 'afternoon', 'evening', 'other']
            .filter((period) => groupedTimes[period]?.length)
            .map((period) => {
              const times = groupedTimes[period];
              const availableInPeriod = times.filter((time) => !bookedSlots.includes(time)).length;
              const buttons = times
                .map((time) => buildTimeSlotButton(time, slotIndex++, bookedSlots.includes(time)))
                .join('');

              return `
                <section class="time-period" aria-label="${getTimePeriodLabel(period)}">
                  <div class="time-period-header">
                    <h4>${getTimePeriodLabel(period)}</h4>
                    <span>${getSlotCountLabel(availableInPeriod)}</span>
                  </div>
                  <div class="time-period-grid">
                    ${buttons}
                  </div>
                </section>
              `;
            })
            .join('');
          timeSlotsGrid.innerHTML = timeSlotsHTML;
        }

        // Ocultar el estado de carga y ajustar altura
        timeSlotWrapper.classList.remove('is-loading');
        adjustFormHeight();
      }, 600); // Simular retardo de red
    }

    function generateTimesFromRules(rules) {
      const times = [];

      rules.forEach((rule) => {
        const start = timeToMinutes(rule.start_time);
        const end = timeToMinutes(rule.end_time);
        const slotMinutes = Number(rule.slot_minutes || 15);

        if (start === null || end === null || slotMinutes <= 0 || start >= end) {
          return;
        }

        for (let current = start; current < end; current += slotMinutes) {
          times.push(minutesToTime(current));
        }
      });

      return Array.from(new Set(times)).sort();
    }

    function doctorWorksOnDate(dateString, isoWeekday) {
      return getScheduleRulesForDate(dateString, isoWeekday).length > 0;
    }

    function getScheduleRulesForDate(dateString, isoWeekday) {
      const weeklyRules = selection.doctorScheduleRules[isoWeekday] || [];
      const datedRules = selection.doctorScheduleRules[dateString] || [];
      return datedRules.concat(weeklyRules);
    }

    function timeToMinutes(value) {
      const match = String(value || '').match(/^(\d{2}):(\d{2})(?::\d{2})?$/);
      if (!match) return null;

      return Number(match[1]) * 60 + Number(match[2]);
    }

    function minutesToTime(value) {
      const hours = Math.floor(value / 60);
      const minutes = value % 60;
      return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
    }

    function getCsrfToken() {
      const match = document.cookie.match(new RegExp('(^|;)\\s*XSRF-TOKEN\\s*=\\s*([^;]+)'));
      return match ? decodeURIComponent(match.pop()) : '';
    }

    /**
     * Navega al mes anterior o siguiente en el calendario.
     * @param {number} direction - 1 para siguiente, -1 para anterior.
     */
    function navigateMonth(direction) {
      // Add a class to trigger the fade-out animation
      calendarGrid.classList.add('is-changing');
      // Disable nav buttons to prevent rapid clicking
      prevMonthBtn.disabled = true;
      nextMonthBtn.disabled = true;

      // Wait for the animation to be halfway through before swapping content
      setTimeout(() => {
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

        // Remove the class to trigger the fade-in animation and re-enable buttons
        calendarGrid.classList.remove('is-changing');
        prevMonthBtn.disabled = false;
        nextMonthBtn.disabled = false;
      }, 150); // Half of 300ms transition
    }

    /**
     * Maneja la selección de un horario.
     * @param {Event} e - El evento de clic.
     */
    function handleTimeSelection(e) {
      const selectedCard = e.target.closest('.selectable-card:not(.is-disabled)');
      if (!selectedCard) return;

      markSelectedCard(timeSlotsGrid, selectedCard);

      selection.time = selectedCard.dataset.value;
      updateScheduleSummary();

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
          fieldIsValid = isValidPhone(value);
          break;
        case 'patient-notes':
          selection.patientNotes = value;
          fieldIsValid = true;
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
    async function submitForm() {
      const existingError = form.querySelector('.form-submission-error');
      if (existingError) {
        existingError.remove();
      }

      nextBtn.disabled = true;
      prevBtn.style.visibility = 'hidden';
      nextBtn.innerHTML = '<span class="btn-loader"></span>'; // Add a loader
      nextBtn.classList.add('is-loading');

      try {
        const response = await fetch(GENERAL_API, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-Token': getCsrfToken(),
          },
          body: JSON.stringify({
            action: 'create_appointment',
            specialtyId: selection.specialtyId,
            specialtyName: selection.specialtyName,
            doctorId: selection.doctorId,
            doctorName: selection.doctorName,
            date: selection.date,
            time: selection.time,
            patientName: selection.patientName,
            patientEmail: selection.patientEmail,
            patientPhone: selection.patientPhone,
            patientNotes: selection.patientNotes,
          }),
        });

        const result = await response.json().catch(() => ({}));
        if (!response.ok || !result.success) {
          throw new Error(result.error || 'No se pudo crear la cita.');
        }

        showSuccessMessage();
      } catch (error) {
        nextBtn.disabled = false;
        prevBtn.style.visibility = 'visible';
        nextBtn.textContent = getConfigText('confirmLabel', 'Confirmar Cita');
        nextBtn.classList.remove('is-loading');

        const errorContainer = document.createElement('div');
        errorContainer.className = 'form-submission-error';
        errorContainer.textContent = error.message || 'No se pudo crear la cita. Intente nuevamente.';
        errorContainer.setAttribute('role', 'alert');

        const navigation = form.querySelector('.form-navigation');
        navigation.insertAdjacentElement('beforebegin', errorContainer);
        adjustFormHeight();
      }
    }

    /**
     * Replaces the form content with a success message.
     */
    function showSuccessMessage() {
      const formWrapper = document.querySelector('.reservation-form-wrapper');
      const clinicName = getConfigText('clinicName', 'la clinica');
      const successTitle = getConfigText('successTitle', 'Cita Agendada');
      const successText = getConfigText('successText', 'Su cita ha sido confirmada exitosamente. Recibira un correo electronico con todos los detalles. Gracias por confiar en {clinic}.').replaceAll('{clinic}', clinicName);
      const backHomeLabel = getConfigText('backHomeLabel', 'Volver al Inicio');
      const successHTML = `
        <div class="success-message-container">
          <div class="success-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
              <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
          </div>
          <h2 class="success-title">${escapeHtml(successTitle)}</h2>
          <p class="success-text">${escapeHtml(successText)}</p>
          <a href="${escapeAttribute(config.homeUrl || appUrl(''))}" class="btn btn-primary">${escapeHtml(backHomeLabel)}</a>
        </div>`;
      formWrapper.innerHTML = successHTML;
      formWrapper.style.height = 'auto'; // Let the content define the height
    }

    function getConfigText(key, fallback) {
      const value = String(config[key] ?? '').trim();
      return value !== '' ? value : fallback;
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

    // --- Event Listeners ---
    nextBtn.addEventListener('click', goToNextStep);
    prevBtn.addEventListener('click', goToPrevStep);
    specialtyFilterInput.addEventListener('input', window.ClinicaUtils.debounce(filterSpecialties, 300));
    specialtyGrid.addEventListener('click', handleSpecialtySelection);
    doctorFilterInput.addEventListener('input', window.ClinicaUtils.debounce(filterDoctors, 300));
    doctorSelectionGrid.addEventListener('click', handleDoctorSelection);
    calendarGrid.addEventListener('click', handleDateSelection);
    prevMonthBtn.addEventListener('click', () => navigateMonth(-1));
    nextMonthBtn.addEventListener('click', () => navigateMonth(1));
    timeSlotsGrid.addEventListener('click', handleTimeSelection);
    patientNameInput.addEventListener('input', handlePatientDataInput);
    patientEmailInput.addEventListener('input', handlePatientDataInput);
    patientPhoneInput.addEventListener('input', handlePatientDataInput);
    patientNotesInput.addEventListener('input', handlePatientDataInput);
    updateScheduleSummary();

    // --- Initialization ---

    // Ajustar la altura inicial al cargar la página
    adjustFormHeight();

    // Reajustar la altura si cambia el tamaño de la ventana (responsive)
    window.addEventListener('resize', adjustFormHeight);

    applyUrlPreselection();
    validateStep();

    function applyUrlPreselection() {
      const preselectedDoctorId = form.dataset.selectedDoctorId;
      let preselectedSpecialtyId = form.dataset.selectedSpecialtyId;

      if (!preselectedSpecialtyId && preselectedDoctorId) {
        Object.entries(doctorsData).some(([specialtyId, doctors]) => {
          const doctorExists = doctors.some((doctor) => doctor.id === preselectedDoctorId);
          if (doctorExists) {
            preselectedSpecialtyId = specialtyId;
          }
          return doctorExists;
        });
      }

      if (!preselectedSpecialtyId) return;

      const specialtyCard = findCardByValue(specialtyGrid, preselectedSpecialtyId);
      if (!specialtyCard) return;

      selectSpecialtyCard(specialtyCard, false);
      renderDoctors(preselectedSpecialtyId);
      currentStep = 1;

      if (preselectedDoctorId) {
        const doctorCard = findCardByValue(doctorSelectionGrid, preselectedDoctorId);
        if (doctorCard) {
          selectDoctorCard(doctorCard, false);
          currentStep = 2;
          renderCalendar(calendarState.currentYear, calendarState.currentMonth);
        }
      }

      updateFormUI();
    }
  });
})();

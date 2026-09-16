/* Adaptive reservation controller v2.
 * Preserves the original reservation UX while adapting the number of steps
 * to the active doctor/specialty configuration.
 */
(function () {
  'use strict';

  const base = String(window.CLINICA_BASE_PATH || '').replace(/\/$/, '');
  const url = (path) => `${base}/${String(path || '').replace(/^\/+/, '')}`;
  const cfg = typeof reservationConfig !== 'undefined' ? reservationConfig : {};
  const API = String(cfg.generalApi || url('api/appointments/'));

  const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
  }[char]));

  const getCsrf = () => {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
    return match ? decodeURIComponent(match[1]) : '';
  };

  const validEmail = (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value).trim());
  const validDocument = (value) => /^[A-Za-z0-9]{6,20}$/.test(String(value).trim());
  const validPhone = (value) => /^\d{7,15}$/.test(String(value).trim());

  const dateKey = (year, month, day) =>
    `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;

  const formatDate = (value, options = {}) => value
    ? new Date(`${value}T00:00:00`).toLocaleDateString('es-EC', {
        weekday: options.weekday || 'long',
        day: 'numeric',
        month: options.month || 'long',
        year: options.year || 'numeric',
      })
    : 'Pendiente';

  const timeToMinutes = (value) => {
    const match = String(value || '').match(/^(\d{2}):(\d{2})(?::\d{2})?$/);
    if (!match) return null;
    return Number(match[1]) * 60 + Number(match[2]);
  };

  const minutesToTime = (value) =>
    `${String(Math.floor(value / 60)).padStart(2, '0')}:${String(value % 60).padStart(2, '0')}`;

  document.addEventListener('DOMContentLoaded', async () => {
    const form = document.getElementById('reservation-form');
    if (!form) return;

    const steps = [...form.querySelectorAll('.form-step')];
    const progress = [...document.querySelectorAll('.progress-step')];
    const next = document.getElementById('next-btn');
    const prev = document.getElementById('prev-btn');
    const specialtyGrid = document.getElementById('specialty-grid');
    const specialtyFilter = document.getElementById('specialty-filter');
    const doctorGrid = document.getElementById('doctor-selection-grid');
    const doctorFilter = document.getElementById('doctor-filter');
    const doctorNoResults = document.getElementById('doctor-no-results');
    const calendarGrid = document.getElementById('calendar-grid');
    const calendarTitle = document.getElementById('calendar-month-year');
    const timeGrid = document.getElementById('time-slots');
    const timeWrapper = document.querySelector('.time-slot-wrapper');
    const selectedDateLabel = document.getElementById('selected-date-label');
    const availableCount = document.getElementById('available-slots-count');
    const scheduleDoctor = document.getElementById('schedule-doctor-label');
    const scheduleDate = document.getElementById('schedule-date-label');
    const scheduleTime = document.getElementById('schedule-time-label');

    const patient = {
      name: document.getElementById('patient-name'),
      cedula: document.getElementById('patient-cedula'),
      email: document.getElementById('patient-email'),
      phone: document.getElementById('patient-phone'),
      notes: document.getElementById('patient-notes'),
      emailOpt: document.getElementById('notify-email-optin'),
      whatsappOpt: document.getElementById('notify-whatsapp-optin'),
    };

    const summary = {
      specialty: document.getElementById('summary-specialty'),
      doctor: document.getElementById('summary-doctor'),
      date: document.getElementById('summary-date'),
      time: document.getElementById('summary-time'),
      name: document.getElementById('summary-name'),
      cedula: document.getElementById('summary-cedula'),
      email: document.getElementById('summary-email'),
      phone: document.getElementById('summary-phone'),
    };

    let mode = 'multi_doctor_multi_specialty';
    let stepNames = [];
    let stepMap = [];
    let currentStep = 0;
    let specialties = [];
    let doctors = [];
    let slotsRequestId = 0;
    let slotPollTimer = null;

    const selection = {
      specialtyId: null,
      specialtyName: null,
      doctorId: null,
      doctorName: null,
      date: null,
      time: null,
    };

    const calendar = {
      month: new Date().getMonth(),
      year: new Date().getFullYear(),
    };

    const findSpecialty = (id) =>
      specialties.find((item) => String(item.id) === String(id)) || null;

    const findDoctor = (id) =>
      doctors.find((item) => String(item.id) === String(id)) || null;

    const doctorsForSpecialty = (specialtyId) =>
      doctors.filter((doctor) =>
        (doctor.especialidades || []).some(
          (specialty) => String(specialty.id) === String(specialtyId)
        )
      );

    const scheduleRules = () => findDoctor(selection.doctorId)?.horarios_detallados || {};

    const rulesForDate = (date) => {
      const dateObject = new Date(`${date}T00:00:00`);
      const weekday = String(dateObject.getDay() === 0 ? 7 : dateObject.getDay());
      const rules = scheduleRules();
      return [...(rules[date] || []), ...(rules[weekday] || [])];
    };

    const doctorWorksOnDate = (date) => rulesForDate(date).length > 0;

    function setContextSteps() {
      if (mode === 'single_doctor_single_specialty') {
        stepNames = ['Fecha y Hora', 'Sus Datos', 'Confirmar'];
        stepMap = [2, 3, 4];
      } else if (mode === 'single_doctor_multi_specialty') {
        stepNames = ['Especialidad', 'Fecha y Hora', 'Sus Datos', 'Confirmar'];
        stepMap = [0, 2, 3, 4];
      } else if (mode === 'multi_doctor_single_specialty') {
        stepNames = ['Doctor', 'Fecha y Hora', 'Sus Datos', 'Confirmar'];
        stepMap = [1, 2, 3, 4];
      } else {
        stepNames = ['Especialidad', 'Doctor', 'Fecha y Hora', 'Sus Datos', 'Confirmar'];
        stepMap = [0, 1, 2, 3, 4];
      }

      steps.forEach((step, index) => {
        step.style.display = stepMap.includes(index) ? '' : 'none';
      });

      progress.forEach((item, index) => {
        const visible = index < stepNames.length;
        item.style.display = visible ? '' : 'none';
        if (visible) item.dataset.stepName = stepNames[index];
      });
    }

    function validate() {
      const name = stepNames[currentStep];
      let valid = false;

      if (name === 'Especialidad') valid = !!selection.specialtyId;
      else if (name === 'Doctor') valid = !!selection.doctorId;
      else if (name === 'Fecha y Hora') valid = !!selection.date && !!selection.time;
      else if (name === 'Sus Datos') {
        valid = patient.name.value.trim() !== ''
          && validDocument(patient.cedula.value)
          && validEmail(patient.email.value)
          && validPhone(patient.phone.value);
      } else valid = true;

      next.disabled = !valid;
    }

    function updateScheduleSummary(slotText) {
      scheduleDoctor.textContent = selection.doctorName || 'Pendiente';
      scheduleDate.textContent = selection.date ? formatDate(selection.date) : 'Pendiente';
      scheduleTime.textContent = selection.time || 'Pendiente';
      selectedDateLabel.textContent = selection.date
        ? formatDate(selection.date)
        : 'Seleccione una fecha';
      if (availableCount) availableCount.textContent = slotText || (selection.date ? 'Buscando' : '--');
    }

    function renderUI() {
      setContextSteps();

      steps.forEach((step, index) => {
        step.classList.toggle('active', stepMap[currentStep] === index);
      });

      progress.forEach((item, index) => {
        if (index >= stepNames.length) return;
        item.classList.toggle('active', index <= currentStep);
        if (index === currentStep) item.setAttribute('aria-current', 'step');
        else item.removeAttribute('aria-current');
      });

      prev.style.visibility = currentStep > 0 ? 'visible' : 'hidden';
      next.textContent = currentStep === stepNames.length - 1
        ? (cfg.confirmLabel || 'Confirmar Cita')
        : (cfg.nextLabel || 'Siguiente');
      validate();
    }

    function renderSpecialties() {
      const query = String(specialtyFilter?.value || '').toLowerCase().trim();
      const list = specialties.filter((item) =>
        String(item.titulo || '').toLowerCase().includes(query)
      );

      specialtyGrid.innerHTML = list.map((item) => {
        const active = String(item.id) === String(selection.specialtyId);
        return `
          <button type="button" class="selectable-card${active ? ' active' : ''}"
                  data-value="${escapeHtml(item.id)}"
                  data-name="${escapeHtml(item.titulo)}"
                  aria-pressed="${active}">
            <div class="card-icon">${item.icono || ''}</div>
            <span class="card-title">${escapeHtml(item.titulo)}</span>
          </button>
        `;
      }).join('') || '<p class="no-results-message" style="display:block;grid-column:1/-1">No hay especialidades disponibles.</p>';
    }

    function renderDoctors() {
      let list = selection.specialtyId ? doctorsForSpecialty(selection.specialtyId) : doctors;
      const query = String(doctorFilter?.value || '').toLowerCase().trim();
      list = list.filter((doctor) => String(doctor.nombre || '').toLowerCase().includes(query));

      doctorGrid.innerHTML = list.map((doctor) => {
        const active = String(doctor.id) === String(selection.doctorId);
        const rating = Math.max(0, Math.min(5, Number(doctor.valoracion || 0)));
        const stars = '★'.repeat(rating) + '☆'.repeat(5 - rating);
        return `
          <button type="button" class="selectable-card${active ? ' active' : ''}"
                  data-value="${escapeHtml(doctor.id)}"
                  data-name="${escapeHtml(doctor.nombre)}"
                  aria-pressed="${active}">
            <img class="doctor-photo" src="${escapeHtml(doctor.foto || '')}"
                 alt="Foto de ${escapeHtml(doctor.nombre)}">
            <div class="doctor-info">
              <span class="card-title">${escapeHtml(doctor.nombre)}</span>
              <div class="doctor-rating" aria-label="Valoración: ${rating} de 5 estrellas">${stars}</div>
            </div>
          </button>
        `;
      }).join('');

      doctorNoResults.style.display = list.length ? 'none' : 'block';
    }

    function renderCalendar() {
      const first = new Date(calendar.year, calendar.month, 1);
      const last = new Date(calendar.year, calendar.month + 1, 0);
      const today = new Date();
      today.setHours(0, 0, 0, 0);

      calendarTitle.textContent = first.toLocaleDateString('es-EC', {
        month: 'long',
        year: 'numeric',
      });

      const offset = (first.getDay() + 6) % 7;
      let html = '';

      for (let index = 0; index < offset; index += 1) {
        html += '<span class="calendar-day is-other-month" aria-hidden="true"></span>';
      }

      for (let day = 1; day <= last.getDate(); day += 1) {
        const date = new Date(calendar.year, calendar.month, day);
        date.setHours(0, 0, 0, 0);
        const value = dateKey(calendar.year, calendar.month, day);
        const disabled = date <= today || !doctorWorksOnDate(value);
        const selected = selection.date === value;

        html += `
          <button type="button"
                  class="calendar-day${disabled ? ' is-disabled' : ''}${date.toDateString() === today.toDateString() ? ' is-today' : ''}${selected ? ' is-selected' : ''}"
                  data-date="${value}"
                  aria-label="${escapeHtml(formatDate(value))}"
                  aria-pressed="${selected}"
                  ${disabled ? 'disabled aria-disabled="true"' : ''}>
            ${day}
          </button>
        `;
      }

      calendarGrid.innerHTML = html;
    }

    function renderTimeSlots(bookedSlots = []) {
      const rules = rulesForDate(selection.date);
      const selectedDate = new Date(`${selection.date}T00:00:00`);
      const now = new Date();
      const isToday = selectedDate.toDateString() === now.toDateString();
      const currentMinutes = now.getHours() * 60 + now.getMinutes();
      const available = [];

      rules.forEach((rule) => {
        const start = timeToMinutes(rule.start_time);
        const end = timeToMinutes(rule.end_time);
        const duration = Math.max(5, Number(rule.slot_minutes || 15));
        if (start === null || end === null || start >= end) return;

        for (let minute = start; minute < end; minute += duration) {
          if (isToday && minute <= currentMinutes) continue;
          const candidateEnd = minute + duration;
          const conflicts = bookedSlots.some((booked) => {
            const bookedStart = timeToMinutes(booked.time);
            const bookedDuration = Math.max(5, Number(booked.slot_minutes || 15));
            if (bookedStart === null) return false;
            const bookedEnd = bookedStart + bookedDuration;
            return minute - 15 < bookedEnd && candidateEnd + 15 > bookedStart;
          });
          if (!conflicts) available.push(minutesToTime(minute));
        }
      });

      const times = [...new Set(available)].sort();
      timeWrapper?.classList.toggle('has-slots', times.length > 0);
      timeWrapper?.classList.toggle('has-no-slots', times.length === 0);
      timeWrapper?.classList.remove('is-loading');

      if (!times.length) {
        timeGrid.innerHTML = '<div class="no-time-selected"><p>No hay horarios disponibles para esta fecha.</p></div>';
        updateScheduleSummary('Sin cupos');
        selection.time = null;
        validate();
        return;
      }

      const groups = times.reduce((result, time) => {
        const minutes = timeToMinutes(time);
        const period = minutes < 720 ? 'morning' : minutes < 1080 ? 'afternoon' : 'evening';
        (result[period] ||= []).push(time);
        return result;
      }, {});

      let animationIndex = 0;
      timeGrid.innerHTML = ['morning', 'afternoon', 'evening']
        .filter((period) => groups[period]?.length)
        .map((period) => {
          const buttons = groups[period].map((time) => {
            const selected = selection.time === time;
            const index = animationIndex++;
            return `
              <button type="button"
                      class="selectable-card time-slot${selected ? ' active' : ''}"
                      data-time="${time}"
                      aria-pressed="${selected}"
                      style="--animation-order:${index}">
                <span class="card-title">${time}</span>
              </button>
            `;
          }).join('');

          const label = period === 'morning' ? 'Mañana'
            : period === 'afternoon' ? 'Tarde' : 'Noche';

          return `
            <section class="time-period" aria-label="${label}">
              <div class="time-period-header">
                <h4>${label}</h4>
                <span>${groups[period].length} ${groups[period].length === 1 ? 'cupo' : 'cupos'}</span>
              </div>
              <div class="time-period-grid">${buttons}</div>
            </section>
          `;
        }).join('');

      updateScheduleSummary(`${times.length} ${times.length === 1 ? 'cupo' : 'cupos'}`);
    }

    async function loadSlots() {
      if (!selection.doctorId || !selection.date) return;
      const requestId = ++slotsRequestId;
      timeWrapper?.classList.add('is-loading');
      timeGrid.innerHTML = '<div class="no-time-selected"><p>Buscando horarios disponibles...</p></div>';
      updateScheduleSummary('Buscando');

      try {
        const response = await fetch(
          `${url('api/general/')}?action=slot_status&doctorId=${encodeURIComponent(selection.doctorId)}&date=${encodeURIComponent(selection.date)}`,
          { credentials: 'same-origin', headers: { Accept: 'application/json' } }
        );
        const result = await response.json().catch(() => ({}));
        if (requestId !== slotsRequestId) return;
        if (!response.ok || result.success === false) throw new Error('availability');
        renderTimeSlots(Array.isArray(result.bookedSlots) ? result.bookedSlots : []);
      } catch (error) {
        if (requestId !== slotsRequestId) return;
        timeWrapper?.classList.remove('is-loading');
        timeGrid.innerHTML = '<div class="no-time-selected"><p>No se pudo actualizar la disponibilidad. Intente nuevamente.</p></div>';
        updateScheduleSummary('--');
        selection.time = null;
        validate();
      }
    }

    function stopSlotPolling() {
      if (slotPollTimer) {
        clearInterval(slotPollTimer);
        slotPollTimer = null;
      }
    }

    function startSlotPolling() {
      stopSlotPolling();
      slotPollTimer = window.setInterval(async () => {
        if (stepNames[currentStep] !== 'Fecha y Hora' || !selection.doctorId || !selection.date) {
          stopSlotPolling();
          return;
        }
        await loadSlots();
      }, 6000);
    }

    function populateSummary() {
      summary.specialty.textContent = selection.specialtyName || findSpecialty(selection.specialtyId)?.titulo || 'Pendiente';
      summary.doctor.textContent = selection.doctorName || findDoctor(selection.doctorId)?.nombre || 'Pendiente';
      summary.date.textContent = formatDate(selection.date);
      summary.time.textContent = selection.time || 'Pendiente';
      summary.name.textContent = patient.name.value.trim();
      summary.cedula.textContent = patient.cedula.value.trim();
      summary.email.textContent = patient.email.value.trim();
      summary.phone.textContent = patient.phone.value.trim();
    }

    async function submit() {
      next.disabled = true;
      try {
        const body = {
          action: 'create_appointment',
          specialtyId: selection.specialtyId,
          doctorId: selection.doctorId,
          date: selection.date,
          time: selection.time,
          patientName: patient.name.value.trim(),
          patientCedula: patient.cedula.value.trim().toUpperCase(),
          patientEmail: patient.email.value.trim().toLowerCase(),
          patientPhone: patient.phone.value.trim(),
          patientNotes: patient.notes.value.trim(),
          emailOptIn: !!patient.emailOpt?.checked,
          whatsappOptIn: !!patient.whatsappOpt?.checked,
        };

        const response = await fetch(API, {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrf() },
          body: JSON.stringify(body),
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok || !result.success) throw new Error(result.error || 'No se pudo crear la cita.');

        stopSlotPolling();
        form.innerHTML = `
          <div class="reservation-success">
            <h2>${escapeHtml(cfg.successTitle || 'Cita Agendada')}</h2>
            <p>${escapeHtml(String(cfg.successText || 'Su cita ha sido confirmada exitosamente.').replace('{clinic}', cfg.clinicName || 'la clínica'))}</p>
            <a class="btn btn-primary" href="${escapeHtml(cfg.homeUrl || url(''))}">${escapeHtml(cfg.backHomeLabel || 'Volver al Inicio')}</a>
          </div>
        `;
      } catch (error) {
        alert(error.message || 'No se pudo crear la cita.');
        next.disabled = false;
      }
    }

    function goToStep(target) {
      if (target < 0 || target >= stepNames.length) return;
      const previousSemantic = stepNames[currentStep];
      currentStep = target;
      const semantic = stepNames[currentStep];

      if (previousSemantic === 'Fecha y Hora' && semantic !== 'Fecha y Hora') stopSlotPolling();
      if (semantic === 'Doctor') renderDoctors();
      if (semantic === 'Fecha y Hora') {
        renderCalendar();
        if (selection.date) loadSlots();
        startSlotPolling();
      }
      if (semantic === 'Confirmar') populateSummary();
      renderUI();
    }

    function advanceIfPossible() {
      if (!next.disabled) goToStep(currentStep + 1);
    }

    specialtyGrid.addEventListener('click', (event) => {
      const card = event.target.closest('.selectable-card');
      if (!card) return;

      selection.specialtyId = card.dataset.value;
      selection.specialtyName = card.dataset.name;
      selection.doctorId = null;
      selection.doctorName = null;
      selection.date = null;
      selection.time = null;
      slotsRequestId += 1;
      renderSpecialties();

      const candidates = doctorsForSpecialty(selection.specialtyId);
      if (candidates.length === 1) {
        selection.doctorId = candidates[0].id;
        selection.doctorName = candidates[0].nombre;
        goToStep(stepNames.indexOf('Fecha y Hora'));
        return;
      }

      advanceIfPossible();
    });

    doctorGrid.addEventListener('click', (event) => {
      const card = event.target.closest('.selectable-card');
      if (!card) return;
      const doctor = findDoctor(card.dataset.value);
      if (!doctor) return;

      selection.doctorId = doctor.id;
      selection.doctorName = doctor.nombre;
      selection.date = null;
      selection.time = null;
      slotsRequestId += 1;

      const doctorSpecialties = doctor.especialidades || [];
      if (!selection.specialtyId && doctorSpecialties.length === 1) {
        selection.specialtyId = doctorSpecialties[0].id;
        selection.specialtyName = doctorSpecialties[0].nombre;
      }

      renderDoctors();
      goToStep(stepNames.indexOf('Fecha y Hora'));
    });

    calendarGrid.addEventListener('click', (event) => {
      const day = event.target.closest('[data-date]');
      if (!day || day.disabled) return;
      selection.date = day.dataset.date;
      selection.time = null;
      renderCalendar();
      loadSlots();
      validate();
    });

    timeGrid.addEventListener('click', (event) => {
      const card = event.target.closest('[data-time]');
      if (!card) return;
      selection.time = card.dataset.time;
      timeGrid.querySelectorAll('[data-time]').forEach((item) => {
        item.classList.toggle('active', item === card);
        item.setAttribute('aria-pressed', item === card ? 'true' : 'false');
      });
      updateScheduleSummary();
      validate();
      window.setTimeout(() => advanceIfPossible(), 180);
    });

    specialtyFilter?.addEventListener('input', renderSpecialties);
    doctorFilter?.addEventListener('input', renderDoctors);

    const navigateMonth = (direction) => {
      calendarGrid.classList.add('is-changing');
      const previous = document.getElementById('prev-month-btn');
      const following = document.getElementById('next-month-btn');
      if (previous) previous.disabled = true;
      if (following) following.disabled = true;

      window.setTimeout(() => {
        calendar.month += direction;
        if (calendar.month < 0) {
          calendar.month = 11;
          calendar.year -= 1;
        } else if (calendar.month > 11) {
          calendar.month = 0;
          calendar.year += 1;
        }
        renderCalendar();
        calendarGrid.classList.remove('is-changing');
        if (previous) previous.disabled = false;
        if (following) following.disabled = false;
      }, 150);
    };

    document.getElementById('prev-month-btn')?.addEventListener('click', () => navigateMonth(-1));
    document.getElementById('next-month-btn')?.addEventListener('click', () => navigateMonth(1));

    next.addEventListener('click', () => {
      if (currentStep === stepNames.length - 1) submit();
      else advanceIfPossible();
    });
    prev.addEventListener('click', () => goToStep(currentStep - 1));

    Object.values(patient).forEach((input) => {
      input?.addEventListener('input', validate);
      input?.addEventListener('change', validate);
    });

    try {
      const response = await fetch(url('api/catalog/'), {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
      });
      const result = await response.json().catch(() => ({}));
      if (!response.ok || !result.success) throw new Error('catalog');

      mode = result.context?.mode || mode;
      specialties = Array.isArray(result.context?.specialties) ? result.context.specialties : [];
      doctors = Array.isArray(result.context?.doctors) ? result.context.doctors : [];
      setContextSteps();

      const presetSpecialty = form.dataset.selectedSpecialtyId;
      const presetDoctor = form.dataset.selectedDoctorId;
      if (presetSpecialty && findSpecialty(presetSpecialty)) {
        selection.specialtyId = presetSpecialty;
        selection.specialtyName = findSpecialty(presetSpecialty).titulo;
      }
      if (presetDoctor && findDoctor(presetDoctor)) {
        selection.doctorId = presetDoctor;
        selection.doctorName = findDoctor(presetDoctor).nombre;
      }

      if (!selection.doctorId && doctors.length === 1) {
        selection.doctorId = doctors[0].id;
        selection.doctorName = doctors[0].nombre;
      }
      if (!selection.specialtyId && selection.doctorId) {
        const doctorSpecialties = findDoctor(selection.doctorId)?.especialidades || [];
        if (doctorSpecialties.length === 1) {
          selection.specialtyId = doctorSpecialties[0].id;
          selection.specialtyName = doctorSpecialties[0].nombre;
        }
      }

      renderSpecialties();
      renderDoctors();
      renderCalendar();
      updateScheduleSummary();
      renderUI();

      if (stepNames[currentStep] === 'Especialidad' && selection.specialtyId) {
        const candidates = doctorsForSpecialty(selection.specialtyId);
        if (candidates.length === 1) {
          selection.doctorId = candidates[0].id;
          selection.doctorName = candidates[0].nombre;
          goToStep(stepNames.indexOf('Fecha y Hora'));
        }
      } else if (stepNames[currentStep] === 'Doctor' && selection.doctorId) {
        goToStep(stepNames.indexOf('Fecha y Hora'));
      } else if (stepNames[currentStep] === 'Fecha y Hora') {
        renderCalendar();
        startSlotPolling();
      }
    } catch (error) {
      next.disabled = true;
      specialtyGrid.innerHTML = '<p class="no-results-message" style="display:block;grid-column:1/-1">No se pudo cargar el catálogo de la clínica. Recargue la página.</p>';
    }
  });
}());

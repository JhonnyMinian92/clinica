/* Adaptive public reservation flow. The server remains authoritative for all rules. */
(function () {
  'use strict';

  const basePath = String(window.CLINICA_BASE_PATH || '').replace(/\/$/, '');
  const url = (path) => `${basePath}/${String(path || '').replace(/^\/+/, '')}`;
  const config = typeof reservationConfig !== 'undefined' ? reservationConfig : {};
  const API = config.generalApi || url('api/appointments/');
  const CATALOG_API = url('api/catalog/');

  document.addEventListener('DOMContentLoaded', async () => {
    const form = document.getElementById('reservation-form');
    if (!form) return;

    const steps = Array.from(form.querySelectorAll('.form-step'));
    const progress = Array.from(document.querySelectorAll('.progress-step'));
    const next = document.getElementById('next-btn');
    const prev = document.getElementById('prev-btn');
    const specialtyGrid = document.getElementById('specialty-grid');
    const specialtyFilter = document.getElementById('specialty-filter');
    const doctorGrid = document.getElementById('doctor-selection-grid');
    const doctorFilter = document.getElementById('doctor-filter');
    const doctorNoResults = document.getElementById('doctor-no-results');
    const calendarGrid = document.getElementById('calendar-grid');
    const monthTitle = document.getElementById('calendar-month-year');
    const timeGrid = document.getElementById('time-slots');
    const dateLabel = document.getElementById('selected-date-label');
    const countLabel = document.getElementById('available-slots-count');
    const doctorLabel = document.getElementById('schedule-doctor-label');
    const scheduleDateLabel = document.getElementById('schedule-date-label');
    const scheduleTimeLabel = document.getElementById('schedule-time-label');
    const summary = {
      specialty: document.getElementById('summary-specialty'), doctor: document.getElementById('summary-doctor'),
      date: document.getElementById('summary-date'), time: document.getElementById('summary-time'),
      name: document.getElementById('summary-name'), cedula: document.getElementById('summary-cedula'),
      email: document.getElementById('summary-email'), phone: document.getElementById('summary-phone')
    };
    const patient = {
      name: document.getElementById('patient-name'), cedula: document.getElementById('patient-cedula'),
      email: document.getElementById('patient-email'), phone: document.getElementById('patient-phone'),
      notes: document.getElementById('patient-notes'), emailOptIn: document.getElementById('notify-email-optin'),
      whatsappOptIn: document.getElementById('notify-whatsapp-optin')
    };

    let context = null;
    let current = 0;
    let specialtyList = [];
    let doctors = [];
    let mode = 'multi_doctor_multi_specialty';
    const state = { specialtyId: null, specialtyName: null, doctorId: null, doctorName: null, date: null, time: null };
    const calendar = { month: new Date().getMonth(), year: new Date().getFullYear() };

    const readCsrf = () => {
      const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
      return match ? decodeURIComponent(match[1]) : '';
    };
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
    const dateKey = (y, m, d) => `${y}-${String(m + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
    const formatDate = (value) => value ? new Date(value + 'T00:00:00').toLocaleDateString('es-EC', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) : 'Pendiente';
    const specialtyById = (id) => specialtyList.find((s) => String(s.id) === String(id)) || null;
    const doctorById = (id) => doctors.find((d) => String(d.id) === String(id)) || null;
    const doctorsForSpecialty = (specialtyId) => doctors.filter((d) => (d.especialidades || []).some((s) => String(s.id) === String(specialtyId)));
    const specialtiesForDoctor = (doctor) => (doctor && doctor.especialidades) || [];

    function buildSteps() {
      let names;
      if (mode === 'single_doctor_single_specialty') names = ['Fecha y Hora', 'Sus Datos', 'Confirmar'];
      else if (mode === 'single_doctor_multi_specialty') names = ['Especialidad', 'Fecha y Hora', 'Sus Datos', 'Confirmar'];
      else if (mode === 'multi_doctor_single_specialty') names = ['Doctor', 'Fecha y Hora', 'Sus Datos', 'Confirmar'];
      else names = ['Especialidad', 'Doctor', 'Fecha y Hora', 'Sus Datos', 'Confirmar'];
      steps.forEach((step, i) => { step.dataset.adaptiveVisible = i < names.length ? '1' : '0'; step.style.display = i < names.length ? '' : 'none'; });
      progress.forEach((p, i) => { p.style.display = i < names.length ? '' : 'none'; if (i < names.length) p.dataset.stepName = names[i]; });
      form.dataset.adaptiveStepCount = String(names.length);
      return names;
    }

    function stepIndex(name) {
      const names = buildSteps();
      return names.indexOf(name);
    }
    function activeStepNames() { return buildSteps(); }
    function updateUI() {
      const names = activeStepNames();
      steps.forEach((s, i) => { if (i < names.length) s.classList.toggle('active', i === current); });
      progress.forEach((p, i) => { if (i < names.length) { p.classList.toggle('active', i <= current); i === current ? p.setAttribute('aria-current', 'step') : p.removeAttribute('aria-current'); }});
      const pct = names.length > 1 ? current / (names.length - 1) * 100 : 0;
      const bar = document.querySelector('.progress-bar'); if (bar) bar.style.setProperty('--progress-width', `${pct}%`);
      prev.style.visibility = current > 0 ? 'visible' : 'hidden';
      next.textContent = current === names.length - 1 ? (config.confirmLabel || 'Confirmar Cita') : (config.nextLabel || 'Siguiente');
      validate();
    }
    function validate() {
      const names = activeStepNames();
      const name = names[current];
      let valid = false;
      if (name === 'Especialidad') valid = !!state.specialtyId;
      else if (name === 'Doctor') valid = !!state.doctorId;
      else if (name === 'Fecha y Hora') valid = !!state.date && !!state.time;
      else if (name === 'Sus Datos') valid = patient.name.value.trim() !== '' && /^[A-Za-z0-9]{6,20}$/.test(patient.cedula.value.trim()) && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(patient.email.value.trim()) && /^\d{7,15}$/.test(patient.phone.value.trim());
      else valid = true;
      next.disabled = !valid;
    }

    function renderSpecialties() {
      if (!specialtyGrid) return;
      const term = (specialtyFilter?.value || '').toLowerCase().trim();
      specialtyGrid.innerHTML = specialtyList.filter(s => String(s.titulo || '').toLowerCase().includes(term)).map(s => `<button type="button" class="selectable-card${String(s.id) === String(state.specialtyId) ? ' active' : ''}" data-value="${escapeHtml(s.id)}" data-name="${escapeHtml(s.titulo)}" aria-pressed="${String(s.id) === String(state.specialtyId)}"><div class="card-icon">${s.icono || ''}</div><span class="card-title">${escapeHtml(s.titulo)}</span></button>`).join('');
      if (!specialtyGrid.innerHTML) specialtyGrid.innerHTML = '<p class="no-results-message" style="display:block;grid-column:1/-1">No hay especialidades disponibles.</p>';
    }
    function renderDoctors() {
      if (!doctorGrid) return;
      let list = state.specialtyId ? doctorsForSpecialty(state.specialtyId) : doctors;
      const term = (doctorFilter?.value || '').toLowerCase().trim();
      list = list.filter(d => String(d.nombre || '').toLowerCase().includes(term));
      doctorGrid.innerHTML = list.map(d => `<button type="button" class="selectable-card${String(d.id) === String(state.doctorId) ? ' active' : ''}" data-value="${escapeHtml(d.id)}" data-name="${escapeHtml(d.nombre)}" aria-pressed="${String(d.id) === String(state.doctorId)}"><img class="doctor-card-image" src="${escapeHtml(d.foto || '')}" alt=""><span class="card-title">${escapeHtml(d.nombre)}</span><small>${escapeHtml((d.especialidades || []).map(s => s.nombre).join(' · '))}</small></button>`).join('');
      if (doctorNoResults) doctorNoResults.style.display = list.length ? 'none' : 'block';
    }

    function ensureSingleContext() {
      const d = doctors[0];
      if (!d) return;
      if (!state.doctorId) { state.doctorId = d.id; state.doctorName = d.nombre; }
      const specs = specialtiesForDoctor(d);
      if (specs.length === 1 && !state.specialtyId) { state.specialtyId = specs[0].id; state.specialtyName = specs[0].nombre; }
    }

    function renderCalendar() {
      if (!calendarGrid || !monthTitle) return;
      const first = new Date(calendar.year, calendar.month, 1);
      const last = new Date(calendar.year, calendar.month + 1, 0);
      monthTitle.textContent = first.toLocaleDateString('es-EC', { month: 'long', year: 'numeric' });
      const offset = (first.getDay() + 6) % 7;
      let html = '';
      for (let i = 0; i < offset; i++) html += '<span class="calendar-day is-empty"></span>';
      const today = new Date(); today.setHours(0,0,0,0);
      for (let d = 1; d <= last.getDate(); d++) {
        const value = dateKey(calendar.year, calendar.month, d);
        const day = new Date(calendar.year, calendar.month, d); day.setHours(0,0,0,0);
        const disabled = day <= today;
        html += `<button type="button" class="calendar-day${state.date === value ? ' selected' : ''}" data-date="${value}" ${disabled ? 'disabled' : ''}>${d}</button>`;
      }
      calendarGrid.innerHTML = html;
    }

    async function loadSlots() {
      if (!state.doctorId || !state.date) return;
      timeGrid.innerHTML = '<div class="no-time-selected"><p>Buscando horarios disponibles...</p></div>';
      try {
        const response = await fetch(`${url('api/general/')}?action=slot_status&doctorId=${encodeURIComponent(state.doctorId)}&date=${encodeURIComponent(state.date)}`, { credentials: 'same-origin' });
        const data = await response.json();
        const booked = new Set(data.bookedTimes || []);
        const doctor = doctorById(state.doctorId);
        const rules = doctor?.horarios_detallados || {};
        const weekday = new Date(state.date + 'T00:00:00').getDay() || 7;
        const blocks = [...(rules[state.date] || []), ...(rules[String(weekday)] || [])];
        const times = [];
        blocks.forEach(r => {
          const start = Number(String(r.start_time).slice(0,5).split(':')[0]) * 60 + Number(String(r.start_time).slice(0,5).split(':')[1]);
          const end = Number(String(r.end_time).slice(0,5).split(':')[0]) * 60 + Number(String(r.end_time).slice(0,5).split(':')[1]);
          const slot = Math.max(5, Number(r.slot_minutes || 15));
          for (let m = start; m < end; m += slot) { const t = `${String(Math.floor(m/60)).padStart(2,'0')}:${String(m%60).padStart(2,'0')}`; if (!booked.has(t)) times.push(t); }
        });
        const unique = [...new Set(times)].sort();
        timeGrid.innerHTML = unique.length ? unique.map(t => `<button type="button" class="selectable-card time-slot${state.time === t ? ' active' : ''}" data-time="${t}">${t}</button>`).join('') : '<div class="no-time-selected"><p>No hay horarios disponibles para esta fecha.</p></div>';
        if (countLabel) countLabel.textContent = unique.length ? `${unique.length} cupos` : 'Sin cupos';
      } catch (e) { timeGrid.innerHTML = '<div class="no-time-selected"><p>No se pudo actualizar la disponibilidad. Intente nuevamente.</p></div>'; }
    }

    function populateSummary() {
      const s = specialtyById(state.specialtyId);
      const d = doctorById(state.doctorId);
      if (summary.specialty) summary.specialty.textContent = state.specialtyName || s?.titulo || 'Pendiente';
      if (summary.doctor) summary.doctor.textContent = state.doctorName || d?.nombre || 'Pendiente';
      if (summary.date) summary.date.textContent = formatDate(state.date);
      if (summary.time) summary.time.textContent = state.time || 'Pendiente';
      if (summary.name) summary.name.textContent = patient.name.value.trim();
      if (summary.cedula) summary.cedula.textContent = patient.cedula.value.trim();
      if (summary.email) summary.email.textContent = patient.email.value.trim();
      if (summary.phone) summary.phone.textContent = patient.phone.value.trim();
    }

    async function submit() {
      next.disabled = true;
      try {
        const body = { action:'create_appointment', specialtyId:state.specialtyId, doctorId:state.doctorId, date:state.date, time:state.time, patientName:patient.name.value.trim(), patientCedula:patient.cedula.value.trim().toUpperCase(), patientEmail:patient.email.value.trim().toLowerCase(), patientPhone:patient.phone.value.trim(), patientNotes:patient.notes.value.trim(), emailOptIn:!!patient.emailOptIn.checked, whatsappOptIn:!!patient.whatsappOptIn.checked };
        const response = await fetch(API, { method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/json','X-CSRF-Token':readCsrf()}, body:JSON.stringify(body) });
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.error || 'No se pudo crear la cita.');
        form.innerHTML = `<div class="reservation-success"><h2>${escapeHtml(config.successTitle || 'Cita Agendada')}</h2><p>${escapeHtml((config.successText || 'Su cita ha sido confirmada exitosamente.').replace('{clinic}', config.clinicName || 'la clinica'))}</p><a class="btn btn-primary" href="${escapeHtml(config.homeUrl || url(''))}">${escapeHtml(config.backHomeLabel || 'Volver al Inicio')}</a></div>`;
      } catch (e) { alert(e.message || 'No se pudo crear la cita.'); next.disabled = false; }
    }

    function nextStep() {
      const names = activeStepNames(); if (next.disabled) return;
      if (current === names.length - 1) return submit();
      const leaving = names[current]; current++;
      const entering = names[current];
      if (entering === 'Doctor') renderDoctors();
      if (entering === 'Fecha y Hora') { renderCalendar(); updateScheduleSummary(); loadSlots(); }
      if (entering === 'Confirmar') populateSummary();
      updateUI();
    }
    function prevStep() {
      if (current <= 0) return;
      const names = activeStepNames(); current--;
      const entering = names[current];
      if (entering === 'Doctor') renderDoctors();
      if (entering === 'Fecha y Hora') { renderCalendar(); loadSlots(); }
      updateUI();
    }
    function updateScheduleSummary() {
      if (doctorLabel) doctorLabel.textContent = state.doctorName || 'Pendiente';
      if (scheduleDateLabel) scheduleDateLabel.textContent = formatDate(state.date);
      if (scheduleTimeLabel) scheduleTimeLabel.textContent = state.time || 'Pendiente';
      if (dateLabel) dateLabel.textContent = state.date ? formatDate(state.date) : 'Seleccione una fecha';
    }

    specialtyGrid?.addEventListener('click', e => { const card = e.target.closest('.selectable-card'); if (!card) return; state.specialtyId=card.dataset.value; state.specialtyName=card.dataset.name; state.doctorId=null; state.doctorName=null; state.date=null; state.time=null; renderSpecialties(); validate(); if (mode === 'single_doctor_multi_specialty') { current = stepIndex('Fecha y Hora'); const d=doctors[0]; state.doctorId=d?.id||null; state.doctorName=d?.nombre||null; renderCalendar(); updateUI(); } });
    doctorGrid?.addEventListener('click', e => { const card=e.target.closest('.selectable-card'); if(!card) return; const d=doctorById(card.dataset.value); if(!d)return; state.doctorId=d.id; state.doctorName=d.nombre; if(!state.specialtyId){ const specs=specialtiesForDoctor(d); if(specs.length===1){state.specialtyId=specs[0].id;state.specialtyName=specs[0].nombre;} } renderDoctors(); validate(); if(mode==='multi_doctor_single_specialty') { current=stepIndex('Fecha y Hora'); renderCalendar(); updateUI(); } });
    calendarGrid?.addEventListener('click', e => { const b=e.target.closest('[data-date]'); if(!b || b.disabled)return; state.date=b.dataset.date; state.time=null; renderCalendar(); updateScheduleSummary(); loadSlots(); validate(); });
    timeGrid?.addEventListener('click', e => { const b=e.target.closest('[data-time]'); if(!b)return; state.time=b.dataset.time; timeGrid.querySelectorAll('[data-time]').forEach(x=>x.classList.toggle('active',x===b)); updateScheduleSummary(); validate(); });
    specialtyFilter?.addEventListener('input', renderSpecialties); doctorFilter?.addEventListener('input', renderDoctors);
    document.getElementById('prev-month-btn')?.addEventListener('click',()=>{calendar.month--;if(calendar.month<0){calendar.month=11;calendar.year--;}renderCalendar();});
    document.getElementById('next-month-btn')?.addEventListener('click',()=>{calendar.month++;if(calendar.month>11){calendar.month=0;calendar.year++;}renderCalendar();});
    next?.addEventListener('click', nextStep); prev?.addEventListener('click', prevStep);
    Object.values(patient).forEach(el => el?.addEventListener?.('input', validate));

    try {
      const response = await fetch(CATALOG_API, { credentials:'same-origin', headers:{Accept:'application/json'} });
      const data = await response.json();
      if (!response.ok || !data.success) throw new Error(data.error || 'No se pudo cargar el catalogo.');
      context=data.context; mode=context.mode; specialtyList=context.specialties || []; doctors=context.doctors || [];
      buildSteps();
      if (mode === 'single_doctor_single_specialty') ensureSingleContext();
      else if (mode === 'single_doctor_multi_specialty') ensureSingleContext();
      else if (mode === 'multi_doctor_single_specialty' && specialtyList[0]) { state.specialtyId=specialtyList[0].id; state.specialtyName=specialtyList[0].titulo; }
      renderSpecialties(); renderDoctors(); updateScheduleSummary(); updateUI();
      // Deep-link compatibility: honor server-selected values where valid.
      const presetSpecialty=form.dataset.selectedSpecialtyId; const presetDoctor=form.dataset.selectedDoctorId;
      if(presetSpecialty && specialtyById(presetSpecialty)){state.specialtyId=presetSpecialty;state.specialtyName=specialtyById(presetSpecialty).titulo;}
      if(presetDoctor && doctorById(presetDoctor)){state.doctorId=presetDoctor;state.doctorName=doctorById(presetDoctor).nombre;}
      if(state.specialtyId)renderSpecialties(); if(state.doctorId)renderDoctors(); validate();
    } catch (e) {
      console.error(e); next.disabled=true;
      if (specialtyGrid) specialtyGrid.innerHTML='<p class="no-results-message" style="display:block;grid-column:1/-1">No se pudo cargar la disponibilidad de la clinica. Recargue la pagina.</p>';
    }
  });
})();

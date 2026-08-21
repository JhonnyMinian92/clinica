/*
 * gestion.js
 * Motor CRUD compartido para las paginas de pages/gestion.
 */

(function () {
  'use strict';

  function appUrl(path) {
    const basePath = String(window.CLINICA_BASE_PATH || '').replace(/\/$/, '');
    return `${basePath}/${String(path || '').replace(/^\/+/, '')}`;
  }

  const API_URL = appUrl('api/gestion/');

  function getCsrfToken() {
    const match = document.cookie.match(new RegExp('(^|;)\\s*XSRF-TOKEN\\s*=\\s*([^;]+)'));
    return match ? decodeURIComponent(match.pop()) : '';
  }

  function boot(config) {
    const start = () => init(config);
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', start, { once: true });
    } else {
      start();
    }
  }

  async function init(config) {
    const dom = {
      stats: document.getElementById('gestion-stats'),
      list: document.getElementById('gestion-list'),
      form: document.getElementById('gestion-form'),
      overview: document.getElementById('gestion-overview'),
      search: document.getElementById('gestion-search'),
      status: document.getElementById('gestion-status-filter'),
      subToolbar: document.querySelector('.gestion-sub-toolbar'),
      filters: document.querySelector('.gestion-filters'),
      newBtn: document.getElementById('gestion-new-btn'),
      singletonEditBtn: document.getElementById('gestion-singleton-edit-btn'),
      overviewEditBtn: document.getElementById('gestion-overview-edit-btn'),
      reloadBtn: document.getElementById('gestion-reload-btn'),
      listSummary: document.getElementById('gestion-list-summary'),
      editorTitle: document.getElementById('gestion-editor-title'),
      editorSummary: document.getElementById('gestion-editor-summary'),
      modal: document.getElementById('gestion-editor-modal'),
      modalClose: document.getElementById('gestion-modal-close'),
      toast: document.getElementById('gestion-toast'),
    };

    const state = {
      records: config.singleton ? {} : [],
      metadata: {},
      editingId: null,
      loading: false,
      scheduleWeekStart: getWeekStartIso(new Date()),
      scheduleWeeks: Number(config.weeksToShow || config.defaultWeeks || 4),
    };

    const shell = document.querySelector('.gestion-shell');
    shell?.classList.toggle('is-singleton', Boolean(config.singleton));
    shell?.classList.toggle('is-collection', !config.singleton);
    shell?.classList.toggle('has-schedule-board', isScheduleBoard(config));

    if (config.singleton) {
      dom.stats?.setAttribute('hidden', '');
      dom.newBtn?.setAttribute('hidden', '');
      document.querySelector('.gestion-list-panel')?.setAttribute('hidden', '');
    }

    dom.search?.addEventListener('input', () => renderList(config, state, dom));
    dom.status?.addEventListener('change', () => renderList(config, state, dom));
    dom.reloadBtn?.addEventListener('click', () => loadData(config, state, dom));
    dom.newBtn?.addEventListener('click', () => {
      state.editingId = null;
      renderForm(config, state, dom, getNewRecordSeed(config, dom));
      openEditorModal(dom);
    });
    const openSingletonEditor = () => {
      renderForm(config, state, dom, state.records);
      openEditorModal(dom);
    };
    dom.singletonEditBtn?.addEventListener('click', openSingletonEditor);
    dom.overviewEditBtn?.addEventListener('click', openSingletonEditor);
    dom.list?.addEventListener('click', (event) => handleListAction(event, config, state, dom));
    dom.overview?.addEventListener('click', (event) => handleListAction(event, config, state, dom));
    dom.form?.addEventListener('submit', (event) => handleSubmit(event, config, state, dom));
    dom.modalClose?.addEventListener('click', () => closeEditorModal(dom));
    dom.modal?.addEventListener('click', (event) => {
      if (event.target instanceof Element && event.target.matches('[data-modal-close]')) closeEditorModal(dom);
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && dom.modal?.classList.contains('is-open')) {
        closeEditorModal(dom);
      }
    });

    await loadData(config, state, dom);
  }

  async function loadData(config, state, dom) {
    state.loading = true;
    if (dom.list) {
      dom.list.innerHTML = '<div class="gestion-loading">Cargando registros...</div>';
    }

    try {
      const response = await fetch(`${API_URL}?resource=${encodeURIComponent(config.resource)}`, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
      });
      const result = await response.json().catch(() => ({}));
      if (!response.ok || !result.success) {
        throw new Error(result.error || 'No se pudo cargar la informacion.');
      }

      state.records = result.data || (config.singleton ? {} : []);
      state.metadata = result.metadata || {};
      setupScheduleControls(config, state, dom);
      state.loading = false;
      renderStats(config, state, dom);
      renderList(config, state, dom);
      renderSingletonOverview(config, state, dom);
      renderForm(config, state, dom, config.singleton ? state.records : {});
    } catch (error) {
      state.loading = false;
      if (dom.list) {
        dom.list.innerHTML = `<div class="gestion-empty">${escapeHtml(error.message)}</div>`;
      }
      if (dom.overview) {
        dom.overview.innerHTML = `<div class="gestion-empty">${escapeHtml(error.message)}</div>`;
      }
      showToast(dom, error.message, 'error');
    }
  }

  function renderStats(config, state, dom) {
    if (!dom.stats) return;

    if (config.singleton) {
      dom.stats.innerHTML = '';
      return;
    }

    const records = Array.isArray(state.records) ? state.records : [];
    if (isScheduleBoard(config)) {
      const activeRecords = records.filter((item) => item.active !== false);
      const doctorIds = new Set(activeRecords.map((item) => item.doctor_id).filter(Boolean));
      const specialties = new Set(
        Array.from(doctorIds)
          .map((doctorId) => findMetadataItem(state.metadata.doctors, doctorId)?.specialty_id)
          .filter(Boolean),
      );

      dom.stats.innerHTML = `
        ${statHtml(doctorIds.size, 'Doctores con horario')}
        ${statHtml(specialties.size, 'Especialidades cubiertas')}
        ${statHtml(activeRecords.length, 'Bloques activos')}
        ${statHtml(records.length - activeRecords.length, 'Bloques inactivos')}
      `;
      return;
    }

    const active = records.filter((item) => item.active !== false).length;
    const featured = records.filter((item) => item.featured === true).length;
    const inactive = records.length - active;
    dom.stats.innerHTML = `
      ${statHtml(records.length, 'Registros')}
      ${statHtml(active, 'Activos')}
      ${statHtml(inactive, 'Inactivos')}
      ${statHtml(featured, 'Destacados')}
    `;
  }

  function statHtml(value, label) {
    return `<div class="gestion-stat"><span>${escapeHtml(label)}</span><strong>${escapeHtml(value)}</strong></div>`;
  }

  function renderList(config, state, dom) {
    if (!dom.list) return;

    if (config.singleton) {
      renderSingletonOverview(config, state, dom);
      return;
    }

    const filtered = getFilteredRecords(config, state, dom);
    dom.listSummary.textContent = `${filtered.length} de ${state.records.length} registros visibles.`;

    if (isScheduleBoard(config)) {
      renderScheduleBoard(config, state, dom, filtered);
      return;
    }

    if (filtered.length === 0) {
      dom.list.innerHTML = '<div class="gestion-empty">No hay registros con los filtros actuales.</div>';
      return;
    }

    const columns = config.columns || [];
    dom.list.style.setProperty('--gestion-columns', String(Math.max(1, columns.length)));
    dom.list.innerHTML = `
      <div class="gestion-row is-header">
        <span>Registro</span>
        ${columns.map((column) => `<span>${escapeHtml(column.label)}</span>`).join('')}
        <span>Acciones</span>
      </div>
      ${filtered.map((record) => renderRow(config, record, state.metadata)).join('')}
    `;
  }

  function renderSingletonOverview(config, state, dom) {
    if (!config.singleton || !dom.overview) return;

    const record = state.records || {};
    const fieldByName = new Map((config.fields || []).map((field) => [field.name, field]));
    const preferredFields = ['name', 'legal_name', 'tagline', 'hero_title', 'reservation_title', 'address', 'email', 'phone_display', 'emergency_phone', 'whatsapp_url', 'facebook_url', 'instagram_url'];
    const selected = preferredFields.map((name) => fieldByName.get(name)).filter(Boolean);
    const extra = (config.fields || []).filter((field) => !preferredFields.includes(field.name)).slice(0, Math.max(0, 10 - selected.length));
    const fields = selected.concat(extra).slice(0, 12);
    const name = getValue(record, 'name') || 'Clinica';
    const tagline = getValue(record, 'tagline') || getValue(record, 'description') || 'Sin descripcion publica.';
    const heroImage = normalizeAssetUrl(getValue(record, 'hero_image') || getValue(record, 'hero_mobile_image'));

    dom.overview.innerHTML = `
      <div class="gestion-overview-hero">
        <div class="gestion-overview-visual">
          ${heroImage ? `<img src="${escapeHtml(heroImage)}" alt="${escapeHtml(name)}">` : '<span>Sin imagen</span>'}
        </div>
        <div class="gestion-overview-copy">
          <span class="gestion-kicker">Clinica</span>
          <h3>${escapeHtml(name)}</h3>
          <p>${escapeHtml(tagline)}</p>
        </div>
        <button type="button" class="btn btn-primary" data-action="edit-single">${iconSvg('edit')} Editar informacion</button>
      </div>
      <div class="gestion-overview-grid">
        ${fields.map((field) => renderOverviewItem(field, record, state.metadata)).join('')}
      </div>
    `;
  }

  function renderOverviewItem(field, record, metadata) {
    const value = formatDisplayValue(field, getValue(record, field.name), { metadata });
    return `
      <div class="gestion-overview-item">
        <span>${escapeHtml(field.label)}</span>
        <strong>${escapeHtml(value || 'Sin dato')}</strong>
      </div>
    `;
  }

  function getFilteredRecords(config, state, dom) {
    const term = (dom.search?.value || '').toLowerCase().trim();
    const status = dom.status?.value || 'all';
    const records = Array.isArray(state.records) ? state.records : [];
    const selectedDoctor = isScheduleBoard(config) ? dom.scheduleDoctor?.value || '' : '';
    const selectedSpecialty = isScheduleBoard(config) ? dom.scheduleSpecialty?.value || '' : '';

    return records.filter((record) => {
      const matchesStatus = status === 'all' || (status === 'active' && record.active !== false) || (status === 'inactive' && record.active === false);
      const doctor = isScheduleBoard(config) ? findMetadataItem(state.metadata.doctors, record.doctor_id) : null;
      const matchesDoctor = !selectedDoctor || record.doctor_id === selectedDoctor;
      const matchesSpecialty = !selectedSpecialty || doctor?.specialty_id === selectedSpecialty;
      const haystack = isScheduleBoard(config) ? scheduleSearchText(record, state.metadata) : JSON.stringify(record).toLowerCase();
      return matchesStatus && matchesDoctor && matchesSpecialty && (term === '' || haystack.includes(term));
    });
  }

  function renderRow(config, record, metadata) {
    const titleField = config.titleField || 'name';
    const title = getValue(record, titleField) || record.id || 'Registro';
    const columns = config.columns || [];

    return `
      <div class="gestion-row" data-id="${escapeHtml(record.id || '')}">
        <span class="gestion-cell is-title" data-label="Registro">
          <strong>${escapeHtml(title)}</strong>
          <span class="gestion-badges">${statusBadges(record)}</span>
        </span>
        ${columns.map((column) => `<span class="gestion-cell is-muted" data-label="${escapeHtml(column.label)}">${escapeHtml(formatDisplayValue(column, getValue(record, column.name), { metadata }))}</span>`).join('')}
        <span class="gestion-row-actions" data-label="Acciones">
          <button type="button" class="gestion-icon-btn" data-action="edit" aria-label="Editar" title="Editar">${iconSvg('edit')}<span class="sr-only">Editar</span></button>
          <button type="button" class="gestion-icon-btn is-danger" data-action="delete" aria-label="Desactivar" title="Desactivar">${iconSvg('trash')}<span class="sr-only">Desactivar</span></button>
        </span>
      </div>
    `;
  }

  function statusBadges(record) {
    const active = record.active !== false;
    const featured = record.featured === true;
    return `
      <span class="gestion-badge ${active ? 'is-active' : ''}">${active ? 'Activo' : 'Inactivo'}</span>
      ${featured ? '<span class="gestion-badge is-featured">Destacado</span>' : ''}
    `;
  }

  function isScheduleBoard(config) {
    return config.view === 'schedule-board';
  }

  function setupScheduleControls(config, state, dom) {
    if (!isScheduleBoard(config) || !dom.filters) return;

    // Ensure the sub-toolbar exists before trying to use it.
    if (!dom.subToolbar) {
      dom.subToolbar = document.createElement('div');
      dom.subToolbar.className = 'gestion-sub-toolbar';
      dom.filters.after(dom.subToolbar);
    }

    dom.filters.classList.add('is-schedule-filters');

    if (!dom.scheduleSpecialty) {
      dom.scheduleSpecialty = document.createElement('select');
      dom.scheduleSpecialty.id = 'gestion-schedule-specialty-filter';
      dom.scheduleSpecialty.className = 'gestion-select';
      dom.scheduleSpecialty.setAttribute('aria-label', 'Filtrar horarios por especialidad');
      const handleSpecialtyFilter = () => {
        populateScheduleDoctorOptions(state, dom);
        applyScheduleMobileDefaultDoctor(dom);
        renderList(config, state, dom);
      };
      dom.scheduleSpecialty.addEventListener('input', handleSpecialtyFilter);
      dom.scheduleSpecialty.addEventListener('change', handleSpecialtyFilter);
      dom.filters.appendChild(dom.scheduleSpecialty);
    }

    if (!dom.scheduleDoctor) {
      dom.scheduleDoctor = document.createElement('select');
      dom.scheduleDoctor.id = 'gestion-schedule-doctor-filter';
      dom.scheduleDoctor.className = 'gestion-select';
      dom.scheduleDoctor.setAttribute('aria-label', 'Filtrar horarios por doctor');
      const handleDoctorFilter = () => {
        dom.scheduleDoctor.dataset.userSelected = 'true';
        renderList(config, state, dom);
      };
      dom.scheduleDoctor.addEventListener('input', handleDoctorFilter);
      dom.scheduleDoctor.addEventListener('change', handleDoctorFilter);
      dom.filters.appendChild(dom.scheduleDoctor);
    }

    if (!dom.scheduleWeekNav) {
      dom.scheduleWeekNav = document.createElement('div');
      dom.scheduleWeekNav.className = 'gestion-schedule-week-nav';

      dom.schedulePrevWeekBtn = document.createElement('button');
      dom.schedulePrevWeekBtn.type = 'button';
      dom.schedulePrevWeekBtn.className = 'gestion-icon-btn';
      dom.schedulePrevWeekBtn.innerHTML = iconSvg('chevron-left');
      dom.schedulePrevWeekBtn.setAttribute('aria-label', 'Semana anterior');

      dom.scheduleTodayBtn = document.createElement('button');
      dom.scheduleTodayBtn.type = 'button';
      dom.scheduleTodayBtn.className = 'gestion-icon-btn';
      dom.scheduleTodayBtn.innerHTML = iconSvg('calendar');
      dom.scheduleTodayBtn.setAttribute('aria-label', 'Semana actual');

      dom.scheduleWeekDisplay = document.createElement('span');
      dom.scheduleWeekDisplay.className = 'gestion-schedule-week-display';

      dom.scheduleNextWeekBtn = document.createElement('button');
      dom.scheduleNextWeekBtn.type = 'button';
      dom.scheduleNextWeekBtn.className = 'gestion-icon-btn';
      dom.scheduleNextWeekBtn.innerHTML = iconSvg('chevron-right');
      dom.scheduleNextWeekBtn.setAttribute('aria-label', 'Semana siguiente');

      const navigateWeek = (days) => {
        const current = parseIsoDate(state.scheduleWeekStart) || new Date();
        const nextDate = addDays(current, days);
        state.scheduleWeekStart = formatDateIso(nextDate);
        renderList(config, state, dom);
      };

      dom.schedulePrevWeekBtn.addEventListener('click', () => navigateWeek(-7));
      dom.scheduleNextWeekBtn.addEventListener('click', () => navigateWeek(7));
      dom.scheduleTodayBtn.addEventListener('click', () => {
        state.scheduleWeekStart = getWeekStartIso(new Date());
        renderList(config, state, dom);
      });

      dom.scheduleWeekNav.appendChild(dom.schedulePrevWeekBtn);
      dom.scheduleWeekNav.appendChild(dom.scheduleTodayBtn);
      dom.scheduleWeekNav.appendChild(dom.scheduleWeekDisplay);
      dom.scheduleWeekNav.appendChild(dom.scheduleNextWeekBtn);
      dom.subToolbar.appendChild(dom.scheduleWeekNav);
    }

    if (!dom.scheduleWeeks) {
      // If a fixed number of weeks is provided as a number, don't create the selector.
      if (typeof config.weeksToShow === 'number' && !isNaN(config.weeksToShow)) {
        state.scheduleWeeks = config.weeksToShow;
      } else {
        dom.scheduleWeeks = document.createElement('select');
        dom.scheduleWeeks.id = 'gestion-schedule-weeks-filter';
        dom.scheduleWeeks.className = 'gestion-select';
        dom.scheduleWeeks.setAttribute('aria-label', 'Cantidad de semanas futuras');
        const options = config.weekOptions || [1, 2, 4, 8, 12];
        dom.scheduleWeeks.innerHTML = options.map((value) => `<option value="${escapeHtml(value)}">${escapeHtml(value)} semana${Number(value) === 1 ? '' : 's'}</option>`).join('');
        dom.scheduleWeeks.value = String(state.scheduleWeeks);
        const handleWeeks = () => {
          const nextValue = Number(dom.scheduleWeeks.value || state.scheduleWeeks || 4);
          state.scheduleWeeks = Math.max(1, Math.min(12, nextValue));
          renderList(config, state, dom);
        };
        dom.scheduleWeeks.addEventListener('input', handleWeeks);
        dom.scheduleWeeks.addEventListener('change', handleWeeks);
        dom.subToolbar.appendChild(dom.scheduleWeeks);
      }
    }

    populateScheduleSpecialtyOptions(state, dom);
    populateScheduleDoctorOptions(state, dom);
    applyScheduleMobileDefaultDoctor(dom);
  }

  function populateScheduleSpecialtyOptions(state, dom) {
    const current = dom.scheduleSpecialty.value || '';
    const specialties = Array.isArray(state.metadata.specialties) ? state.metadata.specialties : [];
    dom.scheduleSpecialty.innerHTML = `
      <option value="">Todas las especialidades</option>
      ${specialties.map((item) => `<option value="${escapeHtml(item.id)}">${escapeHtml(item.name)}</option>`).join('')}
    `;
    dom.scheduleSpecialty.value = specialties.some((item) => item.id === current) ? current : '';
  }

  function populateScheduleDoctorOptions(state, dom) {
    const current = dom.scheduleDoctor.value || '';
    const selectedSpecialty = dom.scheduleSpecialty?.value || '';
    const doctors = (Array.isArray(state.metadata.doctors) ? state.metadata.doctors : []).filter((doctor) => {
      return !selectedSpecialty || doctor.specialty_id === selectedSpecialty;
    });

    dom.scheduleDoctor.innerHTML = `
      <option value="">Todos los doctores</option>
      ${doctors.map((doctor) => `<option value="${escapeHtml(doctor.id)}">${escapeHtml(doctor.name)}</option>`).join('')}
    `;
    dom.scheduleDoctor.value = doctors.some((doctor) => doctor.id === current) ? current : '';
  }

  function applyScheduleMobileDefaultDoctor(dom) {
    if (!dom.scheduleDoctor || dom.scheduleDoctor.dataset.userSelected === 'true') return;
    if (!window.matchMedia('(max-width: 720px)').matches) return;
    if (dom.scheduleDoctor.value !== '') return;

    const firstDoctorOption = Array.from(dom.scheduleDoctor.options).find((option) => option.value !== '');
    if (firstDoctorOption) {
      dom.scheduleDoctor.value = firstDoctorOption.value;
    }
  }

  function renderScheduleBoard(config, state, dom, records) {
    const allRecords = Array.isArray(state.records) ? state.records : [];
    const weekdays = Array.isArray(state.metadata.weekdays) ? state.metadata.weekdays : [];
    const doctors = getVisibleScheduleDoctors(state, dom, records);
    const weeks = getScheduleWeeks(state, dom);

    if (dom.scheduleWeekDisplay) {
      dom.scheduleWeekDisplay.textContent = formatWeekRange(weeks[0].start, weeks[0].end);
    }

    dom.listSummary.textContent = `${records.length} de ${allRecords.length} bloques filtrados. Mostrando ${weeks.length} semana${weeks.length === 1 ? '' : 's'} desde ${formatShortDate(weeks[0]?.start)}.`;

    if (doctors.length === 0) {
      dom.list.innerHTML = '<div class="gestion-empty">No hay doctores u horarios que coincidan con los filtros actuales.</div>';
      return;
    }

    const grouped = groupScheduleRecords(records);

    dom.list.innerHTML = `
      <div class="schedule-weeks">
        ${weeks.map((week, index) => renderScheduleWeekSection(week, index, doctors, weekdays, grouped, state.metadata)).join('')}
      </div>
    `;
  }

  function getVisibleScheduleDoctors(state, dom, filteredRecords) {
    const doctors = Array.isArray(state.metadata.doctors) ? state.metadata.doctors : [];
    const selectedSpecialty = dom.scheduleSpecialty?.value || '';
    const selectedDoctor = dom.scheduleDoctor?.value || '';
    const term = (dom.search?.value || '').toLowerCase().trim();
    const recordDoctorIds = new Set(filteredRecords.map((record) => record.doctor_id).filter(Boolean));

    return doctors.filter((doctor) => {
      const specialty = findMetadataItem(state.metadata.specialties, doctor.specialty_id);
      const doctorText = `${doctor.name || ''} ${specialty?.name || ''}`.toLowerCase();
      const matchesSpecialty = !selectedSpecialty || doctor.specialty_id === selectedSpecialty;
      const matchesDoctor = !selectedDoctor || doctor.id === selectedDoctor;
      const matchesSearch = term === '' || doctorText.includes(term) || recordDoctorIds.has(doctor.id);
      return matchesSpecialty && matchesDoctor && matchesSearch;
    });
  }

  function groupScheduleRecords(records) {
    return records.reduce(
      (grouped, record) => {
        const doctorId = record.doctor_id || '';
        const date = String(record.date || '').trim();
        const weekday = String(record.weekday || '');
        const bucket = date ? 'dates' : 'weekdays';
        const key = date || weekday;
        if (!doctorId || !key) return grouped;
        if (!grouped[bucket][doctorId]) grouped[bucket][doctorId] = {};
        if (!grouped[bucket][doctorId][key]) grouped[bucket][doctorId][key] = [];
        grouped[bucket][doctorId][key].push(record);
        grouped[bucket][doctorId][key].sort((a, b) => String(a.start_time || '').localeCompare(String(b.start_time || '')));
        return grouped;
      },
      { weekdays: {}, dates: {} },
    );
  }

  function renderScheduleWeekSection(week, index, doctors, weekdays, grouped, metadata) {
    const days = getWeekDays(week.start, weekdays);
    return `
      <section class="schedule-week-section">
        <div class="schedule-week-title">
          <span>Semana</span>
          <strong>${escapeHtml(formatWeekRange(week.start, week.end))}</strong>
        </div>
        <div class="schedule-board" style="--schedule-days: ${days.length};">
          <div class="schedule-board-head">
            <span class="schedule-doctor-heading">Doctor</span>
            ${days.map((day) => renderScheduleDayHeading(day)).join('')}
          </div>
          ${doctors.map((doctor) => renderScheduleDoctorRow(doctor, days, grouped, metadata)).join('')}
        </div>
      </section>
    `;
  }

  function renderScheduleDayHeading(day) {
    return `
      <span class="schedule-day-heading">
        <strong>${escapeHtml(day.name)}</strong>
        <small>${escapeHtml(day.dateLabel)}</small>
      </span>
    `;
  }

  function renderScheduleDoctorRow(doctor, days, grouped, metadata) {
    const specialty = findMetadataItem(metadata.specialties, doctor.specialty_id);
    return `
      <div class="schedule-doctor-row">
        <div class="schedule-doctor-cell">
          <strong>${escapeHtml(doctor.name || 'Doctor')}</strong>
          <span>${escapeHtml(specialty?.name || 'Sin especialidad')}</span>
        </div>
        ${days.map((day) => renderScheduleDayCell(doctor, day, getScheduleRecordsForDay(grouped, doctor.id, day))).join('')}
      </div>
    `;
  }

  function renderScheduleDayCell(doctor, day, records) {
    const isPastDate = day.date < todayIso();
    return `
      <div class="schedule-day-cell" data-label="${escapeHtml(day.name)}">
        <div class="schedule-day-mobile-label">${escapeHtml(day.name)} <span>${escapeHtml(day.dateLabel)}</span></div>
        <div class="schedule-slot-list">
          ${records.length > 0 ? records.map(renderScheduleSlot).join('') : '<span class="schedule-empty">Sin horario</span>'}
        </div>
        <button type="button" class="schedule-add-btn" data-action="new-schedule" data-doctor-id="${escapeHtml(doctor.id)}" data-weekday="${escapeHtml(day.id)}" data-date="${escapeHtml(day.date)}" ${isPastDate ? 'disabled title="Fecha anterior"' : ''}>Agregar</button>
      </div>
    `;
  }

  function renderScheduleSlot(record) {
    const active = record.active !== false;
    const time = `${record.start_time || '--:--'} - ${record.end_time || '--:--'}`;
    const scope = record.date ? `Fecha ${record.date}` : 'Semanal';
    return `
      <div class="schedule-slot-wrap ${active ? '' : 'is-inactive'}" data-id="${escapeHtml(record.id || '')}">
        <button type="button" class="schedule-slot" data-action="edit" aria-label="Editar horario ${escapeHtml(time)}">
          <span>${escapeHtml(time)}</span>
          <small>${escapeHtml(record.slot_minutes || 15)} min - ${escapeHtml(scope)}</small>
        </button>
        <button type="button" class="schedule-slot-delete" data-action="delete" aria-label="Eliminar horario" title="Eliminar">${iconSvg('trash')}</button>
      </div>
    `;
  }

  function getScheduleRecordsForDay(grouped, doctorId, day) {
    const weekly = grouped.weekdays?.[doctorId]?.[String(day.id)] || [];
    const dated = grouped.dates?.[doctorId]?.[String(day.date)] || [];
    return weekly.concat(dated).sort((a, b) => String(a.start_time || '').localeCompare(String(b.start_time || '')));
  }

  function scheduleSearchText(record, metadata) {
    const doctor = findMetadataItem(metadata.doctors, record.doctor_id);
    const specialty = findMetadataItem(metadata.specialties, doctor?.specialty_id);
    const weekday = findMetadataItem(metadata.weekdays, record.weekday);
    return `${JSON.stringify(record)} ${record.date || ''} ${doctor?.name || ''} ${specialty?.name || ''} ${weekday?.name || ''}`.toLowerCase();
  }

  function findMetadataItem(items, id) {
    return (Array.isArray(items) ? items : []).find((item) => String(item.id) === String(id)) || null;
  }

  function getScheduleWeeks(state, dom) {
    const start = getWeekStart(parseIsoDate(state.scheduleWeekStart) || new Date());
    const count = Math.max(1, Math.min(12, Number(dom.scheduleWeeks?.value || state.scheduleWeeks || 4)));

    return Array.from({ length: count }, (_, index) => {
      const weekStart = addDays(start, index * 7);
      return {
        start: weekStart,
        end: addDays(weekStart, 6),
      };
    });
  }

  function getWeekDays(weekStart, weekdays) {
    return weekdays.map((day) => {
      const offset = Math.max(1, Math.min(7, Number(day.id || 1))) - 1;
      const date = addDays(weekStart, offset);
      return {
        ...day,
        date: formatDateIso(date),
        dateLabel: formatShortDate(date),
      };
    });
  }

  function getWeekStartIso(date) {
    return formatDateIso(getWeekStart(date));
  }

  function getWeekStart(date) {
    const copy = new Date(date);
    copy.setHours(0, 0, 0, 0);
    const day = copy.getDay() === 0 ? 7 : copy.getDay();
    copy.setDate(copy.getDate() - day + 1);
    return copy;
  }

  function addDays(date, days) {
    const copy = new Date(date);
    copy.setDate(copy.getDate() + days);
    copy.setHours(0, 0, 0, 0);
    return copy;
  }

  function parseIsoDate(value) {
    const match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (!match) return null;
    return new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
  }

  function formatDateIso(date) {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
  }

  function todayIso() {
    return formatDateIso(new Date());
  }

  function formatShortDate(date) {
    if (!(date instanceof Date) || Number.isNaN(date.getTime())) return '';
    return date.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' });
  }

  function formatWeekRange(start, end) {
    return `${formatShortDate(start)} - ${formatShortDate(end)}`;
  }

  function getNewRecordSeed(config, dom) {
    if (!isScheduleBoard(config)) return {};

    return {
      doctor_id: dom.scheduleDoctor?.value || '',
      date: '',
      weekday: '1',
      start_time: '08:00',
      end_time: '12:00',
      slot_minutes: 15,
      active: true,
    };
  }

  function getSvgPreview(value) {
    const trimmedValue = String(value || '').trim();
    if (!trimmedValue) return null;

    if (!isSafeSvgMarkup(trimmedValue)) return null;
    const lowerValue = trimmedValue.toLowerCase();

    // Case 1: It's already a full SVG element
    if (lowerValue.startsWith('<svg') && lowerValue.endsWith('</svg>')) {
      return trimmedValue;
    }

    // Case 2: It's SVG content (like <path...>) that needs to be wrapped
    if (trimmedValue.startsWith('<') && trimmedValue.endsWith('>')) {
      const isLikelySvgContent = ['<path', '<circle', '<rect', '<polygon', '<polyline', '<line', '<ellipse', '<g'].some((tag) => lowerValue.startsWith(tag));
      if (isLikelySvgContent) {
        // This wrapper is a generic one for icons.
        return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${trimmedValue}</svg>`;
      }
    }

    return null;
  }

  function isSafeSvgMarkup(value) {
    const markup = String(value || '').trim();
    if (!markup.startsWith('<')) return true;

    return !/(<\s*script|<\s*foreignobject|<\s*iframe|<\s*object|<\s*embed|on[a-z]+\s*=|javascript:|data:text\/html)/i.test(markup);
  }

  function renderForm(config, state, dom, record) {
    const isEditing = Boolean(record && record.id);
    state.editingId = isEditing ? record.id : null;
    dom.form.classList.toggle('is-singleton-form', Boolean(config.singleton));
    const title = config.singleton ? 'Editar clinica' : isScheduleBoard(config) ? (isEditing ? 'Editar horario' : 'Nuevo horario') : isEditing ? 'Editar registro' : 'Nuevo registro';
    if (dom.editorTitle) dom.editorTitle.textContent = title;
    dom.editorSummary.textContent = config.singleton ? 'Actualice los datos que se publican en el sitio.' : isEditing ? `Editando ${record.id}` : 'Complete los campos requeridos.';

    clearValidationErrors(dom);
    const fieldsHtml = config.fields.map((field) => renderField(field, record || {}, state.metadata)).join('');
    const submitLabel = config.singleton ? 'Guardar cambios' : isEditing ? 'Actualizar' : 'Crear';
    dom.form.innerHTML = `
      ${fieldsHtml}
      <div class="gestion-form-actions">
        <button type="submit" class="btn btn-primary" data-submit-btn data-default-label="${escapeHtml(submitLabel)}">${escapeHtml(submitLabel)}</button>
        <button type="button" class="btn btn-tertiary" id="gestion-cancel-btn">Cancelar</button>
      </div>
    `;

    document.getElementById('gestion-cancel-btn')?.addEventListener('click', () => closeEditorModal(dom));
    attachImageUploadHandlers(config, dom);
    attachValidationCleanup(dom);
    attachUserRoleHandlers(config, dom);
    attachScheduleDateSync(config, dom);
  }

  function renderField(field, record, metadata) {
    const value = getValue(record, field.name);
    const id = fieldDomId(field.name);
    const helpId = `${id}-help`;
    const errorId = `${id}-error`;
    const help = field.help ? `<small id="${escapeHtml(helpId)}">${escapeHtml(field.help)}</small>` : '';
    const error = `<span class="gestion-field-error" id="${escapeHtml(errorId)}" aria-live="polite"></span>`;
    const describedBy = `${field.help ? `${helpId} ` : ''}${errorId}`.trim();
    const fieldClass = `gestion-field is-${escapeHtml(field.type || 'text')}`;

    if (field.type === 'checkbox') {
      const checkboxValue = value === '' && field.name === 'active' ? true : value;
      return `
        <div class="gestion-check-field" data-field="${escapeHtml(field.name)}">
          <label for="${escapeHtml(id)}">${escapeHtml(field.label)}</label>
          <input id="${escapeHtml(id)}" name="${escapeHtml(field.name)}" type="checkbox" ${checkboxValue === true || checkboxValue === 'true' ? 'checked' : ''}>
        </div>
      `;
    }

    if (field.type === 'image') {
      return renderImageField(field, value, help, error, describedBy, fieldClass);
    }

    if (field.type === 'textarea') {
      return `
        <div class="${fieldClass}" data-field="${escapeHtml(field.name)}">
          <label for="${escapeHtml(id)}">${escapeHtml(field.label)}</label>
          <textarea id="${escapeHtml(id)}" class="gestion-textarea" name="${escapeHtml(field.name)}" ${renderFieldAttributes(field, 'textarea', describedBy)}>${escapeHtml(formatInputValue(value))}</textarea>
          ${help}
          ${error}
        </div>
      `;
    }

    if (field.type === 'select') {
      return `
        <div class="${fieldClass}" data-field="${escapeHtml(field.name)}">
          <label for="${escapeHtml(id)}">${escapeHtml(field.label)}</label>
          <select id="${escapeHtml(id)}" class="gestion-select" name="${escapeHtml(field.name)}" ${renderFieldAttributes(field, 'select', describedBy)}>
            <option value="">Seleccione...</option>
            ${getOptions(field, metadata)
              .map((option) => `<option value="${escapeHtml(option.id)}" ${String(option.id) === String(value) ? 'selected' : ''}>${escapeHtml(option.name)}</option>`)
              .join('')}
          </select>
          ${help}
          ${error}
        </div>
      `;
    }

    const inputType = field.type || 'text';
    const inputValue = inputType === 'password' ? '' : formatInputValue(value);
    return `
      <div class="${fieldClass}" data-field="${escapeHtml(field.name)}">
        <label for="${escapeHtml(id)}">${escapeHtml(field.label)}</label>
        <input id="${escapeHtml(id)}" class="gestion-input" name="${escapeHtml(field.name)}" type="${escapeHtml(inputType)}" value="${escapeHtml(inputValue)}" ${renderFieldAttributes(field, inputType, describedBy)}>
        ${help}
        ${error}
      </div>
    `;
  }

  function renderImageField(field, value, help, error, describedBy, fieldClass) {
    const inputValue = formatInputValue(value); // Puede ser URL o codigo SVG
    const accept = field.accept || 'image/jpeg,image/png,image/webp,image/gif,image/svg+xml'; // Añadir SVG a los formatos aceptados
    let previewContent = '<span>Sin imagen</span>';

    const svgPreview = getSvgPreview(inputValue);
    if (svgPreview) {
      previewContent = svgPreview;
    } else if (String(inputValue).trim().startsWith('<')) {
      previewContent = '<span>SVG no seguro</span>';
    } else {
      const previewUrl = normalizeAssetUrl(inputValue);
      if (previewUrl) {
        previewContent = `<img src="${escapeHtml(previewUrl)}" alt="">`;
      }
    }

    return `
      <div class="${fieldClass}" data-field="${escapeHtml(field.name)}" data-image-field="${escapeHtml(field.name)}">
        <label for="${escapeHtml(fieldDomId(field.name))}">${escapeHtml(field.label)}</label>
        <div class="gestion-image-control">
          <div class="gestion-image-preview" data-image-preview="${escapeHtml(field.name)}">
            ${previewContent}
          </div>
          <div class="gestion-image-body">
            <input id="${escapeHtml(fieldDomId(field.name))}" class="gestion-input" name="${escapeHtml(field.name)}" type="text" value="${escapeHtml(inputValue)}" placeholder="${escapeHtml(field.placeholder || 'URL o codigo SVG...')}" ${describedBy ? `aria-describedby="${escapeHtml(describedBy)}"` : ''}>
            <div class="gestion-image-actions">
              <label class="btn btn-tertiary btn-sm gestion-upload-label" for="${escapeHtml(fieldDomId(field.name))}-file">Subir imagen</label>
              <input id="${escapeHtml(fieldDomId(field.name))}-file" class="sr-only" type="file" accept="${escapeHtml(accept)}" data-image-upload="${escapeHtml(field.name)}">
              <button type="button" class="btn btn-tertiary btn-sm" data-action="clear-image" data-image-field-name="${escapeHtml(field.name)}">Limpiar</button>
            </div>
            <span class="gestion-upload-status" data-image-status="${escapeHtml(field.name)}" aria-live="polite"></span>
            ${help}
            ${error}
          </div>
        </div>
      </div>
    `;
  }

  function fieldDomId(name) {
    return `field-${String(name || '').replace(/[^a-z0-9_-]/gi, '-')}`;
  }

  function renderFieldAttributes(field, inputType, describedBy) {
    const attributes = [];
    if (field.required) attributes.push('required');
    if (field.min !== undefined && field.min !== '') attributes.push(`min="${escapeHtml(field.min)}"`);
    if (field.max !== undefined && field.max !== '') attributes.push(`max="${escapeHtml(field.max)}"`);
    if (field.step !== undefined && field.step !== '') attributes.push(`step="${escapeHtml(field.step)}"`);
    if (field.minlength !== undefined && field.minlength !== '') attributes.push(`minlength="${escapeHtml(field.minlength)}"`);
    if (field.maxlength !== undefined && field.maxlength !== '') attributes.push(`maxlength="${escapeHtml(field.maxlength)}"`);
    if (field.pattern) attributes.push(`pattern="${escapeHtml(field.pattern)}"`);
    if (field.placeholder) attributes.push(`placeholder="${escapeHtml(field.placeholder)}"`);
    if (field.autocomplete) attributes.push(`autocomplete="${escapeHtml(field.autocomplete)}"`);
    if (field.inputmode) attributes.push(`inputmode="${escapeHtml(field.inputmode)}"`);
    if (describedBy) attributes.push(`aria-describedby="${escapeHtml(describedBy)}"`);
    if (inputType === 'time' && field.step === undefined) attributes.push('step="300"');
    return attributes.join(' ');
  }

  function attachImageUploadHandlers(config, dom) {
    dom.form?.querySelectorAll('[data-image-upload]').forEach((input) => {
      input.addEventListener('change', () => uploadImage(input, config, dom));
    });

    dom.form?.querySelectorAll('[data-action="clear-image"]').forEach((button) => {
      button.addEventListener('click', () => {
        const fieldName = button.dataset.imageFieldName;
        dom.form.elements[fieldName].value = '';
        updateImagePreview(fieldName, '', dom);
      });
    });

    dom.form?.querySelectorAll('[data-image-field] .gestion-input').forEach((input) => {
      input.addEventListener('input', () => updateImagePreview(input.name, input.value, dom));
    });
  }

  async function uploadImage(input, config, dom) {
    const fieldName = input.dataset.imageUpload || '';
    const file = input.files?.[0];
    if (!fieldName || !file) return;

    const fieldElement = input.closest('[data-image-field]');
    const status = fieldElement?.querySelector('[data-image-status]');
    const label = fieldElement?.querySelector('.gestion-upload-label');
    const textInput = dom.form?.elements[fieldName];
    const uploadData = new FormData();
    uploadData.append('action', 'upload');
    uploadData.append('field', fieldName);
    uploadData.append('image', file);

    try {
      if (status) status.textContent = 'Subiendo imagen...';
      label?.classList.add('is-uploading');

      const response = await fetch(`${API_URL}?resource=${encodeURIComponent(config.resource)}`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          'X-CSRF-Token': getCsrfToken(),
        },
        body: uploadData,
      });
      const result = await response.json().catch(() => ({}));
      if (!response.ok || !result.success || !result.url) {
        throw new Error(result.error || 'No se pudo subir la imagen.');
      }

      if (textInput) textInput.value = result.url;
      updateImagePreview(fieldName, result.url, dom);
      if (status) status.textContent = 'Imagen lista para guardar.';
      showToast(dom, 'Imagen subida correctamente.');
    } catch (error) {
      if (status) status.textContent = '';
      showToast(dom, error.message || 'No se pudo subir la imagen.', 'error');
    } finally {
      label?.classList.remove('is-uploading');
      input.value = '';
    }
  }

  function updateImagePreview(fieldName, value, dom) {
    const preview = dom.form?.querySelector(`[data-image-preview="${fieldName}"]`);
    if (!preview) return;

    const svgPreview = getSvgPreview(value);
    if (svgPreview) {
      preview.innerHTML = svgPreview;
    } else if (String(value || '').trim().startsWith('<')) {
      preview.innerHTML = '<span>SVG no seguro</span>';
    } else {
      const url = normalizeAssetUrl(value);
      preview.innerHTML = url ? `<img src="${escapeHtml(url)}" alt="">` : '<span>Sin imagen</span>';
    }
  }
  function attachScheduleDateSync(config, dom) {
    if (!isScheduleBoard(config)) return;

    const dateInput = dom.form?.elements.date;
    const weekdayInput = dom.form?.elements.weekday;
    if (!dateInput || !weekdayInput) return;

    dateInput.addEventListener('change', () => {
      const date = parseIsoDate(dateInput.value);
      if (!date) return;
      weekdayInput.value = String(date.getDay() === 0 ? 7 : date.getDay());
    });
  }

  function attachValidationCleanup(dom) {
    dom.form?.querySelectorAll('input, select, textarea').forEach((field) => {
      const clear = () => clearFieldError(dom, field.name);
      field.addEventListener('input', clear);
      field.addEventListener('change', clear);
    });
  }

  function attachUserRoleHandlers(config, dom) {
    if (config.resource !== 'users') return;

    const roleInput = dom.form?.elements.role;
    const doctorInput = dom.form?.elements.doctor_id;
    if (!roleInput || !doctorInput) return;

    const syncDoctorField = () => {
      const mustLinkDoctor = roleInput.value === 'DOCTOR';
      const wrapper = getFieldWrapper(dom, 'doctor_id');
      doctorInput.required = mustLinkDoctor;
      doctorInput.disabled = !mustLinkDoctor;
      doctorInput.setAttribute('aria-required', String(mustLinkDoctor));
      wrapper?.classList.toggle('is-disabled', !mustLinkDoctor);

      if (!mustLinkDoctor) {
        doctorInput.value = '';
        clearFieldError(dom, 'doctor_id');
      }
    };

    if (roleInput.dataset.roleSyncAttached !== 'true') {
      roleInput.addEventListener('change', syncDoctorField);
      roleInput.dataset.roleSyncAttached = 'true';
    }
    syncDoctorField();
  }

  function validateGestionRecord(config, state, dom, record) {
    clearValidationErrors(dom);

    const isNew = !state.editingId && !config.singleton;
    const addError = (fieldName, message) => setFieldError(dom, fieldName, message);

    (config.fields || []).forEach((field) => {
      if (field.type === 'checkbox') return;
      const value = String(record[field.name] ?? '').trim();
      const element = dom.form?.elements[field.name];

      if (field.required && !element?.disabled && value === '') {
        addError(field.name, 'Este campo es requerido.');
        return;
      }

      if (value === '') return;

      if (field.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
        addError(field.name, 'Ingrese un correo valido.');
      }

      if (field.type === 'url' && !isValidHttpUrl(value)) {
        addError(field.name, 'Ingrese una URL valida con http o https.');
      }

      if (field.type === 'number') {
        const numberValue = Number(value);
        if (Number.isNaN(numberValue)) {
          addError(field.name, 'Ingrese un numero valido.');
        } else if (field.min !== undefined && numberValue < Number(field.min)) {
          addError(field.name, `El valor minimo es ${field.min}.`);
        } else if (field.max !== undefined && numberValue > Number(field.max)) {
          addError(field.name, `El valor maximo es ${field.max}.`);
        }
      }

      if (field.minlength !== undefined && value.length < Number(field.minlength)) {
        addError(field.name, `Debe tener al menos ${field.minlength} caracteres.`);
      }

      if (field.pattern && !(new RegExp(field.pattern).test(value))) {
        addError(field.name, 'Revise el formato de este campo.');
      }

      if (field.type === 'image' && value.startsWith('<') && !isSafeSvgMarkup(value)) {
        addError(field.name, 'El SVG contiene codigo no permitido.');
      }
    });

    if (config.resource === 'users') {
      const username = String(record.username || '').trim();
      if (username && !/^[a-z0-9._-]{3,50}$/i.test(username)) {
        addError('username', 'Use 3 a 50 caracteres: letras, numeros, punto, guion o guion bajo.');
      }

      if (record.role === 'DOCTOR' && String(record.doctor_id || '').trim() === '') {
        addError('doctor_id', 'Los usuarios doctores deben vincularse con un doctor.');
      }

      const password = String(record.password || '');
      if (isNew && password.trim() === '') {
        addError('password', 'La contrasena es requerida para usuarios nuevos.');
      } else if (password !== '' && password.length < 8) {
        addError('password', 'Use al menos 8 caracteres.');
      }
    }

    if (isScheduleBoard(config)) {
      const start = String(record.start_time || '');
      const end = String(record.end_time || '');
      if (start && end && start >= end) {
        addError('end_time', 'La hora de fin debe ser posterior a la hora de inicio.');
      }

      if (isNew && record.date && record.date < todayIso()) {
        addError('date', 'No cree horarios en fechas anteriores.');
      }
    }

    const firstInvalid = dom.form?.querySelector('.is-invalid input, .is-invalid select, .is-invalid textarea');
    if (firstInvalid instanceof HTMLElement) {
      firstInvalid.focus({ preventScroll: true });
      showToast(dom, 'Revise los campos marcados antes de guardar.', 'error');
      return false;
    }

    return true;
  }

  function isValidHttpUrl(value) {
    try {
      const url = new URL(value);
      return url.protocol === 'http:' || url.protocol === 'https:';
    } catch (error) {
      return false;
    }
  }

  function setFieldError(dom, fieldName, message) {
    if (!message) return;

    const wrapper = getFieldWrapper(dom, fieldName);
    const input = dom.form?.elements[fieldName];
    const error = wrapper?.querySelector('.gestion-field-error');

    wrapper?.classList.add('is-invalid');
    input?.setAttribute?.('aria-invalid', 'true');
    if (error) error.textContent = message;
  }

  function clearFieldError(dom, fieldName) {
    const wrapper = getFieldWrapper(dom, fieldName);
    const input = dom.form?.elements[fieldName];
    const error = wrapper?.querySelector('.gestion-field-error');

    wrapper?.classList.remove('is-invalid');
    input?.removeAttribute?.('aria-invalid');
    if (error) error.textContent = '';
  }

  function clearValidationErrors(dom) {
    dom.form?.querySelectorAll('[data-field]').forEach((field) => field.classList.remove('is-invalid'));
    dom.form?.querySelectorAll('[aria-invalid="true"]').forEach((field) => field.removeAttribute('aria-invalid'));
    dom.form?.querySelectorAll('.gestion-field-error').forEach((error) => {
      error.textContent = '';
    });
  }

  function getFieldWrapper(dom, fieldName) {
    return Array.from(dom.form?.querySelectorAll('[data-field]') || []).find((field) => field.dataset.field === fieldName) || null;
  }

  function getOptions(field, metadata) {
    if (Array.isArray(field.options)) return field.options;
    if (field.optionSource && Array.isArray(metadata[field.optionSource])) return metadata[field.optionSource];
    return [];
  }

  function handleListAction(event, config, state, dom) {
    const button = event.target.closest('[data-action]');
    if (!button) return;

    if (button.dataset.action === 'edit-single') {
      renderForm(config, state, dom, state.records);
      openEditorModal(dom);
      return;
    }

    if (button.dataset.action === 'new-schedule' && isScheduleBoard(config)) {
      state.editingId = null;
      renderForm(config, state, dom, {
        doctor_id: button.dataset.doctorId || '',
        date: button.dataset.date || '',
        weekday: button.dataset.weekday || '',
        start_time: '08:00',
        end_time: '12:00',
        slot_minutes: 15,
        active: true,
      });
      openEditorModal(dom);
      return;
    }

    const row = button.closest('[data-id]');
    const id = row?.dataset.id;
    const record = Array.isArray(state.records) ? state.records.find((item) => item.id === id) : null;
    if (!record) return;

    if (button.dataset.action === 'edit') {
      renderForm(config, state, dom, record);
      openEditorModal(dom);
      return;
    }

    if (button.dataset.action === 'delete') {
      deleteRecord(config, state, dom, record);
    }
  }

  async function handleSubmit(event, config, state, dom) {
    event.preventDefault();
    if (dom.form?.dataset.busy === 'true') return;

    const formData = new FormData(dom.form);
    const record = {};

    config.fields.forEach((field) => {
      if (field.type === 'checkbox') {
        record[field.name] = dom.form.elements[field.name]?.checked || false;
      } else {
        record[field.name] = formData.get(field.name) || '';
      }
    });

    if (state.editingId && !config.singleton) {
      record.id = state.editingId;
    }

    if (!validateGestionRecord(config, state, dom, record)) {
      return;
    }

    await saveRecord(config, state, dom, record);
  }

  async function saveRecord(config, state, dom, record) {
    try {
      setFormBusy(config, dom, true);
      const response = await fetch(`${API_URL}?resource=${encodeURIComponent(config.resource)}`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-Token': getCsrfToken(),
        },
        body: JSON.stringify({ action: 'save', record }),
      });
      const result = await response.json().catch(() => ({}));
      if (!response.ok || !result.success) {
        throw new Error(result.error || 'No se pudo guardar.');
      }

      state.records = result.data;
      state.metadata = result.metadata || state.metadata;
      showToast(dom, 'Datos guardados correctamente.');
      renderStats(config, state, dom);
      renderList(config, state, dom);
      renderSingletonOverview(config, state, dom);
      renderForm(config, state, dom, config.singleton ? state.records : {});
      closeEditorModal(dom);
    } catch (error) {
      showToast(dom, error.message, 'error');
    } finally {
      setFormBusy(config, dom, false);
    }
  }

  async function deleteRecord(config, state, dom, record) {
    const title = record[config.titleField || 'name'] || record.id;
    const confirmed = await showConfirmDialog({
      title: 'Desactivar registro',
      message: `Se desactivara "${title}". Puede reactivarlo editando el registro.`,
      confirmText: 'Desactivar',
    });
    if (!confirmed) return;

    try {
      const response = await fetch(`${API_URL}?resource=${encodeURIComponent(config.resource)}&id=${encodeURIComponent(record.id)}`, {
        method: 'DELETE',
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          'X-CSRF-Token': getCsrfToken(),
        },
      });
      const result = await response.json().catch(() => ({}));
      if (!response.ok || !result.success) {
        throw new Error(result.error || 'No se pudo eliminar.');
      }

      state.records = result.data;
      state.metadata = result.metadata || state.metadata;
      state.editingId = null;
      showToast(dom, 'Registro desactivado correctamente.');
      renderStats(config, state, dom);
      renderList(config, state, dom);
      renderForm(config, state, dom, {});
      closeEditorModal(dom);
    } catch (error) {
      showToast(dom, error.message, 'error');
    }
  }

  function setFormBusy(config, dom, busy) {
    if (!dom.form) return;

    dom.form.dataset.busy = busy ? 'true' : 'false';
    dom.form.classList.toggle('is-busy', busy);
    const submitButton = dom.form.querySelector('[data-submit-btn]');

    dom.form.querySelectorAll('input, select, textarea, button').forEach((element) => {
      if (!(element instanceof HTMLElement)) return;
      if (busy) {
        element.dataset.wasDisabled = element.disabled ? 'true' : 'false';
        element.disabled = true;
      } else {
        element.disabled = element.dataset.wasDisabled === 'true';
        delete element.dataset.wasDisabled;
      }
    });

    if (submitButton) {
      submitButton.textContent = busy ? 'Guardando...' : submitButton.dataset.defaultLabel || 'Guardar';
    }

    if (!busy) {
      attachUserRoleHandlers(config, dom);
    }
  }

  function showConfirmDialog({ title, message, confirmText = 'Confirmar' }) {
    return new Promise((resolve) => {
      const dialog = document.createElement('div');
      dialog.className = 'gestion-confirm-modal';
      dialog.innerHTML = `
        <div class="gestion-confirm-backdrop" data-confirm-cancel></div>
        <section class="gestion-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="gestion-confirm-title">
          <span class="gestion-kicker">Confirmacion</span>
          <h3 id="gestion-confirm-title">${escapeHtml(title)}</h3>
          <p>${escapeHtml(message)}</p>
          <div class="gestion-confirm-actions">
            <button type="button" class="btn btn-tertiary" data-confirm-cancel>Cancelar</button>
            <button type="button" class="btn btn-danger" data-confirm-accept>${escapeHtml(confirmText)}</button>
          </div>
        </section>
      `;

      const cleanup = (result) => {
        document.removeEventListener('keydown', handleKeydown);
        dialog.remove();
        resolve(result);
      };

      const handleKeydown = (event) => {
        if (event.key === 'Escape') cleanup(false);
      };

      dialog.addEventListener('click', (event) => {
        const target = event.target instanceof Element ? event.target : null;
        if (target?.matches('[data-confirm-cancel]')) cleanup(false);
        if (target?.matches('[data-confirm-accept]')) cleanup(true);
      });

      document.addEventListener('keydown', handleKeydown);
      document.body.appendChild(dialog);
      dialog.querySelector('[data-confirm-accept]')?.focus({ preventScroll: true });
    });
  }

  function getValue(record, name) {
    if (!record || typeof record !== 'object') return '';
    return record[name] ?? '';
  }

  function formatDisplayValue(field, value, config) {
    if (field.type === 'checkbox') return value ? 'Si' : 'No';
    if (field.type === 'select') {
      const source = field.optionSource;
      const options = config.metadata?.[source] || [];
      const option = options.find((item) => String(item.id) === String(value));
      return option?.name || value || '';
    }
    if (Array.isArray(value)) return value.join(', ');
    return value ?? '';
  }

  function formatInputValue(value) {
    if (Array.isArray(value)) return value.join(', ');
    return value ?? '';
  }

  function normalizeAssetUrl(value) {
    const url = String(value || '').trim();
    if (!url) return '';
    if (/^https?:\/\//i.test(url) || url.startsWith('data:image/')) return url;
    if (url.startsWith('/clinica/')) return appUrl(url.slice('/clinica/'.length));
    if (url.startsWith('/')) return url;
    if (/^[a-z][a-z0-9+.-]*:/i.test(url)) return '';
    return appUrl(url.replace(/^\.?\//, ''));
  }

  function openEditorModal(dom) {
    if (!dom.modal) return;
    dom.lastFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    dom.modal.classList.add('is-open');
    dom.modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('gestion-modal-open');

    window.setTimeout(() => {
      const firstField = dom.form?.querySelector('input, select, textarea, button');
      firstField?.focus({ preventScroll: true });
    }, 0);
  }

  function closeEditorModal(dom) {
    if (!dom.modal) return;
    dom.modal.classList.remove('is-open');
    dom.modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('gestion-modal-open');
    dom.lastFocus?.focus?.({ preventScroll: true });
  }

  function showToast(dom, message, type = 'success') {
    if (!dom.toast) return;
    dom.toast.textContent = message;
    dom.toast.className = `gestion-toast is-visible ${type === 'error' ? 'is-error' : ''}`;
    clearTimeout(showToast.timer);
    showToast.timer = setTimeout(() => dom.toast.classList.remove('is-visible'), 3600);
  }

  function iconSvg(name) {
    const icons = {
      edit: '<path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"></path>',
      trash: '<path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M19 6l-1 14H6L5 6"></path><path d="M10 11v5"></path><path d="M14 11v5"></path>',
      calendar: '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line>',
      'chevron-left': '<polyline points="15 18 9 12 15 6"></polyline>',
      'chevron-right': '<polyline points="9 18 15 12 9 6"></polyline>',
    };

    return `
      <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
        ${icons[name] || ''}
      </svg>
    `;
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

  window.ClinicaGestion = { boot };
})();

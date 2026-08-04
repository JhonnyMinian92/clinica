/*
 * gestion.js
 * Motor CRUD compartido para las paginas de pages/gestion.
 */

(function () {
  'use strict';

  const API_URL = '/clinica/api/gestion/';

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
    const preferredFields = [
      'name',
      'legal_name',
      'tagline',
      'hero_title',
      'reservation_title',
      'address',
      'email',
      'phone_display',
      'emergency_phone',
      'whatsapp_url',
      'facebook_url',
      'instagram_url',
    ];
    const selected = preferredFields
      .map((name) => fieldByName.get(name))
      .filter(Boolean);
    const extra = (config.fields || [])
      .filter((field) => !preferredFields.includes(field.name))
      .slice(0, Math.max(0, 10 - selected.length));
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
      const matchesStatus =
        status === 'all' ||
        (status === 'active' && record.active !== false) ||
        (status === 'inactive' && record.active === false);
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
          <button type="button" class="gestion-icon-btn is-danger" data-action="delete" aria-label="Eliminar" title="Eliminar">${iconSvg('trash')}<span class="sr-only">Eliminar</span></button>
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

    dom.filters.classList.add('is-schedule-filters');

    if (!dom.scheduleSpecialty) {
      dom.scheduleSpecialty = document.createElement('select');
      dom.scheduleSpecialty.id = 'gestion-schedule-specialty-filter';
      dom.scheduleSpecialty.className = 'gestion-select';
      dom.scheduleSpecialty.setAttribute('aria-label', 'Filtrar horarios por especialidad');
      const handleSpecialtyFilter = () => {
        populateScheduleDoctorOptions(state, dom);
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
      const handleDoctorFilter = () => renderList(config, state, dom);
      dom.scheduleDoctor.addEventListener('input', handleDoctorFilter);
      dom.scheduleDoctor.addEventListener('change', handleDoctorFilter);
      dom.filters.appendChild(dom.scheduleDoctor);
    }

    populateScheduleSpecialtyOptions(state, dom);
    populateScheduleDoctorOptions(state, dom);
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

  function renderScheduleBoard(config, state, dom, records) {
    const allRecords = Array.isArray(state.records) ? state.records : [];
    const weekdays = Array.isArray(state.metadata.weekdays) ? state.metadata.weekdays : [];
    const doctors = getVisibleScheduleDoctors(state, dom, records);

    dom.listSummary.textContent = `${records.length} de ${allRecords.length} bloques visibles. Seleccione un bloque para editar o agregue disponibilidad en una celda.`;

    if (doctors.length === 0) {
      dom.list.innerHTML = '<div class="gestion-empty">No hay doctores u horarios que coincidan con los filtros actuales.</div>';
      return;
    }

    const grouped = groupScheduleRecords(records);

    dom.list.innerHTML = `
      <div class="schedule-board" style="--schedule-days: ${weekdays.length};">
        <div class="schedule-board-head">
          <span class="schedule-doctor-heading">Doctor</span>
          ${weekdays.map((day) => `<span class="schedule-day-heading">${escapeHtml(day.name)}</span>`).join('')}
        </div>
        ${doctors.map((doctor) => renderScheduleDoctorRow(doctor, weekdays, grouped, state.metadata)).join('')}
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
    return records.reduce((grouped, record) => {
      const doctorId = record.doctor_id || '';
      const weekday = String(record.weekday || '');
      if (!grouped[doctorId]) grouped[doctorId] = {};
      if (!grouped[doctorId][weekday]) grouped[doctorId][weekday] = [];
      grouped[doctorId][weekday].push(record);
      grouped[doctorId][weekday].sort((a, b) => String(a.start_time || '').localeCompare(String(b.start_time || '')));
      return grouped;
    }, {});
  }

  function renderScheduleDoctorRow(doctor, weekdays, grouped, metadata) {
    const specialty = findMetadataItem(metadata.specialties, doctor.specialty_id);
    return `
      <div class="schedule-doctor-row">
        <div class="schedule-doctor-cell">
          <strong>${escapeHtml(doctor.name || 'Doctor')}</strong>
          <span>${escapeHtml(specialty?.name || 'Sin especialidad')}</span>
        </div>
        ${weekdays.map((day) => renderScheduleDayCell(doctor, day, grouped[doctor.id]?.[String(day.id)] || [])).join('')}
      </div>
    `;
  }

  function renderScheduleDayCell(doctor, day, records) {
    return `
      <div class="schedule-day-cell" data-label="${escapeHtml(day.name)}">
        <div class="schedule-day-mobile-label">${escapeHtml(day.name)}</div>
        <div class="schedule-slot-list">
          ${records.length > 0 ? records.map(renderScheduleSlot).join('') : '<span class="schedule-empty">Sin horario</span>'}
        </div>
        <button type="button" class="schedule-add-btn" data-action="new-schedule" data-doctor-id="${escapeHtml(doctor.id)}" data-weekday="${escapeHtml(day.id)}">Agregar</button>
      </div>
    `;
  }

  function renderScheduleSlot(record) {
    const active = record.active !== false;
    const time = `${record.start_time || '--:--'} - ${record.end_time || '--:--'}`;
    return `
      <div class="schedule-slot-wrap ${active ? '' : 'is-inactive'}" data-id="${escapeHtml(record.id || '')}">
        <button type="button" class="schedule-slot" data-action="edit" aria-label="Editar horario ${escapeHtml(time)}">
          <span>${escapeHtml(time)}</span>
          <small>${escapeHtml(record.slot_minutes || 15)} min por cupo</small>
        </button>
        <button type="button" class="schedule-slot-delete" data-action="delete" aria-label="Eliminar horario" title="Eliminar">${iconSvg('trash')}</button>
      </div>
    `;
  }

  function scheduleSearchText(record, metadata) {
    const doctor = findMetadataItem(metadata.doctors, record.doctor_id);
    const specialty = findMetadataItem(metadata.specialties, doctor?.specialty_id);
    const weekday = findMetadataItem(metadata.weekdays, record.weekday);
    return `${JSON.stringify(record)} ${doctor?.name || ''} ${specialty?.name || ''} ${weekday?.name || ''}`.toLowerCase();
  }

  function findMetadataItem(items, id) {
    return (Array.isArray(items) ? items : []).find((item) => String(item.id) === String(id)) || null;
  }

  function getNewRecordSeed(config, dom) {
    if (!isScheduleBoard(config)) return {};

    return {
      doctor_id: dom.scheduleDoctor?.value || '',
      weekday: '1',
      start_time: '08:00',
      end_time: '12:00',
      slot_minutes: 15,
      active: true,
    };
  }

  function renderForm(config, state, dom, record) {
    const isEditing = Boolean(record && record.id);
    state.editingId = isEditing ? record.id : null;
    dom.form.classList.toggle('is-singleton-form', Boolean(config.singleton));
    const title = config.singleton ? 'Editar clinica' : isScheduleBoard(config) ? (isEditing ? 'Editar horario' : 'Nuevo horario') : isEditing ? 'Editar registro' : 'Nuevo registro';
    if (dom.editorTitle) dom.editorTitle.textContent = title;
    dom.editorSummary.textContent = config.singleton ? 'Actualice los datos que se publican en el sitio.' : isEditing ? `Editando ${record.id}` : 'Complete los campos requeridos.';

    const fieldsHtml = config.fields.map((field) => renderField(field, record || {}, state.metadata)).join('');
    dom.form.innerHTML = `
      ${fieldsHtml}
      <div class="gestion-form-actions">
        <button type="submit" class="btn btn-primary">${config.singleton ? 'Guardar cambios' : isEditing ? 'Actualizar' : 'Crear'}</button>
        <button type="button" class="btn btn-tertiary" id="gestion-cancel-btn">Cancelar</button>
      </div>
    `;

    document.getElementById('gestion-cancel-btn')?.addEventListener('click', () => closeEditorModal(dom));
  }

  function renderField(field, record, metadata) {
    const value = getValue(record, field.name);
    const help = field.help ? `<small>${escapeHtml(field.help)}</small>` : '';
    const fieldClass = `gestion-field is-${escapeHtml(field.type || 'text')}`;

    if (field.type === 'checkbox') {
      const checkboxValue = value === '' && field.name === 'active' ? true : value;
      return `
        <div class="gestion-check-field">
          <label for="field-${escapeHtml(field.name)}">${escapeHtml(field.label)}</label>
          <input id="field-${escapeHtml(field.name)}" name="${escapeHtml(field.name)}" type="checkbox" ${checkboxValue === true || checkboxValue === 'true' ? 'checked' : ''}>
        </div>
      `;
    }

    if (field.type === 'textarea') {
      return `
        <div class="${fieldClass}">
          <label for="field-${escapeHtml(field.name)}">${escapeHtml(field.label)}</label>
          <textarea id="field-${escapeHtml(field.name)}" class="gestion-textarea" name="${escapeHtml(field.name)}" ${field.required ? 'required' : ''}>${escapeHtml(formatInputValue(value))}</textarea>
          ${help}
        </div>
      `;
    }

    if (field.type === 'select') {
      return `
        <div class="${fieldClass}">
          <label for="field-${escapeHtml(field.name)}">${escapeHtml(field.label)}</label>
          <select id="field-${escapeHtml(field.name)}" class="gestion-select" name="${escapeHtml(field.name)}" ${field.required ? 'required' : ''}>
            <option value="">Seleccione...</option>
            ${getOptions(field, metadata).map((option) => `<option value="${escapeHtml(option.id)}" ${String(option.id) === String(value) ? 'selected' : ''}>${escapeHtml(option.name)}</option>`).join('')}
          </select>
          ${help}
        </div>
      `;
    }

    const inputType = field.type || 'text';
    const inputValue = inputType === 'password' ? '' : formatInputValue(value);
    return `
      <div class="${fieldClass}">
        <label for="field-${escapeHtml(field.name)}">${escapeHtml(field.label)}</label>
        <input id="field-${escapeHtml(field.name)}" class="gestion-input" name="${escapeHtml(field.name)}" type="${escapeHtml(inputType)}" value="${escapeHtml(inputValue)}" ${field.required ? 'required' : ''} ${field.min ? `min="${escapeHtml(field.min)}"` : ''} ${field.max ? `max="${escapeHtml(field.max)}"` : ''}>
        ${help}
      </div>
    `;
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

    await saveRecord(config, state, dom, record);
  }

  async function saveRecord(config, state, dom, record) {
    try {
      const response = await fetch(`${API_URL}?resource=${encodeURIComponent(config.resource)}`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
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
    }
  }

  async function deleteRecord(config, state, dom, record) {
    const title = record[config.titleField || 'name'] || record.id;
    if (!window.confirm(`Eliminar ${title}? Esta accion no se puede deshacer.`)) return;

    try {
      const response = await fetch(`${API_URL}?resource=${encodeURIComponent(config.resource)}`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        body: JSON.stringify({ action: 'delete', id: record.id }),
      });
      const result = await response.json().catch(() => ({}));
      if (!response.ok || !result.success) {
        throw new Error(result.error || 'No se pudo eliminar.');
      }

      state.records = result.data;
      state.metadata = result.metadata || state.metadata;
      state.editingId = null;
      showToast(dom, 'Registro eliminado.');
      renderStats(config, state, dom);
      renderList(config, state, dom);
      renderForm(config, state, dom, {});
      closeEditorModal(dom);
    } catch (error) {
      showToast(dom, error.message, 'error');
    }
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
    if (/^https?:\/\//i.test(url) || url.startsWith('/') || url.startsWith('data:image/')) return url;
    if (/^[a-z][a-z0-9+.-]*:/i.test(url)) return '';
    return `/clinica/${url.replace(/^\.?\//, '')}`;
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

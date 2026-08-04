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
      search: document.getElementById('gestion-search'),
      status: document.getElementById('gestion-status-filter'),
      newBtn: document.getElementById('gestion-new-btn'),
      reloadBtn: document.getElementById('gestion-reload-btn'),
      listSummary: document.getElementById('gestion-list-summary'),
      editorSummary: document.getElementById('gestion-editor-summary'),
      toast: document.getElementById('gestion-toast'),
    };

    const state = {
      records: config.singleton ? {} : [],
      metadata: {},
      editingId: null,
      loading: false,
    };

    dom.search?.addEventListener('input', () => renderList(config, state, dom));
    dom.status?.addEventListener('change', () => renderList(config, state, dom));
    dom.reloadBtn?.addEventListener('click', () => loadData(config, state, dom));
    dom.newBtn?.addEventListener('click', () => {
      state.editingId = null;
      renderForm(config, state, dom, {});
    });
    dom.list?.addEventListener('click', (event) => handleListAction(event, config, state, dom));
    dom.form?.addEventListener('submit', (event) => handleSubmit(event, config, state, dom));

    await loadData(config, state, dom);
  }

  async function loadData(config, state, dom) {
    state.loading = true;
    dom.list.innerHTML = '<div class="gestion-loading">Cargando registros...</div>';

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
      state.loading = false;
      renderStats(config, state, dom);
      renderList(config, state, dom);
      renderForm(config, state, dom, config.singleton ? state.records : {});
    } catch (error) {
      state.loading = false;
      dom.list.innerHTML = `<div class="gestion-empty">${escapeHtml(error.message)}</div>`;
      showToast(dom, error.message, 'error');
    }
  }

  function renderStats(config, state, dom) {
    if (!dom.stats) return;

    if (config.singleton) {
      const filled = config.fields.filter((field) => String(getValue(state.records, field.name) || '').trim() !== '').length;
      dom.stats.innerHTML = `
        ${statHtml(filled, 'Campos completos')}
        ${statHtml(config.fields.length, 'Campos configurables')}
        ${statHtml('1', 'Registro maestro')}
        ${statHtml('OK', 'Fuente publica')}
      `;
      return;
    }

    const records = Array.isArray(state.records) ? state.records : [];
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
    return `<div class="gestion-stat"><strong>${escapeHtml(value)}</strong><span>${escapeHtml(label)}</span></div>`;
  }

  function renderList(config, state, dom) {
    if (!dom.list) return;

    if (config.singleton) {
      dom.search.disabled = true;
      dom.status.disabled = true;
      dom.newBtn.textContent = 'Restablecer editor';
      dom.listSummary.textContent = 'Registro unico que alimenta encabezado, home y footer.';
      dom.list.innerHTML = renderSingletonSummary(config, state.records);
      return;
    }

    const filtered = getFilteredRecords(config, state, dom);
    dom.listSummary.textContent = `${filtered.length} de ${state.records.length} registros visibles.`;

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

  function renderSingletonSummary(config, record) {
    const fields = config.fields.slice(0, 8);
    return fields
      .map((field) => {
        const value = formatDisplayValue(field, getValue(record, field.name), {});
        return `
          <div class="gestion-row" style="--gestion-columns: 1;">
            <span class="gestion-cell">${escapeHtml(field.label)}</span>
            <span class="gestion-cell is-muted">${escapeHtml(value || 'Sin dato')}</span>
            <span class="gestion-row-actions"><button type="button" class="gestion-icon-btn" data-action="edit-single" aria-label="Editar">E</button></span>
          </div>
        `;
      })
      .join('');
  }

  function getFilteredRecords(config, state, dom) {
    const term = (dom.search?.value || '').toLowerCase().trim();
    const status = dom.status?.value || 'all';
    const records = Array.isArray(state.records) ? state.records : [];

    return records.filter((record) => {
      const matchesStatus =
        status === 'all' ||
        (status === 'active' && record.active !== false) ||
        (status === 'inactive' && record.active === false);
      const haystack = JSON.stringify(record).toLowerCase();
      return matchesStatus && (term === '' || haystack.includes(term));
    });
  }

  function renderRow(config, record, metadata) {
    const titleField = config.titleField || 'name';
    const title = getValue(record, titleField) || record.id || 'Registro';
    const columns = config.columns || [];

    return `
      <div class="gestion-row" data-id="${escapeHtml(record.id || '')}">
        <span class="gestion-cell">
          ${escapeHtml(title)}
          <br>
          ${statusBadges(record)}
        </span>
        ${columns.map((column) => `<span class="gestion-cell is-muted">${escapeHtml(formatDisplayValue(column, getValue(record, column.name), { metadata }))}</span>`).join('')}
        <span class="gestion-row-actions">
          <button type="button" class="gestion-icon-btn" data-action="edit" aria-label="Editar">E</button>
          <button type="button" class="gestion-icon-btn is-danger" data-action="delete" aria-label="Eliminar">X</button>
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

  function renderForm(config, state, dom, record) {
    const isEditing = Boolean(record && record.id);
    state.editingId = isEditing ? record.id : null;
    dom.editorSummary.textContent = config.singleton ? 'Actualice la informacion publica de la clinica.' : isEditing ? `Editando ${record.id}` : 'Creando nuevo registro.';

    const fieldsHtml = config.fields.map((field) => renderField(field, record || {}, state.metadata)).join('');
    dom.form.innerHTML = `
      ${fieldsHtml}
      <div class="gestion-form-actions">
        <button type="submit" class="btn btn-primary">${config.singleton ? 'Guardar cambios' : isEditing ? 'Actualizar' : 'Crear'}</button>
        <button type="button" class="btn btn-tertiary" id="gestion-cancel-btn">Limpiar</button>
      </div>
    `;

    document.getElementById('gestion-cancel-btn')?.addEventListener('click', () => renderForm(config, state, dom, config.singleton ? state.records : {}));
  }

  function renderField(field, record, metadata) {
    const value = getValue(record, field.name);
    const help = field.help ? `<small>${escapeHtml(field.help)}</small>` : '';

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
        <div class="gestion-field">
          <label for="field-${escapeHtml(field.name)}">${escapeHtml(field.label)}</label>
          <textarea id="field-${escapeHtml(field.name)}" class="gestion-textarea" name="${escapeHtml(field.name)}" ${field.required ? 'required' : ''}>${escapeHtml(formatInputValue(value))}</textarea>
          ${help}
        </div>
      `;
    }

    if (field.type === 'select') {
      return `
        <div class="gestion-field">
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
      <div class="gestion-field">
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
      return;
    }

    const row = button.closest('[data-id]');
    const id = row?.dataset.id;
    const record = Array.isArray(state.records) ? state.records.find((item) => item.id === id) : null;
    if (!record) return;

    if (button.dataset.action === 'edit') {
      renderForm(config, state, dom, record);
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
      renderForm(config, state, dom, config.singleton ? state.records : result.record || {});
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

  function showToast(dom, message, type = 'success') {
    if (!dom.toast) return;
    dom.toast.textContent = message;
    dom.toast.className = `gestion-toast is-visible ${type === 'error' ? 'is-error' : ''}`;
    clearTimeout(showToast.timer);
    showToast.timer = setTimeout(() => dom.toast.classList.remove('is-visible'), 3600);
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

const DOCTOR_SPECIALTIES_API = `${String(window.CLINICA_BASE_PATH || '').replace(/\/$/, '')}/api/doctor-specialties/`;

function doctorCsrfToken() {
  const match = document.cookie.match(new RegExp('(^|;)\\s*XSRF-TOKEN\\s*=\\s*([^;]+)'));
  return match ? decodeURIComponent(match.pop()) : '';
}

function doctorEscape(value) {
  return String(value ?? '').replace(/[&<>"']/g, (match) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[match]));
}

function doctorInstallSpecialtyManager() {
  const list = document.getElementById('gestion-list');
  if (!list || list.dataset.specialtyManagerInstalled === 'true') return;
  list.dataset.specialtyManagerInstalled = 'true';

  const addButtons = () => {
    list.querySelectorAll('.gestion-row[data-id]').forEach((row) => {
      const id = row.dataset.id || '';
      const actions = row.querySelector('.gestion-row-actions');
      if (!id || !actions || actions.querySelector('[data-doctor-specialties]')) return;
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'gestion-icon-btn';
      button.dataset.doctorSpecialties = id;
      button.title = 'Gestionar especialidades';
      button.setAttribute('aria-label', 'Gestionar especialidades');
      button.innerHTML = '<span aria-hidden="true">✚</span><span class="sr-only">Especialidades</span>';
      actions.insertBefore(button, actions.lastElementChild || null);
    });
  };

  const observer = new MutationObserver(addButtons);
  observer.observe(list, { childList: true, subtree: true });
  addButtons();

  list.addEventListener('click', (event) => {
    const target = event.target instanceof Element ? event.target.closest('[data-doctor-specialties]') : null;
    if (!target) return;
    event.preventDefault();
    event.stopPropagation();
    openDoctorSpecialtiesModal(target.dataset.doctorSpecialties || '');
  });
}

async function openDoctorSpecialtiesModal(doctorId) {
  if (!doctorId) return;
  const row = document.querySelector(`.gestion-row[data-id="${CSS.escape(doctorId)}"]`);
  const doctorName = row?.querySelector('.is-title strong')?.textContent?.trim() || 'Doctor';
  const metadata = window.__clinicaGestionDoctorMetadata || {};
  const specialties = Array.isArray(metadata.specialties) ? metadata.specialties : [];

  const modal = document.createElement('div');
  modal.className = 'doctor-specialties-modal';
  modal.innerHTML = `<div class="doctor-specialties-backdrop" data-close-specialties></div><section class="doctor-specialties-dialog" role="dialog" aria-modal="true" aria-labelledby="doctor-specialties-title"><div class="doctor-specialties-header"><div><span class="gestion-kicker">Configuracion del doctor</span><h3 id="doctor-specialties-title">Especialidades de ${doctorEscape(doctorName)}</h3><p>Seleccione una o varias especialidades. La primera seleccionada sera la especialidad principal.</p></div><button type="button" class="gestion-icon-btn" data-close-specialties aria-label="Cerrar">&times;</button></div><div class="doctor-specialties-status" data-specialties-status>Cargando...</div><div class="doctor-specialties-options" data-specialties-options></div><div class="doctor-specialties-actions"><button type="button" class="btn btn-tertiary" data-close-specialties>Cancelar</button><button type="button" class="btn btn-primary" data-save-specialties>Guardar especialidades</button></div></section>`;
  document.body.appendChild(modal);

  const close = () => modal.remove();
  modal.addEventListener('click', (event) => {
    const element = event.target instanceof Element ? event.target : null;
    if (element?.matches('[data-close-specialties]')) close();
  });

  const status = modal.querySelector('[data-specialties-status]');
  const options = modal.querySelector('[data-specialties-options]');
  const saveButton = modal.querySelector('[data-save-specialties]');

  try {
    const response = await fetch(`${DOCTOR_SPECIALTIES_API}?doctor_id=${encodeURIComponent(doctorId)}`, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
    const result = await response.json().catch(() => ({}));
    if (!response.ok || !result.success) throw new Error(result.error || 'No se pudieron cargar las especialidades.');
    const selected = new Set((result.specialtyIds || []).map((id) => String(id)));
    options.innerHTML = specialties.length ? specialties.map((specialty) => `<label class="doctor-specialty-option"><input type="checkbox" value="${doctorEscape(specialty.id)}" ${selected.has(String(specialty.id)) ? 'checked' : ''}><span>${doctorEscape(specialty.name)}</span></label>`).join('') : '<p>No hay especialidades disponibles.</p>';
    status.textContent = `${selected.size} seleccionada${selected.size === 1 ? '' : 's'}.`;
    options.querySelectorAll('input').forEach((input) => input.addEventListener('change', () => { const count = options.querySelectorAll('input:checked').length; status.textContent = `${count} seleccionada${count === 1 ? '' : 's'}.`; }));
  } catch (error) {
    status.textContent = error.message || 'No se pudieron cargar las especialidades.';
    if (saveButton) saveButton.disabled = true;
    return;
  }

  saveButton?.addEventListener('click', async () => {
    const specialtyIds = Array.from(options.querySelectorAll('input:checked')).map((input) => input.value);
    if (!specialtyIds.length) { status.textContent = 'Seleccione al menos una especialidad.'; return; }
    saveButton.disabled = true;
    status.textContent = 'Guardando...';
    try {
      const response = await fetch(`${DOCTOR_SPECIALTIES_API}?doctor_id=${encodeURIComponent(doctorId)}`, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-Token': doctorCsrfToken() }, body: JSON.stringify({ specialtyIds }) });
      const result = await response.json().catch(() => ({}));
      if (!response.ok || !result.success) throw new Error(result.error || 'No se pudieron guardar las especialidades.');
      status.textContent = 'Especialidades guardadas correctamente.';
      window.setTimeout(close, 450);
      const reload = document.getElementById('gestion-reload-btn');
      reload?.click();
    } catch (error) {
      status.textContent = error.message || 'No se pudieron guardar las especialidades.';
      saveButton.disabled = false;
    }
  });
}

window.ClinicaGestionDoctor = { installSpecialtyManager: doctorInstallSpecialtyManager };

window.ClinicaGestion.boot({
  resource: 'doctors',
  titleField: 'name',
  columns: [
    { name: 'specialty_id', label: 'Especialidad principal', type: 'select', optionSource: 'specialties' },
    { name: 'rating', label: 'Estrellas' },
    { name: 'experience_years', label: 'Experiencia' },
  ],
  fields: [
    { name: 'name', label: 'Nombre', type: 'text', required: true, autocomplete: 'name' },
    { name: 'photo', label: 'Foto', type: 'image', help: 'Suba JPG, PNG, WEBP o GIF. Si no sube una foto, puede conservar el avatar placeholder.' },
    { name: 'specialty_id', label: 'Especialidad principal', type: 'select', optionSource: 'specialties', required: true },
    { name: 'sub_specialty', label: 'Subespecialidad', type: 'text' },
    { name: 'experience_years', label: 'Anos de experiencia', type: 'number', min: 0, max: 70, step: 1, inputmode: 'numeric' },
    { name: 'university', label: 'Formacion', type: 'text' },
    { name: 'languages', label: 'Idiomas', type: 'text', help: 'Separe por comas. Ej: Espanol, Ingles' },
    { name: 'recognitions', label: 'Reconocimientos', type: 'text', help: 'Separe por comas.' },
    { name: 'rating', label: 'Estrellas', type: 'number', min: 1, max: 5, step: 1, required: true, inputmode: 'numeric' },
    { name: 'patients', label: 'Pacientes atendidos', type: 'number', min: 0, step: 1, inputmode: 'numeric' },
    { name: 'featured', label: 'Destacado en home', type: 'checkbox' },
    { name: 'active', label: 'Activo', type: 'checkbox' },
  ],
});

document.addEventListener('DOMContentLoaded', () => {
  const metadataObserver = new MutationObserver(() => {
    const list = document.getElementById('gestion-list');
    const stats = document.getElementById('gestion-stats');
    if (!list) return;
    // The shared CRUD engine exposes metadata through its rendered select options.
    const specialtyOptions = document.querySelector('#field-specialty_id')?.querySelectorAll('option') || [];
    window.__clinicaGestionDoctorMetadata = { specialties: Array.from(specialtyOptions).filter((option) => option.value).map((option) => ({ id: option.value, name: option.textContent || option.value })) };
    if (stats) window.ClinicaGestionDoctor.installSpecialtyManager();
  });
  metadataObserver.observe(document.body, { childList: true, subtree: true });
  window.ClinicaGestionDoctor.installSpecialtyManager();
});

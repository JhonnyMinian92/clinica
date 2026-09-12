/*
 * dashboard.js
 * Bandeja de citas para doctores, secretaria y administracion.
 */

(function () {
  'use strict';

  function appUrl(path) {
    const basePath = String(window.CLINICA_BASE_PATH || '').replace(/\/$/, '');
    return `${basePath}/${String(path || '').replace(/^\/+/, '')}`;
  }

  document.addEventListener('DOMContentLoaded', () => {
    const DASHBOARD_API = appUrl('api/dashboard/');
    const GENERAL_API = appUrl('api/general/');
    const STAFF_ROLES = ['ADMINISTRATOR', 'SECRETARIA'];
    const dashboardShell = document.querySelector('.dashboard-container');
    const DEFAULT_DATE_SCOPE = dashboardShell?.dataset.defaultDateScope || 'today';

    let allAppointments = [];
    let pagination = { page: 1, perPage: 6, total: 0, totalPages: 1, from: 0, to: 0 };
    let user = null;
    let userRole = '';
    let eventsReady = false;
    let confirmCallback = null;
    let lastFocusedElement = null;
    let toastTimer = null;

    const dom = {
      appointmentsList: document.getElementById('appointments-list'),
      searchInput: document.getElementById('patient-search-input'),
      kpiGrid: document.getElementById('kpi-grid'),
      filterControls: document.getElementById('filter-controls'),
      filterCounts: document.querySelectorAll('[data-filter-count]'),
      dateControls: document.getElementById('date-filter-controls'),
      dateInput: document.getElementById('date-filter-input'),
      doctorSelect: document.getElementById('doctor-filter-select'),
      pagination: document.getElementById('appointments-pagination'),
      paginationSummary: document.getElementById('pagination-summary'),
      paginationPrev: document.getElementById('pagination-prev'),
      paginationNext: document.getElementById('pagination-next'),
      paginationPages: document.getElementById('pagination-pages'),
      pageSizeSelect: document.getElementById('page-size-select'),
      logoutBtn: document.getElementById('logout-btn'),
      toast: document.getElementById('dashboard-toast'),
      modal: document.getElementById('confirmation-modal'),
      modalTitle: document.getElementById('modal-title'),
      modalMessage: document.getElementById('modal-message'),
      modalConfirmBtn: document.getElementById('modal-confirm-btn'),
      modalCancelBtn: document.getElementById('modal-cancel-btn'),
      modalCloseBtn: document.querySelector('#confirmation-modal .modal-close-btn'),
      historyModal: document.getElementById('history-modal'),
      historyModalSubtitle: document.getElementById('history-modal-subtitle'),
      historyModalBody: document.getElementById('history-modal-body'),
      historyModalCloseBtn: document.getElementById('history-modal-close-btn'),
      kpiValues: {
        total: document.querySelector('#kpi-total .kpi-value'), pending: document.querySelector('#kpi-pending .kpi-value'),
        attended: document.querySelector('#kpi-attended .kpi-value'), absent: document.querySelector('#kpi-absent .kpi-value'),
        rejected: document.querySelector('#kpi-rejected .kpi-value'), cancelled: document.querySelector('#kpi-cancelled .kpi-value'),
      },
      insights: {
        nextTitle: document.querySelector('[data-insight="next-title"]'), nextDetail: document.querySelector('[data-insight="next-detail"]'),
        queueTitle: document.querySelector('[data-insight="queue-title"]'), queueDetail: document.querySelector('[data-insight="queue-detail"]'),
        resolutionTitle: document.querySelector('[data-insight="resolution-title"]'), resolutionDetail: document.querySelector('[data-insight="resolution-detail"]'),
      },
    };

    const statusLabels = { pendiente: 'Pendiente', atendida: 'Atendida', 'no-presentado': 'No se presento', rechazada: 'Rechazada', cancelada: 'Cancelada' };
    const finalStateCopy = {
      atendida: { title: 'Atendida', description: 'Paciente atendido y cierre registrado.' },
      'no-presentado': { title: 'No se presento', description: 'Ausencia registrada en la agenda.' },
      rechazada: { title: 'Rechazada', description: 'Cita cerrada sin atencion.' },
      cancelada: { title: 'Cancelada', description: 'Cita cancelada.' },
    };
    const notificationChannelLabels = { email: 'Email', whatsapp: 'WhatsApp' };
    const notificationStateLabels = { sent: 'Enviado', failed: 'Fallo', skipped: 'Simulado', pendiente: 'Pendiente' };
    const filterEmptyCopy = {
      all: { title: 'No hay citas para mostrar.', description: 'Cambie la fecha, el doctor o borre la busqueda del paciente.' },
      pendiente: { title: 'No hay citas pendientes.', description: 'La agenda seleccionada no tiene pacientes en espera.' },
      atendida: { title: 'No hay citas atendidas.', description: 'Cuando se marque una cita como atendida aparecera aqui.' },
      'no-presentado': { title: 'No hay no presentados.', description: 'No se registran ausencias para este rango.' },
      rechazada: { title: 'No hay citas rechazadas.', description: 'No se registran rechazos para este rango.' },
      cancelada: { title: 'No hay citas canceladas.', description: 'No se registran cancelaciones para este rango.' },
    };
    const AUTO_REFRESH_INTERVAL_MS = 60000;

    function init() {
      activateDateScope(DEFAULT_DATE_SCOPE); setStatusFilter(defaultStatusForScope(DEFAULT_DATE_SCOPE), false); attachEventListeners(); fetchDashboardData();
      setInterval(() => { const modalOpen = dom.modal?.getAttribute('aria-hidden') === 'false'; if (!modalOpen) fetchDashboardData(); }, AUTO_REFRESH_INTERVAL_MS);
    }
    function attachEventListeners() {
      if (eventsReady) return;
      dom.searchInput?.addEventListener('input', debounce(handleSearchInput, 350));
      dom.filterControls?.addEventListener('click', handleFilterClick); dom.kpiGrid?.addEventListener('click', handleKpiClick);
      dom.dateControls?.addEventListener('click', handleDateScopeClick); dom.dateInput?.addEventListener('change', handleExactDateChange);
      dom.appointmentsList?.addEventListener('click', handleActionClick);
      dom.doctorSelect?.addEventListener('change', () => { pagination.page = 1; fetchDashboardData(); });
      dom.paginationPrev?.addEventListener('click', () => goToPage(pagination.page - 1)); dom.paginationNext?.addEventListener('click', () => goToPage(pagination.page + 1));
      dom.pageSizeSelect?.addEventListener('change', () => { pagination.perPage = Number(dom.pageSizeSelect.value) || 6; pagination.page = 1; fetchDashboardData(); });
      dom.logoutBtn?.addEventListener('click', handleLogout);
      if (dom.modal) {
        dom.modalCancelBtn?.addEventListener('click', closeModal); dom.modalCloseBtn?.addEventListener('click', closeModal);
        dom.modalConfirmBtn?.addEventListener('click', () => { if (typeof confirmCallback === 'function') confirmCallback(); closeModal(); });
        dom.modal.addEventListener('click', (event) => { if (event.target === dom.modal) closeModal(); }); document.addEventListener('keydown', handleModalKeydown);
      }
      if (dom.historyModal) { dom.historyModalCloseBtn?.addEventListener('click', closeHistoryModal); dom.historyModal.addEventListener('click', (event) => { if (event.target === dom.historyModal) closeHistoryModal(); }); }
      eventsReady = true;
    }
    function renderKPIs(counts) { counts = normalizeCounts(counts); if (dom.kpiValues.total) dom.kpiValues.total.textContent = counts.all; if (dom.kpiValues.pending) dom.kpiValues.pending.textContent = counts.pendiente; if (dom.kpiValues.attended) dom.kpiValues.attended.textContent = counts.atendida; if (dom.kpiValues.absent) dom.kpiValues.absent.textContent = counts['no-presentado']; if (dom.kpiValues.rejected) dom.kpiValues.rejected.textContent = counts.rechazada; if (dom.kpiValues.cancelled) dom.kpiValues.cancelled.textContent = counts.cancelada; }
    function renderFilterCounts(counts) { counts = normalizeCounts(counts); dom.filterCounts.forEach((countElement) => { const key = countElement.dataset.filterCount || 'all'; countElement.textContent = counts[key] ?? 0; }); }
    function renderAgendaInsights(summary) { const counts = normalizeCounts(summary?.counts); const nextPending = summary?.next_pending || null; const closedCount = counts.atendida + counts['no-presentado'] + counts.rechazada + counts.cancelada; const resolution = counts.all > 0 ? Math.round((closedCount / counts.all) * 100) : 0; if (nextPending) { const specialty = nextPending.especialidad ? ` - ${nextPending.especialidad}` : ''; const overdue = appointmentHasStarted(nextPending) ? ' - pendiente vencida' : ''; dom.insights.nextTitle.textContent = `${formatTime(nextPending.hora)} - ${nextPending.nombre_paciente || 'Paciente'}`; dom.insights.nextDetail.textContent = `${formatDate(nextPending.fecha)}${specialty}${overdue}`; } else { dom.insights.nextTitle.textContent = 'Sin pendientes'; dom.insights.nextDetail.textContent = counts.all > 0 ? 'Todas las citas estan cerradas' : 'No hay citas en este rango'; } dom.insights.queueTitle.textContent = `${counts.pendiente} ${counts.pendiente === 1 ? 'pendiente' : 'pendientes'}`; dom.insights.queueDetail.textContent = counts.pendiente > 0 ? 'Listas para atencion' : 'Bandeja despejada'; dom.insights.resolutionTitle.textContent = `${resolution}%`; dom.insights.resolutionDetail.textContent = `${closedCount} de ${counts.all} citas cerradas`; }
    function renderDoctorSelect(doctors) { if (!dom.doctorSelect || !Array.isArray(doctors)) return; const selectedValue = dom.doctorSelect.value; const options = doctors.map((doctor) => `<option value="${escapeHtml(doctor.id)}">${escapeHtml(doctor.nombre)}</option>`).join(''); dom.doctorSelect.innerHTML = `<option value="">Todos los doctores</option>${options}`; dom.doctorSelect.value = selectedValue; }
    function renderAppointmentsList(appointments) { if (!dom.appointmentsList) return; if (appointments.length === 0) { const activeFilter = getActiveStatusFilter(); const emptyCopy = filterEmptyCopy[activeFilter] || filterEmptyCopy.all; dom.appointmentsList.innerHTML = `<div class="appointments-empty"><span class="empty-kicker">Sin citas</span><h3>${escapeHtml(emptyCopy.title)}</h3><p>${escapeHtml(emptyCopy.description)}</p></div>`; return; } const nextPendingId = appointments.find((appointment) => appointment.estado === 'pendiente')?.id || ''; dom.appointmentsList.innerHTML = appointments.map((appointment) => renderAppointmentCard(appointment, String(appointment.id) === String(nextPendingId))).join(''); }
    function renderAppointmentCard(appointment, isNext) { const adminView = STAFF_ROLES.includes(userRole); const status = appointment.estado || 'pendiente'; const patient = appointment.nombre_paciente || 'Paciente sin nombre'; const doctorName = appointment.doctor_info?.nombre || ''; const phone = appointment.telefono_paciente || ''; const email = appointment.email_paciente || ''; const patientId = appointment.patient_id || ''; const details = [adminView && doctorName ? renderDetail('Doctor', doctorName) : '', appointment.especialidad ? renderDetail('Especialidad', appointment.especialidad) : '', phone ? renderContactDetail('Telefono', phone, `tel:${phone}`) : renderDetail('Telefono', 'No registrado'), email ? renderContactDetail('Correo', email, `mailto:${email}`) : renderDetail('Correo', 'No registrado')].join(''); return `<article class="appointment-card status-card-${escapeHtml(status)} ${isNext ? 'is-next' : ''}" data-id="${escapeHtml(appointment.id)}" data-status="${escapeHtml(status)}" data-patient-id="${escapeHtml(patientId)}"><div class="appointment-card-top"><div class="appointment-time-block"><span class="appointment-date">${escapeHtml(formatDate(appointment.fecha))}</span><strong class="appointment-time">${escapeHtml(formatTime(appointment.hora))}</strong></div><div class="appointment-state">${isNext ? '<span class="next-pill">Siguiente cita</span>' : ''}<span class="status-badge status-${escapeHtml(status)}">${escapeHtml(getStatusLabel(status))}</span></div></div><div class="appointment-body"><p class="appointment-label">Paciente</p><h3 class="appointment-patient">${escapeHtml(patient)}</h3><p class="appointment-reason">${escapeHtml(appointment.motivo_consulta || 'Sin motivo registrado')}</p>${patientId ? `<button type="button" class="appointment-history-btn" data-action="history" data-patient-id="${escapeHtml(patientId)}" data-patient-name="${escapeHtml(patient)}">Ver historial del paciente</button>` : ''}</div>${details ? `<div class="appointment-details">${details}</div>` : ''}<div class="appointment-notifications" aria-label="Estado de comunicacion con el paciente">${renderNotificationBadges(appointment.notifications, appointment.id)}</div><div class="appointment-actions" aria-label="Acciones para ${escapeHtml(patient)}">${getActionsHtml(appointment)}</div></article>`; }
    function renderNotificationBadges(notifications, appointmentId) { if (!notifications) return ''; return ['email', 'whatsapp'].map((channel) => { const pills = ['confirmation', 'reminder'].map((type) => renderNotificationPill(channel, type, notifications[channel]?.[type], appointmentId)).join(''); return `<span class="notification-channel-group"><span class="notification-channel-label">${escapeHtml(notificationChannelLabels[channel])}</span>${pills}</span>`; }).join(''); }
    function renderNotificationPill(channel, type, status, appointmentId) { const state = status || 'pendiente'; const typeLabel = type === 'reminder' ? 'Recordatorio' : 'Confirmacion'; const stateLabel = notificationStateLabels[state] || state; const canResend = state !== 'pendiente' || type === 'confirmation'; return `<span class="notif-pill notif-${escapeHtml(state)}" title="${escapeHtml(typeLabel)}: ${escapeHtml(stateLabel)}"><span class="notif-pill-label">${escapeHtml(typeLabel)}: ${escapeHtml(stateLabel)}</span>${canResend ? `<button type="button" class="notif-resend-btn" data-appointment-id="${escapeHtml(appointmentId)}" data-channel="${channel}" data-type="${type}" aria-label="Reenviar ${escapeHtml(typeLabel.toLowerCase())} por ${escapeHtml(notificationChannelLabels[channel])}">Reenviar</button>` : ''}</span>`; }
    function renderDetail(label, value) { return `<div class="appointment-detail"><span>${escapeHtml(label)}</span><strong>${escapeHtml(value)}</strong></div>`; }
    function renderContactDetail(label, value, href) { return `<div class="appointment-detail"><span>${escapeHtml(label)}</span><strong><a class="appointment-contact-link" href="${escapeHtml(href)}">${escapeHtml(value)}</a></strong></div>`; }
    function getActionsHtml(appointment) { if (appointment.estado !== 'pendiente') return renderFinalState(appointment.estado); const canCloseClinicalStatus = appointmentHasStarted(appointment); const disabledAttrs = canCloseClinicalStatus ? '' : 'disabled aria-disabled="true" title="Disponible desde la hora de la cita"'; return `<button type="button" class="appointment-action appointment-action-attend" data-action="attend" ${disabledAttrs}><span class="action-title">Atender cita</span><span class="action-help">${canCloseClinicalStatus ? 'Paciente atendido' : 'Disponible al iniciar'}</span></button><button type="button" class="appointment-action appointment-action-no-show" data-action="no-show" ${disabledAttrs}><span class="action-title">No se presento</span><span class="action-help">${canCloseClinicalStatus ? 'Paciente ausente' : 'Disponible al iniciar'}</span></button><button type="button" class="appointment-action appointment-action-reject" data-action="reject"><span class="action-title">Rechazar cita</span><span class="action-help">Cancelar o cerrar</span></button>`; }
    function renderFinalState(status) { const copy = finalStateCopy[status] || { title: getStatusLabel(status), description: 'Estado cerrado en la agenda.' }; return `<div class="appointment-final-state final-state-${escapeHtml(status)}"><span class="final-state-icon" aria-hidden="true"></span><span class="final-state-copy"><span>Estado registrado</span><strong>${escapeHtml(copy.title)}</strong><em>${escapeHtml(copy.description)}</em></span></div>`; }
    function handleFilterClick(event) { const filterButton = event.target.closest('.btn-filter'); if (!filterButton) return; setStatusFilter(filterButton.dataset.filter || 'all'); }
    function handleKpiClick(event) { const kpiButton = event.target.closest('[data-kpi-filter]'); if (!kpiButton) return; setStatusFilter(kpiButton.dataset.kpiFilter || 'all'); }
    function handleSearchInput() { pagination.page = 1; fetchDashboardData(); }
    function setStatusFilter(statusFilter, shouldApply = true) { const buttons = Array.from(dom.filterControls?.querySelectorAll('.btn-filter') || []); const target = buttons.find((button) => button.dataset.filter === statusFilter) || buttons[0]; const activeStatus = target?.dataset.filter || 'all'; buttons.forEach((button) => { const isActive = button === target; button.classList.toggle('active', isActive); button.setAttribute('aria-pressed', String(isActive)); }); dom.kpiGrid?.querySelectorAll('[data-kpi-filter]').forEach((button) => { const isActive = button.dataset.kpiFilter === activeStatus; button.classList.toggle('is-active', isActive); button.setAttribute('aria-pressed', String(isActive)); }); if (shouldApply) { pagination.page = 1; fetchDashboardData(); } }
    function defaultStatusForScope(scope) { if (scope === 'all') return 'all'; if (scope === 'past') return 'atendida'; return 'pendiente'; }
    function handleDateScopeClick(event) { const dateButton = event.target.closest('.date-filter-btn'); if (!dateButton) return; const scope = dateButton.dataset.dateScope || DEFAULT_DATE_SCOPE; activateDateScope(scope); setStatusFilter(defaultStatusForScope(scope), false); if (dom.dateInput) dom.dateInput.value = ''; pagination.page = 1; fetchDashboardData(); }
    function handleExactDateChange() { if (dom.dateInput?.value) { dom.dateControls?.querySelectorAll('.date-filter-btn').forEach((button) => { button.classList.remove('active'); button.setAttribute('aria-pressed', 'false'); }); } else { activateDateScope(DEFAULT_DATE_SCOPE); setStatusFilter(defaultStatusForScope(DEFAULT_DATE_SCOPE), false); } pagination.page = 1; fetchDashboardData(); }
    function activateDateScope(scope) { const buttons = Array.from(dom.dateControls?.querySelectorAll('.date-filter-btn') || []); if (buttons.length === 0) return; const target = buttons.find((button) => button.dataset.dateScope === scope) || buttons[0]; buttons.forEach((button) => { const isActive = button === target; button.classList.toggle('active', isActive); button.setAttribute('aria-pressed', String(isActive)); }); }
    function handleActionClick(event) { const resendBtn = event.target.closest('.notif-resend-btn'); if (resendBtn) { handleResendClick(resendBtn); return; } const actionButton = event.target.closest('[data-action]'); if (!actionButton || actionButton.disabled) return; if (actionButton.dataset.action === 'history') { openHistoryModal(actionButton.dataset.patientId || '', actionButton.dataset.patientName || 'Paciente'); return; } const card = actionButton.closest('.appointment-card'); const appointmentId = card?.dataset.id; const appointment = allAppointments.find((item) => String(item.id) === String(appointmentId)); const patientName = appointment?.nombre_paciente || 'este paciente'; const action = actionButton.dataset.action; const actions = { attend: { status: 'atendida', title: 'Atender cita', message: `Confirme que la cita de <strong>${escapeHtml(patientName)}</strong> ya inicio y el paciente fue atendido.`, confirmText: 'Si, atender', confirmClass: 'btn-primary', successText: `Cita de ${patientName} marcada como atendida.` }, 'no-show': { status: 'no-presentado', title: 'Paciente ausente', message: `Confirme que la cita de <strong>${escapeHtml(patientName)}</strong> ya inicio y el paciente no se presento.`, confirmText: 'Si, no se presento', confirmClass: 'btn-danger', successText: `Cita de ${patientName} marcada como no presentada.` }, reject: { status: 'rechazada', title: 'Rechazar cita', message: `Confirme que la cita de <strong>${escapeHtml(patientName)}</strong> debe quedar rechazada o cancelada.`, confirmText: 'Si, rechazar', confirmClass: 'btn-danger', successText: `Cita de ${patientName} rechazada.` } }; const config = actions[action]; if (!config || !appointmentId) return; openModal({ title: config.title, message: config.message, confirmText: config.confirmText, confirmClass: config.confirmClass, onConfirm: () => updateAppointmentStatus(card, appointmentId, config.status, config.successText) }); }
    function getCsrfTokenFromCookie() { const match = document.cookie.match(new RegExp('(^|;)\\s*XSRF-TOKEN\\s*=\\s*([^;]+)')); return match ? decodeURIComponent(match.pop()) : ''; }
    async function handleResendClick(button) { const { appointmentId, channel, type } = button.dataset; button.disabled = true; try { const response = await fetch(DASHBOARD_API, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-Token': getCsrfTokenFromCookie() }, body: JSON.stringify({ action: 'resend_notification', appointmentId, channel, type }) }); const result = await response.json().catch(() => ({})); if (!response.ok || !result.success) throw new Error(result.error || 'No se pudo reenviar la notificacion.'); showToast(result.message || 'Notificacion reenviada.', 'success'); await fetchDashboardData(); } catch (error) { showToast(error.message || 'No se pudo reenviar la notificacion.', 'error'); button.disabled = false; } }
    async function updateAppointmentStatus(card, appointmentId, newStatus, successText) { const actionsArea = card?.querySelector('.appointment-actions'); const originalActionsHTML = actionsArea?.innerHTML || ''; card?.classList.add('is-updating'); if (actionsArea) actionsArea.innerHTML = '<div class="action-updating"><div class="spinner"></div><span>Actualizando cita...</span></div>'; try { const response = await fetch(DASHBOARD_API, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-Token': getCsrfTokenFromCookie() }, body: JSON.stringify({ appointmentId, newStatus, reason: getStatusReason(newStatus) }) }); const result = await response.json().catch(() => ({})); if (!response.ok || !result.success) throw new Error(result.error || 'No se pudo actualizar la cita.'); showToast(successText || 'Cita actualizada correctamente.', 'success'); await fetchDashboardData(); } catch (error) { if (actionsArea) actionsArea.innerHTML = originalActionsHTML; showToast(error.message || 'No se pudo actualizar la cita. Intente nuevamente.', 'error'); } finally { card?.classList.remove('is-updating'); } }
    async function fetchDashboardData() { setLoadingState(); try { const query = buildDashboardQuery(); const response = await fetch(`${DASHBOARD_API}${query}`, { credentials: 'same-origin', headers: { Accept: 'application/json' } }); if (response.status === 401) { window.location.href = appUrl(`pages/login/?redirect=${encodeURIComponent(appUrl('pages/dashboard/'))}`); return; } const data = await response.json().catch(() => ({})); if (!response.ok || !data.success) throw new Error(data.error || 'Error al cargar el dashboard.'); user = data.user; userRole = data.user?.role || ''; allAppointments = Array.isArray(data.appointments) ? data.appointments : []; pagination = normalizePagination(data.pagination); renderDoctorSelect(data.doctors || []); setStatusFilter(data.filters?.status || getActiveStatusFilter(), false); renderKPIs(data.summary?.counts); renderFilterCounts(data.summary?.counts); renderAgendaInsights(data.summary || {}); renderAppointmentsList(allAppointments); renderPagination(pagination); } catch (error) { showListError(error.message || 'Error al cargar los datos. Intente recargar.'); } }
    function buildDashboardQuery() { const params = new URLSearchParams(); const doctorId = dom.doctorSelect?.value || ''; const exactDate = dom.dateInput?.value || ''; const activeDateButton = dom.dateControls?.querySelector('.date-filter-btn.active'); const dateScope = activeDateButton?.dataset.dateScope || DEFAULT_DATE_SCOPE; const status = getActiveStatusFilter(); const search = dom.searchInput?.value.trim() || ''; if (doctorId) params.set('doctor_id', doctorId); if (status && status !== 'all') params.set('status', status); if (search) params.set('search', search); params.set('page', String(pagination.page)); params.set('per_page', String(pagination.perPage)); if (exactDate) params.set('date', exactDate); else params.set('date_scope', dateScope); const query = params.toString(); return query ? `?${query}` : ''; }
    async function handleLogout() { dom.logoutBtn.disabled = true; try { const response = await fetch(GENERAL_API, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-Token': getCsrfTokenFromCookie() }, body: JSON.stringify({ action: 'logout' }) }); const result = await response.json().catch(() => ({})); window.location.href = result.redirect || appUrl('pages/login/'); } catch (error) { window.location.href = appUrl('pages/login/'); } }
    function setLoadingState() { if (!dom.appointmentsList) return; if (dom.pagination) dom.pagination.hidden = true; dom.appointmentsList.innerHTML = '<div class="appointments-loading"><div class="spinner"></div><span>Cargando citas...</span></div>'; }
    function showListError(message) { if (dom.appointmentsList) dom.appointmentsList.innerHTML = `<div class="appointments-empty"><span class="empty-kicker">Error</span><h3>No se pudo cargar la agenda.</h3><p>${escapeHtml(message)}</p></div>`; }
    function normalizeCounts(counts) { return { all: Number(counts?.all || 0), pendiente: Number(counts?.pendiente || 0), atendida: Number(counts?.atendida || 0), 'no-presentado': Number(counts?.['no-presentado'] || 0), rechazada: Number(counts?.rechazada || 0), cancelada: Number(counts?.cancelada || 0) }; }
    function normalizePagination(value) { return { page: Number(value?.page || 1), perPage: Number(value?.per_page || 6), total: Number(value?.total || 0), totalPages: Number(value?.total_pages || 1), from: Number(value?.from || 0), to: Number(value?.to || 0) }; }
    function getActiveStatusFilter() { return dom.filterControls?.querySelector('.btn-filter.active')?.dataset.filter || 'all'; }
    function getStatusLabel(status) { return statusLabels[status] || status; }
    function formatDate(value) { if (!value) return ''; const date = new Date(`${value}T00:00:00`); return Number.isNaN(date.getTime()) ? value : date.toLocaleDateString('es-EC', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' }); }
    function formatTime(value) { return String(value || '').slice(0, 5); }
    function appointmentHasStarted(appointment) { if (!appointment?.fecha || !appointment?.hora) return false; const date = new Date(`${appointment.fecha}T${String(appointment.hora).slice(0, 5)}:00`); return !Number.isNaN(date.getTime()) && date <= new Date(); }
    function escapeHtml(value) { return String(value ?? '').replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char])); }
    function getStatusReason(status) { return { atendida: 'Paciente atendido desde dashboard.', 'no-presentado': 'Paciente no se presento desde dashboard.', rechazada: 'Cita rechazada desde dashboard.', cancelada: 'Cita cancelada desde dashboard.' }[status] || 'Cambio desde dashboard'; }
    function debounce(fn, delay) { let timer = null; return (...args) => { clearTimeout(timer); timer = setTimeout(() => fn(...args), delay); }; }
    function goToPage(page) { if (page < 1 || page > pagination.totalPages) return; pagination.page = page; fetchDashboardData(); }
    function showToast(message, type) { if (!dom.toast) return; clearTimeout(toastTimer); dom.toast.textContent = message; dom.toast.dataset.type = type || 'info'; dom.toast.hidden = false; toastTimer = setTimeout(() => { dom.toast.hidden = true; }, 4000); }
    function openModal({ title, message, confirmText, confirmClass, onConfirm }) { if (!dom.modal) { onConfirm?.(); return; } lastFocusedElement = document.activeElement; dom.modalTitle.textContent = title; dom.modalMessage.innerHTML = message; dom.modalConfirmBtn.textContent = confirmText; dom.modalConfirmBtn.className = `btn ${confirmClass || 'btn-primary'}`; confirmCallback = onConfirm; dom.modal.setAttribute('aria-hidden', 'false'); dom.modal.hidden = false; dom.modalConfirmBtn.focus(); }
    function closeModal() { if (!dom.modal) return; dom.modal.setAttribute('aria-hidden', 'true'); dom.modal.hidden = true; confirmCallback = null; lastFocusedElement?.focus?.(); lastFocusedElement = null; }
    function handleModalKeydown(event) { if (dom.modal?.getAttribute('aria-hidden') === 'false' && event.key === 'Escape') closeModal(); }
    async function openHistoryModal(patientId, patientName) { if (!dom.historyModal || !patientId) return; dom.historyModalSubtitle.textContent = patientName; dom.historyModalBody.innerHTML = '<div class="appointments-loading"><div class="spinner"></div><span>Cargando historial...</span></div>'; dom.historyModal.setAttribute('aria-hidden', 'false'); dom.historyModal.hidden = false; try { const response = await fetch(`${DASHBOARD_API}?action=patient_history&patient_id=${encodeURIComponent(patientId)}`, { credentials: 'same-origin', headers: { Accept: 'application/json' } }); const data = await response.json().catch(() => ({})); if (!response.ok || !data.success) throw new Error(data.error || 'No se pudo cargar el historial.'); dom.historyModalBody.innerHTML = Array.isArray(data.appointments) && data.appointments.length ? data.appointments.map((item) => `<div class="history-row"><span>${escapeHtml(formatDate(item.fecha))} ${escapeHtml(formatTime(item.hora))}</span><strong>${escapeHtml(item.especialidad || 'Sin especialidad')}</strong><span>${escapeHtml(item.doctor_nombre || 'Sin doctor')}</span><span>${escapeHtml(getStatusLabel(item.estado))}</span><p>${escapeHtml(item.motivo_consulta || '')}</p></div>`).join('') : '<p>No hay historial disponible para este paciente.</p>'; } catch (error) { dom.historyModalBody.innerHTML = `<p>${escapeHtml(error.message || 'No se pudo cargar el historial.')}</p>`; } }
    function closeHistoryModal() { if (!dom.historyModal) return; dom.historyModal.setAttribute('aria-hidden', 'true'); dom.historyModal.hidden = true; }

    init();
  });
})();

/*
 * dashboard.js
 * Lógica de frontend para el panel de control de doctores y administradores.
 * Clínica Estándar
 */

document.addEventListener('DOMContentLoaded', () => {
  console.log('Dashboard script loaded.');

  let allAppointments = []; // Caché de citas para filtros y KPIs
  let user; // Objeto de usuario para lógica condicional
  let userRole = ''; // Rol del usuario para lógica condicional

  const modal = document.getElementById('confirmation-modal');
  const modalTitle = document.getElementById('modal-title');
  const modalMessage = document.getElementById('modal-message');
  const modalConfirmBtn = document.getElementById('modal-confirm-btn');
  const modalCancelBtn = document.getElementById('modal-cancel-btn');
  const modalCloseBtn = modal?.querySelector('.modal-close-btn');
  let confirmCallback = null;

  const appointmentsTbody = document.getElementById('appointments-tbody');
  const kpiValues = {
    total: document.querySelector('#kpi-total .kpi-value'),
    pending: document.querySelector('#kpi-pending .kpi-value'),
    attended: document.querySelector('#kpi-attended .kpi-value'),
    absent: document.querySelector('#kpi-absent .kpi-value'),
  };

  function renderKPIs(appointments) {
    const counts = {
      // Recalcula los contadores desde el array de citas
      total: appointments.length,
      pending: appointments.filter((c) => c.estado === 'pendiente').length,
      attended: appointments.filter((c) => c.estado === 'atendida').length,
      absent: appointments.filter((c) => c.estado === 'no-presentado').length,
    };

    kpiValues.total.textContent = counts.total;
    kpiValues.pending.textContent = counts.pending;
    kpiValues.attended.textContent = counts.attended;
    kpiValues.absent.textContent = counts.absent;
  }

  function renderAppointmentsTable(appointments, userRole) {
    if (!appointmentsTbody) return;

    appointmentsTbody.innerHTML = ''; // Limpiar el contenido actual

    if (appointments.length === 0) {
      const colspan = document.querySelectorAll('.appointments-table th').length || 5;
      appointmentsTbody.innerHTML = `
                <tr>
                    <td colspan="${colspan}" class="no-results-cell">No tiene citas programadas para hoy.</td>
                </tr>
            `;
      return;
    }

    const rows = appointments
      .map((cita) => {
        const isAdmin = userRole === 'ADMINISTRATOR';
        const doctorColumn = isAdmin ? `<td>${escapeHtml(cita.doctor_info.nombre)}</td>` : '';

        const actionsHtml = {
          pendiente: `
                    <button class="btn btn-sm btn-icon btn-action-positive" title="Marcar como Atendida" data-action="attend">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </button>
                    <button class="btn btn-sm btn-icon btn-action-negative" title="Marcar como No Presentado" data-action="no-show">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                `,
          atendida: `<span class="action-text">Completada</span>`,
          'no-presentado': `<span class="action-text">Ausente</span>`,
          rechazada: `<span class="action-text">Rechazada</span>`,
        };

        return `
                <tr data-id="${escapeHtml(cita.id)}" data-status="${escapeHtml(cita.estado)}" data-patient-name="${escapeHtml(cita.nombre_paciente.toLowerCase())}">
                    <td>${escapeHtml(cita.hora)}</td>
                    <td>${escapeHtml(cita.nombre_paciente)}</td>
                    ${doctorColumn}
                    <td>${escapeHtml(cita.motivo_consulta)}</td>
                    <td><span class="status-badge status-${escapeHtml(cita.estado)}">${escapeHtml(cita.estado.replace('-', ' '))}</span></td>
                    <td class="actions-cell">
                        ${actionsHtml[cita.estado] || ''}
                    </td>
                </tr>
            `;
      })
      .join('');

    appointmentsTbody.innerHTML = rows;
  }

  function escapeHtml(str) {
    return str.replace(/[&<>"']/g, function (match) {
      return {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
      }[match];
    });
  }

  function debounce(func, delay) {
    let timeout;
    return function (...args) {
      const context = this;
      clearTimeout(timeout);
      timeout = setTimeout(() => func.apply(context, args), delay);
    };
  }

  function openModal({ title, message, confirmText = 'Confirmar', confirmClass = 'btn-primary', onConfirm }) {
    if (!modal) return;
    modalTitle.textContent = title;
    modalMessage.innerHTML = message; // Usar innerHTML para permitir etiquetas como <strong>
    modalConfirmBtn.textContent = confirmText;

    modalConfirmBtn.classList.remove('btn-primary', 'btn-danger');
    modalConfirmBtn.classList.add(confirmClass);

    confirmCallback = onConfirm;
    modal.setAttribute('aria-hidden', 'false');
    modalConfirmBtn.focus();
  }

  function closeModal() {
    if (!modal) return;
    modal.setAttribute('aria-hidden', 'true');
    confirmCallback = null;
  }

  function initializeEventListeners() {
    const searchInput = document.getElementById('patient-search-input');
    if (searchInput) {
      searchInput.addEventListener('input', debounce(applyFilters, 300));
    }

    const filterControls = document.getElementById('filter-controls');
    if (filterControls) {
      filterControls.addEventListener('click', handleFilterClick);
    }

    if (modal) {
      modalCancelBtn.addEventListener('click', closeModal);
      modalCloseBtn.addEventListener('click', closeModal);
      modalConfirmBtn.addEventListener('click', () => {
        if (typeof confirmCallback === 'function') {
          confirmCallback();
        }
        closeModal();
      });
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') closeModal();
      });
      modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
      });
    }

    if (appointmentsTbody) {
      appointmentsTbody.addEventListener('click', handleActionClick);
    }
  }

  function handleFilterClick(event) {
    const filterButton = event.target.closest('.btn-filter');
    if (!filterButton) return;

    // Actualizar estado activo del botón
    document.querySelectorAll('.btn-filter').forEach((btn) => btn.classList.remove('active'));
    filterButton.classList.add('active');

    applyFilters();
  }

  function applyFilters() {
    const statusFilter = document.querySelector('.btn-filter.active').dataset.filter;
    const searchTerm = document.getElementById('patient-search-input').value.toLowerCase().trim();
    const rows = appointmentsTbody.querySelectorAll('tr');

    let visibleRows = 0;
    rows.forEach((row) => {
      if (!row.dataset.id) return; // Ignorar filas de mensajes

      const rowStatus = row.dataset.status;
      const rowPatientName = row.dataset.patientName;

      const statusMatch = statusFilter === 'all' || statusFilter === rowStatus;
      const searchMatch = searchTerm === '' || rowPatientName.includes(searchTerm);

      if (statusMatch && searchMatch) {
        row.style.display = '';
        visibleRows++;
      } else {
        row.style.display = 'none';
      }
    });

    // Manejar mensaje de "sin resultados para el filtro"
    const noResultsRow = appointmentsTbody.querySelector('.no-results-cell');
    if (noResultsRow) noResultsRow.remove();

    if (visibleRows === 0 && allAppointments.length > 0) {
      const colspan = user.role === 'ADMINISTRATOR' ? 6 : 5;
      appointmentsTbody.insertAdjacentHTML(
        'beforeend',
        `
                <tr>
                    <td colspan="${colspan}" class="no-results-cell">No se encontraron citas que coincidan con los filtros.</td>
                </tr>
            `,
      );
    }
  }

  async function handleActionClick(event) {
    const actionButton = event.target.closest('[data-action]');
    if (!actionButton) return;

    const row = actionButton.closest('tr');
    const appointmentId = row.dataset.id;
    const action = actionButton.dataset.action;
    const patientName = row.cells[1].textContent;

    const newStatus = action === 'attend' ? 'atendida' : action === 'no-show' ? 'no-presentado' : null;
    if (!newStatus) return;

    if (action === 'no-show') {
      openModal({
        title: 'Confirmar Ausencia',
        message: `¿Está seguro de que desea marcar la cita del paciente <strong>${escapeHtml(patientName)}</strong> como "No Presentado"? Esta acción no se puede deshacer.`,
        confirmText: 'Sí, confirmar',
        confirmClass: 'btn-danger',
        onConfirm: () => updateAppointmentStatus(row, appointmentId, newStatus),
      });
    } else {
      updateAppointmentStatus(row, appointmentId, newStatus);
    }
  }
  async function updateAppointmentStatus(row, appointmentId, newStatus) {
    const actionsCell = row.querySelector('.actions-cell');
    const originalActionsHTML = actionsCell.innerHTML;

    row.classList.add('is-updating');
    actionsCell.innerHTML = '<div class="spinner"></div>';

    try {
      const response = await fetch('/clinica/api/dashboard_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ appointmentId, newStatus }),
      });

      if (!response.ok) throw new Error('La respuesta del servidor no fue exitosa.');

      const result = await response.json();
      if (!result.success) throw new Error(result.error || 'Ocurrió un error al actualizar.');

      // Actualizar caché local
      const appointment = allAppointments.find((c) => c.id === appointmentId);
      if (appointment) appointment.estado = newStatus;

      renderKPIs(allAppointments);

      // Actualizar la fila específica
      const statusBadge = row.querySelector('.status-badge');
      row.dataset.status = newStatus;
      statusBadge.className = `status-badge status-${newStatus}`;
      statusBadge.textContent = newStatus.replace('-', ' ');
      actionsCell.innerHTML = `<span class="action-text">${newStatus === 'atendida' ? 'Completada' : 'Ausente'}</span>`;

      // Ocultar la fila si ya no coincide con el filtro activo
      applyFilters();
    } catch (error) {
      console.error('Error al actualizar el estado de la cita:', error);
      alert('No se pudo actualizar la cita. Por favor, intente de nuevo.');
      actionsCell.innerHTML = originalActionsHTML; // Revertir solo la celda de acciones
    } finally {
      row.classList.remove('is-updating');
    }
  }

  async function fetchDashboardData() {
    try {
      const response = await fetch('/clinica/api/dashboard_handler.php');

      if (!response.ok) {
        if (response.status === 401) {
          window.location.href = '/clinica/pages/login/';
          return;
        }
        throw new Error(`Error en la red: ${response.statusText}`);
      }

      const data = await response.json();
      console.log('Datos recibidos:', data);

      allAppointments = data.appointments.sort((a, b) => a.hora.localeCompare(b.hora));
      user = data.user;
      userRole = data.user.role;

      renderKPIs(allAppointments);
      renderAppointmentsTable(allAppointments, userRole);
      initializeEventListeners();
    } catch (error) {
      console.error('Error al cargar los datos del dashboard:', error);
      if (appointmentsTbody) {
        const colspan = document.querySelectorAll('.appointments-table th').length || 5;
        appointmentsTbody.innerHTML = `
                    <tr><td colspan="${colspan}" class="no-results-cell" style="color: red;">Error al cargar los datos. Intente recargar.</td></tr>
                `;
      }
    }
  }

  fetchDashboardData();
});

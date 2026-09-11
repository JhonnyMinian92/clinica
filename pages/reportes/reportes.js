/*
 * reportes.js
 * Reportes de citas por doctor y por estado, con exportacion a CSV.
 */

(function () {
  'use strict';

  function appUrl(path) {
    const basePath = String(window.CLINICA_BASE_PATH || '').replace(/\/$/, '');
    return `${basePath}/${String(path || '').replace(/^\/+/, '')}`;
  }

  document.addEventListener('DOMContentLoaded', () => {
    const REPORTES_API = appUrl('api/reportes/');
    const shell = document.querySelector('.reportes-container');
    const canSeeAll = shell?.dataset.canSeeAll === '1';

    const dom = {
      tabByDoctor: document.getElementById('tab-by-doctor'),
      tabByStatus: document.getElementById('tab-by-status'),
      panelByDoctor: document.getElementById('by-doctor-panel'),
      panelByStatus: document.getElementById('by-status-panel'),
      fromInput: document.getElementById('report-from'),
      toInput: document.getElementById('report-to'),
      doctorSelect: document.getElementById('report-doctor-select'),
      statusField: document.getElementById('report-status-field'),
      statusSelect: document.getElementById('report-status-select'),
      exportBtn: document.getElementById('report-export-btn'),
      byDoctorWrapper: document.getElementById('by-doctor-table-wrapper'),
      byStatusWrapper: document.getElementById('by-status-table-wrapper'),
      statusKpiGrid: document.getElementById('status-kpi-grid'),
      statusPagination: document.getElementById('status-pagination'),
      statusPaginationSummary: document.getElementById('status-pagination-summary'),
      statusPaginationPages: document.getElementById('status-pagination-pages'),
      statusPaginationPrev: document.getElementById('status-pagination-prev'),
      statusPaginationNext: document.getElementById('status-pagination-next'),
    };

    const statusLabels = {
      pendiente: 'Pendiente',
      atendida: 'Atendida',
      'no-presentado': 'No presentado',
      rechazada: 'Rechazada',
      cancelada: 'Cancelada',
    };

    let activeTab = 'by-doctor';
    let activeStatus = 'atendida';
    let statusPage = 1;
    let doctorsPopulated = false;

    function init() {
      attachEventListeners();
      fetchActiveReport();
    }

    function attachEventListeners() {
      dom.tabByDoctor?.addEventListener('click', () => switchTab('by-doctor'));
      dom.tabByStatus?.addEventListener('click', () => switchTab('by-status'));
      dom.fromInput?.addEventListener('change', handleFilterChange);
      dom.toInput?.addEventListener('change', handleFilterChange);
      dom.doctorSelect?.addEventListener('change', handleFilterChange);
      dom.statusSelect?.addEventListener('change', () => {
        activeStatus = dom.statusSelect.value;
        statusPage = 1;
        fetchByStatusDetail();
      });
      dom.statusKpiGrid?.addEventListener('click', (event) => {
        const card = event.target.closest('[data-status]');
        if (!card) return;
        activeStatus = card.dataset.status;
        if (dom.statusSelect) dom.statusSelect.value = activeStatus;
        statusPage = 1;
        markActiveStatusCard();
        fetchByStatusDetail();
      });
      dom.exportBtn?.addEventListener('click', handleExport);
      dom.statusPaginationPrev?.addEventListener('click', () => {
        if (statusPage > 1) {
          statusPage--;
          fetchByStatusDetail();
        }
      });
      dom.statusPaginationNext?.addEventListener('click', () => {
        statusPage++;
        fetchByStatusDetail();
      });
    }

    function switchTab(tab) {
      activeTab = tab;
      const isByDoctor = tab === 'by-doctor';

      dom.tabByDoctor?.classList.toggle('active', isByDoctor);
      dom.tabByDoctor?.setAttribute('aria-selected', String(isByDoctor));
      dom.tabByStatus?.classList.toggle('active', !isByDoctor);
      dom.tabByStatus?.setAttribute('aria-selected', String(!isByDoctor));

      if (dom.panelByDoctor) dom.panelByDoctor.hidden = !isByDoctor;
      if (dom.panelByStatus) dom.panelByStatus.hidden = isByDoctor;
      if (dom.statusField) dom.statusField.hidden = isByDoctor;

      fetchActiveReport();
    }

    function handleFilterChange() {
      statusPage = 1;
      fetchActiveReport();
    }

    function fetchActiveReport() {
      if (activeTab === 'by-doctor') {
        fetchByDoctor();
      } else {
        fetchByStatusSummary();
        fetchByStatusDetail();
      }
    }

    function getDateRange() {
      const from = dom.fromInput?.value || '';
      const to = dom.toInput?.value || '';
      return { from, to };
    }

    function baseParams() {
      const { from, to } = getDateRange();
      const params = new URLSearchParams({ from, to });
      const doctorId = dom.doctorSelect?.value || '';
      if (doctorId) params.set('doctor_id', doctorId);
      return params;
    }

    async function fetchJson(params) {
      const response = await fetch(`${REPORTES_API}?${params.toString()}`, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
      });
      const data = await response.json().catch(() => ({}));
      if (!response.ok || !data.success) {
        throw new Error(data.error || 'No se pudo cargar el reporte.');
      }
      return data;
    }

    async function fetchByDoctor() {
      setLoading(dom.byDoctorWrapper);
      try {
        const params = baseParams();
        params.set('report', 'by_doctor');
        const data = await fetchJson(params);
        populateDoctorSelect(data.doctors);
        renderByDoctorTable(data.rows || []);
      } catch (error) {
        showError(dom.byDoctorWrapper, error.message);
      }
    }

    async function fetchByStatusSummary() {
      try {
        const params = baseParams();
        params.set('report', 'by_status');
        const data = await fetchJson(params);
        populateDoctorSelect(data.doctors);
        renderStatusKpis(data.counts || {});
      } catch (error) {
        // El resumen es secundario; si falla, la tabla de detalle mostrara el error.
      }
    }

    async function fetchByStatusDetail() {
      setLoading(dom.byStatusWrapper);
      try {
        const params = baseParams();
        params.set('report', 'by_status_detail');
        params.set('status', activeStatus);
        params.set('page', String(statusPage));
        params.set('per_page', '25');
        const data = await fetchJson(params);
        renderByStatusDetailTable(data.rows || []);
        renderStatusPagination(data.pagination || {});
      } catch (error) {
        showError(dom.byStatusWrapper, error.message);
        if (dom.statusPagination) dom.statusPagination.hidden = true;
      }
    }

    function populateDoctorSelect(doctors) {
      if (!canSeeAll || !dom.doctorSelect || doctorsPopulated || !Array.isArray(doctors)) return;

      const currentValue = dom.doctorSelect.value;
      doctors.forEach((doctor) => {
        const option = document.createElement('option');
        option.value = doctor.id;
        option.textContent = doctor.nombre;
        dom.doctorSelect.appendChild(option);
      });
      dom.doctorSelect.value = currentValue;
      doctorsPopulated = true;
    }

    function markActiveStatusCard() {
      dom.statusKpiGrid?.querySelectorAll('[data-status]').forEach((card) => {
        card.classList.toggle('active', card.dataset.status === activeStatus);
      });
    }

    function renderStatusKpis(counts) {
      dom.statusKpiGrid?.querySelectorAll('[data-status]').forEach((card) => {
        const value = Number(counts[card.dataset.status] || 0);
        const valueEl = card.querySelector('.status-kpi-value');
        if (valueEl) valueEl.textContent = String(value);
      });
    }

    function renderByDoctorTable(rows) {
      if (!dom.byDoctorWrapper) return;

      if (rows.length === 0) {
        dom.byDoctorWrapper.innerHTML = emptyStateHtml('Sin datos', 'No hay citas registradas para este rango de fechas.');
        return;
      }

      const bodyRows = rows
        .map(
          (row) => `
            <tr>
              <td>${escapeHtml(row.doctor_nombre)}</td>
              <td>${escapeHtml(row.pendientes)}</td>
              <td>${escapeHtml(row.atendidas)}</td>
              <td>${escapeHtml(row.no_presentado)}</td>
              <td>${escapeHtml(row.rechazadas)}</td>
              <td>${escapeHtml(row.canceladas)}</td>
              <td><strong>${escapeHtml(row.total)}</strong></td>
            </tr>
          `,
        )
        .join('');

      dom.byDoctorWrapper.innerHTML = `
        <table class="reportes-table">
          <thead>
            <tr>
              <th>Doctor</th>
              <th>Pendientes</th>
              <th>Atendidas</th>
              <th>No presentado</th>
              <th>Rechazadas</th>
              <th>Canceladas</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>${bodyRows}</tbody>
        </table>
      `;
    }

    function renderByStatusDetailTable(rows) {
      if (!dom.byStatusWrapper) return;

      if (rows.length === 0) {
        dom.byStatusWrapper.innerHTML = emptyStateHtml('Sin registros', `No hay citas en estado "${statusLabels[activeStatus] || activeStatus}" para este rango.`);
        return;
      }

      const bodyRows = rows
        .map(
          (row) => `
            <tr>
              <td>${escapeHtml(row.paciente)}</td>
              <td>${escapeHtml(row.doctor_nombre)}</td>
              <td>${escapeHtml(formatDate(row.fecha))} ${escapeHtml(String(row.hora || '').slice(0, 5))}</td>
              <td>${row.fecha_cambio ? escapeHtml(formatDateTime(row.fecha_cambio)) : 'No disponible'}</td>
            </tr>
          `,
        )
        .join('');

      dom.byStatusWrapper.innerHTML = `
        <table class="reportes-table">
          <thead>
            <tr>
              <th>Paciente</th>
              <th>Doctor</th>
              <th>Fecha y hora de la cita</th>
              <th>Fecha y hora del cambio de estado</th>
            </tr>
          </thead>
          <tbody>${bodyRows}</tbody>
        </table>
      `;
    }

    function renderStatusPagination(pageData) {
      if (!dom.statusPagination) return;

      const total = Number(pageData.total || 0);
      dom.statusPagination.hidden = total === 0;
      if (total === 0) return;

      const page = Number(pageData.page || 1);
      const totalPages = Number(pageData.total_pages || 1);
      const perPage = Number(pageData.per_page || 25);
      const from = total === 0 ? 0 : (page - 1) * perPage + 1;
      const to = Math.min(page * perPage, total);

      if (dom.statusPaginationSummary) {
        dom.statusPaginationSummary.textContent = `Mostrando ${from}-${to} de ${total} registros`;
      }
      if (dom.statusPaginationPages) {
        dom.statusPaginationPages.textContent = `${page} / ${totalPages}`;
      }
      if (dom.statusPaginationPrev) dom.statusPaginationPrev.disabled = page <= 1;
      if (dom.statusPaginationNext) dom.statusPaginationNext.disabled = page >= totalPages;
    }

    function handleExport() {
      const params = baseParams();
      if (activeTab === 'by-doctor') {
        params.set('report', 'by_doctor');
      } else {
        params.set('report', 'by_status_detail');
        params.set('status', activeStatus);
      }
      params.set('format', 'csv');
      window.location.href = `${REPORTES_API}?${params.toString()}`;
    }

    function setLoading(container) {
      if (!container) return;
      container.innerHTML = `
        <div class="reportes-loading">
          <div class="spinner"></div>
          <span>Cargando...</span>
        </div>
      `;
    }

    function showError(container, message) {
      if (!container) return;
      container.innerHTML = emptyStateHtml('Error', message || 'No se pudo cargar el reporte.');
    }

    function emptyStateHtml(title, description) {
      return `
        <div class="reportes-empty">
          <h3>${escapeHtml(title)}</h3>
          <p>${escapeHtml(description)}</p>
        </div>
      `;
    }

    function formatDate(dateString) {
      if (!dateString) return '';
      return new Date(`${dateString}T00:00:00`).toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    function formatDateTime(value) {
      if (!value) return '';
      const normalized = String(value).replace(' ', 'T');
      const date = new Date(normalized);
      if (Number.isNaN(date.getTime())) return String(value);
      return `${date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' })} ${date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })}`;
    }

    function escapeHtml(value) {
      return String(value ?? '').replace(/[&<>"']/g, (match) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
      }[match]));
    }

    init();
  });
})();

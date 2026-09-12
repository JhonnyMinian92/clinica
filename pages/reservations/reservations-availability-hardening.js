/* Reservation availability hardening.
 * Keeps the reservation controller authoritative while continuously refreshing
 * public availability and preventing stale UI selections from advancing.
 */
(function () {
  'use strict';

  const base = String(window.CLINICA_BASE_PATH || '').replace(/\/$/, '');
  const endpoint = `${base}/api/general/`;
  const intervalMs = 2000;
  let timer = null;
  let requestId = 0;

  const getContext = () => {
    const form = document.getElementById('reservation-form');
    const step = form?.querySelector('.form-step.active');
    if (!form || !step || !step.querySelector('#calendar-grid')) return null;

    const doctor = document.querySelector('#doctor-selection-grid .selectable-card.active');
    const day = document.querySelector('#calendar-grid .calendar-day.is-selected');
    const doctorId = doctor?.dataset.value || form.dataset.selectedDoctorId || '';
    const date = day?.dataset.date || '';
    return doctorId && date ? { doctorId, date } : null;
  };

  const updateCount = (count, loading) => {
    const node = document.getElementById('available-slots-count');
    if (!node) return;
    if (loading) {
      node.textContent = 'Actualizando';
      return;
    }
    node.textContent = count > 0 ? `${count} ${count === 1 ? 'cupo' : 'cupos'}` : 'Sin cupos';
  };

  const applyAvailability = (result) => {
    if (!result || !Array.isArray(result.availableTimes)) return;
    const available = new Set(result.availableTimes.map(String));
    const buttons = [...document.querySelectorAll('#time-slots [data-time]')];
    let selectedStillAvailable = true;

    buttons.forEach((button) => {
      const isAvailable = available.has(String(button.dataset.time || ''));
      if (!isAvailable) {
        if (button.classList.contains('active')) selectedStillAvailable = false;
        button.remove();
      }
    });

    document.querySelectorAll('#time-slots .time-period').forEach((period) => {
      const grid = period.querySelector('.time-period-grid');
      const remaining = grid ? grid.querySelectorAll('[data-time]').length : 0;
      const countNode = period.querySelector('.time-period-header span');
      if (countNode) countNode.textContent = `${remaining} ${remaining === 1 ? 'cupo' : 'cupos'}`;
      if (!remaining) period.remove();
    });

    updateCount(available.size, false);

    const next = document.getElementById('next-btn');
    const selected = document.querySelector('#time-slots [data-time].active');
    if (!selectedStillAvailable || !selected) {
      if (!selectedStillAvailable) {
        const summaryTime = document.getElementById('schedule-time-label');
        if (summaryTime) summaryTime.textContent = 'Pendiente';
      }
      if (next) next.disabled = true;
    }

    if (!available.size) {
      const grid = document.getElementById('time-slots');
      if (grid && !grid.querySelector('.no-time-selected')) {
        grid.innerHTML = '<div class="no-time-selected"><p>No hay horarios disponibles para esta fecha.</p></div>';
      }
    }
  };

  async function refresh() {
    const context = getContext();
    if (!context || document.visibilityState === 'hidden') return;
    const id = ++requestId;
    updateCount(0, true);
    try {
      const response = await fetch(
        `${endpoint}?action=slot_status&doctorId=${encodeURIComponent(context.doctorId)}&date=${encodeURIComponent(context.date)}&_=${Date.now()}`,
        { credentials: 'same-origin', headers: { Accept: 'application/json', 'Cache-Control': 'no-cache' } }
      );
      const result = await response.json().catch(() => ({}));
      if (id !== requestId || !response.ok || result.success === false) return;
      applyAvailability(result);
    } catch (error) {
      if (id !== requestId) return;
      const node = document.getElementById('available-slots-count');
      if (node && node.textContent === 'Actualizando') node.textContent = '--';
    }
  }

  function schedule() {
    if (timer) clearInterval(timer);
    timer = window.setInterval(refresh, intervalMs);
    refresh();
  }

  document.addEventListener('DOMContentLoaded', () => {
    schedule();
    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'visible') refresh();
    });
    window.addEventListener('focus', refresh);

    // The final POST remains authoritative. A 409 means another request won
    // the race for this slot; return the user to date/time and refresh it.
    const originalFetch = window.fetch.bind(window);
    window.fetch = async function (...args) {
      const response = await originalFetch(...args);
      const input = args[0];
      const init = args[1] || {};
      const requestUrl = typeof input === 'string' ? input : input?.url || '';
      const method = String(init.method || input?.method || 'GET').toUpperCase();
      if (method === 'POST' && requestUrl.includes('/api/appointments/') && response.status === 409) {
        window.setTimeout(() => {
          const previous = document.getElementById('prev-btn');
          if (previous) {
            previous.click();
            window.setTimeout(() => previous.click(), 80);
          }
          refresh();
        }, 10);
      }
      return response;
    };
  });
}());

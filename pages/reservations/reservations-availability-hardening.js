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

  const renderLiveTimes = (times) => {
    const grid = document.getElementById('time-slots');
    if (!grid) return null;
    const previous = grid.querySelector('[data-time].active')?.dataset.time || '';
    const groups = { morning: [], afternoon: [], evening: [] };
    times.map(String).forEach((time) => {
      const parts = time.split(':').map(Number);
      const minutes = parts[0] * 60 + parts[1];
      const period = minutes < 720 ? 'morning' : minutes < 1080 ? 'afternoon' : 'evening';
      groups[period].push(time);
    });
    const labels = { morning: 'Mañana', afternoon: 'Tarde', evening: 'Noche' };
    grid.innerHTML = Object.entries(groups).filter(([, values]) => values.length).map(([period, values]) => `
      <section class="time-period" aria-label="${labels[period]}">
        <div class="time-period-header"><h4>${labels[period]}</h4><span>${values.length} ${values.length === 1 ? 'cupo' : 'cupos'}</span></div>
        <div class="time-period-grid">${values.map((time) => `<button type="button" class="selectable-card time-slot${time === previous ? ' active' : ''}" data-time="${time}" aria-pressed="${time === previous ? 'true' : 'false'}"><span class="card-title">${time}</span></button>`).join('')}</div>
      </section>
    `).join('');
    if (!times.length) grid.innerHTML = '<div class="no-time-selected"><p>No hay horarios disponibles para esta fecha.</p></div>';
    return previous && times.includes(previous) ? previous : null;
  };

  const applyAvailability = (result) => {
    if (!result || !Array.isArray(result.availableTimes)) return;
    const availableTimes = [...new Set(result.availableTimes.map(String))].sort();
    const previousSelected = document.querySelector('#time-slots [data-time].active')?.dataset.time || '';
    const currentTimes = [...document.querySelectorAll('#time-slots [data-time]')].map((button) => String(button.dataset.time || '')).sort();
    const next = document.getElementById('next-btn');
    const selectedStillAvailable = previousSelected === '' || availableTimes.includes(previousSelected);
    const sameTimes = currentTimes.length === availableTimes.length && currentTimes.every((time, index) => time === availableTimes[index]);
    if (!sameTimes) renderLiveTimes(availableTimes);
    updateCount(availableTimes.length, false);
    if (!selectedStillAvailable) {
      const summaryTime = document.getElementById('schedule-time-label');
      if (summaryTime) summaryTime.textContent = 'Pendiente';
    }
    if (next) next.disabled = !previousSelected || !selectedStillAvailable;
  };

  async function refresh() {
    const context = getContext();
    if (!context || document.visibilityState === 'hidden') return;
    const id = ++requestId;
    const countNode = document.getElementById('available-slots-count');
    if (countNode && (!countNode.textContent || countNode.textContent === '--' || countNode.textContent === 'Buscando')) {
      updateCount(0, true);
    }
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

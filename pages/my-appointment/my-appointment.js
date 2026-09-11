(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('appointment-lookup-form');
    const cedulaInput = document.getElementById('lookup-cedula');
    const submitButton = document.getElementById('lookup-submit');
    const feedback = document.getElementById('appointment-feedback');
    const results = document.getElementById('appointment-results');
    const config = window.appointmentManagementConfig || {};

    if (!form || !cedulaInput || !submitButton || !feedback || !results) return;

    function getCsrfToken() {
      const match = document.cookie.match(new RegExp('(^|;)\\s*XSRF-TOKEN\\s*=\\s*([^;]+)'));
      return match ? decodeURIComponent(match.pop()) : '';
    }

    function isValidCedula(value) {
      return /^[A-Za-z0-9]{6,20}$/.test(String(value || '').trim());
    }

    function escapeHtml(value) {
      return String(value ?? '').replace(/[&<>"']/g, function (match) {
        return {
          '&': '&amp;',
          '<': '&lt;',
          '>': '&gt;',
          '"': '&quot;',
          "'": '&#39;'
        }[match];
      });
    }

    function formatDate(dateValue) {
      const date = new Date(String(dateValue) + 'T00:00:00');
      if (Number.isNaN(date.getTime())) return String(dateValue || '');
      return date.toLocaleDateString('es-ES', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
    }

    function formatStatus(status) {
      const labels = {
        pendiente: 'Pendiente',
        atendida: 'Atendida',
        'no-presentado': 'No presentado',
        rechazada: 'Rechazada',
        cancelada: 'Cancelada'
      };
      return labels[status] || status;
    }

    function setFeedback(message, type) {
      feedback.hidden = !message;
      feedback.textContent = message || '';
      feedback.className = 'appointment-feedback' + (type ? ' is-' + type : '');
    }

    function setLoading(loading) {
      submitButton.disabled = loading;
      submitButton.textContent = loading ? 'Consultando...' : 'Consultar cita';
    }

    function renderResults(appointments) {
      results.hidden = false;

      if (!appointments.length) {
        results.innerHTML = `
          <div class="appointment-empty">
            <div class="appointment-empty-icon" aria-hidden="true">✓</div>
            <h2>No encontramos citas futuras</h2>
            <p>Verifique que la cedula o documento sea el mismo que utilizo al reservar.</p>
            <a class="btn btn-primary" href="${escapeHtml(config.reservationUrl || '#')}">Agendar una nueva cita</a>
          </div>`;
        return;
      }

      const cards = appointments.map(function (appointment) {
        const cancelButton = appointment.canCancel
          ? `<button type="button" class="btn btn-danger appointment-cancel" data-appointment-id="${Number(appointment.id)}">Anular cita</button>`
          : `<span class="appointment-no-cancel">Esta cita ya no puede ser anulada porque su hora de atencion ya paso.</span>`;

        return `
          <article class="appointment-card">
            <div class="appointment-card-header">
              <div>
                <span class="appointment-card-label">Cita</span>
                <h2>${escapeHtml(appointment.specialty)}</h2>
              </div>
              <span class="appointment-status is-${escapeHtml(appointment.status)}">${escapeHtml(formatStatus(appointment.status))}</span>
            </div>
            <dl class="appointment-details">
              <div><dt>Doctor</dt><dd>${escapeHtml(appointment.doctor)}</dd></div>
              <div><dt>Fecha</dt><dd>${escapeHtml(formatDate(appointment.date))}</dd></div>
              <div><dt>Hora</dt><dd>${escapeHtml(appointment.time)}</dd></div>
            </dl>
            <div class="appointment-card-actions">
              ${cancelButton}
            </div>
          </article>`;
      }).join('');

      results.innerHTML = `
        <div class="results-header">
          <div>
            <span class="my-appointment-eyebrow">Sus citas</span>
            <h2>Citas futuras encontradas</h2>
          </div>
          <a class="btn btn-secondary" href="${escapeHtml(config.reservationUrl || '#')}">Agendar otra cita</a>
        </div>
        <div class="appointment-list">${cards}</div>
        <p class="results-note">Si anula una cita antes de su hora de atencion, el horario se libera y puede reservar nuevamente para otro dia, hora o doctor.</p>`;

      results.querySelectorAll('.appointment-cancel').forEach(function (button) {
        button.addEventListener('click', function () {
          cancelAppointment(Number(button.dataset.appointmentId));
        });
      });
    }

    async function lookupAppointments() {
      const cedula = cedulaInput.value.trim();
      setFeedback('', '');
      results.hidden = true;
      results.innerHTML = '';

      if (!isValidCedula(cedula)) {
        setFeedback('Ingrese una cedula o documento valido de 6 a 20 caracteres, sin espacios.', 'error');
        cedulaInput.focus();
        return;
      }

      setLoading(true);

      try {
        const body = new URLSearchParams();
        body.set('action', 'lookup');
        body.set('cedula', cedula);

        const response = await fetch(config.apiUrl, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
            Accept: 'application/json',
            'X-CSRF-Token': getCsrfToken()
          },
          body: body.toString()
        });
        const data = await response.json().catch(function () { return {}; });

        if (!response.ok || !data.success) {
          throw new Error(data.error || 'No se pudo consultar la cita.');
        }

        renderResults(Array.isArray(data.appointments) ? data.appointments : []);
        setFeedback(data.message || '', data.appointments && data.appointments.length ? 'success' : 'info');
      } catch (error) {
        setFeedback(error.message || 'No se pudo consultar la cita. Intente nuevamente.', 'error');
      } finally {
        setLoading(false);
      }
    }

    async function cancelAppointment(appointmentId) {
      if (!appointmentId) return;

      const confirmed = window.confirm('¿Desea anular esta cita? Solo debe hacerlo si realmente necesita liberar el horario para reservar otro.');
      if (!confirmed) return;

      const cedula = cedulaInput.value.trim();
      const button = results.querySelector(`[data-appointment-id="${appointmentId}"]`);
      if (button) {
        button.disabled = true;
        button.textContent = 'Anulando...';
      }

      setFeedback('', '');

      try {
        const body = new URLSearchParams();
        body.set('action', 'cancel');
        body.set('appointmentId', String(appointmentId));
        body.set('cedula', cedula);

        const response = await fetch(config.apiUrl, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
            Accept: 'application/json',
            'X-CSRF-Token': getCsrfToken()
          },
          body: body.toString()
        });
        const data = await response.json().catch(function () { return {}; });

        if (!response.ok || !data.success) {
          throw new Error(data.error || 'No se pudo anular la cita.');
        }

        setFeedback(data.message || 'Cita anulada correctamente.', 'success');
        await lookupAppointments();
      } catch (error) {
        setFeedback(error.message || 'No se pudo anular la cita. Intente nuevamente.', 'error');
        if (button) {
          button.disabled = false;
          button.textContent = 'Anular cita';
        }
      }
    }

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      lookupAppointments();
    });
  });
})();

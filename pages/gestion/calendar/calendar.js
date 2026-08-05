window.ClinicaGestion.boot({
  resource: 'schedules',
  view: 'schedule-board',
  titleField: 'doctor_id',
  columns: [
    { name: 'doctor_id', label: 'Doctor', type: 'select', optionSource: 'doctors' },
    { name: 'weekday', label: 'Dia', type: 'select', optionSource: 'weekdays' },
    { name: 'start_time', label: 'Inicio' },
    { name: 'end_time', label: 'Fin' },
  ],
  fields: [
    { name: 'doctor_id', label: 'Doctor', type: 'select', optionSource: 'doctors', required: true },
    { name: 'date', label: 'Fecha especifica', type: 'date', help: 'Opcional. Si se deja vacia, el horario se repite cada semana.' },
    { name: 'weekday', label: 'Dia de semana', type: 'select', optionSource: 'weekdays', required: true },
    { name: 'start_time', label: 'Hora inicio', type: 'time', required: true },
    { name: 'end_time', label: 'Hora fin', type: 'time', required: true },
    { name: 'slot_minutes', label: 'Duracion de cupo (min)', type: 'number', min: 5, max: 120 },
    { name: 'active', label: 'Activo', type: 'checkbox' },
  ],
});

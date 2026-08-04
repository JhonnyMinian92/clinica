window.ClinicaGestion.boot({
  resource: 'doctors',
  titleField: 'name',
  columns: [
    { name: 'specialty_id', label: 'Especialidad', type: 'select', optionSource: 'specialties' },
    { name: 'rating', label: 'Estrellas' },
    { name: 'experience_years', label: 'Experiencia' },
  ],
  fields: [
    { name: 'name', label: 'Nombre', type: 'text', required: true },
    { name: 'photo', label: 'Foto', type: 'text', help: 'Ej: /clinica/public/assets/doctor_avatar.jpg' },
    { name: 'specialty_id', label: 'Especialidad', type: 'select', optionSource: 'specialties', required: true },
    { name: 'sub_specialty', label: 'Subespecialidad', type: 'text' },
    { name: 'experience_years', label: 'Anos de experiencia', type: 'number', min: 0 },
    { name: 'university', label: 'Formacion', type: 'text' },
    { name: 'languages', label: 'Idiomas', type: 'text', help: 'Separe por comas. Ej: Espanol, Ingles' },
    { name: 'recognitions', label: 'Reconocimientos', type: 'text', help: 'Separe por comas.' },
    { name: 'rating', label: 'Estrellas', type: 'number', min: 1, max: 5 },
    { name: 'patients', label: 'Pacientes atendidos', type: 'number', min: 0 },
    { name: 'featured', label: 'Destacado en home', type: 'checkbox' },
    { name: 'active', label: 'Activo', type: 'checkbox' },
  ],
});

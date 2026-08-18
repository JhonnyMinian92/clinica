window.ClinicaGestion.boot({
  resource: 'doctors',
  titleField: 'name',
  columns: [
    { name: 'specialty_id', label: 'Especialidad', type: 'select', optionSource: 'specialties' },
    { name: 'rating', label: 'Estrellas' },
    { name: 'experience_years', label: 'Experiencia' },
  ],
  fields: [
    { name: 'name', label: 'Nombre', type: 'text', required: true, autocomplete: 'name' },
    { name: 'photo', label: 'Foto', type: 'image', help: 'Suba JPG, PNG, WEBP o GIF. Si no sube una foto, puede conservar el avatar placeholder.' },
    { name: 'specialty_id', label: 'Especialidad', type: 'select', optionSource: 'specialties', required: true },
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

window.ClinicaGestion.boot({
  resource: 'specialties',
  titleField: 'name',
  columns: [
    { name: 'summary', label: 'Resumen' },
    { name: 'featured', label: 'Home', type: 'checkbox' },
  ],
  fields: [
    { name: 'name', label: 'Nombre', type: 'text', required: true },
    { name: 'summary', label: 'Resumen', type: 'textarea' },
    { name: 'description', label: 'Descripcion completa', type: 'textarea' },
    { name: 'icon', label: 'Icono SVG', type: 'textarea', help: 'Pegue paths SVG internos, sin etiqueta svg.' },
    { name: 'featured', label: 'Destacada en home', type: 'checkbox' },
    { name: 'active', label: 'Activa', type: 'checkbox' },
  ],
});

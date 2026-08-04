window.ClinicaGestion.boot({
  resource: 'services',
  titleField: 'title',
  columns: [
    { name: 'description', label: 'Descripcion' },
    { name: 'featured', label: 'Home', type: 'checkbox' },
  ],
  fields: [
    { name: 'title', label: 'Servicio', type: 'text', required: true },
    { name: 'description', label: 'Descripcion', type: 'textarea' },
    { name: 'icon', label: 'Icono SVG', type: 'textarea', help: 'Pegue paths SVG internos, sin etiqueta svg.' },
    { name: 'featured', label: 'Destacado', type: 'checkbox' },
    { name: 'active', label: 'Activo', type: 'checkbox' },
  ],
});

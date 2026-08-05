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
    { name: 'icon', label: 'Icono', type: 'image', help: 'Suba JPG, PNG, WEBP, GIF o SVG, o pegue el codigo SVG. Se guarda en public/assets/icons/.' },
    { name: 'featured', label: 'Destacado', type: 'checkbox' },
    { name: 'active', label: 'Activo', type: 'checkbox' },
  ],
});

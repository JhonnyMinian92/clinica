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
    { name: 'icon', label: 'Icono', type: 'image', help: 'Suba JPG, PNG, WEBP, GIF o SVG, o pegue el codigo SVG. Se guarda en public/assets/icons/.' },
    { name: 'featured', label: 'Destacada en home', type: 'checkbox' },
    { name: 'active', label: 'Activa', type: 'checkbox' },
  ],
});

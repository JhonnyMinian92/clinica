window.ClinicaGestion.boot({
  resource: 'comments',
  titleField: 'author',
  columns: [
    { name: 'detail', label: 'Detalle' },
    { name: 'rating', label: 'Rating' },
    { name: 'avatar', label: 'Avatar' },
  ],
  fields: [
    { name: 'author', label: 'Autor', type: 'text', required: true },
    { name: 'detail', label: 'Detalle', type: 'text' },
    { name: 'text', label: 'Comentario', type: 'textarea', required: true },
    { name: 'rating', label: 'Estrellas', type: 'number', min: 1, max: 5, required: true },
    { name: 'avatar', label: 'Avatar', type: 'image', help: 'Suba JPG, PNG, WEBP o GIF. Si no sube una imagen, puede conservar el avatar placeholder.' },
    { name: 'featured', label: 'Mostrar en home', type: 'checkbox' },
    { name: 'active', label: 'Activo', type: 'checkbox' },
  ],
});

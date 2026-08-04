window.ClinicaGestion.boot({
  resource: 'users',
  titleField: 'username',
  columns: [
    { name: 'name', label: 'Nombre' },
    { name: 'role', label: 'Rol', type: 'select', optionSource: 'roles' },
    { name: 'doctor_id', label: 'Doctor', type: 'select', optionSource: 'doctors' },
  ],
  fields: [
    { name: 'username', label: 'Usuario', type: 'text', required: true },
    { name: 'name', label: 'Nombre completo', type: 'text', required: true },
    { name: 'role', label: 'Rol', type: 'select', optionSource: 'roles', required: true },
    { name: 'label', label: 'Etiqueta visible', type: 'text' },
    { name: 'doctor_id', label: 'Doctor vinculado', type: 'select', optionSource: 'doctors', help: 'Solo requerido para rol Doctor.' },
    { name: 'password', label: 'Contrasena', type: 'password', help: 'Al editar, deje vacio para conservar la actual.' },
    { name: 'active', label: 'Activo', type: 'checkbox' },
  ],
});

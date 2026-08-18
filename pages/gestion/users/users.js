window.ClinicaGestion.boot({
  resource: 'users',
  titleField: 'username',
  columns: [
    { name: 'name', label: 'Nombre' },
    { name: 'role', label: 'Rol', type: 'select', optionSource: 'roles' },
    { name: 'doctor_id', label: 'Doctor', type: 'select', optionSource: 'doctors' },
  ],
  fields: [
    { name: 'username', label: 'Usuario', type: 'text', required: true, minlength: 3, maxlength: 50, pattern: '^[A-Za-z0-9._-]{3,50}$', autocomplete: 'username', help: 'Use letras, numeros, punto, guion o guion bajo.' },
    { name: 'name', label: 'Nombre completo', type: 'text', required: true, autocomplete: 'name' },
    { name: 'role', label: 'Rol', type: 'select', optionSource: 'roles', required: true, help: 'Administracion gestiona todo, Secretaria opera agenda completa y Doctor ve solo su agenda.' },
    { name: 'doctor_id', label: 'Doctor vinculado', type: 'select', optionSource: 'doctors', help: 'Solo requerido para rol Doctor.' },
    { name: 'password', label: 'Contrasena', type: 'password', minlength: 8, autocomplete: 'new-password', help: 'Minimo 8 caracteres. Al editar, deje vacio para conservar la actual.' },
    { name: 'active', label: 'Activo', type: 'checkbox' },
  ],
});

# Despliegue demo en InfinityFree

## 1. Preparar el sitio

Subir el contenido del repositorio al document root (`htdocs`) del sitio.

El proyecto no requiere Composer ni framework PHP.

InfinityFree free hosting utiliza PHP 8.3 actualmente, por lo que el proyecto debe ejecutarse con esa version del hosting.

## 2. Configurar la base de datos

Crear la base de datos MySQL/MariaDB desde el panel de InfinityFree.

Usar exactamente los valores entregados por el panel para:

- host;
- nombre de base;
- usuario;
- contraseña;
- puerto.

En este demo las credenciales actuales tambien existen en `api/db.php` por decision del propietario del proyecto.

## 3. Crear tablas y datos iniciales

Abrir en el navegador:

`/api/seed.php`

El script:

- crea las tablas que no existan;
- aplica migraciones pendientes;
- crea indices necesarios;
- inserta configuracion, especialidades, doctores, servicios, horarios y usuarios cuando las tablas estan vacias;
- no borra datos existentes.

Si el usuario MySQL de InfinityFree no tiene permiso para `CREATE DATABASE`, el script continua utilizando la base ya creada desde el panel.

**Despues de ejecutar correctamente el seed, eliminar o renombrar `api/seed.php` si el sitio queda publico.**

## 4. Configuracion local opcional

Para un despliegue posterior se puede usar:

- `.env` basado en `.env.example`; o
- `api/db.local.php` basado en `api/db.local.example.php`.

Ambos estan ignorados por Git y el `.htaccess` impide su acceso web directo.

## 5. Email

En el demo no hace falta configurar SMTP.

El codigo no intentara realizar conexiones SMTP si no existe `MAIL_FROM_EMAIL` junto con `SMTP_HOST`. Esto evita que una reserva quede esperando conexiones SMTP inexistentes.

Para el hosting real se configurara:

- `SMTP_HOST`;
- `SMTP_PORT`;
- `SMTP_SECURE`;
- `SMTP_USER`;
- `SMTP_PASS`;
- `MAIL_FROM_EMAIL`;
- `MAIL_FROM_NAME`.

Luego se habilitara el canal de email desde la configuracion de la clinica.

## 6. WhatsApp

No requiere configuracion para el demo.

El codigo soporta `mock`, `twilio` y `meta`. Para produccion se configuraran las credenciales y plantillas correspondientes y se habilitara el canal en la configuracion de la clinica.

## 7. Recordatorios

El free hosting no dispone de cron del sistema. Por eso el demo no depende de cron para funcionar.

El dashboard mantiene un sweep best-effort para no dejar el codigo inutilizado durante la demo. En el hosting de pago se puede ejecutar `api/cron/reminders.php` mediante cron real para obtener recordatorios independientes de que exista un usuario mirando el dashboard.

No se debe intentar sustituir el cron del free hosting mediante servicios externos que generen peticiones periodicas, porque la plataforma aplica restricciones para este tipo de automatizacion.

## 8. API externa

`api/public/` es una API publica de lectura para los datos de portada. El sitio funciona normalmente mediante peticiones same-origin desde el navegador.

InfinityFree free puede restringir accesos programaticos externos por su sistema de seguridad. Por ello no se garantiza que un cliente externo, bot o integracion servidor-servidor pueda consumir la API desde fuera de InfinityFree. Esto es una limitacion del hosting, no un fallo del codigo PHP.

Para integraciones reales, mover la API a un hosting de pago es la opcion recomendada.

## 9. Checklist posterior al despliegue

- [ ] La portada carga correctamente.
- [ ] Especialidades y doctores cargan.
- [ ] El formulario de reserva muestra horarios.
- [ ] No se puede reservar una hora ocupada.
- [ ] Se puede crear una cita valida.
- [ ] Login de administrador funciona.
- [ ] Login de secretaria funciona.
- [ ] Login de doctor funciona y solo ve sus citas.
- [ ] Historial de paciente funciona y respeta el rol.
- [ ] Cambio de estado de cita funciona.
- [ ] Reportes cargan.
- [ ] Exportacion CSV funciona.
- [ ] En demo no se intenta conectar a SMTP sin configuracion.
- [ ] WhatsApp permanece inactivo hasta configurar proveedor.
- [ ] `api/seed.php` se retira despues de inicializar la BD.
- [ ] `diagnostico.php` no existe en el despliegue.

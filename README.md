# Clinica Estandar

Portal web y sistema operativo basico para una clinica privada. Incluye sitio publico, reserva de citas, login por roles, dashboard de agenda y gestion administrativa de contenido, usuarios, doctores, especialidades, servicios y horarios.

Actualizado: 2026-08-18

## Estado Actual

El proyecto ya no es solo una demo front-first: actualmente usa PHP procedural, JavaScript vanilla y MySQL/MariaDB mediante PDO. La aplicacion mantiene contratos JSON para el frontend y centraliza buena parte de la logica en `api/data.php`.

Estado de la auditoria solicitada:

- Codigo de aplicacion: no modificado.
- Documentacion actualizada: `context.md` y `README.md`.
- Validacion local: PHP lint OK, JS syntax OK, JSON de seed OK, conexion DB OK.
- Smoke HTTP: rutas publicas OK, dashboard sin sesion redirige, login admin OK, dashboard API OK, Gestion API OK.
- Hallazgo mas importante de frontend: en mobile se corta contenido en home, reservas y login por overflow horizontal oculto.

## Stack

- PHP 8.2.12
- MySQL/MariaDB con PDO
- JavaScript vanilla
- CSS modular por pagina
- XAMPP como entorno local esperado
- Sin Composer, npm package, bundler ni framework frontend

## Rutas Principales

- Home publica: `/clinica/`
- Especialidades: `/clinica/pages/specialties/`
- Doctores: `/clinica/pages/doctors/`
- Reservas: `/clinica/pages/reservations/`
- Login: `/clinica/pages/login/`
- Dashboard: `/clinica/pages/dashboard/`
- Gestion admin: `/clinica/pages/gestion/clinic/`

## APIs

- `api/public/`: informacion publica de home.
- `api/general/`: sesion, login, logout y creacion de citas.
- `api/dashboard/`: listado, filtros, paginacion y cambio de estado de citas.
- `api/gestion/`: CRUD administrativo y subida de imagenes.
- `api/seed.php`: crea/actualiza esquema y carga datos base desde `api/storage/gestion-data.json`.

## Base De Datos

La configuracion se lee desde variables de entorno, con fallback local:

- `DB_HOST`, fallback `localhost`
- `DB_NAME`, fallback `clinica_base`
- `DB_USER`, fallback `root`
- `DB_PASS`, fallback vacio

Para inicializar:

```powershell
php api/seed.php
```

Tablas detectadas en la auditoria: `clinics`, `specialties`, `doctors`, `users`, `services`, `testimonials`, `doctor_schedules`, `patients`, `appointments`, `appointment_status_history` y `audit_logs`.

Nota: el esquema actual crea indices, pero no declara claves foraneas reales. Ese punto sigue pendiente.

## Credenciales Demo Verificadas

En la BD local auditada solo verifico correctamente:

- Administracion: `admin` / `password123`

Las credenciales documentadas para `secretaria`, `dr.herrera` y `dr.vargas` no coinciden con los hashes actuales de la base de datos. Deben resetearse o documentarse con valores vigentes antes de validar flujos multirol manualmente.

## Pendientes Relevantes

Resueltos o mayormente resueltos:

- Migracion runtime a MySQL/PDO.
- Login con `password_hash` / `password_verify`.
- CSRF en endpoints mutables autenticados.
- Persistencia de citas en BD.
- Dashboard con filtros backend, busqueda, paginacion y tarjetas responsive.
- Auditoria basica en `audit_logs`.
- Horarios administrados para generar cupos.
- `prefers-reduced-motion` en CSS global.

Parciales:

- Configuracion por entorno: hay variables de entorno, pero no `.env` loader y quedan fallbacks hardcodeados.
- Migraciones: existen `CREATE IF NOT EXISTS` y ajustes idempotentes en `seed.php`, no migraciones versionadas.
- Reglas de agenda: valida futuro, especialidad, horario y conflictos, pero el buffer tiene casos borde con citas largas.
- Soft delete: existe en recursos de gestion, no como politica completa para citas/pacientes.
- Logo: hay helper central, pero header/footer aun imprimen etiqueta textual junto al helper.
- Utilidades JS: `ClinicaUtils.debounce` existe, pero dashboard conserva un `debounce` local.

Pendientes abiertos:

- Responsive mobile: corregir cortes horizontales en home, reservas y login.
- Accesibilidad por teclado en tarjetas seleccionables del stepper de reservas.
- Modal de Gestion con focus trap y confirmacion custom en lugar de `window.confirm()`.
- Resetear/documentar credenciales multirol.
- Claves foraneas reales en BD.
- Backups/exportacion antes de migraciones de datos.
- Pruebas automatizadas de smoke/API.
- Flujo de cancelacion y reprogramacion de citas.
- Reportes operativos y recordatorios por email/WhatsApp.

## Validaciones Ejecutadas

```powershell
php -l <todos los .php>
node --check <todos los .js>
php -r "json_decode(file_get_contents('api/storage/gestion-data.json'), true); ..."
```

Resultados:

- PHP lint: OK en 24 archivos.
- Node syntax: OK en 17 archivos.
- JSON `gestion-data.json`: OK.
- Conexion DB: OK.
- Smoke rutas publicas: OK.
- CSRF con token invalido: 403 esperado.

Tambien se tomaron capturas headless con Chrome en viewports desktop y mobile. Las capturas confirmaron problemas responsive en mobile.

## Directrices Para Siguientes Cambios

- No ocultar overflow horizontal como solucion final; corregir el ancho real de header, hero, stepper, calendario y login.
- Mantener contratos JSON existentes para no reescribir frontend.
- Antes de tocar reglas de citas, cubrir con pruebas al menos login, reserva, dashboard GET, cambio de estado y permisos por rol.
- Reutilizar `public/css/style.css`, `components.css`, header/footer y `ClinicaUtils` antes de crear nuevos patrones.
- No reemplazar la arquitectura por un framework sin una decision explicita del proyecto.

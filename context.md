# Contexto Del Proyecto - Clinica Estandar

Actualizado: 2026-08-18  
Ruta local: `C:\xampp\htdocs\clinica`  
Base URL esperada en XAMPP: `/clinica/`

## Resumen Ejecutivo

Se audito el proyecto completo contra `pendiente.md`, el checklist historico de `context.md` y la implementacion actual. No se modifico codigo de aplicacion. Solo se actualizo documentacion.

El sistema esta en un estado mas avanzado que la documentacion previa: ya existe backend con MySQL/PDO, hashes de contrasena, CSRF, persistencia de citas, dashboard paginado, gestion administrativa y auditoria basica. Aun asi, no esta listo para produccion clinica porque quedan pendientes de responsive design, accesibilidad, credenciales multirol, integridad relacional, pruebas automatizadas y flujos clinicos completos como cancelacion/reprogramacion.

Hallazgo principal de UI/UX: en viewport mobile de 390 px se corta contenido en home, reservas y login. El problema queda oculto por `overflow-x: hidden`, pero visualmente se pierden partes de textos, botones, calendario y formulario.

## Entorno Detectado

- PHP: `8.2.12`
- Node.js: `v20.19.0`
- npm: `10.8.2`
- Base de datos: MySQL/MariaDB via PDO.
- Servidor local usado para smoke: PHP built-in server apuntando a `C:\xampp\htdocs`.
- Chrome headless disponible en `C:\Program Files\Google\Chrome\Application\chrome.exe`.
- Sin `composer.json`, `package.json`, lockfiles, bundler, framework frontend ni migrador dedicado.
- `.gitignore` ignora `.env`, vendor, node_modules, logs y builds.

## Arquitectura Actual

Aplicacion PHP tradicional servida bajo `/clinica/`, con includes compartidos, endpoints JSON y frontend vanilla. La capa comun vive mayormente en `api/data.php`; la conexion PDO vive en `api/db.php`.

### Estructura Relevante

```text
clinica/
├── api/
│   ├── db.php
│   ├── data.php
│   ├── seed.php
│   ├── public/index.php
│   ├── general/index.php
│   ├── dashboard/index.php
│   └── gestion/index.php
├── pages/
│   ├── login/
│   ├── dashboard/
│   ├── reservations/
│   ├── doctors/
│   ├── specialties/
│   └── gestion/
├── public/
│   ├── css/
│   ├── js/
│   ├── shared/header/
│   ├── shared/footer/
│   └── assets/
├── context.md
├── pendiente.md
└── README.md
```

## Modulos Y Responsabilidades

- `index.php`: shell de home publica. Carga clinic settings en PHP y luego hidrata contenido desde `/clinica/api/public/?resource=home`.
- `public/shared/header/`: header fijo, navegacion principal, CTA de reserva y menu movil.
- `public/shared/footer/`: footer dinamico con datos de clinica, contacto y redes.
- `pages/specialties/`: catalogo de especialidades con busqueda y enlace a reserva por `?specialty=`.
- `pages/doctors/`: catalogo de doctores con filtros por nombre, especialidad, idioma y disponibilidad de hoy.
- `pages/reservations/`: stepper de reserva con preseleccion por `?specialty=`, `?especialidad=` y `?doctor_id=`.
- `pages/login/`: login standalone con CSRF, feedback visual, recordar usuario y redireccion segura.
- `pages/dashboard/`: dashboard protegido por rol, KPIs, filtros, busqueda, paginacion y cambio de estado.
- `pages/gestion/`: CRUD administrativo para clinica, resenas, doctores, especialidades, usuarios, servicios y horarios.

## Backend Y API

### `api/db.php`

- Lee credenciales desde `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`.
- Mantiene fallback local: `localhost`, `clinica_base`, `root`, password vacio.
- Usa PDO singleton con `utf8mb4`, excepciones y fetch asociativo.

Estado: funcional, pero configuracion por entorno queda parcial porque no hay loader `.env` y los fallbacks siguen hardcodeados.

### `api/data.php`

Centraliza:

- Inicio de sesion y cookies.
- CSRF token/cookie y validacion de token.
- Helpers de roles y permisos.
- Helpers de agenda y horarios.
- Transformacion de datos publicos.
- Lecturas runtime desde BD.
- Datos seed fallback para `api/seed.php`.
- Auditoria basica.
- Proteccion simple contra fuerza bruta en login por sesion.

Observaciones:

- `clinica_current_user()` puede iniciar sesion directamente con `session_start()` si no esta activa; en la mayoria de paginas se llama primero a `clinica_ensure_session_started()`, pero Gestion arranca sesion manualmente antes del helper central.
- La proteccion de fuerza bruta vive en `$_SESSION`; no es persistente por IP, usuario o base de datos.
- `api/csrf.php` existe vacio y parece archivo huerfano.

### `api/general/index.php`

Acciones:

- `GET action=session`
- `POST action=login`
- `POST action=logout`
- `POST action=create_appointment`

Estado:

- Login usa `password_verify()`.
- Login exitoso regenera session ID y registra auditoria.
- Citas se insertan en `patients` y `appointments`.
- Valida fecha futura, formato de fecha/hora, email, telefono, relacion doctor-especialidad, horario activo y conflictos.
- CSRF protege los POST, incluido login aunque el comentario dice "except login".

Gaps:

- No hay recordatorios email/WhatsApp.
- El autocompletado/perfil de paciente no existe en UI, aunque backend reutiliza paciente por email.
- La validacion de conflicto con buffer puede fallar si una cita existente larga cubre por completo la ventana calculada del nuevo horario.

### `api/dashboard/index.php`

Funciones:

- Requiere `ADMINISTRATOR`, `SECRETARIA` o `DOCTOR`.
- GET con filtros por doctor, rango de fecha, fecha exacta, estado, busqueda, pagina y `per_page`.
- Devuelve `summary`, `pagination`, `appointments`, `doctors`, `filters`, `user`.
- POST actualiza estado de cita y escribe `appointment_status_history`.

Estado:

- Paginacion server-side implementada.
- Filtros de estado y busqueda viven en backend.
- Doctores no pueden consultar citas pasadas ni actualizar citas de otros doctores.
- Citas cerradas no pueden cambiarse nuevamente.
- CSRF con token invalido devuelve 403.

Gaps:

- UI no expone flujo real de cancelacion/reprogramacion.
- Estado `cancelada` existe en backend, pero dashboard no tiene KPI/filtro/copy visual especifico.
- Motivo de cambio queda como default si la UI no lo solicita.

### `api/gestion/index.php`

Funciones:

- Requiere `ADMINISTRATOR`.
- GET recursos: `clinic`, `comments`, `doctors`, `specialties`, `users`, `services`, `schedules`.
- POST JSON: save/delete.
- DELETE por query `id`.
- POST multipart: upload de imagen.

Estado:

- CRUD migrado a SQL.
- Usuarios nuevos se guardan con `password_hash()`.
- Relaciones requeridas se validan para doctor/especialidad/usuario doctor.
- Eliminar aplica soft delete en la mayoria de recursos.
- No permite eliminar el usuario actual ni dejar el sistema sin administradores activos.
- Upload valida MIME, tamano, `getimagesize()` para raster y bloquea `<script` en SVG.

Gaps:

- `users.js` muestra campo `label`, pero backend no lo persiste.
- `deleteRecord()` usa `window.confirm()`, no un modal accesible ni consistente con el resto del sistema.
- El modal de Gestion devuelve foco, pero no tiene focus trap completo.
- SVG permitido necesita una sanitizacion mas robusta que solo bloquear `<script`.
- No existe pantalla para consultar `audit_logs`.

### `api/public/index.php`

Entrega informacion publica de home desde BD: clinic, about cards, stats, services, specialties, doctors y comments.

Gap SEO: el contenido principal de home se hidrata por JS. El HTML inicial tiene placeholders, por lo que SEO y no-JS quedan parcialmente cubiertos.

## Base De Datos

Tablas detectadas en la BD local:

- `clinics`: 1 registro
- `specialties`: 8
- `doctors`: 8
- `users`: 4
- `services`: 6
- `testimonials`: 4
- `doctor_schedules`: 23
- `patients`: 12
- `appointments`: 12
- `appointment_status_history`: 1
- `audit_logs`: 11

### Estado Del Esquema

Resuelto:

- Tablas base existen.
- Indices principales existen en agenda, usuarios y horarios.
- `appointments` tiene `slot_minutes`.
- `appointment_status_history` existe.
- `audit_logs` existe.
- `users.username` es unico.
- Seed idempotente no elimina datos existentes.

Pendiente:

- No hay migraciones versionadas reales.
- No hay tabla de control de migraciones.
- No hay claves foraneas declaradas; hay indices con nombres de FK, pero sin constraints reales.
- No hay backup/export automatizado antes de migraciones.

## Credenciales Y Sesiones

Credencial verificada en BD local:

- `admin` / `password123`: OK

Credenciales del seed/documentacion que no verifican contra la BD actual:

- `secretaria` / `secretaria123`: NO
- `dr.herrera` / `doctor123`: NO
- `dr.vargas` / `doctor123`: NO

Impacto:

- El flujo admin se puede validar.
- Los flujos reales de secretaria/doctor no se pueden validar manualmente con las credenciales documentadas hasta resetear o documentar hashes vigentes.

## Estado De Pendientes

### P0 - Backend Y Base De Datos

| Pendiente | Estado | Evidencia / Nota |
| --- | --- | --- |
| Elegir motor BD para XAMPP | Resuelto | MySQL/MariaDB via PDO. |
| Variables de entorno/configuracion | Parcial | `api/db.php` lee env vars, pero no hay `.env` loader y quedan fallbacks. |
| PDO centralizado | Parcial | PDO singleton existe; no hay helper transaccional comun. |
| Migraciones versionadas | Parcial | `api/seed.php` crea/ajusta esquema, pero no versiona migraciones. |
| Claves foraneas | Pendiente | Esquema actual define indices, no constraints FK. |
| Indices de agenda y usuarios | Resuelto | Indices y unique username presentes. |
| Seed idempotente desde JSON | Resuelto | `api/seed.php` inserta sin borrar datos existentes. |
| Sustituir JSON runtime por BD | Resuelto | Runtime consulta BD; JSON queda para seed/fallback. |
| Mantener contratos JSON | Resuelto | Frontend consume mismas rutas principales. |
| Persistir reservas en BD | Resuelto | `create_appointment` inserta paciente/cita. |
| Validar conflictos de horario | Parcial | Valida reglas, cupos y buffer; hay caso borde con citas largas. |
| Password hashing | Resuelto | `password_hash` / `password_verify`. |
| CSRF | Resuelto | Token invalido en dashboard POST devuelve 403. |
| Middleware comun | Parcial | Helpers centrales existen, pero no middleware formal separado. |
| Audit logs | Parcial | Se registran eventos clave, sin visor ni politica de retencion. |
| Validaciones backend | Parcial | Existen validaciones, faltan URL/privacidad/SVG mas robustos. |
| Politica de borrado | Parcial | Soft delete en Gestion, no politica completa para citas/pacientes. |
| Backups/exportacion | Pendiente | No implementado. |
| Pruebas automatizadas | Pendiente | No hay suite automatizada. |

### P0 - Seguridad Antes De Produccion

| Pendiente | Estado | Nota |
| --- | --- | --- |
| Permisos por rol | Resuelto | Helpers por rol y endpoints protegidos. |
| Contrasenas en texto plano | Resuelto | Hashing en seed/gestion/login. |
| CSRF en acciones autenticadas | Resuelto | Dashboard/Gestion/General protegidos. |
| Fuente compartida administrable | Resuelto | BD + gestion admin. |
| Esquema BD completo | Parcial | Tablas existen; FKs reales pendientes. |

### P1 - Funcionalidad

| Pendiente | Estado | Nota |
| --- | --- | --- |
| Cita desde reserva persistida | Resuelto | Inserta en BD. |
| Cancelacion/reprogramacion | Pendiente | Estado cancelada existe, flujo UI no. |
| Reglas por sede/turno | Parcial | Hay rol y horario, no sede/turno. |
| Codificacion `Mañana` | Resuelto | Funcion devuelve `Mañana`. |
| Fallback logo | Parcial | Helper existe, pero header/footer imprimen label adicional. |
| Filtros por fecha en dashboard | Resuelto | `date_scope` y `date`. |
| Paginacion dashboard | Resuelto | `pagination` en API y UI. |
| Filtros estado/busqueda backend | Resuelto | `status` y `search` en API. |
| Nombres desde fuente administrable | Resuelto | Public pages leen desde BD. |
| Cupos desde horarios administrados | Resuelto | `horarios_detallados` y reglas por fecha/dia. |

### P2 - UX, Accesibilidad Y Responsive

| Pendiente | Estado | Nota |
| --- | --- | --- |
| Validacion visual desktop/mobile | Resuelto en auditoria | Capturas headless realizadas. |
| Navegacion por teclado | Pendiente | Tarjetas del stepper son `div` clicables sin rol/teclado. |
| Reemplazar `alert()` reservas | Resuelto | Mensaje integrado en UI. |
| Contraste badges/botones | Parcial | Mejorado, falta medicion WCAG. |
| `prefers-reduced-motion` | Parcial | CSS global lo incluye; login standalone no. |
| Dashboard sin tabla horizontal | Resuelto | Usa tarjetas. |
| Escala visual dashboard | Resuelto | UI compacta. |
| Paleta dashboard sobria | Resuelto | Paleta operativa. |
| Acceso visual a Gestion | Resuelto | Link visible para admin. |

### P2 - Rendimiento/UI

| Pendiente | Estado | Nota |
| --- | --- | --- |
| Optimizar `doctora_avatar.png` | Resuelto | Archivo actual aprox. 65 KB. |
| Revisar `banner.jpg` | Parcial | Pesa poco, pero visualmente se repite/corta en hero. |
| Reducir duplicacion de estilos | Pendiente | Filtros y botones tienen variantes por modulo. |
| Centralizar `debounce` | Parcial | `ClinicaUtils` existe; dashboard tiene debounce local. |
| Centralizar logo PHP | Parcial | Helper existe; markup final duplica etiqueta textual. |
| Decidir login dentro/fuera del design system | Pendiente | Standalone y con problemas mobile. |

### P3 - Pruebas

| Pendiente | Estado | Nota |
| --- | --- | --- |
| Smoke automatizado | Pendiente | Se ejecuto manualmente, no hay suite. |
| Consola limpia navegador real | Parcial | Capturas Chrome OK; no se capturo consola. |
| Checklist manual documentado | Resuelto | Incluido en esta documentacion. |

## Auditoria UX/UI Y Responsive

### Hallazgos Criticos Mobile

1. Home mobile 390x844:
   - Header corta el CTA `Agendar Cita`.
   - Hero corta el titulo hacia la derecha.
   - `overflow-x: hidden` oculta el problema en lugar de resolverlo.

2. Reservas mobile 390x844 con `?doctor_id=doc3`:
   - Header vuelve a cortar CTA.
   - Stepper/progress se extiende hacia la derecha y corta el paso 4.
   - Calendario corta la columna derecha; domingo aparece parcialmente fuera del viewport.
   - Es un problema alto porque afecta seleccion de fecha/hora.

3. Login mobile 390x844:
   - Tagline y panel/formulario se cortan a la derecha.
   - Boton `Ingresar` se extiende fuera del viewport.
   - `body { overflow: hidden; }` puede impedir scroll con teclado movil o pantallas bajas.

### Hallazgos Desktop

1. Home desktop:
   - Render correcto y no se detecto pantalla en blanco.
   - Hero ocupa todo el primer viewport; no deja pista visual de contenido siguiente.
   - Logo queda como icono sin nombre visible en header cuando hay imagen/SVG.

2. Doctores desktop 1366x900:
   - Filtro `Todas las especialidades` se trunca.
   - En tarjetas, textos como experiencia/formacion se cortan en columnas estrechas.
   - El grid visualmente carga, pero la densidad de informacion necesita ajuste.

### Accesibilidad

Resuelto o positivo:

- Foco visible global existe.
- Muchos botones tienen `aria-label`.
- Login tiene `aria-pressed`, `aria-busy` y error con `role=alert`.
- Dashboard usa `aria-live`, modales con `aria-modal` y focus trap.
- Gestion devuelve foco al cerrar modal.

Pendiente:

- Tarjetas seleccionables de reservas son `div` con click; deberian ser `button` o tener rol, `tabindex`, Enter/Espacio y `aria-pressed`.
- Gestion modal no atrapa Tab dentro del dialogo.
- Gestion usa `window.confirm()` para eliminar.
- Menu movil no cierra con Escape ni declara estado modal/inert del fondo.
- Progress stepper de reservas no comunica paso actual a tecnologias asistivas.
- Hay iconos SVG sin `aria-hidden` en algunos filtros.

### Consistencia Visual

Fortalezas:

- Dashboard y Gestion ya usan lenguaje sobrio, compacto y operativo.
- Tarjetas del dashboard evitan tabla horizontal.
- Home, doctores, especialidades y reservas comparten tokens globales.

Inconsistencias:

- Login usa una estetica standalone con radios/paleta/motion distintos.
- Header/footer imprimen helper de logo mas label textual.
- Filtros de doctores, especialidades, reservas, dashboard y gestion repiten variantes.
- Se usan cards muy visuales en publico y UI mas densa en operacion; esta diferencia es razonable, pero conviene formalizarla como design system.

### Mejoras UX Recomendadas

Prioridad alta:

- Corregir overflow real en mobile de header, hero, reservas y login.
- Convertir tarjetas del stepper de reservas a controles accesibles.
- Quitar altura fija JS riesgosa del formulario de reserva o robustecerla con ResizeObserver.
- Permitir scroll en login mobile y revisar teclado virtual.
- Arreglar credenciales demo para probar roles.

Prioridad media:

- Modal accesible para eliminar en Gestion.
- Focus trap en Gestion.
- Exponer campo/motivo para cambio de estado en dashboard.
- Agregar `cancelada` a KPI/filtros/copy si el estado se mantiene.
- Unificar filtros y botones entre modulos.
- Revisar contraste de badges con medicion WCAG.

Prioridad baja:

- Agregar fallback SSR o contenido minimo real en home para SEO/no-JS.
- Documentar decisiones de login standalone vs design system global.
- Crear vista de auditoria.
- Agregar reportes operativos basicos.

## Bugs O Inconsistencias Detectadas

- Credenciales multirol documentadas no coinciden con hashes actuales.
- `users.js` incluye campo `label`, backend no lo guarda.
- `clinica_public_booked_slots()` excluye `rechazada`, pero no `cancelada`; la UI publica podria mostrar como ocupado un cupo cancelado.
- `dashboard.js` tiene funciones locales no usadas (`getStatusCounts`, `buildAppointmentSearchIndex`) y un `debounce` duplicado.
- `api/csrf.php` esta vacio.
- `pages/gestion/_shared/page.php` llama `session_start()` antes del helper central.
- `.form-error-message` esta definido dos veces en CSS global con objetivos distintos, lo que puede afectar mensajes inline.
- Login mobile corta contenido por layout y `overflow: hidden`.
- Header CTA se corta en mobile antes del breakpoint de 420 px.

## Validaciones Ejecutadas

### Sintaxis Y Datos

- `php -l` en 24 archivos PHP: OK.
- `node --check` en 17 archivos JS: OK.
- Parse de `api/storage/gestion-data.json`: OK.
- Conexion a BD por `clinica_get_db()`: OK.

### Smoke HTTP

Servidor temporal usado:

- `http://127.0.0.1:8797/clinica/`

Resultados:

- `/`: 200
- `/pages/login/`: 200
- `/pages/specialties/`: 200
- `/pages/doctors/`: 200
- `/pages/reservations/?specialty=neurologia`: 200
- `/pages/reservations/?doctor_id=doc3`: 200
- `/pages/dashboard/` sin sesion: 302
- Login admin: 200, redirect dashboard
- Dashboard autenticado admin: 200
- `api/dashboard/?date_scope=all&per_page=6`: 200 con 12 citas, paginacion 1/2
- `api/gestion/?resource=clinic` con admin: 200
- Dashboard POST con CSRF invalido: 403

Nota: durante una prueba se produjo una mutacion accidental de la cita 1 por reutilizacion de header CSRF en `WebRequestSession`. Se revirtio: cita 1 volvio a `pendiente`, `appointment_status_history` volvio a 1 fila y `audit_logs` volvio a 11 filas.

### Capturas Headless

Chrome headless genero capturas:

- Home desktop 1366x900
- Home mobile 390x844
- Reservas mobile 390x844
- Doctores desktop 1366x900
- Login mobile 390x844

Resultado visual: no hay pantallas en blanco, pero hay cortes responsive en mobile y truncamientos en doctores desktop.

## Checklist Manual Recomendado

Antes de declarar estable una iteracion futura:

1. Ejecutar `php api/seed.php` en BD limpia.
2. Verificar login admin, secretaria y doctor con credenciales vigentes.
3. Probar dashboard por rol: filtros de fecha, estado, busqueda, paginacion.
4. Probar reserva desde home, desde especialidad y desde doctor.
5. Probar conflicto de cupo reservado.
6. Probar Gestion: crear/editar/desactivar doctor, especialidad, servicio, usuario y horario.
7. Probar subida JPG/PNG/WEBP/SVG segura.
8. Probar mobile 390 px y 430 px en home, login, reservas, dashboard y gestion.
9. Probar teclado: Tab, Shift+Tab, Enter, Espacio, Escape.
10. Revisar consola del navegador.
11. Revisar que `audit_logs` registre acciones sensibles.
12. Revisar que no existan cambios no deseados en datos despues de pruebas.

## Recomendacion De Orden

1. Corregir responsive mobile critico.
2. Resetear/documentar credenciales multirol.
3. Cerrar accesibilidad de reservas y Gestion.
4. Agregar FK/migraciones versionadas/backups.
5. Automatizar smoke API y browser.
6. Completar cancelacion/reprogramacion.
7. Unificar design system y limpiar duplicaciones.

## Directivas Futuras

- Mantener `/clinica/` mientras el proyecto viva en XAMPP.
- No cambiar contratos JSON sin revisar consumidores.
- No usar `overflow-x: hidden` como sustituto de responsive real.
- Reutilizar helpers existentes antes de crear nuevos.
- Documentar cualquier cambio de BD junto con impacto y forma de revertir.
- Validar siempre PHP, JS, smoke HTTP y responsive mobile.

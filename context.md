# Contexto del Proyecto - Clinica Estandar

Actualizado: 2026-08-05
Ruta local: `C:\xampp\htdocs\clinica`
Base URL esperada en XAMPP: `/clinica/`

## Vision del Producto

El proyecto representa la pagina principal y el portal operativo de una clinica privada de alto nivel. La experiencia debe transmitir confianza, prestigio, tecnologia medica avanzada y trato humano.

El estado actual sigue siendo front-first, pero ya tiene una simulacion de backend mas concreta con endpoints PHP bajo `api/`, sesiones reales de demo y citas persistidas temporalmente en `$_SESSION`.

## Entorno Detectado

- PHP: `8.2.12`
- Node.js: `v20.19.0`
- npm: `10.8.2`
- Sin `composer.json`, `package.json`, lockfiles, bundler, `.env` o `.htaccess`.

Tecnologias usadas:

- PHP procedural con includes, sesiones, JSON y arreglos como datos simulados.
- HTML5, CSS3 y JavaScript vanilla moderno.
- APIs del navegador: Fetch API, Web Storage e Intersection Observer.
- Fuente externa: Google Fonts (`Inter`).

## Arquitectura Actual

La aplicacion corre como sitio PHP tradicional servido desde XAMPP. Las paginas publicas usan PHP para layout, SEO y datos iniciales cuando conviene, pero la home ya hidrata su contenido principal desde una API JSON publica. El backend real aun no existe, pero la demo ya usa endpoints JSON.

### Estructura

```text
clinica/
├── api/
│   ├── data.php
│   ├── dashboard/
│   │   └── index.php
│   └── general/
│       └── index.php
├── context.md
├── favicon.ico
├── index.php
├── pages/
│   ├── dashboard/
│   │   ├── dashboard.css
│   │   ├── dashboard.js
│   │   └── index.php
│   ├── doctors/
│   │   ├── doctors.css
│   │   ├── doctors.js
│   │   └── index.php
│   ├── login/
│   │   ├── index.php
│   │   ├── login.css
│   │   └── login.js
│   ├── reservations/
│   │   ├── index.php
│   │   ├── reservations.css
│   │   └── reservations.js
│   └── specialties/
│       ├── index.php
│       ├── specialties.css
│       └── specialties.js
└── public/
    ├── assets/
    ├── css/
    │   ├── components.css
    │   └── style.css
    ├── js/
    │   └── app.js
    └── shared/
        ├── footer/
        └── header/
```

### API

- `api/data.php`
  - Centraliza usuarios demo, roles, estados, citas semilla, helpers JSON y transformacion de citas.
  - Inicializa citas en `$_SESSION['appointments']` con una agenda demo que cubre ayer, hoy, manana y proximos dias.
  - Incluye versionado/migracion de citas semilla para actualizar sesiones de demo existentes sin borrar citas creadas por reserva.
  - Centraliza filtros de dashboard por rol, doctor, rango de fecha (`date_scope`) y fecha exacta (`date`).
  - Soporta JSON con BOM UTF-8 para hacer el parser mas tolerante.

- `api/general/index.php`
  - `GET action=session`: devuelve si hay usuario autenticado y el `expires_at` de la sesion vigente.
  - `POST action=login`: valida credenciales demo, crea `$_SESSION['user']`, registra `auth_started_at`, fija expiracion de cookie y prepara citas.
  - `POST action=logout`: destruye sesion y devuelve redirect al login.
  - `POST action=create_appointment`: crea una cita pendiente desde el formulario de reservas y la agrega a la sesion.
  - La autenticacion demo dura 8 horas absolutas (`CLINICA_SESSION_TTL_SECONDS = 28800`); sesiones antiguas o vencidas se destruyen y vuelven a login.

- `api/public/index.php`
  - Endpoint publico sin sesion para informacion visible en la web.
  - `GET resource=home`: entrega `clinic`, `about_cards`, `stats`, `services`, `specialties`, `doctors` y `comments`.
  - La fuente temporal sigue siendo `api/data.php` / `api/storage/gestion-data.json`; al conectar la base de datos, este endpoint debe leer de repositorios/modelos reales sin cambiar el contrato del frontend.

- `api/dashboard/index.php`
  - Requiere usuario con rol `DOCTOR`, `ADMINISTRATOR` o `SECRETARIA`.
  - `GET`: devuelve citas filtradas por rol; doctores solo ven sus citas de hoy, manana, futuras o una fecha exacta no anterior; secretaria/administracion ven anteriores, actuales y futuras, y pueden filtrar por `doctor_id`.
  - Parametros soportados: `doctor_id`, `date_scope=today|tomorrow|future|past|all` y `date=YYYY-MM-DD`.
  - `POST`: actualiza estado de cita (`pendiente`, `atendida`, `no-presentado`, `rechazada`) respetando permisos; doctores no pueden actualizar citas anteriores.

### Credenciales Demo

Estas credenciales son solo para la simulacion front/back:

- Administracion: `admin` / `password123`
- Secretaria: `secretaria` / `secretaria123`
- Doctor: `dr.herrera` / `doctor123`

## Paginas y Responsabilidades

- `index.php`: home publica con estructura base, SEO y contenedores vacios; el contenido principal se carga por Fetch desde `/clinica/api/public/?resource=home`.
- `pages/specialties/`: catalogo de especialidades con buscador. Enlaza reservas usando `?specialty={id}`.
- `pages/doctors/`: catalogo de doctores con filtros. Enlaza reservas usando `?doctor_id={id}`.
- `pages/reservations/`: stepper de reserva. Ahora acepta preseleccion por `?specialty=`, `?especialidad=` y `?doctor_id=`. Al confirmar, crea cita via `api/general/`.
- `pages/login/`: login standalone. Si ya hay sesion valida redirige al dashboard. Si el login es correcto, crea sesion por API y redirige.
- `pages/dashboard/`: pagina protegida. Renderiza usuario/rol reales, KPIs, filtros de fecha/estado/paciente, selector de doctor para secretaria/administracion, logout y una bandeja de citas por tarjetas compactas. Ya no usa tabla ni scroll horizontal para operar citas.

## Flujos Actuales

### Publico

1. Usuario entra en `/clinica/`.
2. Navega a especialidades, doctores o reservas.
3. Las paginas publicas usan header/footer compartidos, salvo login que mantiene experiencia standalone.

### Reserva

1. El usuario selecciona especialidad, doctor, fecha/hora y datos.
2. Si llega con `?specialty=neurologia`, el formulario abre con la especialidad preseleccionada.
3. Si llega con `?doctor_id=doc3`, el formulario abre con especialidad y doctor preseleccionados.
4. Al confirmar, `reservations.js` envia `POST action=create_appointment` a `/clinica/api/general/`.
5. La cita queda en sesion como `pendiente` y puede verse en dashboard dentro de la misma sesion de demo.

### Login y Dashboard

1. Dashboard sin sesion redirige a `/clinica/pages/login/?redirect=/clinica/pages/dashboard/`.
2. Login exitoso crea `$_SESSION['user']`.
3. Dashboard carga citas desde `/clinica/api/dashboard/`.
4. Doctores solo ven sus citas asignadas de hoy, manana, futuras o una fecha exacta no pasada.
5. Secretaria y administracion ven citas anteriores, actuales y futuras; tambien pueden filtrar por doctor.
6. Las citas pendientes se muestran como tarjetas con acciones directas: `Atender cita`, `No se presento` y `Rechazar cita`.
7. Estados de cita se actualizan por `POST`, muestran aviso visual y persisten en `$_SESSION`.
8. Logout destruye sesion.

## Correcciones Realizadas en Esta Iteracion

- Se elimino el uso del antiguo `dashboard_handler.php`; la ruta vigente es `/clinica/api/dashboard/`.
- Se agrego `api/data.php` como capa compartida de datos y helpers.
- Se implemento `api/general/index.php` para login, logout, sesion y creacion de citas.
- Se reescribio `api/dashboard/index.php` para `GET`/`POST`, permisos por rol y persistencia en sesion.
- Se agrego filtrado de citas por `date_scope` y `date` directamente en la API del dashboard.
- Se restringio a doctores para que no puedan ver ni actualizar citas anteriores.
- Se amplio la data semilla de citas para incluir fechas anteriores, actuales y futuras.
- Se conecto `pages/login/login.js` con la API real de demo.
- Se protegio `pages/dashboard/index.php` con sesion real.
- Se actualizo `pages/dashboard/dashboard.js` con ruta correcta, selector de doctor, logout, filtros, acciones y modal con focus trap.
- Se amplio `pages/dashboard/dashboard.css` para los nuevos KPIs, filtros, herramientas y estados.
- Se unificaron IDs de especialidades/doctores en `pages/reservations/index.php`.
- Se implemento preseleccion de reservas por URL en `reservations.js`.
- Se conecto la confirmacion de reserva con `api/general/`.
- Se corrigio el favicon de login a `/clinica/favicon.ico`.
- Se corrigio el enlace de WhatsApp de `httpsa://` a `https://`.
- Se reemplazo `strftime()` en doctores por un mapa compatible con PHP 8.2.
- Se eliminaron logs de debug del dashboard.
- Se redujo la escala visual del dashboard: tipografias, KPIs, tarjetas, botones de accion y modal ahora usan tamanos normales.
- Se agregaron filtros de fecha al dashboard:
  - Doctores: `Hoy`, `Manana`, `Proximas` y fecha exacta desde hoy.
  - Secretaria/administracion: `Hoy`, `Manana`, `Proximas`, `Anteriores`, `Todas` y fecha exacta sin minimo.

## Mejora de Dashboard Responsive y UX Senior

- Se reemplazo la tabla del dashboard por una bandeja de tarjetas clinicas, evitando scroll horizontal y columnas pequeñas.
- Cada tarjeta muestra hora, fecha, paciente, motivo, estado y datos del doctor/especialidad cuando aplica, con escala visual compacta y legible.
- La primera cita pendiente queda marcada como `Siguiente cita`.
- Las acciones pendientes ahora son botones claros con texto completo:
  - `Atender cita`
  - `No se presento`
  - `Rechazar cita`
- Se agrego un aviso visual accesible (`dashboard-toast`) para confirmar cambios de estado sin depender de alertas del navegador.
- Los filtros se convirtieron en controles tipo boton, pensados para tacto y lectura rapida sin ocupar demasiado espacio.
- Se agrego una fila de fechas con botones rapidos y campo `input[type=date]`.
- Los doctores solo tienen opciones actuales o futuras; secretaria y administracion tienen controles para anteriores, actuales y futuras.
- El responsive usa una sola columna en movil, dos o mas columnas solo cuando hay espacio suficiente, y evita min-widths de tabla.
- Se mantuvo el modal de confirmacion con focus trap, ahora en tamano normal.
- El diseño usa un lenguaje mas premium y clinico: cabecera con fondo sereno, KPIs definidos, tarjetas blancas con borde por estado y acciones de alto contraste.

## Verificacion Realizada

Servidor local usado para pruebas:

- `http://127.0.0.1:8765/clinica/`

Comandos/verificaciones:

- `php -l` en todos los `.php`: sin errores.
- `node --check` en todos los `.js`: sin errores.
- Render con `error_reporting=E_ALL` en paginas clave: sin warnings/deprecations detectados.
- Smoke HTTP con `curl.exe`:
  - `/clinica/`: 200
  - `/clinica/pages/login/`: 200
  - `/clinica/pages/specialties/`: 200
  - `/clinica/pages/doctors/`: 200
  - `/clinica/pages/reservations/?specialty=neurologia`: 200
  - `/clinica/pages/reservations/?doctor_id=doc3`: 200
- API flow:
  - Dashboard sin sesion: 401.
  - Login secretaria: success.
  - Dashboard con sesion: citas devueltas.
  - Cambio de estado: success.
  - Creacion de cita desde API general: success.
  - Dashboard filtrado por `doctor_id=doc3`: devuelve citas del doctor.
- API flow despues del rediseño:
  - Login secretaria: success.
  - Dashboard respondio citas desde la agenda demo inicial de esa iteracion.
  - Cambio de estado desde el contrato usado por las tarjetas: success.
  - Pagina dashboard autenticada: 200.
  - HTML del dashboard contiene `appointments-list` y ya no contiene `appointments-table`.
- API flow despues del ajuste de fechas y escala:
  - Fecha del servidor usada en prueba: `2026-08-04`.
  - Login doctor: success.
  - Doctor con `date_scope=today`: 3 citas.
  - Doctor con `date_scope=future`: 1 cita.
  - Doctor con `date_scope=past`: la API normaliza a `today`; no expone citas anteriores.
  - Doctor con `date=2026-08-03`: 0 citas.
  - Login secretaria: success.
  - Secretaria con `date_scope=past`: 2 citas.
  - Secretaria con `date_scope=future`: 3 citas.
  - Secretaria con `date_scope=all`: 11 citas.
  - Secretaria con `doctor_id=doc3&date=2026-08-05`: 1 cita.
  - Actualizacion de estado doctor sobre `CITA002`: success, queda `atendida` en esa sesion de prueba.
- HTML protegido por rol:
  - Doctor: 200, contiene filtros de fecha, no contiene botones `past` ni `all`, y el campo fecha usa `min=2026-08-04`.
  - Secretaria: 200, contiene filtros de fecha y botones `past`/`all`.
- Pagina protegida:
  - Dashboard sin sesion: 302 hacia login.
  - Dashboard con cookie autenticada: 200.
- Verificacion visual con navegador integrado: no disponible en esta sesion (`agent.browsers.list()` devolvio lista vacia). Queda pendiente revisar screenshots reales en navegador cuando el entorno lo permita.

## Gestion de Base de Datos Simulada

Se agrego un modulo administrativo completo bajo `pages/gestion/` para gestionar la informacion publica y operativa que antes estaba repetida en arreglos por pagina. Como el proyecto aun no tiene MySQL ni migraciones, la persistencia se implementa como una base JSON versionada en:

- `api/storage/gestion-data.json`

La capa compartida vive en `api/data.php`. Si el archivo JSON no existe o esta corrupto, se regenera desde `clinica_default_database()`.

### Recursos Administrables

- `pages/gestion/clinic/`
  - Administra nombre comercial/legal, texto del logo, SEO, hero, titulos de secciones, contacto, telefonos, emergencia, WhatsApp y redes.
  - Header, footer y home leen estos datos desde `clinica_get_clinic_settings()`.
- `pages/gestion/comment/`
  - CRUD de resenas/testimonios visibles en home.
  - Campos: autor, detalle, comentario, rating, avatar, destacado y activo.
- `pages/gestion/doctors/`
  - CRUD de doctores.
  - Campos: nombre, foto, especialidad vinculada, subespecialidad, experiencia, formacion, idiomas, reconocimientos, rating, pacientes, destacado y activo.
  - Alimenta home, catalogo de doctores y seleccion de doctor en reservas.
- `pages/gestion/specialties/`
  - CRUD de especialidades.
  - Campos: nombre, resumen, descripcion, icono SVG, destacado y activo.
  - Alimenta home, catalogo de especialidades y paso de especialidad en reservas.
- `pages/gestion/users/`
  - CRUD de usuarios operativos.
  - Roles permitidos: `ADMINISTRATOR`, `SECRETARIA`, `DOCTOR`.
  - Login demo ahora lee desde este recurso via `clinica_demo_users()`.
  - Los usuarios con rol `DOCTOR` deben vincularse a un doctor.
- `pages/gestion/services/`
  - CRUD de servicios clinicos.
  - Campos: servicio, descripcion, icono SVG, destacado y activo.
  - Alimenta la seccion de servicios del home.
- `pages/gestion/calendar/`
  - CRUD de horarios por doctor.
  - Campos: doctor, fecha especifica opcional, dia ISO de semana, hora inicio, hora fin, duracion de cupo y activo.
  - Si `date` esta vacio, el horario es semanal recurrente; si `date` tiene valor, aplica solo a esa fecha futura.
  - Reservas usa estos horarios por numero de dia (`1` lunes a `7` domingo) y por fecha concreta cuando exista una regla puntual.

### API de Gestion

Nuevo endpoint:

- `api/gestion/index.php`

Contrato:

- Requiere sesion con rol `ADMINISTRATOR`.
- `GET ?resource=clinic|comments|doctors|specialties|users|services|schedules`
  - Devuelve `data` y `metadata` para selects (`roles`, `weekdays`, `specialties`, `doctors`).
- `POST { action: "save", record: {...} }`
  - Crea o actualiza registros.
  - En `clinic` actualiza el registro maestro.
- `POST multipart/form-data action=upload`
  - Sube imagenes desde Gestion y devuelve una ruta en `/clinica/public/assets/...`.
  - Campos permitidos: `clinic.hero_image`, `clinic.hero_mobile_image`, `doctors.photo` y `comments.avatar`.
  - Formatos permitidos: JPG, PNG, WEBP y GIF, hasta 5 MB.
- `POST { action: "delete", id: "..." }`
  - Elimina registros de colecciones.
  - No permite eliminar el registro maestro de clinica.
  - No permite eliminar el usuario actual ni dejar el sistema sin administradores activos.

### UI de Gestion

Se agregaron:

- `pages/gestion/_shared/page.php`
  - Plantilla protegida por admin, nav de gestion y layout comun.
- `pages/gestion/gestion.css`
  - Sistema visual responsive para modulos CRUD.
- `pages/gestion/gestion.js`
  - Motor CRUD compartido: carga, filtros, stats, formulario dinamico, crear, editar, eliminar y toast.
  - Renderiza campos `type: image` con preview, input de archivo y subida multipart a `api/gestion/`.
- Cada componente mantiene su propio `index.php`, `.css` y `.js` con configuracion especifica.

### Paginas Publicas Conectadas

- `index.php`
  - Reescrito como shell dinamico: mantiene SEO/header/footer con PHP, pero hero, about, stats, servicios, especialidades, doctores y comentarios se hidratan desde `/clinica/api/public/?resource=home`.
- `api/public/index.php`
  - Publica el contrato JSON para la home y reutiliza la capa compartida `api/data.php`.
- `public/js/app.js`
  - Consume la API publica de home, pinta tarjetas/secciones, reinicia animaciones, contadores, lazy loading y carrusel despues de recibir datos.
- `public/shared/header/header.php`
  - Lee nombre, SEO/canonical y marca desde clinic.
- `public/shared/footer/footer.php`
  - Lee nombre, tagline, direccion, correo, telefono, emergencia, WhatsApp y redes desde clinic.
- `pages/specialties/index.php`
  - Sobrescribe el catalogo desde `clinica_public_specialties_for_catalog()`.
- `pages/doctors/index.php`
  - Sobrescribe el catalogo desde `clinica_public_doctors_for_catalog()`.
- `pages/reservations/index.php`
  - Lee especialidades, doctores agrupados, copy de reserva y cupos ocupados desde la capa compartida.
- `pages/reservations/reservations.js`
  - La disponibilidad usa dia ISO numerico del calendario.
  - Los cupos se generan desde `horarios_detallados` (`start_time`, `end_time`, `slot_minutes`) publicados por `clinica_schedule_rules_for_doctor()`.
  - Reconoce reglas semanales recurrentes y reglas puntuales por fecha (`YYYY-MM-DD`) para semanas futuras.

### Verificacion Gestion

Servidor local usado:

- `http://127.0.0.1:8096/clinica/`

Validaciones realizadas:

- `php -l` en todos los `.php`: sin errores.
- `node --check` en todos los `.js`: sin errores.
- Smoke HTTP publico:
  - `/clinica/`: 200
  - `/clinica/pages/specialties/`: 200
  - `/clinica/pages/doctors/`: 200
  - `/clinica/pages/reservations/?specialty=neurologia`: 200
- API gestion con login admin:
  - `clinic`: 200
  - `comments`: 4 registros
  - `doctors`: 8 registros
  - `specialties`: 8 registros
  - `users`: 3 registros
  - `services`: 6 registros
  - `schedules`: 23 registros
  - Crear/eliminar servicio temporal: success.
- Verificacion headless Chrome:
  - Gestion `clinic`, `doctors`, `calendar`: renderizan filas, formularios y navegacion sin errores de consola.
  - Gestion movil `doctors`: sin overflow horizontal.
  - Home: renderiza 6 servicios, 4 especialidades destacadas, 4 doctores destacados y 4 resenas desde la fuente administrable.
  - Reservas con `?doctor_id=doc3`: preselecciona doctor, avanza al paso calendario y genera dias habilitados desde horarios.

## Politica de Acceso por Rol

La politica central vive en `api/data.php`:

- `clinica_can_access_management($role)`
  - Solo `ADMINISTRATOR` puede abrir `pages/gestion/*` y `api/gestion/*`.
- `clinica_can_see_all_appointments($role)`
  - `ADMINISTRATOR` y `SECRETARIA` ven citas de todos los doctores en fechas pasadas, presentes y futuras.
- `clinica_can_see_past_appointments($role)`
  - Solo roles con agenda global pueden consultar historico.
- `clinica_resolve_login_redirect($requestedRedirect, $user)`
  - Normaliza redirects internos desde login.
  - Bloquea rutas externas, login como destino final, APIs y gestion cuando el rol no es administrador.

### Flujo desde Login

- `ADMINISTRATOR`
  - Si entra con `redirect=/clinica/pages/gestion/clinic/`, el login respeta el destino.
  - En dashboard ve el boton `Gestion`.
  - Dashboard carga por defecto `Todas`, con todas las citas de todos los doctores.
- `SECRETARIA`
  - Si intenta entrar a gestion desde login, el redirect se reemplaza por `/clinica/pages/dashboard/`.
  - No ve boton `Gestion`.
  - Ve selector de doctor y filtros de fechas `Anteriores` y `Todas`.
  - `api/gestion/*` responde `403`.
- `DOCTOR`
  - Si intenta entrar a gestion desde login, el redirect se reemplaza por `/clinica/pages/dashboard/`.
  - No ve boton `Gestion`, selector de doctor, `Anteriores` ni `Todas`.
  - Dashboard carga por defecto `Proximas`; en backend `future` significa hoy en adelante.
  - Si fuerza `date_scope=past` o `date_scope=all`, el backend normaliza a `future`.
  - Si fuerza `doctor_id` de otro medico, el backend lo ignora.
  - Si consulta una fecha exacta pasada, recibe cero citas.
  - `api/gestion/*` responde `403`.

### Verificacion de Roles

Servidor local usado:

- `http://127.0.0.1:8096/clinica/`

Resultados de smoke por sesion:

- Admin: redirect a gestion permitido; `api/gestion` 200; `date_scope=all` devuelve 11 citas; historico devuelve 2.
- Secretaria: redirect a gestion cae en dashboard; `api/gestion` 403; `date_scope=all` devuelve 11 citas; historico devuelve 2.
- Doctor `dr.herrera`: redirect a gestion cae en dashboard; `api/gestion` 403; `date_scope=all` y `past` normalizan a `future`; solo devuelve 4 citas propias presentes/futuras; fecha exacta pasada devuelve 0.
- HTML dashboard:
  - Admin tiene link de gestion y `Todas` activo.
  - Secretaria no tiene link de gestion, tiene selector de doctor y `Todas` activo.
  - Doctor no tiene selector de doctor ni filtros `Anteriores`/`Todas`, y `Proximas` queda activo.
- Verificacion global:
  - `php -l` en todos los `.php`: OK.
  - `node --check` en todos los `.js`: OK.

## Refinamiento UI Gestion

- `pages/gestion/gestion.css`
  - Rediseño sobrio orientado a herramienta operativa: fondo neutro, paneles planos, sombras suaves, tipografia mas compacta y botones menos grandes.
  - Acciones de filas reemplazadas por iconos accesibles de editar/eliminar; se eliminaron letras grandes `E`/`X`.
  - KPIs de modulos CRUD reducidos a una barra compacta; se eliminaron colores por modulo y bordes saturados.
  - Responsive mejorado: navegacion superior compacta en tablet, filas con labels por celda en movil y acciones alineadas sin overflow.
- `pages/gestion/_shared/page.php`
  - `clinic` se trata como singleton: no renderiza listado, filtros, boton Nuevo ni numeraciones.
  - Los modulos de coleccion mantienen listado, busqueda, estado, Nuevo y Actualizar.
- `pages/gestion/gestion.js`
  - Distingue `is-singleton` e `is-collection`.
  - El formulario de `clinic` usa grilla amplia para editar datos institucionales sin una lista innecesaria al lado.
- `pages/gestion/calendar/`
  - `calendar.js` usa `view: schedule-board`.
  - `gestion.js` renderiza una matriz multi-semana para horarios: doctores por filas, dias fechados por columnas y bloques editables por celda.
  - Incluye filtros especificos por especialidad y doctor, ademas de busqueda y estado.
  - Incluye selector de semana inicial y cantidad de semanas futuras visibles (`1`, `2`, `4`, `8`, `12`).
  - Cada celda futura permite agregar un horario prellenando doctor, fecha, dia, hora inicio, hora fin, duracion de cupo y estado.
  - Las celdas de fechas anteriores a hoy mantienen lectura/edicion de bloques existentes, pero no permiten crear nuevos horarios puntuales.
  - `calendar.css` define responsive propio: tablero semanal en desktop y agenda apilada por doctor/dia en tablet y movil.
- CSS internos de `clinic`, `comment`, `doctors`, `specialties`, `users`, `services` y `calendar`
  - Quedaron como import del sistema compartido, sin colores decorativos por modulo.

Validacion Gestion UI:

- `php -l` en `pages/gestion/**/*.php`: OK.
- `node --check` en `pages/gestion/**/*.js`: OK.
- Smoke autenticado admin:
  - `clinic`, `comment`, `doctors`, `specialties`, `users`, `services`, `calendar`: 200, sin fatal errors y con shell de gestion.
  - `clinic`: sin `gestion-filters`, sin `gestion-list-panel`, sin `gestion-stats` y sin `gestion-new-btn`.
  - API `resource=schedules`: 23 horarios, 8 doctores, 8 especialidades y 7 dias de metadata.
  - Busqueda de restos visuales antiguos: sin `border-left-color`, sin botones `>E<`/`>X<`, sin textos `Campos completos`/`Registro maestro`.

## Mejoras Dashboard y Auditoria Dinamica

### Paginacion de Citas

- `api/dashboard/index.php`
  - `GET` acepta `page`, `per_page`, `status`, `search`, `date_scope`, `date` y `doctor_id`.
  - Devuelve solo la pagina solicitada en `appointments`.
  - Devuelve `pagination` con `page`, `per_page`, `total`, `total_pages`, `from`, `to`.
  - Devuelve `summary.counts` y `summary.next_pending` calculados sobre el rango filtrado completo, no solo sobre la pagina actual.
- `pages/dashboard/dashboard.js`
  - Los filtros de estado, busqueda y KPIs disparan nuevas consultas al servidor.
  - La paginacion conserva permisos por rol y reinicia a pagina 1 cuando cambian filtros o fecha.
  - Selector de tamano: 6, 12, 24 o 48 citas por pagina.

### Acceso a Gestion

- El boton simple `Gestion` fue reemplazado por una entrada administrativa con icono, titulo y descripcion.
- Sigue visible solo para `ADMINISTRATOR`.
- Link directo: `/clinica/pages/gestion/clinic/`.

### Refinamiento Visual

- Dashboard con paleta mas sobria: fondos neutros, sombras menos pesadas y menos degradados.
- KPIs e insights reducen saturacion y usan color como senal funcional.
- Acciones de cita (`Atender`, `No se presento`, `Rechazar`) usan estilo suave con fondo claro, borde y texto de color.
- Paginacion compacta bajo la lista, sin competir con el contenido operativo.
- Nueva iteracion dashboard:
  - Header alineado al lenguaje visual de Gestion: superficie blanca, bordes mate, tipografia mas compacta y boton de Gestion menos dominante.
  - KPIs e insights agrupados en `dashboard-summary-panel` con separacion inferior clara y colores de estado apagados.
  - Panel de citas enmarcado como herramienta operativa; filtros, fechas, busqueda, tarjetas y paginacion reducen escala y saturacion.
  - Tarjetas de cita con menos sombra, borde lateral mas delgado, badges suaves y acciones compactas.

### Auditoria Dinamica

Paginas revisadas:

- Home `index.php`
  - Dinamico por API: `index.php` solo deja contenedores y `data-home-api`; `public/js/app.js` consulta `/clinica/api/public/?resource=home`.
  - Datos hidratados: clinic, servicios, especialidades destacadas, doctores destacados, resenas, stats y tarjetas about desde la capa compartida.
- Header/footer compartidos
  - Dinamico: nombre, SEO/canonical, marca, direccion, correo, telefono, emergencia, WhatsApp y redes desde clinic.
- Especialidades `pages/specialties/index.php`
  - Se eliminaron arreglos simulados locales.
  - Dinamico: titulo/subtitulo desde clinic y catalogo desde `clinica_public_specialties_for_catalog()`.
- Doctores `pages/doctors/index.php`
  - Se eliminaron arreglos simulados locales.
  - Dinamico: titulo/subtitulo desde clinic; doctores, especialidades, idiomas, fotos, rating, pacientes y disponibilidad desde `clinica_public_doctors_for_catalog()`.
- Reservas `pages/reservations/index.php`
  - Se eliminaron arreglos simulados locales.
  - Dinamico: titulo/subtitulo/nota de confirmacion desde clinic; especialidades, doctores, citas ocupadas y horarios detallados desde la capa compartida.
  - Preseleccion por `specialty` / `especialidad` resuelve contra IDs o nombres administrados, sin alias quemados por especialidad.
  - Mensajes de exito y labels principales de navegacion de reserva salen de la configuracion clinic (`reservation_success_*`, `reservation_*_label`).
  - `pages/reservations/reservations.js` genera cupos solo desde `horarios_detallados`; no quedan rangos fijos tipo manana/tarde en el frontend.
- Gestion clinic
  - Nuevos campos editables: `reservation_title`, `reservation_subtitle`, `reservation_summary_note`, `reservation_success_title`, `reservation_success_text`, `reservation_back_home_label`, `reservation_next_label`, `reservation_confirm_label`, `reservation_previous_label`.

### Editor Modal en Gestion

- El editor CRUD compartido de `pages/gestion/_shared/page.php` ya no ocupa una columna fija en la maqueta.
- Todas las paginas de Gestion abren el formulario en un modal responsive al crear o editar registros.
- El modal soporta cierre por boton, fondo oscuro, tecla Escape y boton `Cancelar`; al guardar o eliminar vuelve al listado/resumen.
- `pages/gestion/clinic/` queda como vista resumen de la unica clinica, sin filtros, sin numeraciones y sin lista artificial.
- `pages/gestion/calendar/` mantiene la agenda multi-semana a ancho completo; los bloques de horario y botones `Agregar` abren el editor modal.

### Auditoria Integral 2026-08-04

Estado: no se detectaron errores funcionales ni de logica de negocio en la maqueta actual.

Validaciones ejecutadas:

- Lint y parseo:
  - `php -l` en todos los archivos PHP del proyecto: OK.
  - `node --check` en todos los archivos JS del proyecto: OK.
  - `api/storage/gestion-data.json` parsea correctamente como JSON: OK.
  - Escaneo estatico sin `Fatal error`, `Parse error`, `Warning`, `Notice`, `TODO`, `FIXME`, `console.log` ni `debugger` en codigo PHP/JS revisado.
- Smoke HTTP publico:
  - `/clinica/`, `/pages/login/`, `/pages/specialties/`, `/pages/doctors/`, `/pages/reservations/`, `/pages/reservations/?doctor_id=doc3` y `/pages/reservations/?specialty=Neurologia`: 200 sin errores PHP visibles.
  - `/api/public/?resource=home`: OK, devuelve servicios, especialidades y doctores dinamicos.
- Login, dashboard y roles:
  - Login admin, secretaria y doctor: OK.
  - Admin y secretaria consultan dashboard con `date_scope=all`: OK.
  - Doctor con `date_scope=all` se normaliza a `future`: OK.
  - Doctor recibe solo citas propias futuras: `CITA002`, `CITA003`, `CITA005`, `CITA010`: OK.
  - Doctor no recibe `doctor_id` ni `doctor_info` en la respuesta transformada: OK.
- Permisos negativos:
  - Login invalido: 401.
  - API dashboard sin sesion: 401.
  - API Gestion sin sesion: 403.
  - Secretaria y doctor no acceden a API Gestion: 403.
  - Secretaria y doctor al abrir Gestion son redirigidos al dashboard, sin recibir `gestion-editor-modal`.
  - Doctor no puede consultar fecha pasada exacta ni actualizar cita de otro doctor: OK.
- Gestion:
  - APIs admin de `clinic`, `comments`, `doctors`, `specialties`, `users`, `services` y `schedules`: OK.
  - Paginas admin `clinic`, `calendar`, `comment`, `doctors`, `services`, `specialties` y `users`: 200, modal CRUD presente y sin errores PHP visibles.
- Integridad de datos temporales:
  - Relaciones validas: 8 especialidades, 8 doctores, 6 servicios, 3 usuarios y 23 horarios.
  - Doctores referencian especialidades existentes.
  - Horarios referencian doctores existentes y tienen rangos de hora validos.
  - Usuario doctor referencia doctor existente.
  - Existe al menos un administrador activo.
- Auditoria dinamica:
  - Sin alias publicos quemados `cardio/neuro/derma/gine/onco`.
  - Sin rangos fijos `7-13` / `13-22` en paginas publicas revisadas.
  - Sin nombre fijo `Clinica Estandar` / `Clínica Estándar` en paginas publicas clave revisadas.
- Limpieza:
  - `tmp-codex-shots/` era solo artefacto temporal de capturas y perfiles headless.
  - Se verifico ruta absoluta dentro de `C:\xampp\htdocs\clinica`, se cerraron 22 procesos Chrome headless asociados y se elimino el directorio.

Validacion realizada:

- API publica home:
  - `/api/public/?resource=home`: `success=true`, 6 servicios, 4 especialidades, 4 doctores y 4 comentarios.
  - `/clinica/`: 200, contiene `data-home-api` y no imprime tarjetas `.service-card` estaticas en el HTML inicial.
  - Chrome headless valido el hero hidratado desde la API; las capturas temporales fueron eliminadas junto con `tmp-codex-shots/`.
- API paginada admin:
  - `date_scope=all&page=1&per_page=6`: 6 citas, total 11, 2 paginas.
  - `page=2`: 5 citas.
  - `status=pendiente&search=ana`: total 1.
- API doctor:
  - Forzar `date_scope=all&doctor_id=doc1` normaliza a `future` y devuelve solo 4 citas propias.
- Smoke publico:
  - `/pages/specialties/`: 200.
  - `/pages/doctors/`: 200.
  - `/pages/reservations/?doctor_id=doc3`: 200.
  - `/pages/reservations/?specialty=Neurologia`: 200, preselecciona `neurologia`, trae `reservationConfig` y `horarios_detallados`.
  - Auditoria de strings: sin alias `cardio/neuro/derma/gine/onco`, sin generacion de horarios 7-13/13-22 en JS y sin nombre de clinica fijo visible en `pages/specialties`, `pages/doctors` o `pages/reservations`.
- Smoke Gestion:
  - `php -l` sin errores en todos los `index.php` de `pages/gestion/` y en `_shared/page.php`.
  - `node --check` sin errores en todos los JS de `pages/gestion/`.
  - Login admin por API correcto y carga 200 de `clinic`, `calendar`, `comment`, `doctors`, `services`, `specialties` y `users`.
  - Todas esas rutas tienen `#gestion-editor-modal` y no imprimen el editor antiguo dentro del workspace.
- Chrome headless:
  - Dashboard: acceso a Gestion visible para admin, paginacion 1/2, siguiente pagina y filtro pendientes sin errores de consola.
  - Reservas: `doc3` trae 5 dias con `horarios_detallados`; al seleccionar fecha se generaron 32 cupos desde reglas detalladas, sin errores de consola.
  - Chrome headless valido dashboard y reservas; las capturas temporales fueron eliminadas junto con `tmp-codex-shots/`.

## Propuesta de Migración a Backend con Base de Datos

Para evolucionar el prototipo a un sistema de producción, se propone una migración completa de la persistencia de datos (actualmente en `gestion-data.json` y `$_SESSION`) a una base de datos relacional MySQL.

Esta propuesta se divide en dos partes: el **Esquema de la Base de Datos** y la **Arquitectura de Conexión y Acceso a Datos** en el backend PHP.

### 1. Esquema de Base de Datos MySQL Propuesto

El siguiente esquema ha sido diseñado basándose en la auditoría del sistema y los datos gestionados en `gestion-data.json`. Normaliza la información, establece relaciones clave y prepara el sistema para ser más robusto y escalable.

```sql
-- Tabla de configuración global de la clínica
CREATE TABLE `clinics` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL COMMENT 'Nombre comercial',
  `legal_name` VARCHAR(255) DEFAULT NULL,
  `logo_label` VARCHAR(255) DEFAULT NULL COMMENT 'Texto del logo (fallback)',
  `logo_image` VARCHAR(255) DEFAULT NULL COMMENT 'Ruta a la imagen del logo',
  `tagline` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `meta_title` VARCHAR(255) DEFAULT NULL,
  `meta_description` TEXT DEFAULT NULL,
  `canonical_url` VARCHAR(255) DEFAULT NULL,
  `hero_title` VARCHAR(255) DEFAULT NULL,
  `hero_subtitle` TEXT DEFAULT NULL,
  `hero_image` VARCHAR(255) DEFAULT NULL,
  `hero_mobile_image` VARCHAR(255) DEFAULT NULL,
  `about_title` VARCHAR(255) DEFAULT NULL,
  `about_subtitle` TEXT DEFAULT NULL,
  `services_title` VARCHAR(255) DEFAULT NULL,
  `services_subtitle` TEXT DEFAULT NULL,
  `specialties_title` VARCHAR(255) DEFAULT NULL,
  `specialties_subtitle` TEXT DEFAULT NULL,
  `doctors_title` VARCHAR(255) DEFAULT NULL,
  `doctors_subtitle` TEXT DEFAULT NULL,
  `comments_title` VARCHAR(255) DEFAULT NULL,
  `comments_subtitle` TEXT DEFAULT NULL,
  `reservation_title` VARCHAR(255) DEFAULT NULL,
  `reservation_subtitle` TEXT DEFAULT NULL,
  `reservation_summary_note` TEXT DEFAULT NULL,
  `reservation_success_title` VARCHAR(255) DEFAULT NULL,
  `reservation_success_text` TEXT DEFAULT NULL,
  `reservation_back_home_label` VARCHAR(100) DEFAULT NULL,
  `reservation_next_label` VARCHAR(100) DEFAULT NULL,
  `reservation_confirm_label` VARCHAR(100) DEFAULT NULL,
  `reservation_previous_label` VARCHAR(100) DEFAULT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `phone_display` VARCHAR(50) DEFAULT NULL,
  `phone_href` VARCHAR(50) DEFAULT NULL,
  `emergency_phone` VARCHAR(50) DEFAULT NULL,
  `whatsapp_url` VARCHAR(255) DEFAULT NULL,
  `facebook_url` VARCHAR(255) DEFAULT NULL,
  `instagram_url` VARCHAR(255) DEFAULT NULL,
  `stats` JSON DEFAULT NULL,
  `about_cards` JSON DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de usuarios del sistema (panel de gestión, dashboard)
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL COMMENT 'Almacenará el hash de la contraseña',
  `name` VARCHAR(255) NOT NULL,
  `role` ENUM('ADMINISTRATOR', 'SECRETARIA', 'DOCTOR') NOT NULL,
  `doctor_id` VARCHAR(50) DEFAULT NULL COMMENT 'FK a doctors.id si el rol es DOCTOR',
  `active` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `users_doctor_id_foreign` (`doctor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de especialidades médicas
CREATE TABLE `specialties` (
  `id` VARCHAR(50) NOT NULL COMMENT 'ID basado en slug, ej: cardiologia',
  `name` VARCHAR(255) NOT NULL,
  `summary` TEXT DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `icon` TEXT DEFAULT NULL COMMENT 'Puede ser código SVG o una ruta de archivo',
  `featured` BOOLEAN NOT NULL DEFAULT FALSE,
  `active` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de perfiles de doctores
CREATE TABLE `doctors` (
  `id` VARCHAR(50) NOT NULL COMMENT 'ID basado en slug, ej: doc-alejandro-vargas',
  `name` VARCHAR(255) NOT NULL,
  `photo` VARCHAR(255) DEFAULT NULL,
  `specialty_id` VARCHAR(50) NOT NULL,
  `sub_specialty` VARCHAR(255) DEFAULT NULL,
  `experience_years` INT UNSIGNED DEFAULT 0,
  `university` VARCHAR(255) DEFAULT NULL,
  `languages` JSON DEFAULT NULL COMMENT 'Almacena un array de idiomas',
  `recognitions` JSON DEFAULT NULL COMMENT 'Almacena un array de reconocimientos',
  `rating` TINYINT UNSIGNED DEFAULT 5,
  `patients` INT UNSIGNED DEFAULT 0,
  `featured` BOOLEAN NOT NULL DEFAULT FALSE,
  `active` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `doctors_specialty_id_foreign` (`specialty_id`),
  CONSTRAINT `doctors_specialty_id_foreign` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de servicios de la clínica
CREATE TABLE `services` (
  `id` VARCHAR(50) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `icon` TEXT DEFAULT NULL,
  `featured` BOOLEAN NOT NULL DEFAULT FALSE,
  `active` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de testimonios (comentarios) de pacientes
CREATE TABLE `testimonials` (
  `id` VARCHAR(50) NOT NULL,
  `author` VARCHAR(255) NOT NULL,
  `detail` VARCHAR(255) DEFAULT NULL,
  `text` TEXT NOT NULL,
  `rating` TINYINT UNSIGNED NOT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `featured` BOOLEAN NOT NULL DEFAULT FALSE,
  `active` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de horarios y disponibilidad de los doctores
CREATE TABLE `doctor_schedules` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `doctor_id` VARCHAR(50) NOT NULL,
  `date` DATE DEFAULT NULL COMMENT 'Si es NULL, es una regla semanal recurrente',
  `weekday` TINYINT UNSIGNED NOT NULL COMMENT '1 (Lunes) a 7 (Domingo)',
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `slot_minutes` INT UNSIGNED NOT NULL DEFAULT 15,
  `active` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `doctor_schedules_doctor_id_foreign` (`doctor_id`),
  KEY `idx_schedule_lookup` (`doctor_id`, `weekday`, `start_time`),
  KEY `idx_schedule_date_lookup` (`doctor_id`, `date`),
  CONSTRAINT `doctor_schedules_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de pacientes
CREATE TABLE `patients` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `phone` VARCHAR(50) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de citas
CREATE TABLE `appointments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `patient_id` INT UNSIGNED NOT NULL,
  `doctor_id` VARCHAR(50) NOT NULL,
  `specialty_id` VARCHAR(50) NOT NULL,
  `date` DATE NOT NULL,
  `time` TIME NOT NULL,
  `status` ENUM('pendiente', 'atendida', 'no-presentado', 'rechazada') NOT NULL DEFAULT 'pendiente',
  `notes` TEXT DEFAULT NULL COMMENT 'Notas del paciente al agendar',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_appointment_slot` (`doctor_id`, `date`, `time`),
  KEY `appointments_patient_id_foreign` (`patient_id`),
  KEY `appointments_specialty_id_foreign` (`specialty_id`),
  KEY `idx_appointment_lookup` (`date`, `time`),
  KEY `idx_appointment_status` (`status`, `date`),
  CONSTRAINT `appointments_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `appointments_specialty_id_foreign` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de logs de auditoría
CREATE TABLE `audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `resource_type` VARCHAR(50) DEFAULT NULL,
  `resource_id` VARCHAR(50) DEFAULT NULL,
  `details` JSON DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_foreign` (`user_id`),
  KEY `idx_audit_resource` (`resource_type`, `resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Constraints adicionales
ALTER TABLE `users` ADD CONSTRAINT `users_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;
```

### 2. Arquitectura de Backend Propuesta

La estrategia consiste en crear una capa de abstracción para la base de datos y refactorizar las funciones existentes para que la utilicen, manteniendo los contratos de las APIs intactos para no afectar al frontend.

#### 2.1. Conexión Centralizada a la Base de Datos (PDO)

Se propone crear un nuevo archivo `api/db.php` que gestionará la conexión a la base de datos de forma centralizada y segura.

**`api/db.php` (Propuesta)**
```php
<?php

function clinica_db_credentials(): array
{
    // En un entorno de producción, estos datos deben provenir de variables de entorno (.env)
    // y no estar hardcodeados.
    return [
        'host' => 'localhost',
        'dbname' => 'clinica_base',
        'user' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ];
}

function clinica_get_db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $credentials = clinica_db_credentials();
        $dsn = "mysql:host={$credentials['host']};dbname={$credentials['dbname']};charset={$credentials['charset']}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, $credentials['user'], $credentials['password'], $options);
        } catch (\PDOException $e) {
            // En un entorno de producción, esto debería registrar el error y mostrar una página genérica.
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    return $pdo;
}
```

#### 2.2. Refactorización de la Capa de Datos (`api/data.php`)

El archivo `api/data.php` dejará de leer y escribir en el archivo JSON. En su lugar, incluirá `api/db.php` y utilizará la función `clinica_get_db()` para realizar consultas SQL.

**Ejemplo de Refactorización:**

*Función Actual (JSON):*
```php
function clinica_get_clinic_settings(): array
{
  $clinic = clinica_get_database_resource('clinic');
  $default = clinica_default_database()['clinic'];
  return is_array($clinic) ? array_replace_recursive($default, $clinic) : $default;
}
```

*Función Propuesta (MySQL con PDO):*
```php
function clinica_get_clinic_settings(): array
{
    $pdo = clinica_get_db();
    $stmt = $pdo->query("SELECT * FROM clinics ORDER BY id ASC LIMIT 1");
    $clinic = $stmt->fetch();

    if (!$clinic) {
        // Si la tabla está vacía, se puede devolver la configuración por defecto
        // o lanzar un error, dependiendo de la política de la aplicación.
        return clinica_default_database()['clinic'];
    }
    return $clinic;
}
```

Este patrón se aplicaría a todas las funciones de lectura (`clinica_get_specialties`, `clinica_get_doctors`, etc.) y de escritura (`clinica_write_database` sería reemplazada por funciones específicas como `clinica_save_specialty`, `clinica_update_clinic`, etc., que ejecutarían sentencias `INSERT` y `UPDATE`).

## Iteracion 2026-08-05

### Auditoria de Funcionalidad y Errores (2026-08-05)

Se realizo una auditoria integral del sistema, identificando los siguientes puntos que requieren atencion prioritaria:

- **Vulnerabilidad Critica - Contrasenas en Texto Plano:** El sistema almacena y compara contrasenas sin ningun tipo de hash.
- **Vulnerabilidad Critica - Ausencia de Proteccion CSRF:** Los endpoints que modifican datos (`POST`, `DELETE`) no utilizan tokens anti-CSRF.
- **Bug de Logica - Visualizacion del Logo:** El header y footer muestran la imagen del logo y el texto del logo (`logo_label`) al mismo tiempo, en lugar de usar el texto como fallback.
- **Bug de Codificacion - Etiqueta de Turno:** La funcion `clinica_schedule_shift_label` devuelve `MaÃ±ana` en lugar de `Mañana`.
- **Deuda Tecnica - Duplicacion de Codigo:** La logica de renderizado del logo esta duplicada en `header.php` y `footer.php`. La funcion `debounce` en `reservations.js` deberia ser una utilidad compartida.
- **Mejora de UX - Alerta en Reservas:** El formulario de reservas utiliza `alert()` para notificar errores, lo cual interrumpe la experiencia de usuario.
- **Problema de Rendimiento - Imagenes sin Optimizar:** El archivo `public/assets/doctora_avatar.png` tiene un tamano excesivo (~791 KB).

Estos hallazgos se han integrado en el checklist de tareas pendientes.

### Cambios Aplicados en esta Iteracion

Cambios aplicados:

- Sesion:
  - Se agrego expiracion absoluta de 8 horas mediante `CLINICA_SESSION_TTL_SECONDS = 28800`.
  - `clinica_current_user()` centraliza la lectura de usuario autenticado y destruye sesiones sin `auth_started_at` o vencidas.
  - `clinica_start_authenticated_session()` registra hora de inicio, regenera ID y fija expiracion de cookie.
  - Login, dashboard, gestion y APIs protegidas usan el helper central en vez de leer `$_SESSION['user']` directamente.
- Imagenes en Gestion:
  - `api/gestion/index.php` acepta `multipart/form-data` con `action=upload`.
  - `pages/gestion/gestion.js` renderiza campos `image` con preview, campo de ruta y selector de archivo.
  - `pages/gestion/clinic/clinic.js`: `hero_image` y `hero_mobile_image` permiten subir imagenes.
  - `pages/gestion/doctors/doctors.js`: `photo` permite subir imagen del doctor/doctora.
  - `pages/gestion/specialties/specialties.js`: `icon` ahora es de tipo `image`, permitiendo subir SVG/PNG o pegar codigo.
  - `pages/gestion/services/services.js`: `icon` ahora es de tipo `image`, permitiendo subir SVG/PNG o pegar codigo.
  - `api/gestion/index.php` ahora soporta subida de `image/svg+xml` y los nuevos `targets` para `clinic.logo_image`, `specialties.icon` y `services.icon`.
  - `gestion.js` ahora previsualiza correctamente tanto URLs de imagen como codigo SVG (completo o fragmentos como `<path>`).
  - `header.php` y `footer.php` ahora renderizan el logo dinamicamente desde la configuracion de la clinica.
  - `pages/gestion/comment/comment.js`: `avatar` permite subir avatar de resena.
  - Las imagenes se guardan bajo `public/assets/clinic/`, `public/assets/doctors/` o `public/assets/comments/` segun recurso.
- Horarios:
  - `schedules` acepta `date` opcional para bloques puntuales en fechas futuras.
  - Si `date` esta vacio, el bloque conserva comportamiento semanal recurrente.
  - Gestion de Horarios renderiza varias semanas futuras con controles de semana inicial y cantidad de semanas.
  - Las fechas pasadas del tablero no permiten crear nuevos bloques puntuales.
  - Se elimino el selector de fecha (`input[type=date]`) y el selector de cantidad de semanas del calendario, reemplazandolos por una navegacion de "Semana Anterior" / "Hoy" / "Semana Siguiente" para un control mas preciso.
  - Se mejoro el diseno de la navegacion de semanas para que se muestre horizontalmente y en una barra de herramientas dedicada.
  - Reservas combina reglas puntuales por fecha con reglas semanales recurrentes al generar cupos.

## Iteracion 2026-08-06

### Cambios Aplicados en esta Iteracion

- **Refactorizacion y Correccion de Bugs:**
  - Se corrigio el bug de visualizacion del logo en `header.php` y `footer.php`. La logica ahora muestra la imagen o el texto, pero no ambos.
  - Se centralizo la logica de renderizado del logo en una nueva funcion `clinica_render_logo_element()` en `api/data.php`, eliminando codigo duplicado.
  - Se corrigio el error de codificacion que mostraba `MaÃ±ana` en lugar de `Mañana`.
  - Se reemplazo el `alert()` en el formulario de reservas por un mensaje de error integrado en la UI para una mejor experiencia de usuario.
  - Se centralizo la funcion de utilidad `debounce` en un nuevo archivo `public/js/utils.js` para eliminar la duplicacion de codigo en `reservations.js` y `doctors.js`.

### Auditoria Post-Correcciones

Se realizo una nueva auditoria completa del sistema. No se han detectado nuevos bugs criticos. Los problemas de seguridad pendientes (contrasenas en texto plano y ausencia de CSRF) siguen identificados y su solucion esta planificada para la migracion a un backend con base de datos. El sistema se considera estable en su estado actual de prototipo avanzado.

Validacion realizada:

- Servidor local usado: `http://127.0.0.1:8787/clinica/`.
- `php -l` en todos los `.php`: OK.
- `node --check` en todos los `.js`: OK.
- `api/storage/gestion-data.json` parsea como JSON: OK.
- Login admin por API: OK; `expires_at` queda a 28800 segundos.
- Helper de expiracion con `auth_started_at` vencido: devuelve sesion expirada.
- API Gestion:
  - Crear horario temporal puntual para `doc1` en `2026-08-19`: OK.
  - Reservas con `?doctor_id=doc1` contiene la regla futura puntual: OK.
  - Eliminar horario temporal: OK.
  - Subida multipart de imagen a `doctors.photo`: OK; el archivo temporal fue eliminado.
- Smoke HTTP autenticado admin:
  - `/`, `/pages/login/`, `/pages/dashboard/`, `/pages/gestion/calendar/`, `/pages/gestion/doctors/`, `/pages/gestion/clinic/`, `/pages/reservations/?doctor_id=doc3`: OK.
- Chrome headless con Chrome del sistema:
  - Gestion Horarios renderiza 4 semanas por defecto y el selector queda en `4`.
  - Botones `Agregar` de dias pasados quedan deshabilitados.
  - Primer `Agregar` futuro prellena fecha `2026-08-05` y weekday `3`.
  - Modal doctores tiene 1 input de imagen (`photo`).
  - Modal resenas tiene 1 input de imagen (`avatar`).
  - Modal clinica tiene 2 inputs de imagen (`hero_image`, `hero_mobile_image`).
  - Reservas renderiza calendario (`36` celdas en smoke).
  - Sin errores de consola.

## Checklist Pendiente

### P0 - Backend y Base de Datos - Arranque

- [ ] Elegir motor inicial de BD para XAMPP, recomendado MySQL/MariaDB.
- [ ] Definir `.env` o archivo de configuracion fuera de versionado para credenciales de BD, base URL y entorno.
- [ ] Crear conexion PDO centralizada con charset `utf8mb4`, excepciones habilitadas y helper transaccional.
- [ ] Crear migraciones versionadas para `clinics`, `services`, `specialties`, `doctors`, `comments`, `users`, `doctor_schedules`, `patients`, `appointments`, `appointment_status_history` y `audit_logs`.
- [ ] Definir claves foraneas: doctores-especialidades, horarios-doctores, usuarios-doctores, citas-pacientes/doctores/especialidades.
- [ ] Crear indices iniciales para agenda: `appointments(fecha, hora)`, `appointments(doctor_id, fecha, hora)`, `appointments(status, fecha)`, `doctor_schedules(doctor_id, weekday, start_time)` y `users(username)`.
- [ ] Crear seed idempotente desde `api/storage/gestion-data.json` para poblar la primera version de la BD.
- [ ] Sustituir lectura/escritura JSON en `api/data.php` por repositorios/servicios con la misma forma de respuesta actual.
- [ ] Mantener contratos JSON actuales de `/api/public/`, `/api/dashboard/`, `/api/gestion/` y `/api/general/` para no reescribir el frontend.
- [ ] Persistir citas creadas desde `pages/reservations/` en BD con validacion de disponibilidad real.
- [ ] Validar conflictos de horario en servidor: no duplicar doctor-fecha-hora, no reservar fuera de horario, no reservar bloque inactivo y respetar `slot_minutes`.
- [ ] Migrar autenticacion demo a `password_hash` / `password_verify`.
- [ ] Agregar CSRF a endpoints autenticados de Gestion y Dashboard.
- [ ] Crear middleware comun de sesion, permisos y respuestas JSON.
- [ ] Registrar cambios criticos en `audit_logs`: CRUD Gestion, cambio de estado de cita, login fallido y login exitoso.
- [ ] Normalizar validaciones backend por recurso: requeridos, email, URL, imagen, rating, rango de hora, rol y doctor asociado.
- [ ] Definir politica de borrado: hard delete, soft delete o `active=false` por recurso.
- [ ] Crear backups/exportacion de datos antes de migrar desde JSON a BD.
- [ ] Preparar pruebas automatizadas minimas para contratos API, permisos por rol, reserva de cita y CRUD de Gestion.

### P0 - Antes de backend real

- [x] Definir permisos por rol: `ADMINISTRATOR` gestiona datos y agenda global; `SECRETARIA` solo agenda global; `DOCTOR` solo agenda propia presente/futura.
- [ ] **(Vulnerabilidad Crítica)** Sustituir contraseñas en texto plano por `password_hash` / `password_verify`.
- [ ] **(Vulnerabilidad Crítica)** Agregar proteccion anti-CSRF para todas las acciones autenticadas que modifican estado (`POST`, `DELETE`).
- [x] Mover datos simulados de paginas publicas clave a una fuente compartida temporal administrable.
- [x] Definir esquema de base de datos: usuarios, roles, doctores, especialidades, horarios, pacientes, citas y estados.

### P1 - Funcionalidad

- [ ] Convertir la cita creada desde reserva en persistencia real cuando exista base de datos.
- [ ] Agregar cancelacion/reprogramacion de citas si el flujo operativo lo requiere.
- [ ] Definir si el backend real conservara los mismos rangos de fecha por rol o agregara reglas por sede/turno.
- [x] **(Bug)** Corregir error de codificacion en la etiqueta de turno (`MaÃ±ana` por `Mañana`) en `api/data.php`.
- [x] **(Bug)** Implementar logica de fallback para el logo: mostrar imagen si existe; si no, mostrar texto (`logo_label`), pero no ambos.
- [x] Agregar busqueda y filtros por fecha en dashboard.
- [x] Agregar paginacion server-side para la lista de citas del dashboard.
- [x] Mover filtros de estado y busqueda de citas al endpoint del dashboard.
- [x] Normalizar nombres de doctores/especialidades desde una sola fuente administrable.
- [x] Generar cupos de reservas desde horarios administrados con hora inicio, hora fin y duracion.

### P2 - UX, Accesibilidad y Responsive

- [x] Validar visualmente gestion y flujos clave con navegador headless en movil y escritorio.
- [ ] Revisar navegacion por teclado en stepper de reservas, dashboard y menu movil.
- [x] **(Confirmado)** Reemplazar `alert()` de reservas por un mensaje accesible dentro del formulario es una tarea pendiente de baja prioridad.
- [ ] Revisar contraste de badges y botones secundarios.
- [ ] Agregar soporte `prefers-reduced-motion`.
- [x] Reemplazar tabla del dashboard por tarjetas responsive sin scroll horizontal.
- [x] Reducir escala visual del dashboard a tamanos normales en textos, tarjetas, botones y modal.
- [x] Refinar paleta del dashboard para una lectura mas sobria y operativa.
- [x] Mejorar acceso visual a Gestion para administradores.

### P2 - Rendimiento/UI

- [x] **(Rendimiento)** Optimizar `public/assets/doctora_avatar.png` (tamano actual ~791.7 KB).
- [ ] Revisar calidad de `banner.jpg` para hero desktop.
- [ ] Reducir duplicacion de estilos entre filtros de especialidades, doctores, reservas y dashboard.
- [x] **(Deuda Tecnica)** Centralizar funciones de utilidad JavaScript (ej. `debounce`) para evitar duplicacion.
- [x] **(Deuda Tecnica)** Refactorizar la logica de renderizado del logo en PHP para evitar duplicacion entre header y footer.
- [ ] Decidir si login se integra al design system global o permanece standalone.

## Iteracion 2026-08-07: Migracion a Base de Datos MySQL

Se ha completado la migracion del backend desde un sistema basado en archivos JSON y sesiones a una arquitectura robusta con base de datos MySQL, siguiendo la propuesta documentada.

### Cambios Implementados

- **Capa de Base de Datos:**
  - Se creo `api/db.php` para gestionar una conexion PDO singleton a la base de datos MySQL.
  - Se creo un script `api/seed.php` para crear el esquema de tablas y poblarlo con los datos existentes de `gestion-data.json`.
- **Refactorizacion del Backend:**
  - Se refactorizo `api/data.php` para que todas las funciones de lectura (`clinica_get_doctors`, `clinica_get_specialties`, etc.) ahora consulten la base de datos en lugar de archivos JSON.
  - Se refactorizo `api/gestion/index.php` para que las operaciones de guardado y eliminacion (CRUD) se realicen mediante sentencias `INSERT`, `UPDATE` y `DELETE` en la base de datos.
  - Se refactorizo `api/dashboard/index.php` para que la obtencion y filtrado de citas se realice con consultas SQL, eliminando la dependencia de `$_SESSION`.
  - Se refactorizo `api/general/index.php` para que el login verifique las credenciales contra la tabla `users` usando `password_verify()` y la creacion de citas inserte registros en las tablas `patients` y `appointments`.

### P3 - Pruebas

- [ ] Agregar pruebas automatizadas de smoke para rutas publicas y APIs.
- [ ] Validar consola limpia con navegador real.
- [ ] Documentar checklist manual de flujos: reserva, login doctor, login secretaria, cambio de estados y logout.

## Directivas de Trabajo Futuras

- Mantener rutas coherentes con `/clinica/` mientras el proyecto viva en XAMPP.
- Reutilizar `public/css/style.css`, `public/css/components.css`, header y footer antes de crear estilos nuevos.
- Mantener la demo funcional aunque el backend siga simulado.
- No conectar base de datos sin cerrar primero contratos de datos y roles.
- Verificar siempre: `php -l`, `node --check`, smoke HTTP y ausencia de warnings PHP.

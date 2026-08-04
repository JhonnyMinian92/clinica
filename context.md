# Contexto del Proyecto - Clinica Estandar

Actualizado: 2026-08-04
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

La aplicacion corre como sitio PHP tradicional servido desde XAMPP. Las paginas publicas renderizan HTML desde PHP y usan JavaScript por pagina. El backend real aun no existe, pero la demo ya usa endpoints JSON.

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
  - `GET action=session`: devuelve si hay usuario autenticado.
  - `POST action=login`: valida credenciales demo, crea `$_SESSION['user']` y prepara citas.
  - `POST action=logout`: destruye sesion y devuelve redirect al login.
  - `POST action=create_appointment`: crea una cita pendiente desde el formulario de reservas y la agrega a la sesion.

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

- `index.php`: home publica con hero, servicios, especialidades destacadas, doctores destacados y testimonios.
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

## Checklist Pendiente

### P0 - Antes de backend real

- [ ] Definir si `ADMINISTRATOR` y `SECRETARIA` tendran permisos identicos o distintos.
- [ ] Sustituir contraseñas demo por `password_hash` / `password_verify`.
- [ ] Agregar CSRF para acciones autenticadas.
- [ ] Mover datos simulados de todas las paginas publicas a una fuente compartida temporal o al futuro modelo de BD.
- [ ] Definir esquema de base de datos: usuarios, roles, doctores, especialidades, horarios, pacientes, citas y estados.

### P1 - Funcionalidad

- [ ] Convertir la cita creada desde reserva en persistencia real cuando exista base de datos.
- [ ] Agregar cancelacion/reprogramacion de citas si el flujo operativo lo requiere.
- [ ] Definir si el backend real conservara los mismos rangos de fecha por rol o agregara reglas por sede/turno.
- [x] Agregar busqueda y filtros por fecha en dashboard.
- [ ] Normalizar acentos y nombres de doctores/especialidades desde una sola fuente.

### P2 - UX, Accesibilidad y Responsive

- [ ] Validar visualmente con navegador en movil, tablet y escritorio cuando haya navegador disponible.
- [ ] Revisar navegacion por teclado en stepper de reservas, dashboard y menu movil.
- [ ] Reemplazar `alert()` de reservas por un mensaje accesible dentro del formulario.
- [ ] Revisar contraste de badges y botones secundarios.
- [ ] Agregar soporte `prefers-reduced-motion`.
- [x] Reemplazar tabla del dashboard por tarjetas responsive sin scroll horizontal.
- [x] Reducir escala visual del dashboard a tamanos normales en textos, tarjetas, botones y modal.

### P2 - Rendimiento/UI

- [ ] Optimizar `public/assets/doctora_avatar.png` (aprox. 791.7 KB).
- [ ] Revisar calidad de `banner.jpg` para hero desktop.
- [ ] Reducir duplicacion de estilos entre filtros de especialidades, doctores, reservas y dashboard.
- [ ] Decidir si login se integra al design system global o permanece standalone.

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

# ==============================================
# GEMINI SOFTWARE ARCHITECT MODE
# ==============================================

Eres un Arquitecto de Software Senior, UI/UX Designer Senior, Frontend Engineer Senior, Backend PHP Senior, Especialista en Accesibilidad, Especialista en Performance y Especialista en Arquitectura Web. Tu nivel es equivalente a un equipo completo de desarrollo de Google.

Primero analizas, después diseñas, después implementas, después verificas y finalmente optimizas. Nunca escribes código sin comprender completamente el proyecto.

# ==============================================
# PROTOCOLO DE SEGURIDAD Y CALIDAD (PRIME DIRECTIVE)
# ==============================================

## Objetivo
Garantizar que cada cambio mejore el sistema sin introducir regresiones, errores o inconsistencias. Este protocolo es de cumplimiento **OBLIGATORIO** antes y después de cada intervención.

## 1. Auditoría Pre-Cambio (Análisis)
- `[ ]` **Identificar Archivos:** Localizar todos los archivos que serán afectados por la solicitud.
- `[ ]` **Análisis de Código:** Comprender la función, dependencias y estilo del código existente en dichos archivos.
- `[ ]` **Reutilización de CSS:** Revisar `public/css/style.css` para encontrar clases y variables del Design System "Aura Health" que se puedan reutilizar.
- `[ ]` **Reutilización de JS:** Revisar `public/js/app.js` para encontrar funciones o patrones de JS que se puedan reutilizar.
- `[ ]` **Coherencia Arquitectónica:** Confirmar que el cambio solicitado no contradice la arquitectura o la lógica de negocio descrita en este documento.

## 2. Implementación Segura (Diseño e Implementación)
- Escribir código claro, comentado y consistente con el estilo del proyecto.
- Reutilizar componentes, funciones y estilos existentes como prioridad absoluta.
- Asegurar que todo el HTML nuevo sea semántico y accesible desde su concepción.

## 3. Checklist de Verificación Post-Cambio (Verificación Obligatoria)
*No se considera una tarea completa hasta que todos los puntos de esta lista sean verificados y aprobados.*

### Funcionalidad Core
- `[ ]` **Navegación General:** ¿Funcionan todos los enlaces del header y footer?
- `[ ]` **Navegación Móvil:** ¿El menú hamburguesa abre, cierra y sus enlaces funcionan correctamente?
- `[ ]` **Funcionalidad JS:** ¿El lazy loading de imágenes, las animaciones de scroll y el carrusel de testimonios siguen operando sin fallos?

### Calidad Técnica
- `[ ]` **Consola Limpia:** ¿La consola de desarrollador del navegador está libre de errores y advertencias de JavaScript?
- `[ ]` **HTML Válido:** ¿El nuevo código HTML pasa la validación (ej. W3C Validator)?

### Experiencia de Usuario (UX) y UI
- `[ ]` **Sin Regresiones Visuales:** ¿El diseño se ve correctamente en los principales breakpoints (móvil < 768px, tablet >= 768px, desktop >= 1024px)? ¿No hay elementos desalineados o rotos?
- `[ ]` **Rendimiento:** ¿La página sigue cargando rápido? ¿El cambio no introdujo un `layout shift` (CLS) notable?
- `[ ]` **Accesibilidad (WCAG):** ¿Los nuevos elementos son 100% navegables con teclado? ¿Tienen los atributos `aria-*` correctos? ¿El contraste de color es adecuado?

## 4. Actualización de Bitácora
- Solo después de que la verificación sea exitosa, registrar el cambio en la sección `Cambios Realizados` de la bitácora en este mismo archivo, actualizando la versión del proyecto.


# ==============================================
# PROYECTO: CLÍNICA ESTÁNDAR (v3.1)
# ==============================================

## Visión General
Es el sitio web oficial de una clínica privada. Debe transmitir inmediatamente:
• **Confianza y Prestigio**: Sentimiento de estar en uno de los mejores hospitales del mundo.
• **Isotipo de Marca**: El logo debe representar excelencia médica, no ser genérico.
• **Elegancia y Modernidad**: Una experiencia de usuario Premium, comparable a un desarrollo internacional.
• **Tecnología y Calidad Humana**: Fusión de medicina moderna con un trato cálido.

## Objetivo General
**EVOLUCIONAR, no revolucionar.** Mantener la identidad visual, estructura y experiencia actual, pero elevando cada aspecto a un nivel superior de modernidad y profesionalismo.

# ==============================================
# ARQUITECTURA Y ESTADO ACTUAL DEL SISTEMA (MEMORIA)
# ==============================================
 
Esta sección funciona como tu memoria y mapa del sistema. Describe el estado actual del proyecto.

## 1. Información de la Clínica
- **Nombre:** Clínica Estándar
- **Dirección:** Buenavista y Tarqui, Machala, Ecuador.
- **Contacto Principal:**
  - **Teléfono:** (Número a definir)
  - **Correo:** (Correo a definir)
- **Redes Sociales:**
  - Facebook
  - Instagram
  - WhatsApp

## 2. Componentes Principales del Sistema
- **`index.php`**: Página de aterrizaje (landing page) estática y pública. Muestra la información principal de la clínica: especialidades destacadas, doctores, equipos (radiografía, ecografía), y datos de contacto. Es el principal punto de entrada para los pacientes.
- **`public/css/style.css`**: Hoja de estilos global. Define el "Design System: Aura Health" (variables, resets, clases de utilidad) y estilos para secciones no componentizadas de `index.php`.
- **`public/js/app.js`**: Archivo JavaScript principal. Gestiona la interactividad global del sitio que no pertenece a un componente específico (ej. animaciones de scroll, lazy loading, carrusel de testimonios).
- **`public/shared/header/`**: Componente de cabecera reutilizable.
  - `header.php`: Contiene el `DOCTYPE`, `<head>` y la estructura `<header>`. Gestiona la carga de assets CSS globales, de componentes y condicionales por página.
  - `header.css`: Estilos específicos del header.
  - `header.js`: Lógica JavaScript para la navegación (sticky y móvil).
- **`public/css/components.css`**: Hoja de estilos para componentes de UI reutilizables en múltiples páginas (ej. `.page-header`).
- **`public/shared/footer/`**: Componente de pie de página reutilizable.
  - `footer.php`: Contiene la estructura `<footer>` y el cierre de la página. Gestiona la carga de assets JS globales, de componentes y condicionales por página.
  - `footer.css`: Estilos específicos del footer.
  - `footer.js`: Lógica JavaScript específica del footer (actualmente vacío).

## 3. Estructura de Páginas (Directorio `/pages`)
- **`/pages/specialties/`**: Catálogo de especialidades.
  - `index.php`: Vista principal de la página.
  - `specialties.js`: Lógica de frontend específica para esta página (actualmente vacío).
- **`/pages/doctors/`**: Directorio de médicos.
  - `index.php`: Vista principal con la lógica de filtrado y renderizado de perfiles.
  - `doctors.css`: Estilos específicos para los perfiles y filtros de la página de doctores.
  - `doctors.js`: Lógica de frontend para la interactividad de los filtros.
- **`/pages/reservations/`**: Sistema de agendamiento de citas. Implementado como un "stepper" de 5 pasos para guiar al usuario.
- **`/pages/login/`**: Portal de acceso **exclusivo para doctores**. No es accesible desde la navegación principal del sitio público.
- **`/pages/dashboard/`**: Panel de control para doctores, visible únicamente después de un inicio de sesión exitoso.

## 4. Flujos de Usuario Clave
- **Agendamiento de Cita:**
  1.  **Especialidad:** El usuario elige una especialidad (este paso se omite si viene de la página de una especialidad específica).
  2.  **Doctor:** Se muestran los doctores disponibles para esa especialidad.
  3.  **Fecha y Hora:** Un calendario dinámico muestra los días y horas disponibles del doctor seleccionado.
  4.  **Datos del Paciente:** El usuario ingresa su información personal y el motivo de la consulta.
  5.  **Confirmación:** Se muestra un resumen completo de la cita para su confirmación final.

# ==============================================
# BITÁCORA DE CAMBIOS Y TAREAS PENDIENTES
# ==============================================

## Cambios Realizados

- **v4.2 (Fecha Actual):**
  - **Refinamiento de UX y Correcciones en Formulario (Tarea 14.1):** Se han realizado múltiples mejoras en la experiencia de usuario y se han corregido errores de layout en el formulario de reservaciones.
    - **Corrección de Layout (Botones):** Se solucionó el bug que ocultaba los botones de navegación al ajustar dinámicamente la altura del contenedor para que siempre sean visibles.
    - **Mejora de Validación:** Se implementó una validación más estricta para el campo de teléfono y se añadieron mensajes de error específicos y claros para los campos de email y teléfono.
    - **Mejora de Estilo (Botón "Anterior"):** Se corrigió el estilo del botón "Anterior" para garantizar su visibilidad sobre fondos claros.
    - **UX Loader:** Se reemplazó el skeleton loader de los horarios por un spinner de carga superpuesto, mejorando la comunicación visual con el usuario.

- **v4.1:**
  - **Mejora Avanzada del Agendamiento (Tareas 13.1-13.3):** Se ha implementado la lógica de disponibilidad real del doctor y se han mejorado las funcionalidades del formulario.
    - **Filtro de Doctores (Paso 2):** Se añadió un campo de búsqueda para filtrar doctores por nombre.
    - **Intervalos de 15 Minutos (Paso 3):** Se ajustó la generación de horarios a intervalos de 15 minutos.
    - **Disponibilidad Real (Paso 3):** El calendario ahora deshabilita los días en que el doctor no trabaja. La lista de horarios respeta el turno del doctor (Mañana/Tarde/Completo) y muestra como "ocupados" los horarios ya reservados.
    - **Mejoras de UX:** Se añadieron animaciones sutiles al cambiar de mes en el calendario y al mostrar los horarios.

- **v4.0:**
  - **Implementación del Formulario de Reservaciones (Tareas 12.1-12.4):** Se ha construido el flujo completo del formulario de agendamiento de citas.
    - **HTML:** Se maquetó la estructura de 5 pasos, incluyendo el nuevo calendario interactivo y los contenedores para cada etapa.
    - **CSS:** Se diseñó la barra de progreso, las transiciones entre pasos y se creó un layout responsive y funcional para el calendario y la selección de horarios, con alturas fijas y scroll en escritorio.
    - **JS:** Se implementó toda la lógica del "stepper" (navegación, validación, actualización de UI), la selección de especialidad, la carga dinámica de doctores, la generación del calendario (deshabilitando días pasados), la generación de horarios dinámicos (filtrando horas pasadas en el día actual) y la simulación de envío con mensaje de éxito.

- **v3.6:**
  - **Corrección y Mejora de Filtros de Doctores (Tarea 11):** Se solucionaron múltiples errores en la página de doctores que impedían el funcionamiento de los filtros.
    - **JS:** Se corrigió el filtro de "disponibilidad hoy" cambiando su detector de eventos de `input` a `change` para garantizar la compatibilidad. Se hizo que el filtro de especialidad sea insensible a mayúsculas y minúsculas para mayor robustez.
    - **HTML:** Se reorganizó la disposición de los filtros, moviendo el de "disponibilidad hoy" después del de "idiomas", según lo solicitado, y se ajustaron las animaciones de entrada.
- **v3.5:**
  - **Refactorización de la Página de Doctores (Tarea 10.1):** Se ha realizado una mejora integral de la página de doctores, implementando un indicador de disponibilidad en tiempo real basado en el día actual y rediseñando las tarjetas de perfil y los filtros para una experiencia de usuario superior.
- **v3.5:**
  - **Rediseño Premium de la Página de Doctores (Tarea 10):** Se ha realizado una mejora integral de la página de doctores (`/pages/doctors/`).
    - **CSS:** Se rediseñaron los filtros para ser más limpios y organizados. Las tarjetas de perfil de los doctores ahora tienen un diseño más sofisticado y profesional, con una mejor jerarquía visual y un responsive design perfeccionado.
    - **JS:** Se optimizó la interactividad. El filtrado ahora utiliza una animación suave de atenuación en lugar de ocultar elementos bruscamente. Se implementó `debounce` en la búsqueda por nombre para mejorar el rendimiento y se añadió la funcionalidad para un nuevo botón de "Limpiar filtros".
    - **HTML:** Se actualizó la estructura para reflejar el nuevo diseño de los filtros y añadir el botón de reinicio.
- **v3.4 (Fecha Actual):**
  - **Optimización del Filtro de Especialidades (Tarea 8.1):** Se ha refactorizado el script `specialties.js` para incluir una función `debounce`. Esto mejora el rendimiento y la experiencia de usuario al no ejecutar la búsqueda en cada pulsación de tecla, sino tras una breve pausa. Adicionalmente, se han añadido estilos al mensaje de "sin resultados" en `specialties.css` y se ha limpiado el HTML correspondiente en `pages/specialties/index.php` para una mejor separación de responsabilidades (CSS sobre estilos inline).
- **v3.3 (Fecha Actual):**
  - **Implementación de Sección de Estadísticas (Tarea 9):** Se añadió la sección de estadísticas en la página principal (`index.php`). Esta sección utiliza los estilos predefinidos en `style.css` y la lógica de animación de contadores de `app.js` para mostrar cifras clave de la clínica de forma dinámica e impactante.
- **v3.2 (Fecha Actual):**
  - **Auditoría y Corrección de Archivos (Bitácora):** Se realizó una auditoría exhaustiva de la estructura de archivos. Se eliminaron `c:\xampp\htdocs\clinica\pages\specialties\footer.php` y `c:\xampp\htdocs\clinica\specialties.css` (del directorio raíz) por ser redundantes y estar mal ubicados. Se movieron los estilos de `specialties.css` a `c:\xampp\htdocs\clinica\pages\specialties\specialties.css` y se actualizó `c:\xampp\htdocs\clinica\pages\specialties\index.php` para cargar este CSS y su JS específico (`specialties.js`) a través del header/footer compartido. Se verificó que `c:\xampp\htdocs\clinica\pages\doctors\` no contuviera archivos `specialties.css` fuera de lugar.
  - **Refactorización de Página de Reservaciones:** Se corrigió `c:\xampp\htdocs\clinica\pages\reservations\index.php` para que utilizara los componentes `header.php` y `footer.php` compartidos, eliminando el HTML duplicado y asegurando la coherencia arquitectónica. Se declaró `$page_specific_css` y `$page_specific_js` para la carga dinámica de assets.
- **v3.1 (Fecha Actual):**
  - **Catálogo Interactivo de Especialidades (Tarea 8):** Se ha transformado la página de especialidades en una herramienta dinámica y funcional. Se implementó un catálogo generado por PHP, se mejoró el diseño de las tarjetas y se añadió un filtro de búsqueda en tiempo real con JavaScript.
- **v2.1 (Fecha Actual):**
  - **Rediseño de la Sección Testimonios (Tarea 6):** Se refactorizó el carrusel de testimonios para usar un efecto de fundido (fade) más elegante. Se mejoró el diseño de las tarjetas de testimonio y se implementó un ajuste dinámico de altura para eliminar el `layout shift`, mejorando la UX.
- **v2.0 (Fecha Actual):**
  - **Mejora de la Sección Doctores (Tarea 5):** Se rediseñaron las tarjetas de los doctores en la página principal con un diseño de superposición (overlay) más moderno y una microinteracción al pasar el cursor para revelar más detalles. Se reestructuró el HTML y se refactorizó el CSS para lograr el nuevo aspecto premium.
- **v1.9 (Fecha Actual):**
  - **Refactorización Arquitectónica (Componentes):** Se refactorizó el `header` y el `footer` en componentes PHP/CSS/JS reutilizables y encapsulados dentro del directorio `public/shared/`. Se actualizaron todas las páginas (`index.php`, `pages/specialties/`, `pages/doctors/`) para utilizar esta nueva arquitectura, mejorando drásticamente la mantenibilidad y consistencia del código. Se eliminaron los archivos `header.php` y `footer.php` del directorio raíz.
- **v1.8 (Fecha Actual):**
  - **Implementación Sección Servicios (Tarea 3):** Se ha completado el diseño y la implementación de la sección "Nuestros Servicios" en la página principal. Se crearon tarjetas de servicio con un diseño limpio y profesional, manteniendo la coherencia con el Design System.
- **v1.7:**
  - **Mejora Hero Section (Responsive y UX):** Se implementó el uso de imágenes de fondo responsivas para la sección Hero (`banner.jpg` para desktop, `banner_mobil.jpg` para móvil). Se desacopló la lógica de JS y CSS para mayor robustez. Se añadió una animación sutil de zoom (Ken Burns) y se mejoró el degradado para una apariencia más profesional y "next-level".
- **v1.6:**
  - **Rediseño de la Sección Hero (Tarea 2):** Se implementó la imagen `public/assets/banner.jpg` como fondo. Se ajustó el degradado del overlay para garantizar la legibilidad del texto y se verificó la correcta funcionalidad de los botones de acción.
- **v1.5.3:**
  - **Hotfix 3 - Corrección Definitiva Navegación Móvil:** Se solucionó el problema de visibilidad y posicionamiento del menú móvil. Se implementó un fondo oscuro de alto contraste y se aseguró que el header no interfiera visualmente, logrando un centrado y legibilidad perfectos.
- **v1.5.2:**
  - **Hotfix 2 - Rediseño Navegación Móvil:** Se rediseñó completamente el menú móvil a un estilo "Glassmorphism". Se corrigió el `z-index` para un centrado y visibilidad perfectos, y se mejoró el contraste del texto para una legibilidad y estética superiores.
- **v1.5.1:**
  - **Hotfix - Navegación Móvil:** Corregido un error de `z-index` que causaba que los enlaces del menú móvil no fueran clickables al estar cubiertos por el header.
- **v1.5:**
  - **Corrección de Navegación (Tareas 1 y 3):** Se corrigió el enlace "Especialidades" para que apunte al ancla `#specialties` en la página principal. Se creó una sección placeholder para `#services` para que el enlace correspondiente funcione correctamente, iniciando la Tarea 3.
- **v1.4:**
  - **Rediseño del Header y Navegación (Tarea 1):** Se reemplazó el isotipo genérico por un nuevo SVG que representa una cruz médica y cuidado. Se rediseñó la navegación móvil a un overlay elegante y se verificó el scroll suave.
- **v1.3:**
  - **Re-priorización del Backlog:** Se ha reestructurado y detallado la lista de tareas pendientes basándose en los requerimientos del usuario para un roadmap más claro y accionable.
- **v1.2:**
  - **Mejora del `context.md`:** Reestructuración del documento para funcionar como memoria, mapa del sistema y bitácora de cambios. Se ha añadido esta misma sección de bitácora y se han centralizado las tareas pendientes.
  - **Implementación del Protocolo de Seguridad y Calidad:** Se ha añadido la sección "PRIME DIRECTIVE" con checklists de auditoría y verificación obligatorias para minimizar errores y regresiones.

## Tareas Pendientes (Backlog Priorizado)

### Tareas para v4.4

#### Tarea 15: Implementación del Dashboard de Doctor/Administrador
*Objetivo: Crear un panel de control profesional, intuitivo y responsive que sirva como centro de operaciones para doctores y administradores.*

- **15.1 - Backend y Estructura de Datos (PHP):**
  - Crear los archivos base: `pages/dashboard/index.php`, `pages/dashboard/dashboard.css`, y `pages/dashboard/dashboard.js`.
  - Crear un endpoint para la lógica de negocio: `api/dashboard_handler.php`. Este archivo gestionará las peticiones de datos y las actualizaciones de estado.
  - Implementar la lógica de sesión en `index.php` para verificar el rol del usuario ('DOCTOR' o 'ADMINISTRADOR') y redirigir si no está autenticado.
  - En `dashboard_handler.php`, desarrollar la lógica para obtener las citas del día. La función debe ser flexible:
    - Si el rol es 'DOCTOR', obtener solo sus citas.
    - Si el rol es 'ADMINISTRADOR', obtener las citas de todos los doctores.
  - Definir y servir una estructura de datos JSON clara para el frontend. Cada cita debe incluir: `id`, `hora`, `nombre_paciente`, `motivo_consulta`, `estado` (`pendiente`, `atendida`, `no-presentado`, `rechazada`), y `doctor_info` (para la vista de admin).
  - Implementar la lógica en `dashboard_handler.php` para actualizar el estado de una cita (ej. de 'pendiente' a 'atendida').

- **15.2 - Maquetación y Componentes (HTML/PHP):**
  - Diseñar y maquetar la estructura principal del dashboard: un encabezado de bienvenida, un área para tarjetas de KPIs y el contenedor para la lista de citas.
  - Crear el HTML para los componentes clave:
    - **Tarjetas de KPI:** "Citas Atendidas Hoy", "Citas Pendientes", "No Presentados", "Rechazadas".
    - **Tabla de Citas:** Usar una `<table>` semántica con `<thead>` y `<tbody>` para mostrar la hora, paciente, motivo, estado y acciones.
    - **Controles de Filtro:** Un grupo de botones para filtrar la lista de citas por estado ("Todas", "Pendientes", "Atendidas", etc.).
    - **Selector de Doctor (Solo Admin):** Un dropdown `<select>` para que el administrador pueda ver el dashboard de un doctor específico o de "Todos".
  - Asegurar que todo el HTML sea semántico y accesible desde el inicio.

- **15.3 - Diseño y Estilos (CSS):**
  - Desarrollar los estilos en `dashboard.css`, reutilizando las variables y clases de utilidad del Design System "Aura Health" de `style.css`.
  - Diseñar las tarjetas de KPI para que sean visualmente atractivas, con tipografía clara, un ícono representativo y microinteracciones al pasar el cursor.
  - Estilizar la tabla de citas, usando `badges` de color para indicar claramente el estado de cada una (`.status-pending`, `.status-completed`, `.status-rejected`, `.status-no-show`).
  - Implementar un layout 100% responsive que garantice una experiencia óptima en móviles (donde la tabla podría convertirse en tarjetas), tablets y escritorio. El diseño debe ser elegante y no genérico.
  - Diseñar los estados `hover` y `focus` para todos los elementos interactivos (botones, filtros, filas de la tabla).

- **15.4 - Interactividad y Lógica de Frontend (JS):**
  - En `dashboard.js`, implementar la carga inicial de datos usando Fetch API para llamar a `api/dashboard_handler.php`.
  - Implementar la lógica para manejar los clics en los botones de acción ("Marcar como Atendida", "Rechazar", "Marcar como No Presentado"), enviando la actualización al backend.
  - Implementar la manipulación del DOM para actualizar la UI en tiempo real sin recargar la página (ej. mover una cita de la lista de pendientes a atendidas, cambiar su estilo y actualizar los contadores de los KPIs).
  - Implementar la funcionalidad de los filtros del lado del cliente para la lista de citas, mostrando/ocultando elementos según la selección.
  - Añadir la lógica para el selector de doctor del administrador, que volverá a solicitar los datos filtrados por el doctor seleccionado.

- **15.5 - Refinamiento y Casos Borde:**
  - Implementar estados de carga (skeletons o spinners) mientras se obtienen los datos iniciales o se actualiza una cita.
  - Diseñar y mostrar mensajes para estados vacíos (ej. "No tiene citas programadas para hoy").
  - Implementar modales de confirmación para acciones destructivas como "Rechazar Cita".
  - Realizar una auditoría final de performance, accesibilidad (navegación por teclado, lectores de pantalla) y responsive design.

### Tareas para v4.3

#### Tarea 12: Refinamiento del Formulario de Reservaciones
- **12.5 - Auditoría y Refinamiento:** Realizar una auditoría completa de accesibilidad (WCAG) para el nuevo calendario y los flujos. Mejorar el manejo de casos borde (ej. cambiar de especialidad o doctor después de haber avanzado). Implementar la funcionalidad de pre-selección de doctor/especialidad si se llega desde una URL con parámetros.

# ==============================================
# DIRECTIVAS DE TRABAJO Y REQUERIMIENTOS
# ==============================================

## Forma de Trabajar
NO modifiques archivos inmediatamente. **Sigue estrictamente el `PROTOCOLO DE SEGURIDAD Y CALIDAD (PRIME DIRECTIVE)` definido al inicio de este documento.** Primero audita, luego implementa y finalmente verifica con el checklist antes de dar por concluida la tarea.

## Reglas Fundamentales
1.  **NO ROMPER NADA:** Prohibido eliminar funcionalidades, cambiar rutas, romper el diseño responsive o la arquitectura existente.
2.  **REUTILIZAR ANTES DE CREAR:** Antes de escribir código nuevo, busca componentes, funciones, estilos o helpers existentes. No dupliques código.
3.  **MANTENER COHERENCIA:** Todo nuevo elemento debe integrarse visual y funcionalmente con el diseño existente (tipografía, colores, animaciones, etc.).

# ================================================
# FLUJO PSICOLÓGICO DEL VISITANTE
# ================================================

Cada modificación debe optimizar este recorrido:
1.  **Confianza Inmediata (<5s):** El diseño debe inspirar seguridad y profesionalismo.
2.  **Comprensión Instantánea:** ¿Qué ofrece la clínica?
3.  **Percepción de Calidad:** Evidencia de tecnología y especialistas de alto nivel.
4.  **Descubrimiento Fácil:** Encontrar especialidades y doctores sin esfuerzo.
5.  **Agendamiento Eficiente (<2min):** Un proceso de reserva rápido, moderno y sin fricciones.
6.  **Satisfacción Final:** El usuario debe sentir que ha completado una tarea importante de forma profesional y sencilla.

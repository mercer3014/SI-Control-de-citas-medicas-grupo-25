# Plan de Implementación Integral: Casos de Uso CU-01 al CU-17 (Clínica Vida Salud)

> **Para agentes y desarrolladores:** SUB-SKILL REQUERIDA: Usar `superpowers:executing-plans` para implementar este plan tarea por tarea. Cada paso utiliza sintaxis de casillas (`- [ ]`) para seguimiento estricto.

**Meta:** Finalizar, integrar y desplegar al 100% todos los casos de uso clínicos y administrativos del sistema (CU-01 al CU-17) con una interfaz de usuario clínica moderna, 100% responsiva (drawer overlay en móviles), modales flotantes para formularios/confirmaciones y exportación nativa a `.xlsx`.

**Arquitectura:** Laravel 12 MVC (PHP 8.4) + Inertia.js v3 + Vue 3 (Composition API / `<script setup>`) + Tailwind CSS v4 con paleta clínica sobria (Navy `#0F172A`, Slate, Esmeralda/Teal, Azul Salud). Base de datos PostgreSQL 18 con transacciones atómicas y registro de bitácora transversal.

**Pila Tecnológica:**
- Backend: Laravel 12, Eloquent ORM, `phpoffice/phpspreadsheet` (5.10.0), PDO PostgreSQL.
- Frontend: Vue 3, Inertia.js, Vite 7, Lucide/Heroicons SVG custom icons.
- Despliegue: Docker Swarm / Dokploy VPS (`148.116.104.212`), Traefik v3 HTTPS (`citasmedicas.nickzar.online`).

---

## Restricciones Globales de Diseño e Interfaz

1. **Responsividad 100% y Menú Lateral (Drawer Overlay):**
   - En pantallas móviles/tablets (`< lg`): el menú lateral no debe empujar el contenido de la pantalla. Debe abrirse como un drawer flotante (`z-50`) sobre un fondo traslúcido oscuro (`backdrop-blur-xs bg-slate-900/50`). Al hacer clic afuera o navegar a una ruta, se cierra automáticamente.
   - El contenido principal se mantiene en `ml-0` en móviles y solo aplica margen `lg:ml-64` / `lg:ml-[68px]` en escritorio.
   - El Navbar debe incluir botón hamburguesa visible únicamente en móviles (`lg:hidden`) para alternar el menú.
2. **Modales y Confirmaciones Obligatorias:**
   - Toda acción destructiva o cambio de estado clínico (cancelación de cita, reprogramación, baja lógica de usuario/paciente/médico, registro de consulta médica) **debe** solicitar confirmación mediante un diálogo modal (`DialogModal.vue` / `Modal.vue`).
   - Los formularios de creación/edición de turnos, ausencias, citas y consultas médicas deben presentarse en modales flotantes centrados con desenfoque de fondo y scroll interior independiente.
3. **Calidad Visual y Proporciones:**
   - Evitar estilos genéricos de IA (fondos crema amarillentos o pasteles aburridos). Emplear fondos limpios `bg-slate-50`, tarjetas blancas con bordes sutiles `border-slate-200/80` y sombras rasantes.
   - Radios de borde sobrios (`rounded-lg` a `rounded-xl`), evitando bordes redondeados excesivos o botones píldora gigantes.
   - Tamaños de campos e inputs uniformes y compactos (altura `h-9` a `h-10`, texto `text-xs` / `text-[13px]`, padding proporcional).

---

## Mapeo de Casos de Uso (CU-01 a CU-17)

| Código | Caso de Uso | Estado Actual | Tarea del Plan |
| :---: | :--- | :---: | :--- |
| **CU-01** | Gestionar Usuarios | Funcional | Tarea 1: Pulido de UI, confirmación modal y baja lógica. |
| **CU-02** | Gestionar Roles y Permisos | Funcional | Tarea 1: Vista agrupada por secciones con sincronización. |
| **CU-03** | Consultar Bitácora de Auditoría | Funcional | Tarea 1: Visor modal de JSON diff de auditoría. |
| **CU-04** | Gestionar Respaldos BD | Funcional | Tarea 1: Verificación de descarga SQL y tamaño. |
| **CU-05** | Gestionar Especialidades | Funcional | Tarea 2: Modal de alta/edición y validaciones. |
| **CU-06** | Gestionar Médicos | Funcional | Tarea 2: Especialidades múltiples y aranceles. |
| **CU-07** | Gestionar Perfil Médico Base | Funcional | Tarea 2: Ruta dedicada `/mi-perfil-medico` y edición. |
| **CU-08** | Gestionar Pacientes | Funcional | Tarea 2: Ficha médica, contacto de emergencia, bajas lógicas. |
| **CU-09** | Configurar Horarios Regulares | Pendiente | Tarea 3: Días de semana, turnos y validación de traslape. |
| **CU-10** | Gestionar Excepciones y Ausencias | Pendiente | Tarea 3: Registro de licencias/vacaciones médicas. |
| **CU-11** | Programar Cita Médica | Pendiente | Tarea 4: Wizard/modal de reserva, cálculo de slots disponibles. |
| **CU-12** | Reprogramar Cita Médica | Pendiente | Tarea 4: Modal de cambio de fecha/hora con confirmación. |
| **CU-13** | Cancelar Cita Médica | Pendiente | Tarea 4: Modal con motivo de cancelación y confirmación. |
| **CU-14** | Registrar Atención de Paciente | Pendiente | Tarea 5: Diagnóstico, receta, notas y cierre de cita. |
| **CU-15** | Consultar Historial de Citas | Pendiente | Tarea 5: Módulo central de citas con filtros y badges. |
| **CU-16** | Generar Reportes Administrativos | Pendiente | Tarea 6: KPIs, atenciones por médico, ingresos y gráficos. |
| **CU-17** | Exportar Datos a .xlsx | Pendiente | Tarea 6: Descarga Excel nativa con PhpSpreadsheet. |

---

## Tareas de Implementación

### Tarea 1: Responsividad del Layout (Drawer Overlay en Móvil) y Pulido de CU-01 a CU-04
**Archivos:**
- Modificar: `resources/js/Layouts/AppLayout.vue`
- Modificar: `resources/js/Components/Navbar.vue`
- Modificar: `resources/js/Components/Sidebar.vue`
- Modificar: `resources/js/Components/AppIcon.vue`
- Modificar: `resources/js/Pages/Usuarios/Index.vue`
- Modificar: `resources/js/Pages/Bitacora/Index.vue`

**Pasos:**
- [ ] En `AppIcon.vue`, incorporar iconos faltantes: `menu`, `calendar-days`, `file-spreadsheet`, `chart-bar`, `user-check`, `stethoscope-off`.
- [ ] En `Navbar.vue`, añadir botón hamburguesa en móviles (`lg:hidden`) que emita `@toggle-sidebar`.
- [ ] En `Sidebar.vue`, implementar modo overlay para móviles con backdrop traslúcido clickeable (`fixed inset-0 bg-slate-900/50 z-40 lg:hidden`). En pantallas `>= lg`, mantener colapsado inteligente a 68px/240px.
- [ ] En `AppLayout.vue`, eliminar márgenes forzados en móvil (`ml-0 lg:ml-64` / `lg:ml-[68px]`) para que la pantalla no se empuje.
- [ ] En `Usuarios/Index.vue`, asegurar que la eliminación/baja lógica pida confirmación con `DialogModal.vue`.
- [ ] En `Bitacora/Index.vue`, habilitar modal de inspección JSON diff para revisar valores anteriores y nuevos de cada evento.

---

### Tarea 2: Consolidación de Catálogos y Perfil Médico (CU-05, CU-06, CU-07, CU-08)
**Archivos:**
- Modificar: `routes/web.php`
- Modificar: `app/Http/Controllers/MedicoController.php`
- Crear/Modificar: `app/Http/Controllers/PerfilMedicoController.php`
- Modificar: `resources/js/Pages/Medicos/Perfil.vue`
- Modificar: `resources/js/Pages/Pacientes/Index.vue`
- Modificar: `resources/js/Pages/Pacientes/Show.vue`

**Pasos:**
- [ ] Crear ruta `/mi-perfil-medico` asignada al médico autenticado para gestionar biografía, consultorio y tarifas por especialidad (CU-07).
- [ ] En `Pacientes/Index.vue` y `Show.vue`, garantizar confirmación modal antes de baja lógica o reactivación.
- [ ] Verificar que los campos no se desborden en pantallas pequeñas (usar grids responsivas `grid-cols-1 sm:grid-cols-2`).

---

### Tarea 3: Configuración de Horarios y Ausencias Médicas (CU-09 y CU-10)
**Archivos:**
- Crear: `app/Http/Controllers/HorarioAtencionController.php`
- Crear: `app/Http/Controllers/AusenciaMedicaController.php`
- Modificar: `routes/web.php`
- Crear: `resources/js/Pages/Horarios/Index.vue`
- Crear: `resources/js/Pages/Ausencias/Index.vue`

**Pasos:**
- [ ] Implementar `HorarioAtencionController.php`:
  - Listar horarios por médico y especialidad.
  - Guardar/actualizar bloques de atención (día de la semana, hora inicio, hora fin, intervalo en minutos) con validación estricta para evitar traslapes de horario en el mismo día.
  - Toggle de activación de horario con confirmación modal.
- [ ] Implementar `AusenciaMedicaController.php`:
  - Registrar excepciones/ausencias de médicos con fecha de inicio, fin y motivo.
  - Validación de fechas (fecha fin >= fecha inicio).
  - Eliminación/cancelación de ausencia con diálogo modal.
- [ ] Crear la vista `resources/js/Pages/Horarios/Index.vue` con selector de médico, grilla visual semanal de turnos y formulario modal para añadir/editar franjas horarias.
- [ ] Crear la vista `resources/js/Pages/Ausencias/Index.vue` con listado de ausencias activas/históricas y modal de registro.

---

### Tarea 4: Agenda y Gestión de Citas Médicas (CU-11, CU-12, CU-13)
**Archivos:**
- Crear: `app/Http/Controllers/CitaController.php`
- Modificar: `routes/web.php`
- Crear: `app/Services/DisponibilidadCitaService.php`
- Crear: `resources/js/Pages/Citas/Index.vue`
- Crear: `resources/js/Pages/Citas/Partials/ModalProgramarCita.vue`
- Crear: `resources/js/Pages/Citas/Partials/ModalReprogramarCita.vue`
- Crear: `resources/js/Pages/Citas/Partials/ModalCancelarCita.vue`

**Pasos:**
- [ ] Crear el servicio `DisponibilidadCitaService.php`:
  - Dado un médico, especialidad y fecha seleccionada, obtiene los slots horarios según `horarios_atencion`.
  - Excluye slots que caigan en períodos de `ausencias_medicas`.
  - Excluye slots que ya tengan una cita en estado `Programada` o `Confirmada`.
- [ ] Implementar `CitaController.php`:
  - Endpoint `slots-disponibles`: devuelve franjas horarias libres en JSON.
  - Método `store` (CU-11): valida disponibilidad, genera código de reserva único (`CIT-XXXX`), registra la cita y emite evento en bitácora.
  - Método `reprogramar` (CU-12): valida nuevo slot disponible, actualiza fecha/hora, registra en bitácora.
  - Método `cancelar` (CU-13): recibe motivo obligatorio, cambia estado a Cancelada (ID: 4), registra en bitácora.
- [ ] Crear componentes Vue:
  - `ModalProgramarCita.vue`: selector de especialidad, médico, fecha (datepicker), franja horaria disponible generada dinámicamente y buscador de paciente con autocompletado.
  - `ModalReprogramarCita.vue`: modal limpio para cambiar fecha y franja horaria con confirmación explícita.
  - `ModalCancelarCita.vue`: campo de texto para motivo de cancelación y botón de confirmación destructivo.

---

### Tarea 5: Atención Médica y Registro Clínico (CU-14 y CU-15)
**Archivos:**
- Crear: `app/Http/Controllers/AtencionClinicaController.php`
- Modificar: `app/Http/Controllers/CitaController.php`
- Crear: `resources/js/Pages/Atenciones/Index.vue`
- Crear: `resources/js/Pages/Atenciones/Partials/ModalRegistrarAtencion.vue`
- Crear: `resources/js/Pages/Atenciones/Partials/ModalDetalleAtencion.vue`

**Pasos:**
- [ ] Implementar `AtencionClinicaController.php`:
  - Registro de atención clínica con diagnóstico, prescripción/tratamiento y notas complementarias.
  - Transacción de base de datos que registra `atenciones_clinicas` y actualiza la cita a `Atendida` (ID: 3).
  - Consulta de atenciones clínicas asociadas al historial de un paciente.
- [ ] En `Citas/Index.vue` (CU-15):
  - Grilla y tabla de citas con filtros combinados: búsqueda por paciente, filtro por médico, especialidad, rango de fechas y estado (`Programada`, `Confirmada`, `Atendida`, `Cancelada`).
  - Badges visuales distinguidos para cada estado.
  - Botones de acción contextual:
    - Si está `Programada`/`Confirmada`: Reprogramar, Atender, Cancelar.
    - Si está `Atendida`: Ver informe de atención clínica.
- [ ] Crear `Atenciones/Index.vue` con la cola de pacientes citados para hoy para atención médica rápida.

---

### Tarea 6: Reportes Administrativos y Exportación a Excel .xlsx (CU-16 y CU-17)
**Archivos:**
- Crear: `app/Http/Controllers/ReporteController.php`
- Crear: `app/Services/ExportExcelService.php`
- Modificar: `routes/web.php`
- Crear: `resources/js/Pages/Reportes/Index.vue`

**Pasos:**
- [ ] Implementar `ExportExcelService.php` utilizando `PhpOffice\PhpSpreadsheet`:
  - Generación de hojas de cálculo estilizadas con encabezado corporativo "Clínica Vida Salud", colores de cabecera institucionales, columnas autoajustadas y formatos de celda (fechas, monedas, textos).
  - Método de exportación de Citas Médicas filtradas.
  - Método de exportación de Pacientes y Fichas Clínicas.
  - Método de exportación del Resumen Administrativo y Financiero de consultas.
- [ ] Implementar `ReporteController.php`:
  - Cálculo de métricas ejecutivas en rango de fechas:
    - Total de citas agendadas, atendidas, canceladas y tasa de cumplimiento.
    - Total facturado / recaudado según aranceles de consultas atendidas por médico y especialidad.
    - Distribución de pacientes por especialidad más demandada.
  - Endpoints de exportación `.xlsx` con respuesta `streamDownload`.
- [ ] Crear `Reportes/Index.vue` con:
  - Selector de rango de fechas y filtros.
  - Tarjetas de KPIs numéricos ejecutivos.
  - Tablas de rendimiento por médico y especialidad.
  - Botón de descarga con icono de Excel: "Exportar a Excel (.xlsx)".

---

### Tarea 7: Poblamiento de Datos (Seeders) y Navegación del Sidebar
**Archivos:**
- Modificar: `database/seeders/DatabaseSeeder.php`
- Modificar: `resources/js/Components/Sidebar.vue`

**Pasos:**
- [ ] Actualizar `DatabaseSeeder.php`:
  - Sembrar franjas de horarios regulares para los 10 médicos y especialidades (`horarios_atencion`).
  - Sembrar ausencias médicas de muestra (`ausencias_medicas`).
  - Sembrar al menos 15-20 citas realistas con variedad de fechas (ayer, hoy, mañana, semana próxima) y estados (Programadas, Atendidas con registro clínico, Canceladas con motivo).
  - Sembrar atenciones clínicas vinculadas con diagnósticos médicos creíbles.
- [ ] Actualizar `Sidebar.vue`:
  - Sección **Atención y Citas**:
    - Citas Médicas (`/citas`)
    - Horarios de Atención (`/horarios`)
    - Ausencias Médicas (`/ausencias`)
    - Registro de Atenciones (`/atenciones`)
    - Reportes Administrativos (`/reportes`)
  - Sección **Catálogos Clínicos** (Arriba de seguridad):
    - Especialidades (`/especialidades`)
    - Médicos (`/medicos`)
    - Mi Perfil Médico (`/mi-perfil-medico`)
    - Pacientes (`/pacientes`)
  - Sección **Seguridad y Acceso** (Siempre abajo):
    - Usuarios (`/usuarios`)
    - Roles y Permisos (`/roles`)
    - Bitácora (`/bitacora`)
    - Respaldos BD (`/backups`)

---

### Tarea 8: Pruebas Automatizadas de Calidad (QA), Compilación y Despliegue en VPS
**Archivos:**
- Crear: `tests/Feature/CicloDosCasosDeUsoTest.php`

**Pasos:**
- [ ] Escribir pruebas funcionales en `CicloDosCasosDeUsoTest.php` que verifiquen:
  - CU-09: Configuración de horarios sin solapamiento.
  - CU-10: Registro y consulta de ausencias médicas.
  - CU-11: Programación de cita con código único y deducción de disponibilidad.
  - CU-12: Reprogramación exitosa y rechazo ante médico no disponible.
  - CU-13: Cancelación de cita con motivo obligatorio.
  - CU-14: Registro de atención clínica y cambio de estado a Atendida.
  - CU-15: Filtrado de citas en historial.
  - CU-16: Generación de métricas de reporte.
  - CU-17: Generación de descarga binaria `.xlsx`.
- [ ] Ejecutar `php artisan test` y verificar 100% de tests aprobados.
- [ ] Ejecutar `pnpm run build` y verificar 0 advertencias o errores de bundling.
- [ ] Sincronizar y subir cambios a Git (`DEV-AC` y `main`).
- [ ] Desplegar en la VPS Dokploy (`citasmedicas.nickzar.online`), ejecutar migraciones/seeders y comprobar accesibilidad HTTPS.

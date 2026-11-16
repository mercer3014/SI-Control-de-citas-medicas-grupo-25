# Sistema de Información para la Gestión, Programación y Control de Citas Médicas
## Clínica "Vida Salud" — Grupo #25

**Materia:** Ingeniería de Requisitos y Modelado - SC (Semestre 2-2026)  
**Docente:** Ing. Garzón Cuéllar Angélica  
**Universidad Autónoma Gabriel René Moreno (U.A.G.R.M.)**  
**Facultad de Ingeniería en Ciencias de la Computación y Telecomunicaciones**  

### Integrantes:
* **Cerezo Choque Alex Brayan** — Reg. 224102117
* **Vincenty Mamani David Leonel** — Reg. 224029584

---

## 1. Descripción del Proyecto

El sistema es una solución web integral orientada a la administración de pacientes, cuerpo médico, especialidades y control estricto de citas médicas de la **Clínica Vida Salud**. Reemplaza los procesos manuales por una arquitectura moderna y segura que previene duplicidad de turnos, garantiza trazabilidad histórica mediante bajas lógicas y registra todas las acciones operativas en bitácora.

---

## 2. Tecnologías Empleadas

* **Backend:** PHP 8.2 / Laravel 12 (Arquitectura Modelo-Controlador-Vista estricta).
* **Persistencia:** PostgreSQL 18 (Conexión PDO `pgsql`).
* **Frontend:** Vue.js 3 (Composition API / `<script setup>`) con **Inertia.js** y **Vite**.
* **Estilos & UI:** Tailwind CSS v4 con sistema de diseño clínico (paleta azul zafiro `#0F2942`, azul `#2563EB`, slate), alta densidad de datos (padding compacto), menú lateral retráctil y ventanas modales de confirmación estilo C# / escritorio.
* **Servicio de Correo:** Resend API para recuperación transaccional de contraseñas.
* **Pruebas Automatizadas:** PHPUnit con Feature Tests completos contra PostgreSQL.

---

## 3. Estado del Avance: Presentación 1 (Avance 50% - Ciclo 1)

Se implementaron de forma completa los dos primeros módulos del alcance oficial:

### Módulo 1: Gestión de Seguridad y Usuarios
* **`CU-01: Gestionar Usuarios`**: Inicio de sesión seguro, validaciones de inputs en cliente y servidor, bloqueo temporal de cuenta tras 5 intentos fallidos consecutivos, recuperación de contraseña con token temporal enviado por correo vía Resend API, y salida del sistema protegida por diálogo modal de confirmación.
* **`CU-09: Gestionar Roles y Permisos`**: Catálogo de roles (Administrador, Recepcionista, Médico, Paciente) y asignación granular de permisos del sistema.
* **`CU-10: Consultar Bitácora de Auditoría`**: Trazabilidad inalterable de ingresos, bloqueos y modificaciones con IP de origen, usuario responsable y marca temporal.
* **`CU-11: Gestionar Respaldos de Base de Datos`**: Monitoreo de tablas en PostgreSQL y descarga directa de respaldos en formato `.sql`.

### Módulo 2: Gestión de Catálogos (Registros)
* **`CU-04: Gestionar Especialidades`**: Registro y consulta con validación de nombres normalizados únicos y bajas lógicas.
* **`CU-03: Gestionar Médicos`**: Administración de profesionales con matrícula profesional única, teléfono de contacto y asignación de múltiples especialidades con aranceles diferenciados.
* **`CU-12: Gestionar Perfil Médico Base`**: Ficha médica con especialidades, aranceles por consulta y datos de contacto.
* **`CU-02: Gestionar Pacientes`**: Ficha clínica con validación de CI único, grupo sanguíneo, contacto de emergencia y antecedentes médicos.

---

## 4. Guía de Instalación y Despliegue Local

### Requisitos Previos
1. PHP 8.2 o superior con extensiones `pdo_pgsql` y `pgsql` habilitadas.
2. Composer 2.x
3. Node.js 18+ y pnpm (v9+)
4. PostgreSQL 14+ con la base de datos `clinica` creada.

### Pasos de Configuración:
```bash
# 1. Clonar el repositorio
git clone -b DEV-AC https://github.com/mercer3014/SI-Control-de-sitas-medicas-grupo-25.git
cd "SI-Control-de-sitas-medicas-grupo-25"

# 2. Instalar dependencias backend y frontend
composer install
pnpm install

# 3. Configurar variables de entorno
cp .env.example .env
php artisan key:generate

# Configurar en .env la conexión a PostgreSQL:
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=clinica
# DB_USERNAME=postgres
# DB_PASSWORD=tu_contraseña
# RESEND_API_KEY=tu_api_key_aqui

# 4. Ejecutar migraciones y datos semilla
php artisan migrate:fresh --seed

# 5. Compilar assets frontend
pnpm run build

# 6. Iniciar servidor local
php artisan serve
```

### Credenciales de Acceso Inicial:
* **Usuario:** `admin`
* **Contraseña:** `admin123`
* **Rol:** Administrador

---

## 5. Verificación y Pruebas Automatizadas (QA)

Para ejecutar la suite de pruebas unitarias y de integración que certifica el correcto funcionamiento del 50%:
```bash
php artisan test --filter=CicloUnoQATest
```
Las 8 pruebas automatizadas validan:
1. Autenticación exitosa y registro de bitácora.
2. Bloqueo de cuenta tras 5 fallos consecutivos de clave.
3. Envío y registro de token para recuperación de contraseña.
4. Prevención de especialidades duplicadas.
5. Prevención de matrículas médicas duplicadas.
6. Prevención de CI duplicado en pacientes.
7. Conservación de datos históricos mediante bajas lógicas (`estado = FALSE`).
8. Generación íntegra de volcado SQL de respaldo.

---

## 6. Pasarela de pagos (CU-18, CU-19 y recaudación de CU-16)

El cobro en línea no guarda datos de tarjeta. `PasarelaPagoService` implementa `InterfazPasarela` y elige el proveedor con `PASARELA_DRIVER` en `.env`:

| Variable | Uso |
| --- | --- |
| `PASARELA_DRIVER` | `fake` (sandbox local, valor por defecto) o `stripe` |
| `PASARELA_WEBHOOK_SECRET` | Secreto HMAC del webhook. Vacío + `fake` usa el secreto de desarrollo `fake-local-sandbox` |
| `PASARELA_API_KEY` / `PASARELA_URL` | Reservadas para un proveedor HTTP propio |
| `STRIPE_SECRET` | Clave secreta de Stripe (`sk_test_...`), solo si el driver es `stripe` |
| `STRIPE_WEBHOOK_SECRET` | Secreto de firma de Stripe (`whsec_...`) |
| `STRIPE_CURRENCY` | Moneda del Checkout. Stripe no admite BOB; déjela vacía para intentar `bob` o indique una moneda soportada |

```bash
# .env
PASARELA_DRIVER=fake
PASARELA_WEBHOOK_SECRET=
PASARELA_FAKE_ACEPTA_REEMBOLSO=true
```

Después de `php artisan migrate --seed` quedan cuatro métodos de pago (QR Simple, tarjeta de débito, tarjeta de crédito y transferencia).

### Probar el flujo local (sin credenciales)

1. Inicie sesión como paciente (`paciente.lucia` / `paciente123`) y abra **Mis Citas**.
2. En una cita **Programada**, pulse **Pagar consulta**. El monto sale de `medico_especialidad.precio_consulta`.
3. Elija el método y pulse **Ir a pagar**. El sistema crea el pago en **Pendiente** y abre el checkout simulado.
4. **Aprobar pago** envía `POST /pagos/webhook` firmado con HMAC-SHA256 (`referencia|estado|monto|moneda`, cabecera `X-Pasarela-Firma`). La cita pasa a **Confirmada** y se muestra el comprobante.
5. **Rechazar pago** deja la cita **Programada** y muestra «Pago rechazado, intente nuevamente».
6. Como administrador (`admin` / `admin123`), en **Gestión de Pagos** reembolse un pago **Aprobado**. Si la cita no fue atendida pasa a **Cancelada**; si ya está **Atendida**, no cambia. Un pago que no esté aprobado responde «Solo se pueden reembolsar pagos aprobados».
7. En **Reportes Administrativos**, la recaudación suma solo pagos **Aprobado** con `fecha_pago` en el rango. **Pendiente** y **Rechazado** no suman. Los **Reembolsado** aparecen aparte como reembolsos del periodo (`fecha_reembolso`).

La ruta `POST /pagos/webhook` está fuera de la verificación CSRF. Reenviar la misma referencia no duplica el pago ni la bitácora.

### Pruebas automáticas PN11–PN18

Con PostgreSQL en marcha (`DB_DATABASE=clinica`, como en `phpunit.xml`):

```bash
php artisan test --filter=PasarelaPagosTest
php artisan test
npm run build
```


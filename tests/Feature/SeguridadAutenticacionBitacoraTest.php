<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\UsuarioModel;
use App\Models\PersonaModel;
use App\Models\RolModel;
use App\Models\BitacoraModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SeguridadAutenticacionBitacoraTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');

        // Garantizar estado limpio para el usuario administrador de pruebas
        UsuarioModel::where('username', 'admin')->update([
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
            'nivel_bloqueo' => 0,
            'fecha_bloqueo' => null,
            'estado' => true,
        ]);
    }

    /**
     * 1. Bloqueo por intentos fallidos consecutivos:
     * - 4 intentos fallidos: incrementa contador pero NO bloquea.
     * - 5to intento fallido consecutivo: activa bloqueo temporal (nivel 1, 5 minutos),
     *   establece `bloqueado_hasta` y genera registro en bitácora (BLOQUEO_SEGURIDAD).
     */
    public function test_bloqueo_estricto_tras_cinco_intentos_fallidos_consecutivos(): void
    {
        $usuario = UsuarioModel::where('username', 'admin')->first();

        // Intentos 1 al 4
        for ($i = 1; $i <= 4; $i++) {
            $resp = $this->post('/login', [
                'login' => 'admin',
                'password' => 'clave_invalida_' . $i,
            ]);
            $resp->assertSessionHasErrors(['login']);
            $usuario->refresh();
            $this->assertEquals($i, $usuario->intentos_fallidos);
            $this->assertNull($usuario->bloqueado_hasta);
            $this->assertFalse($usuario->isBloqueado());
        }

        // 5to intento: Debe disparar el bloqueo
        $resp5 = $this->post('/login', [
            'login' => 'admin',
            'password' => 'clave_invalida_5',
        ]);

        $resp5->assertSessionHasErrors(['login']);
        $usuario->refresh();

        $this->assertEquals(5, $usuario->intentos_fallidos);
        $this->assertEquals(1, $usuario->nivel_bloqueo);
        $this->assertNotNull($usuario->bloqueado_hasta);
        $this->assertTrue($usuario->bloqueado_hasta->isFuture());
        $this->assertTrue($usuario->isBloqueado());

        // Verificar registro de auditoría de bloqueo
        $this->assertDatabaseHas('bitacora', [
            'usuario_id' => $usuario->id,
            'accion' => 'BLOQUEO_SEGURIDAD',
            'modulo' => 'Seguridad',
        ]);
    }

    /**
     * 1b. Bloqueo activo impide nuevo acceso antes del tiempo límite,
     * incluso si se ingresan las credenciales correctas.
     */
    public function test_cuenta_bloqueada_rechaza_acceso_incluso_con_credenciales_correctas(): void
    {
        $usuario = UsuarioModel::where('username', 'admin')->first();

        // Forzar estado bloqueado con 5 minutos a futuro
        $usuario->update([
            'intentos_fallidos' => 5,
            'nivel_bloqueo' => 1,
            'bloqueado_hasta' => now()->addMinutes(5),
            'fecha_bloqueo' => now(),
        ]);

        $this->assertTrue($usuario->isBloqueado());

        // Intentar iniciar sesión con la contraseña CORRECTA ('admin123')
        $response = $this->post('/login', [
            'login' => 'admin',
            'password' => 'admin123',
        ]);

        // Debe ser rechazado, sin iniciar sesión
        $response->assertSessionHasErrors(['login']);
        $this->assertGuest();

        // Debe registrar evento de ACCESO_BLOQUEADO en bitácora
        $this->assertDatabaseHas('bitacora', [
            'usuario_id' => $usuario->id,
            'accion' => 'ACCESO_BLOQUEADO',
            'modulo' => 'Seguridad',
        ]);
    }

    /**
     * 1c. Escalamiento de niveles de bloqueo:
     * Nivel 1 (5 min) -> Nivel 2 (30 min) -> Nivel 3 (Permanente).
     */
    public function test_escalamiento_de_bloqueo_a_nivel_dos_y_permanente(): void
    {
        $usuario = UsuarioModel::where('username', 'admin')->first();

        // Nivel 1 (simular que expiró y vuelve a fallar 5 veces)
        $usuario->update(['nivel_bloqueo' => 1, 'intentos_fallidos' => 4, 'bloqueado_hasta' => null]);
        $quedoBloqueada = $usuario->registrarIntentoFallido();
        $this->assertTrue($quedoBloqueada);
        $this->assertEquals(2, $usuario->nivel_bloqueo);
        $this->assertNotNull($usuario->bloqueado_hasta);

        // Nivel 2 -> Nivel 3 (Permanente)
        $usuario->update(['intentos_fallidos' => 4, 'bloqueado_hasta' => null]);
        $quedoBloqueada = $usuario->registrarIntentoFallido();
        $this->assertTrue($quedoBloqueada);
        $this->assertEquals(3, $usuario->nivel_bloqueo);
        $this->assertNull($usuario->bloqueado_hasta); // Nivel 3 es permanente
        $this->assertTrue($usuario->esBloqueoPermanente());
        $this->assertTrue($usuario->isBloqueado());

        // Intento de login en cuenta bloqueada permanentemente
        $resp = $this->post('/login', [
            'login' => 'admin',
            'password' => 'admin123',
        ]);
        $resp->assertSessionHasErrors(['login']);
        $this->assertGuest();
    }

    /**
     * 2. Recuperación por correo electrónico:
     * - Generación de token en password_reset_tokens (6 dígitos).
     * - Envío seguro y registro en bitácora (SOLICITUD_RECUPERACION).
     */
    public function test_solicitud_recuperacion_contrasena_genera_token_y_bitacora(): void
    {
        $email = 'admin@gmail.com';

        // Limpiar tokens previos
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        $response = $this->post('/forgot-password', [
            'email' => $email,
        ]);

        $response->assertRedirect(route('password.reset', ['email' => $email]));
        $response->assertSessionHas('success');

        // Verificar token de 6 dígitos numéricos generado en base de datos
        $record = DB::table('password_reset_tokens')->where('email', $email)->first();
        $this->assertNotNull($record);
        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $record->token);

        // Verificar registro en bitácora
        $this->assertDatabaseHas('bitacora', [
            'accion' => 'SOLICITUD_RECUPERACION',
            'modulo' => 'Seguridad',
        ]);
    }

    /**
     * 2b. Restablecimiento de contraseña con confirmación, validación y desbloqueo de cuenta.
     */
    public function test_restablecimiento_exitoso_cambia_password_desbloquea_y_permite_acceso(): void
    {
        $usuario = UsuarioModel::where('username', 'admin')->first();

        // Poner la cuenta en estado bloqueado para verificar que el cambio de clave la desbloquee
        $usuario->update([
            'intentos_fallidos' => 5,
            'nivel_bloqueo' => 2,
            'bloqueado_hasta' => now()->addMinutes(30),
        ]);
        $this->assertTrue($usuario->isBloqueado());

        // Generar token en DB
        $token = '123456';
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $usuario->email],
            ['token' => $token, 'created_at' => now()]
        );

        // Ejecutar restablecimiento con confirmación
        $nuevaClave = 'nuevaClaveSegura2026';
        $response = $this->post('/reset-password', [
            'email' => $usuario->email,
            'token' => $token,
            'password' => $nuevaClave,
            'password_confirmation' => $nuevaClave,
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('success');

        // Token debe haberse eliminado de password_reset_tokens
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $usuario->email,
        ]);

        // Verificar que la cuenta quedó completamente desbloqueada
        $usuario->refresh();
        $this->assertFalse($usuario->isBloqueado());
        $this->assertEquals(0, $usuario->intentos_fallidos);
        $this->assertEquals(0, $usuario->nivel_bloqueo);
        $this->assertNull($usuario->bloqueado_hasta);

        // Verificar registro de auditoría
        $this->assertDatabaseHas('bitacora', [
            'usuario_id' => $usuario->id,
            'accion' => 'CAMBIO_PASSWORD',
            'modulo' => 'Seguridad',
        ]);

        // Verificar que ahora se puede iniciar sesión con la nueva contraseña
        $loginResp = $this->post('/login', [
            'login' => $usuario->username,
            'password' => $nuevaClave,
        ]);
        $loginResp->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($usuario);
    }

    /**
     * 2c. Rechazo de token incorrecto y token expirado (> 15 minutos).
     */
    public function test_restablecimiento_rechaza_token_invalido_y_token_expirado(): void
    {
        $email = 'admin@gmail.com';

        // Caso 1: Token incorrecto
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => '999999', 'created_at' => now()]
        );

        $respInvalido = $this->post('/reset-password', [
            'email' => $email,
            'token' => '000000',
            'password' => 'clave123',
            'password_confirmation' => 'clave123',
        ]);
        $respInvalido->assertSessionHasErrors(['token']);

        // Caso 2: Token expirado (> 15 min)
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => '555555', 'created_at' => now()->subMinutes(20)]
        );

        $respExpirado = $this->post('/reset-password', [
            'email' => $email,
            'token' => '555555',
            'password' => 'clave123',
            'password_confirmation' => 'clave123',
        ]);
        $respExpirado->assertSessionHasErrors(['token']);
    }

    /**
     * 3. Bitácora de Auditoría:
     * Verificar que cada acción crítica registre IP, usuario, módulo y detalle.
     */
    public function test_bitacora_registra_eventos_completos_con_ip_usuario_modulo_y_detalle(): void
    {
        $admin = UsuarioModel::where('username', 'admin')->first();

        // A. Login
        $this->post('/login', [
            'login' => 'admin',
            'password' => 'admin123',
        ]);
        $this->assertDatabaseHas('bitacora', [
            'usuario_id' => $admin->id,
            'accion' => 'INICIO_SESION',
            'modulo' => 'Seguridad',
        ]);

        // Verificar que los campos requeridos no sean nulos
        $logLogin = BitacoraModel::where('accion', 'INICIO_SESION')->latest('id')->first();
        $this->assertNotNull($logLogin->ip_origen);
        $this->assertNotNull($logLogin->fecha_hora);
        $this->assertNotNull($logLogin->detalle);

        // B. Cierre de sesión (Logout)
        $this->actingAs($admin)->post('/logout');
        $this->assertDatabaseHas('bitacora', [
            'usuario_id' => $admin->id,
            'accion' => 'CIERRE_SESION',
            'modulo' => 'Seguridad',
        ]);

        // C. Creación de Usuario
        $randomCi = (string) random_int(10000000, 99999999);
        $username = 'audit_user_' . uniqid();
        $email = $username . '@test.com';

        $this->actingAs($admin)->post('/usuarios', [
            'ci' => $randomCi,
            'nombres' => 'Audit',
            'apellidos' => 'User',
            'fecha_nacimiento' => '1990-01-01',
            'genero' => 'M',
            'rol_id' => $admin->rol_id,
            'username' => $username,
            'email' => $email,
            'password' => 'password123',
        ]);

        $this->assertDatabaseHas('bitacora', [
            'accion' => 'CREACION_USUARIO',
            'modulo' => 'Seguridad',
        ]);

        $nuevoUsuario = UsuarioModel::where('username', $username)->first();
        $this->assertNotNull($nuevoUsuario);

        // D. Modificación de Usuario
        $this->actingAs($admin)->put("/usuarios/{$nuevoUsuario->id}", [
            'ci' => $randomCi,
            'nombres' => 'Audit Modificado',
            'apellidos' => 'User Modificado',
            'fecha_nacimiento' => '1990-01-01',
            'genero' => 'M',
            'rol_id' => $admin->rol_id,
            'username' => $username,
            'email' => $email,
            'estado' => true,
        ]);

        $this->assertDatabaseHas('bitacora', [
            'accion' => 'MODIFICACION_USUARIO',
            'modulo' => 'Seguridad',
        ]);

        // E. Baja lógica (Eliminación) de Usuario
        $this->actingAs($admin)->delete("/usuarios/{$nuevoUsuario->id}");

        $this->assertDatabaseHas('bitacora', [
            'accion' => 'ELIMINACION_USUARIO',
            'modulo' => 'Seguridad',
            'usuario_id' => $nuevoUsuario->id,
        ]);
    }

    /**
     * 3b. Módulo de Bitácora: Consulta, filtros y control de acceso.
     */
    public function test_modulo_bitacora_permite_filtrado_y_restringe_a_no_administradores(): void
    {
        $admin = UsuarioModel::where('username', 'admin')->first();

        // Acceso como Administrador
        $respAdmin = $this->actingAs($admin)->get('/bitacora?modulo=Seguridad&accion=INICIO_SESION');
        $respAdmin->assertStatus(200);

        // Acceso como usuario no administrador (Recepcionista) -> 403 Forbidden
        $rolRecepcion = RolModel::where('nombre', 'Recepcionista')->first();
        $persona = PersonaModel::create([
            'ci' => (string) random_int(10000000, 99999999),
            'nombres' => 'Recepcionista',
            'apellidos' => 'Audit',
            'fecha_nacimiento' => '1995-03-12',
            'genero' => 'F',
        ]);
        $recepcionista = UsuarioModel::create([
            'persona_id' => $persona->id,
            'rol_id' => $rolRecepcion->id,
            'username' => 'recep_' . uniqid(),
            'email' => 'recep_' . uniqid() . '@test.com',
            'password' => Hash::make('secret123'),
            'estado' => true,
        ]);

        $respRecep = $this->actingAs($recepcionista)->get('/bitacora');
        $respRecep->assertStatus(403);
    }
}

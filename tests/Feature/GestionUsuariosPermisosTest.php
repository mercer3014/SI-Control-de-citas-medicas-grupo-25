<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\UsuarioModel;
use App\Models\PersonaModel;
use App\Models\RolModel;
use App\Models\PermisoModel;
use Illuminate\Support\Facades\Hash;

class GestionUsuariosPermisosTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    /**
     * 1. Creación de Usuarios: Registro exitoso creando tanto Persona como Usuario asociado (CU-01 / CU-02)
     */
    public function test_creacion_exitosa_de_usuario_y_persona_asociada(): void
    {
        $admin = UsuarioModel::where('username', 'admin')->first();
        $rolRecepcion = RolModel::where('nombre', 'Recepcionista')->first();

        $ci = (string) random_int(10000000, 99999999);
        $username = 'usuario_test_' . uniqid();
        $email = 'test_' . uniqid() . '@clinica.com';

        $response = $this->actingAs($admin)->post('/usuarios', [
            'ci' => $ci,
            'nombres' => 'María Belén',
            'apellidos' => 'Pérez Ramos',
            'fecha_nacimiento' => '1995-04-12',
            'genero' => 'F',
            'telefono' => '78912345',
            'direccion' => 'Av. San Martín #450',
            'rol_id' => $rolRecepcion->id,
            'username' => $username,
            'email' => $email,
            'password' => 'secret123',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        // Verificar que la persona fue creada
        $this->assertDatabaseHas('personas', [
            'ci' => $ci,
            'nombres' => 'María Belén',
            'apellidos' => 'Pérez Ramos',
            'genero' => 'F',
        ]);

        $persona = PersonaModel::where('ci', $ci)->first();
        $this->assertNotNull($persona);

        // Verificar que el usuario fue creado asociado a la persona
        $this->assertDatabaseHas('usuarios', [
            'persona_id' => $persona->id,
            'rol_id' => $rolRecepcion->id,
            'username' => strtolower($username),
            'email' => strtolower($email),
            'estado' => true,
        ]);

        $usuario = UsuarioModel::where('username', strtolower($username))->first();
        $this->assertNotNull($usuario);
        $this->assertTrue(Hash::check('secret123', $usuario->password));

        // Verificar registro de auditoría en bitácora
        $this->assertDatabaseHas('bitacora', [
            'accion' => 'CREACION_USUARIO',
            'modulo' => 'Seguridad',
        ]);
    }

    /**
     * 1. Creación de Usuarios: Validación de CI único en la tabla personas
     */
    public function test_creacion_usuario_rechaza_ci_duplicado(): void
    {
        $admin = UsuarioModel::where('username', 'admin')->first();
        $rol = RolModel::first();
        $personaExistente = PersonaModel::first();

        $response = $this->actingAs($admin)->post('/usuarios', [
            'ci' => $personaExistente->ci, // CI duplicado
            'nombres' => 'Juan Carlos',
            'apellidos' => 'Gutiérrez',
            'fecha_nacimiento' => '1990-01-01',
            'genero' => 'M',
            'rol_id' => $rol->id,
            'username' => 'nuevo_user_' . uniqid(),
            'email' => 'nuevo_' . uniqid() . '@clinica.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['ci']);
        $this->assertEquals(
            'Ya existe una persona registrada con este documento de identidad.',
            session('errors')->first('ci')
        );
    }

    /**
     * 1. Creación de Usuarios: Validación de username único en la tabla usuarios
     */
    public function test_creacion_usuario_rechaza_username_duplicado(): void
    {
        $admin = UsuarioModel::where('username', 'admin')->first();
        $rol = RolModel::first();

        $response = $this->actingAs($admin)->post('/usuarios', [
            'ci' => (string) random_int(10000000, 99999999),
            'nombres' => 'Carlos',
            'apellidos' => 'Mendoza',
            'fecha_nacimiento' => '1988-06-20',
            'genero' => 'M',
            'rol_id' => $rol->id,
            'username' => 'admin', // Username ya existente
            'email' => 'admin_otro_' . uniqid() . '@clinica.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['username']);
        $this->assertEquals(
            'Este nombre de usuario ya se encuentra en uso.',
            session('errors')->first('username')
        );
    }

    /**
     * 1. Creación de Usuarios: Validación de email único en la tabla usuarios
     */
    public function test_creacion_usuario_rechaza_email_duplicado(): void
    {
        $admin = UsuarioModel::where('username', 'admin')->first();
        $rol = RolModel::first();

        $response = $this->actingAs($admin)->post('/usuarios', [
            'ci' => (string) random_int(10000000, 99999999),
            'nombres' => 'Lucía',
            'apellidos' => 'Fernández',
            'fecha_nacimiento' => '1992-03-15',
            'genero' => 'F',
            'rol_id' => $rol->id,
            'username' => 'lucia_' . uniqid(),
            'email' => 'admin@gmail.com', // Email ya existente
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertEquals(
            'Este correo electrónico ya está registrado.',
            session('errors')->first('email')
        );
    }

    /**
     * 2. Eliminación de Usuarios: Baja Lógica (DELETE /usuarios/{id}) cambia estado = false
     * y conserva el registro físico sin romper integridad referencial
     */
    public function test_eliminacion_de_usuario_ejecuta_baja_logica_y_conserva_registro_fisico(): void
    {
        $admin = UsuarioModel::where('username', 'admin')->first();
        $rol = RolModel::first();

        // Crear usuario para la prueba de baja lógica
        $persona = PersonaModel::create([
            'ci' => (string) random_int(10000000, 99999999),
            'nombres' => 'Usuario',
            'apellidos' => 'Para Eliminar',
            'fecha_nacimiento' => '1990-10-10',
            'genero' => 'M',
        ]);

        $usuario = UsuarioModel::create([
            'persona_id' => $persona->id,
            'rol_id' => $rol->id,
            'username' => 'usuario_borrar_' . uniqid(),
            'email' => 'borrar_' . uniqid() . '@clinica.com',
            'password' => Hash::make('password123'),
            'estado' => true,
        ]);

        $this->assertTrue($usuario->estado);

        // Ejecutar eliminación (baja lógica)
        $response = $this->actingAs($admin)->delete("/usuarios/{$usuario->id}");

        $response->assertSessionHas('success');

        // Verificar que el estado cambió a false (baja lógica)
        $usuario->refresh();
        $this->assertFalse($usuario->estado);

        // INTEGRIDAD REFERENCIAL: El registro físico del usuario NO debe ser eliminado de la BD
        $this->assertDatabaseHas('usuarios', [
            'id' => $usuario->id,
            'estado' => false,
        ]);

        // INTEGRIDAD REFERENCIAL: El registro físico de la persona asociada NO debe ser eliminado de la BD
        $this->assertDatabaseHas('personas', [
            'id' => $persona->id,
        ]);

        // Verificar registro de auditoría en bitácora
        $this->assertDatabaseHas('bitacora', [
            'accion' => 'ELIMINACION_USUARIO',
            'modulo' => 'Seguridad',
            'usuario_id' => $usuario->id,
        ]);
    }

    /**
     * 2. Eliminación de Usuarios: El usuario autenticado no puede eliminarse a sí mismo
     */
    public function test_usuario_autenticado_no_puede_eliminarse_a_si_mismo(): void
    {
        $admin = UsuarioModel::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->delete("/usuarios/{$admin->id}");

        $response->assertSessionHas('error', 'No puedes eliminar tu propia cuenta activa.');

        $admin->refresh();
        $this->assertTrue($admin->estado);
    }

    /**
     * 3. Asignación y Quita de Permisos (CU-02): Asignación dinámica con sync
     */
    public function test_asignacion_dinamica_de_permisos_a_rol(): void
    {
        $admin = UsuarioModel::where('username', 'admin')->first();
        $rol = RolModel::where('nombre', 'Recepcionista')->first();

        // Obtener permisos específicos a asignar
        $permisosIds = PermisoModel::whereIn('nombre', [
            'gestionar_pacientes',
            'programar_citas',
            'cancelar_citas'
        ])->pluck('id')->toArray();

        $response = $this->actingAs($admin)->put("/roles/{$rol->id}", [
            'descripcion' => 'Recepción y coordinación de citas médicas',
            'permisos' => $permisosIds,
            'estado' => true,
        ]);

        $response->assertSessionHas('success');
        $rol->refresh();

        // Verificar que el rol tiene exactamente los permisos asignados
        $this->assertEqualsCanonicalizing($permisosIds, $rol->permisos->pluck('id')->toArray());

        // Verificar bitácora de auditoría
        $this->assertDatabaseHas('bitacora', [
            'accion' => 'MODIFICACION_ROL',
            'modulo' => 'Seguridad',
        ]);
    }

    /**
     * 3. Asignación y Quita de Permisos (CU-02): Quita dinámica de permisos
     */
    public function test_quita_dinamica_de_permisos_a_rol(): void
    {
        $admin = UsuarioModel::where('username', 'admin')->first();
        $rol = RolModel::where('nombre', 'Médico')->first();

        // Inicialmente asignar dos permisos
        $permisosIniciales = PermisoModel::whereIn('nombre', [
            'registrar_atencion',
            'gestionar_pacientes'
        ])->pluck('id')->toArray();
        $rol->permisos()->sync($permisosIniciales);
        $this->assertCount(2, $rol->fresh()->permisos);

        // Ahora quitar 'gestionar_pacientes' dejando únicamente 'registrar_atencion'
        $permisoConservado = PermisoModel::where('nombre', 'registrar_atencion')->pluck('id')->toArray();

        $response = $this->actingAs($admin)->put("/roles/{$rol->id}", [
            'descripcion' => 'Médico especialista de planta',
            'permisos' => $permisoConservado,
            'estado' => true,
        ]);

        $response->assertSessionHas('success');
        $rol->refresh();

        // Verificar que el permiso fue quitado
        $this->assertCount(1, $rol->permisos);
        $this->assertEquals($permisoConservado, $rol->permisos->pluck('id')->toArray());
        $this->assertFalse($rol->permisos->contains('nombre', 'gestionar_pacientes'));
    }

    /**
     * 3. Asignación y Quita de Permisos (CU-02): Desasignación total (array vacío de permisos)
     */
    public function test_desasignacion_total_de_permisos_a_rol(): void
    {
        $admin = UsuarioModel::where('username', 'admin')->first();
        $rol = RolModel::where('nombre', 'Paciente')->first();

        // Enviar array vacío de permisos para revocar todos los permisos
        $response = $this->actingAs($admin)->put("/roles/{$rol->id}", [
            'descripcion' => 'Rol de paciente sin permisos administrativos',
            'permisos' => [],
            'estado' => true,
        ]);

        $response->assertSessionHas('success');
        $rol->refresh();

        $this->assertCount(0, $rol->permisos);
    }
}

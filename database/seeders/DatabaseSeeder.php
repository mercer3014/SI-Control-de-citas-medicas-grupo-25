<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\RolModel;
use App\Models\PermisoModel;
use App\Models\PersonaModel;
use App\Models\UsuarioModel;
use App\Models\DiaSemanaModel;
use App\Models\EstadoCitaModel;
use App\Models\EspecialidadModel;
use App\Models\MedicoModel;
use App\Models\MedicoEspecialidadModel;
use App\Models\PacienteModel;
use App\Models\AntecedenteMedicoModel;
use App\Models\BitacoraModel;
use App\Models\HorarioAtencionModel;
use App\Models\AusenciaMedicaModel;
use App\Models\CitaModel;
use App\Models\AtencionClinicaModel;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // -------------------------------------------------------------
        // 1. DÍAS DE LA SEMANA (Catálogo Base)
        // -------------------------------------------------------------
        $dias = [
            ['id' => 1, 'nombre' => 'Lunes'],
            ['id' => 2, 'nombre' => 'Martes'],
            ['id' => 3, 'nombre' => 'Miércoles'],
            ['id' => 4, 'nombre' => 'Jueves'],
            ['id' => 5, 'nombre' => 'Viernes'],
            ['id' => 6, 'nombre' => 'Sábado'],
            ['id' => 7, 'nombre' => 'Domingo'],
        ];
        foreach ($dias as $d) {
            DiaSemanaModel::updateOrCreate(['id' => $d['id']], $d);
        }

        // -------------------------------------------------------------
        // 2. ESTADOS DE CITA (Catálogo Transaccional)
        // -------------------------------------------------------------
        $estados = [
            ['id' => 1, 'nombre' => 'Programada'],
            ['id' => 2, 'nombre' => 'Confirmada'],
            ['id' => 3, 'nombre' => 'Atendida'],
            ['id' => 4, 'nombre' => 'Cancelada'],
            ['id' => 5, 'nombre' => 'Inasistencia'],
        ];
        foreach ($estados as $est) {
            EstadoCitaModel::updateOrCreate(['id' => $est['id']], $est);
        }

        // -------------------------------------------------------------
        // 3. ROLES DE USUARIO
        // -------------------------------------------------------------
        $roles = [
            ['id' => 1, 'nombre' => 'Administrador', 'descripcion' => 'Control total de configuración, usuarios, seguridad y respaldos.'],
            ['id' => 2, 'nombre' => 'Recepcionista', 'descripcion' => 'Gestión operativa de pacientes, catálogo de médicos y programación de citas.'],
            ['id' => 3, 'nombre' => 'Médico', 'descripcion' => 'Consulta de agenda diaria de citas y registro de atención clínica.'],
            ['id' => 4, 'nombre' => 'Paciente', 'descripcion' => 'Portal de autoservicio para consulta y reserva de citas.'],
        ];
        foreach ($roles as $r) {
            RolModel::updateOrCreate(['id' => $r['id']], $r);
        }

        // -------------------------------------------------------------
        // 4. PERMISOS DEL SISTEMA Y MATRIZ RBAC
        // -------------------------------------------------------------
        $permisos = [
            ['nombre' => 'gestionar_usuarios', 'descripcion' => 'Crear, listar, editar y desactivar usuarios'],
            ['nombre' => 'gestionar_roles', 'descripcion' => 'Ver y administrar roles y asignación de permisos'],
            ['nombre' => 'ver_bitacora', 'descripcion' => 'Consultar bitácora de auditoría de eventos del sistema'],
            ['nombre' => 'gestionar_backups', 'descripcion' => 'Exportar y descargar respaldos SQL de la base de datos'],
            ['nombre' => 'gestionar_especialidades', 'descripcion' => 'Administrar catálogo de especialidades médicas'],
            ['nombre' => 'gestionar_medicos', 'descripcion' => 'Administrar registro, matrícula y aranceles de médicos'],
            ['nombre' => 'gestionar_pacientes', 'descripcion' => 'Administrar ficha clínica y datos de pacientes'],
            ['nombre' => 'gestionar_horarios', 'descripcion' => 'Configurar disponibilidad y turnos médicos'],
            ['nombre' => 'programar_citas', 'descripcion' => 'Registrar, confirmar y reprogramar citas médicas'],
            ['nombre' => 'cancelar_citas', 'descripcion' => 'Cancelar citas programadas'],
            ['nombre' => 'registrar_atencion', 'descripcion' => 'Registrar diagnóstico y tratamiento clínico'],
            ['nombre' => 'ver_reportes', 'descripcion' => 'Generar reportes estadísticos y administrativos'],
        ];

        $adminRol = RolModel::find(1);
        $recepRol = RolModel::find(2);
        $medicoRol = RolModel::find(3);

        foreach ($permisos as $p) {
            $perm = PermisoModel::firstOrCreate(['nombre' => $p['nombre']], $p);
            $adminRol->permisos()->syncWithoutDetaching([$perm->id]);

            if (in_array($p['nombre'], ['gestionar_pacientes', 'gestionar_medicos', 'gestionar_especialidades', 'programar_citas', 'cancelar_citas', 'gestionar_horarios'])) {
                $recepRol->permisos()->syncWithoutDetaching([$perm->id]);
            }

            if (in_array($p['nombre'], ['registrar_atencion'])) {
                $medicoRol->permisos()->syncWithoutDetaching([$perm->id]);
            }
        }

        // -------------------------------------------------------------
        // 5. ESPECIALIDADES MÉDICAS (10 Registros)
        // -------------------------------------------------------------
        $especialidadesData = [
            ['id' => 1, 'nombre' => 'Medicina General', 'descripcion' => 'Atención primaria, diagnóstico inicial y control preventivo integral.'],
            ['id' => 2, 'nombre' => 'Pediatría', 'descripcion' => 'Cuidado integral de la salud del recién nacido, niños y adolescentes.'],
            ['id' => 3, 'nombre' => 'Ginecología y Obstetricia', 'descripcion' => 'Salud femenina integral, control prenatal y salud reproductiva.'],
            ['id' => 4, 'nombre' => 'Odontología', 'descripcion' => 'Salud bucodental, profilaxis, endodoncia y estética dental.'],
            ['id' => 5, 'nombre' => 'Cardiología', 'descripcion' => 'Diagnóstico, prevención y tratamiento de enfermedades cardiovasculares.'],
            ['id' => 6, 'nombre' => 'Traumatología y Ortopedia', 'descripcion' => 'Tratamiento de lesiones óseas, articulares, ligamentosas y musculares.'],
            ['id' => 7, 'nombre' => 'Dermatología', 'descripcion' => 'Diagnóstico y tratamiento de patologías de la piel, uñas y cabello.'],
            ['id' => 8, 'nombre' => 'Oftalmología', 'descripcion' => 'Cuidado de la visión, fondo de ojo y tratamiento oftalmológico.'],
            ['id' => 9, 'nombre' => 'Neurología', 'descripcion' => 'Evaluación de trastornos neurológicos, cefaleas y sistema nervioso.'],
            ['id' => 10, 'nombre' => 'Otorrinolaringología', 'descripcion' => 'Tratamiento de patologías del oído, nariz, garganta y senos paranasales.'],
        ];

        foreach ($especialidadesData as $ed) {
            EspecialidadModel::updateOrCreate(['id' => $ed['id']], [
                'nombre' => $ed['nombre'],
                'descripcion' => $ed['descripcion'],
                'estado' => true,
            ]);
        }

        // -------------------------------------------------------------
        // 6. USUARIOS Y PERSONAL CLAVE
        // -------------------------------------------------------------
        
        // A) Administrador General
        $pAdmin = PersonaModel::updateOrCreate(
            ['ci' => '10000001'],
            [
                'nombres' => 'Alex Brayan',
                'apellidos' => 'Cerezo Choque',
                'fecha_nacimiento' => '1995-04-12',
                'genero' => 'M',
                'telefono' => '71000001',
                'direccion' => 'Av. Busch y 2do Anillo, Clínica Vida Salud',
            ]
        );

        $uAdmin = UsuarioModel::updateOrCreate(
            ['username' => 'admin'],
            [
                'persona_id' => $pAdmin->id,
                'rol_id' => 1,
                'email' => 'admin@gmail.com',
                'password' => Hash::make('admin123'),
                'estado' => true,
                'intentos_fallidos' => 0,
                'bloqueado_hasta' => null,
            ]
        );

        // B) Recepcionista Principal
        $pRecep1 = PersonaModel::updateOrCreate(
            ['ci' => '20000001'],
            [
                'nombres' => 'Andrea Mariana',
                'apellidos' => 'Morales Vaca',
                'fecha_nacimiento' => '1998-08-20',
                'genero' => 'F',
                'telefono' => '72000002',
                'direccion' => 'Calle Sucre #240, Zona Central',
            ]
        );

        UsuarioModel::updateOrCreate(
            ['username' => 'recepcion'],
            [
                'persona_id' => $pRecep1->id,
                'rol_id' => 2,
                'email' => 'recepcion@vidasalud.com',
                'password' => Hash::make('recep123'),
                'estado' => true,
                'intentos_fallidos' => 0,
                'bloqueado_hasta' => null,
            ]
        );

        // C) Recepcionista Turno Tarde
        $pRecep2 = PersonaModel::updateOrCreate(
            ['ci' => '20000002'],
            [
                'nombres' => 'Fernando David',
                'apellidos' => 'Siles Vincenty',
                'fecha_nacimiento' => '1996-11-15',
                'genero' => 'M',
                'telefono' => '72000003',
                'direccion' => 'Av. San Martín C. 5, Equipetrol',
            ]
        );

        UsuarioModel::updateOrCreate(
            ['username' => 'recepcion2'],
            [
                'persona_id' => $pRecep2->id,
                'rol_id' => 2,
                'email' => 'recepcion2@vidasalud.com',
                'password' => Hash::make('recep123'),
                'estado' => true,
                'intentos_fallidos' => 0,
                'bloqueado_hasta' => null,
            ]
        );

        // -------------------------------------------------------------
        // 7. MÉDICOS Y ARANCELES (10 Registros)
        // -------------------------------------------------------------
        $medicosData = [
            [
                'ci' => '30000001', 'nombres' => 'Carlos Alberto', 'apellidos' => 'Rodríguez Morales',
                'fecha_nacimiento' => '1980-03-14', 'genero' => 'M', 'telefono' => '73000010',
                'direccion' => 'C. Beni #145, Santa Cruz', 'matricula' => 'MP-1001',
                'username' => 'dr.rodriguez', 'email' => 'carlos.rodriguez@vidasalud.com',
                'especialidades' => [
                    ['id' => 5, 'precio' => 180.00], // Cardiología
                    ['id' => 1, 'precio' => 120.00], // Medicina General
                ]
            ],
            [
                'ci' => '30000002', 'nombres' => 'Mariana Elena', 'apellidos' => 'Flores Justiniano',
                'fecha_nacimiento' => '1985-07-22', 'genero' => 'F', 'telefono' => '73000020',
                'direccion' => 'Av. Cristo Redentor #320', 'matricula' => 'MP-1002',
                'username' => 'dra.flores', 'email' => 'mariana.flores@vidasalud.com',
                'especialidades' => [
                    ['id' => 2, 'precio' => 150.00], // Pediatría
                ]
            ],
            [
                'ci' => '30000003', 'nombres' => 'Jorge Luis', 'apellidos' => 'Mendoza Mercado',
                'fecha_nacimiento' => '1979-10-05', 'genero' => 'M', 'telefono' => '73000030',
                'direccion' => 'Barrio Sirari C. Los Pinos #45', 'matricula' => 'MP-1003',
                'username' => 'dr.mendoza', 'email' => 'jorge.mendoza@vidasalud.com',
                'especialidades' => [
                    ['id' => 6, 'precio' => 200.00], // Traumatología
                ]
            ],
            [
                'ci' => '30000004', 'nombres' => 'Valeria Sofía', 'apellidos' => 'Pinto Salvatierra',
                'fecha_nacimiento' => '1988-12-19', 'genero' => 'F', 'telefono' => '73000040',
                'direccion' => 'C. Ballivián #210, Casco Viejo', 'matricula' => 'MP-1004',
                'username' => 'dra.pinto', 'email' => 'valeria.pinto@vidasalud.com',
                'especialidades' => [
                    ['id' => 3, 'precio' => 160.00], // Ginecología
                ]
            ],
            [
                'ci' => '30000005', 'nombres' => 'Roberto Carlos', 'apellidos' => 'Suárez Céspedes',
                'fecha_nacimiento' => '1983-05-30', 'genero' => 'M', 'telefono' => '73000050',
                'direccion' => 'Av. Banzer Km 4', 'matricula' => 'MP-1005',
                'username' => 'dr.suarez', 'email' => 'roberto.suarez@vidasalud.com',
                'especialidades' => [
                    ['id' => 4, 'precio' => 130.00], // Odontología
                ]
            ],
            [
                'ci' => '30000006', 'nombres' => 'Andrea Patricia', 'apellidos' => 'Campero Torrico',
                'fecha_nacimiento' => '1987-09-11', 'genero' => 'F', 'telefono' => '73000060',
                'direccion' => 'Barrio Equipetrol C. 7 #88', 'matricula' => 'MP-1006',
                'username' => 'dra.campero', 'email' => 'andrea.campero@vidasalud.com',
                'especialidades' => [
                    ['id' => 7, 'precio' => 170.00], // Dermatología
                ]
            ],
            [
                'ci' => '30000007', 'nombres' => 'Sergio Andrés', 'apellidos' => 'Claure Guzmán',
                'fecha_nacimiento' => '1976-02-18', 'genero' => 'M', 'telefono' => '73000070',
                'direccion' => 'C. Bolívar #550', 'matricula' => 'MP-1007',
                'username' => 'dr.claure', 'email' => 'sergio.claure@vidasalud.com',
                'especialidades' => [
                    ['id' => 8, 'precio' => 160.00], // Oftalmología
                ]
            ],
            [
                'ci' => '30000008', 'nombres' => 'Álvaro Mauricio', 'apellidos' => 'Gutiérrez Siles',
                'fecha_nacimiento' => '1981-06-25', 'genero' => 'M', 'telefono' => '73000080',
                'direccion' => 'Av. Irala #140', 'matricula' => 'MP-1008',
                'username' => 'dr.gutierrez', 'email' => 'alvaro.gutierrez@vidasalud.com',
                'especialidades' => [
                    ['id' => 9, 'precio' => 220.00], // Neurología
                ]
            ],
            [
                'ci' => '30000009', 'nombres' => 'Gabriela Inés', 'apellidos' => 'Ortiz Montero',
                'fecha_nacimiento' => '1986-04-03', 'genero' => 'F', 'telefono' => '73000090',
                'direccion' => 'C. Warnes #90', 'matricula' => 'MP-1009',
                'username' => 'dra.ortiz', 'email' => 'gabriela.ortiz@vidasalud.com',
                'especialidades' => [
                    ['id' => 10, 'precio' => 150.00], // Otorrinolaringología
                ]
            ],
            [
                'ci' => '30000010', 'nombres' => 'Marcelo David', 'apellidos' => 'Paz Quiroga',
                'fecha_nacimiento' => '1984-11-28', 'genero' => 'M', 'telefono' => '73000100',
                'direccion' => 'Av. Las Américas #600', 'matricula' => 'MP-1010',
                'username' => 'dr.paz', 'email' => 'marcelo.paz@vidasalud.com',
                'especialidades' => [
                    ['id' => 1, 'precio' => 110.00], // Medicina General
                ]
            ],
        ];

        foreach ($medicosData as $m) {
            $pMed = PersonaModel::updateOrCreate(
                ['ci' => $m['ci']],
                [
                    'nombres' => $m['nombres'],
                    'apellidos' => $m['apellidos'],
                    'fecha_nacimiento' => $m['fecha_nacimiento'],
                    'genero' => $m['genero'],
                    'telefono' => $m['telefono'],
                    'direccion' => $m['direccion'],
                ]
            );

            $med = MedicoModel::updateOrCreate(
                ['matricula_profesional' => $m['matricula']],
                [
                    'persona_id' => $pMed->id,
                    'estado' => true,
                ]
            );

            // Crear cuenta de usuario para el médico
            UsuarioModel::updateOrCreate(
                ['username' => $m['username']],
                [
                    'persona_id' => $pMed->id,
                    'rol_id' => 3, // Médico
                    'email' => $m['email'],
                    'password' => Hash::make('medico123'),
                    'estado' => true,
                    'intentos_fallidos' => 0,
                    'bloqueado_hasta' => null,
                ]
            );

            // Vincular Especialidades con Arancel
            foreach ($m['especialidades'] as $esp) {
                MedicoEspecialidadModel::updateOrCreate(
                    [
                        'medico_id' => $med->id,
                        'especialidad_id' => $esp['id'],
                    ],
                    [
                        'precio_consulta' => $esp['precio'],
                    ]
                );
            }
        }

        // -------------------------------------------------------------
        // 8. PACIENTES Y ANTECEDENTES CLÍNICOS (10 Registros)
        // -------------------------------------------------------------
        $pacientesData = [
            [
                'ci' => '40000001', 'nombres' => 'Lucía Camila', 'apellidos' => 'Vargas Méndez',
                'fecha_nacimiento' => '1993-02-14', 'genero' => 'F', 'telefono' => '74000001',
                'direccion' => 'C. Arenales #120', 'sangre' => 'O+',
                'emerg_nombre' => 'Mario Vargas Méndez', 'emerg_tel' => '71092837',
                'antecedentes' => 'Hipertensión arterial diagnosticada en 2021, tratada con Enalapril 10mg diario. Sin alergias medicamentosas conocidas.',
                'username' => 'paciente.lucia', 'email' => 'lucia.vargas@correo.com',
            ],
            [
                'ci' => '40000002', 'nombres' => 'Mateo Nicolás', 'apellidos' => 'Aguilera Roca',
                'fecha_nacimiento' => '1999-05-18', 'genero' => 'M', 'telefono' => '74000002',
                'direccion' => 'Av. Mutualista C. 12 #45', 'sangre' => 'A+',
                'emerg_nombre' => 'Claudia Roca de Aguilera', 'emerg_tel' => '72103948',
                'antecedentes' => 'Alergia severa a la Penicilina y derivados betalactámicos (urticaria generalizada). Cuadros esporádicos de broncoespasmo.',
                'username' => 'paciente.mateo', 'email' => 'mateo.aguilera@correo.com',
            ],
            [
                'ci' => '40000003', 'nombres' => 'Sofía Nicole', 'apellidos' => 'Hurtado Saucedo',
                'fecha_nacimiento' => '2001-08-30', 'genero' => 'F', 'telefono' => '74000003',
                'direccion' => 'C. Charcas #340', 'sangre' => 'B+',
                'emerg_nombre' => 'Juan Carlos Hurtado', 'emerg_tel' => '73192038',
                'antecedentes' => 'Rinitis alérgica estacional controlada con antihistamínicos. Asma bronquial leve en la infancia.',
                'username' => 'paciente.sofia', 'email' => 'sofia.hurtado@correo.com',
            ],
            [
                'ci' => '40000004', 'nombres' => 'Daniel Alejandro', 'apellidos' => 'Cuéllar Soliz',
                'fecha_nacimiento' => '1989-11-09', 'genero' => 'M', 'telefono' => '74000004',
                'direccion' => 'Barrio Urbarí C. 3 #12', 'sangre' => 'O-',
                'emerg_nombre' => 'Patricia Soliz Cuéllar', 'emerg_tel' => '74102938',
                'antecedentes' => 'Cirugía laparoscópica de apendicectomía en 2022 sin secuelas. No presenta alergias ni enfermedades crónicas.',
                'username' => 'paciente.daniel', 'email' => 'daniel.cuellar@correo.com',
            ],
            [
                'ci' => '40000005', 'nombres' => 'Camila Andrea', 'apellidos' => 'Ribera Justiniano',
                'fecha_nacimiento' => '1994-04-25', 'genero' => 'F', 'telefono' => '74000005',
                'direccion' => 'C. Velasco #80', 'sangre' => 'AB+',
                'emerg_nombre' => 'Andrés Ribera Melgar', 'emerg_tel' => '75192039',
                'antecedentes' => 'Hipotiroidismo primario bajo control médico con Levotiroxina sódica 50 mcg/día.',
                'username' => 'paciente.camila', 'email' => 'camila.ribera@correo.com',
            ],
            [
                'ci' => '40000006', 'nombres' => 'Andrés Felipe', 'apellidos' => 'Arteaga Villagómez',
                'fecha_nacimiento' => '1978-01-14', 'genero' => 'M', 'telefono' => '74000006',
                'direccion' => 'Plan 3000, Barrio Los Lotes C. 4', 'sangre' => 'A-',
                'emerg_nombre' => 'Elena Villagómez de Arteaga', 'emerg_tel' => '76102938',
                'antecedentes' => 'Diabetes Mellitus Tipo 2 diagnosticada en 2019, tratada con Metformina 850mg y dieta equilibrada.',
                'username' => 'paciente.andres', 'email' => 'andres.arteaga@correo.com',
            ],
            [
                'ci' => '40000007', 'nombres' => 'Valentina Belén', 'apellidos' => 'Peinado Antelo',
                'fecha_nacimiento' => '2003-10-12', 'genero' => 'F', 'telefono' => '74000007',
                'direccion' => 'Av. Santos Dumont C. 8', 'sangre' => 'O+',
                'emerg_nombre' => 'Rodrigo Peinado Suárez', 'emerg_tel' => '77102948',
                'antecedentes' => 'Gastritis superficial moderada asociada a estrés académico. Intolerancia moderada a la lactosa.',
                'username' => 'paciente.valentina', 'email' => 'valentina.peinado@correo.com',
            ],
            [
                'ci' => '40000008', 'nombres' => 'Lucas Gabriel', 'apellidos' => 'Melgar Vaca',
                'fecha_nacimiento' => '1997-06-03', 'genero' => 'M', 'telefono' => '74000008',
                'direccion' => 'Villa 1ro de Mayo C. 5 #110', 'sangre' => 'B-',
                'emerg_nombre' => 'Martha Vaca de Melgar', 'emerg_tel' => '78192039',
                'antecedentes' => 'Antecedente quirúrgico por fractura diafisaria de radio izquierdo en 2021, osteosíntesis retirada exitosamente.',
                'username' => 'paciente.lucas', 'email' => 'lucas.melgar@correo.com',
            ],
            [
                'ci' => '40000009', 'nombres' => 'Isabella Rocío', 'apellidos' => 'Landívar Chávez',
                'fecha_nacimiento' => '1992-12-01', 'genero' => 'F', 'telefono' => '74000009',
                'direccion' => 'Av. Cañoto #230', 'sangre' => 'O+',
                'emerg_nombre' => 'Sergio Landívar Ortiz', 'emerg_tel' => '79102948',
                'antecedentes' => 'Paciente sin antecedentes médicos de relevancia ni alergias declaradas. Exámenes rutinarios normales.',
                'username' => 'paciente.isabella', 'email' => 'isabella.landivar@correo.com',
            ],
            [
                'ci' => '40000010', 'nombres' => 'Sebastián Ignacio', 'apellidos' => 'Rojas Franco',
                'fecha_nacimiento' => '1986-07-17', 'genero' => 'M', 'telefono' => '74000010',
                'direccion' => 'C. Seoane #410, Zona Central', 'sangre' => 'AB-',
                'emerg_nombre' => 'Verónica Franco de Rojas', 'emerg_tel' => '70102938',
                'antecedentes' => 'Cefaleas tensionales recurrentes y lumbalgia postural mecánica.',
                'username' => 'paciente.sebastian', 'email' => 'sebastian.rojas@correo.com',
            ],
        ];

        foreach ($pacientesData as $p) {
            $pPac = PersonaModel::updateOrCreate(
                ['ci' => $p['ci']],
                [
                    'nombres' => $p['nombres'],
                    'apellidos' => $p['apellidos'],
                    'fecha_nacimiento' => $p['fecha_nacimiento'],
                    'genero' => $p['genero'],
                    'telefono' => $p['telefono'],
                    'direccion' => $p['direccion'],
                ]
            );

            $pac = PacienteModel::updateOrCreate(
                ['persona_id' => $pPac->id],
                [
                    'tipo_sangre' => $p['sangre'],
                    'contacto_emergencia_nombre' => $p['emerg_nombre'],
                    'contacto_emergencia_telefono' => $p['emerg_tel'],
                    'estado' => true,
                ]
            );

            // Antecedente clínico
            AntecedenteMedicoModel::updateOrCreate(
                [
                    'paciente_id' => $pac->id,
                    'condicion' => 'Antecedentes Generales',
                ],
                [
                    'descripcion' => $p['antecedentes'],
                ]
            );

            // Usuario para el paciente
            UsuarioModel::updateOrCreate(
                ['username' => $p['username']],
                [
                    'persona_id' => $pPac->id,
                    'rol_id' => 4, // Paciente
                    'email' => $p['email'],
                    'password' => Hash::make('paciente123'),
                    'estado' => true,
                    'intentos_fallidos' => 0,
                    'bloqueado_hasta' => null,
                ]
            );
        }

        // -------------------------------------------------------------
        // 9. EVENTOS DE AUDITORÍA (Bitácora Poblada)
        // -------------------------------------------------------------
        $eventosBitacora = [
            ['accion' => 'INICIALIZACION_SISTEMA', 'modulo' => 'Seguridad', 'detalle' => 'Configuración de catálogo maestro de roles, permisos y especialidades clínicas.'],
            ['accion' => 'REGISTRO_MEDICO', 'modulo' => 'Médicos', 'detalle' => 'Alta de Dr(a). Carlos Alberto Rodríguez Morales (Mat: MP-1001) con especialidad Cardiología.'],
            ['accion' => 'REGISTRO_MEDICO', 'modulo' => 'Médicos', 'detalle' => 'Alta de Dr(a). Mariana Elena Flores Justiniano (Mat: MP-1002) con especialidad Pediatría.'],
            ['accion' => 'REGISTRO_MEDICO', 'modulo' => 'Médicos', 'detalle' => 'Alta de Dr(a). Jorge Luis Mendoza Mercado (Mat: MP-1003) con especialidad Traumatología.'],
            ['accion' => 'REGISTRO_PACIENTE', 'modulo' => 'Pacientes', 'detalle' => 'Filiación clínica de paciente Lucía Camila Vargas Méndez (CI: 40000001).'],
            ['accion' => 'REGISTRO_PACIENTE', 'modulo' => 'Pacientes', 'detalle' => 'Filiación clínica de paciente Mateo Nicolás Aguilera Roca (CI: 40000002).'],
            ['accion' => 'REGISTRO_PACIENTE', 'modulo' => 'Pacientes', 'detalle' => 'Filiación clínica de paciente Sofía Nicole Hurtado Saucedo (CI: 40000003).'],
            ['accion' => 'CREACION_USUARIO', 'modulo' => 'Seguridad', 'detalle' => 'Creación de cuenta de acceso recepcionista: recepcion (Andrea Mariana Morales Vaca).'],
            ['accion' => 'MODIFICACION_ROL', 'modulo' => 'Seguridad', 'detalle' => 'Actualización de permisos operativos asignados al rol Recepcionista.'],
            ['accion' => 'INICIO_SESION', 'modulo' => 'Seguridad', 'detalle' => 'Inicio de sesión exitoso del usuario administrador desde terminal local.'],
        ];

        foreach ($eventosBitacora as $ev) {
            BitacoraModel::create([
                'usuario_id' => $uAdmin->id,
                'accion' => $ev['accion'],
                'modulo' => $ev['modulo'],
                'detalle' => $ev['detalle'],
                'ip_origen' => '127.0.0.1',
            ]);
        }

        // -------------------------------------------------------------
        // 10. HORARIOS DE ATENCIÓN REGULARES (CU-09)
        // -------------------------------------------------------------
        $medicoEsps = MedicoEspecialidadModel::all();
        foreach ($medicoEsps as $me) {
            // Lunes matutino (08:00 - 12:00)
            HorarioAtencionModel::updateOrCreate(
                ['medico_especialidad_id' => $me->id, 'dia_id' => 1, 'hora_inicio' => '08:00:00'],
                ['hora_fin' => '12:00:00', 'intervalo_minutos' => 30, 'estado' => true]
            );

            // Miércoles vespertino (14:00 - 18:00)
            HorarioAtencionModel::updateOrCreate(
                ['medico_especialidad_id' => $me->id, 'dia_id' => 3, 'hora_inicio' => '14:00:00'],
                ['hora_fin' => '18:00:00', 'intervalo_minutos' => 30, 'estado' => true]
            );

            // Viernes matutino (09:00 - 13:00)
            HorarioAtencionModel::updateOrCreate(
                ['medico_especialidad_id' => $me->id, 'dia_id' => 5, 'hora_inicio' => '09:00:00'],
                ['hora_fin' => '13:00:00', 'intervalo_minutos' => 30, 'estado' => true]
            );
        }

        // -------------------------------------------------------------
        // 11. AUSENCIAS Y EXCEPCIONES MÉDICAS (CU-10)
        // -------------------------------------------------------------
        $medicosList = MedicoModel::all();
        if ($medicosList->count() >= 2) {
            AusenciaMedicaModel::updateOrCreate(
                ['medico_id' => $medicosList[1]->id, 'fecha_inicio' => now()->addDays(15)->toDateString()],
                ['fecha_fin' => now()->addDays(20)->toDateString(), 'motivo' => 'Participación en Congreso Nacional de Pediatría']
            );

            AusenciaMedicaModel::updateOrCreate(
                ['medico_id' => $medicosList[0]->id, 'fecha_inicio' => now()->addDays(25)->toDateString()],
                ['fecha_fin' => now()->addDays(28)->toDateString(), 'motivo' => 'Vacaciones de invierno autorizadas']
            );
        }

        // -------------------------------------------------------------
        // 12. CITAS MÉDICAS Y REGISTRO CLÍNICO (CU-11, 12, 13, 14, 15)
        // -------------------------------------------------------------
        $pacientesList = PacienteModel::all();
        if ($medicoEsps->count() > 0 && $pacientesList->count() > 0) {
            $citasSeed = [
                // 1-8 Atendidas (con diagnóstico clínico)
                [
                    'codigo' => 'CIT-2026-0001', 'pac_idx' => 0, 'me_idx' => 0, 'estado' => EstadoCitaModel::ATENDIDA,
                    'fecha' => now()->subDays(6)->toDateString(), 'ini' => '08:00', 'fin' => '08:30',
                    'motivo' => 'Control rutinario de hipertensión y palpitaciones ocasionales',
                    'diag' => 'Hipertensión arterial estadio 1 controlada.',
                    'trat' => 'Enalapril 10mg cada 12 hrs. Reducción de ingesta de sodio. Control en 30 días.',
                    'notas' => 'Paciente presenta buena tolerancia al fármaco. Presión arterial en consulta: 130/80 mmHg.',
                ],
                [
                    'codigo' => 'CIT-2026-0002', 'pac_idx' => 1, 'me_idx' => 1, 'estado' => EstadoCitaModel::ATENDIDA,
                    'fecha' => now()->subDays(5)->toDateString(), 'ini' => '08:30', 'fin' => '09:00',
                    'motivo' => 'Fiebre de 38.5°C y dolor de garganta desde hace 2 días',
                    'diag' => 'Faringitis estreptocócica aguda con exudado amigdalino.',
                    'trat' => 'Amoxicilina + Ácido Clavulánico 875/125mg cada 12h por 7 días. Ibuprofeno 400mg cada 8h condicional.',
                    'notas' => 'Se indica hidratación abundante y reposo relativo por 48 horas.',
                ],
                [
                    'codigo' => 'CIT-2026-0003', 'pac_idx' => 2, 'me_idx' => 2, 'estado' => EstadoCitaModel::ATENDIDA,
                    'fecha' => now()->subDays(4)->toDateString(), 'ini' => '14:00', 'fin' => '14:30',
                    'motivo' => 'Dolor lumbar moderado tras levantar peso',
                    'diag' => 'Lumbalgia mecánica aguda por esfuerzo muscular.',
                    'trat' => 'Diclofenaco 50mg + Complejo B cada 12h por 5 días. Fisioterapia calor seco local.',
                    'notas' => 'Sin compromiso radicular evidenciado en maniobra de Lasègue negativa.',
                ],
                [
                    'codigo' => 'CIT-2026-0004', 'pac_idx' => 3, 'me_idx' => 3, 'estado' => EstadoCitaModel::ATENDIDA,
                    'fecha' => now()->subDays(3)->toDateString(), 'ini' => '14:30', 'fin' => '15:00',
                    'motivo' => 'Chequeo ginecológico y citología anual',
                    'diag' => 'Control ginecológico de rutina sin hallazgos patológicos.',
                    'trat' => 'Tomas de muestra enviadas a laboratorio de patología.',
                    'notas' => 'Ecografía pélvica de control dentro de parámetros normales.',
                ],
                [
                    'codigo' => 'CIT-2026-0005', 'pac_idx' => 4, 'me_idx' => 4, 'estado' => EstadoCitaModel::ATENDIDA,
                    'fecha' => now()->subDays(3)->toDateString(), 'ini' => '09:00', 'fin' => '09:30',
                    'motivo' => 'Sensibilidad dental en cuadrante superior izquierdo',
                    'diag' => 'Caries dental en pieza 24 con afectación de esmalte.',
                    'trat' => 'Obturación con resina compuesta fotocurable y profilaxis dental.',
                    'notas' => 'Procedimiento concluido sin complicaciones ni dolor referido.',
                ],
                [
                    'codigo' => 'CIT-2026-0006', 'pac_idx' => 5, 'me_idx' => 5, 'estado' => EstadoCitaModel::ATENDIDA,
                    'fecha' => now()->subDays(2)->toDateString(), 'ini' => '09:30', 'fin' => '10:00',
                    'motivo' => 'Erupción cutánea pruriginosa en extremidades',
                    'diag' => 'Dermatitis por contacto alérgica subaguda.',
                    'trat' => 'Cetirizina 10mg diaria por 10 días. Crema de Hidrocortisona al 1% dos veces al día por 5 días.',
                    'notas' => 'Evitar contacto con jabones perfumados y detergentes sin guantes.',
                ],
                [
                    'codigo' => 'CIT-2026-0007', 'pac_idx' => 6, 'me_idx' => 6, 'estado' => EstadoCitaModel::ATENDIDA,
                    'fecha' => now()->subDays(1)->toDateString(), 'ini' => '08:00', 'fin' => '08:30',
                    'motivo' => 'Disminución progresiva de agudeza visual de lejos',
                    'diag' => 'Miopía simple con astigmatismo bilateral.',
                    'trat' => 'Prescripción óptica: OD -1.25 cil -0.50 eje 180°, OI -1.50 cil -0.75 eje 175°.',
                    'notas' => 'Fondo de ojo normal, sin signos de retinopatía.',
                ],
                [
                    'codigo' => 'CIT-2026-0008', 'pac_idx' => 7, 'me_idx' => 7, 'estado' => EstadoCitaModel::ATENDIDA,
                    'fecha' => now()->subDays(1)->toDateString(), 'ini' => '10:00', 'fin' => '10:30',
                    'motivo' => 'Cefalea pulsátil hemicraneal con fotofobia',
                    'diag' => 'Migraña sin aura episódica de frecuencia moderada.',
                    'trat' => 'Zolmitriptán 2.5mg al inicio del dolor agudo. Propranolol 40mg diario como preventivo.',
                    'notas' => 'Se indica llevar diario de cefaleas para identificar detonantes alimenticios.',
                ],

                // 9-16 Programadas / Confirmadas (Hoy y días siguientes)
                [
                    'codigo' => 'CIT-2026-0009', 'pac_idx' => 0, 'me_idx' => 0, 'estado' => EstadoCitaModel::PROGRAMADA,
                    'fecha' => now()->toDateString(), 'ini' => '10:00', 'fin' => '10:30',
                    'motivo' => 'Revisión periódica de presión arterial y ajuste de medicación',
                ],
                [
                    'codigo' => 'CIT-2026-0010', 'pac_idx' => 1, 'me_idx' => 1, 'estado' => EstadoCitaModel::CONFIRMADA,
                    'fecha' => now()->toDateString(), 'ini' => '10:30', 'fin' => '11:00',
                    'motivo' => 'Control de niño sano y verificación del esquema de vacunas',
                ],
                [
                    'codigo' => 'CIT-2026-0011', 'pac_idx' => 2, 'me_idx' => 2, 'estado' => EstadoCitaModel::PROGRAMADA,
                    'fecha' => now()->addDays(1)->toDateString(), 'ini' => '08:00', 'fin' => '08:30',
                    'motivo' => 'Revisión de resonancia magnética de rodilla derecha',
                ],
                [
                    'codigo' => 'CIT-2026-0012', 'pac_idx' => 3, 'me_idx' => 3, 'estado' => EstadoCitaModel::CONFIRMADA,
                    'fecha' => now()->addDays(1)->toDateString(), 'ini' => '09:00', 'fin' => '09:30',
                    'motivo' => 'Resultados de laboratorio y control prenatal',
                ],
                [
                    'codigo' => 'CIT-2026-0013', 'pac_idx' => 4, 'me_idx' => 4, 'estado' => EstadoCitaModel::PROGRAMADA,
                    'fecha' => now()->addDays(2)->toDateString(), 'ini' => '14:00', 'fin' => '14:30',
                    'motivo' => 'Limpieza dental profunda y evaluación de encías',
                ],
                [
                    'codigo' => 'CIT-2026-0014', 'pac_idx' => 5, 'me_idx' => 5, 'estado' => EstadoCitaModel::CONFIRMADA,
                    'fecha' => now()->addDays(2)->toDateString(), 'ini' => '15:00', 'fin' => '15:30',
                    'motivo' => 'Revisión de lunar con bordes asimétricos en espalda',
                ],
                [
                    'codigo' => 'CIT-2026-0015', 'pac_idx' => 6, 'me_idx' => 6, 'estado' => EstadoCitaModel::PROGRAMADA,
                    'fecha' => now()->addDays(3)->toDateString(), 'ini' => '10:00', 'fin' => '10:30',
                    'motivo' => 'Evaluación de presión intraocular y descarte de glaucoma',
                ],
                [
                    'codigo' => 'CIT-2026-0016', 'pac_idx' => 7, 'me_idx' => 7, 'estado' => EstadoCitaModel::CONFIRMADA,
                    'fecha' => now()->addDays(4)->toDateString(), 'ini' => '11:00', 'fin' => '11:30',
                    'motivo' => 'Control evolutivo de mareos posturales',
                ],

                // 17-20 Canceladas (con motivo explícito)
                [
                    'codigo' => 'CIT-2026-0017', 'pac_idx' => 8, 'me_idx' => 0, 'estado' => EstadoCitaModel::CANCELADA,
                    'fecha' => now()->subDays(2)->toDateString(), 'ini' => '11:00', 'fin' => '11:30',
                    'motivo' => 'Chequeo cardiológico preventivo',
                    'cancel_motivo' => 'Paciente tuvo que viajar de urgencia por razones familiares.',
                ],
                [
                    'codigo' => 'CIT-2026-0018', 'pac_idx' => 9, 'me_idx' => 1, 'estado' => EstadoCitaModel::CANCELADA,
                    'fecha' => now()->subDays(1)->toDateString(), 'ini' => '11:30', 'fin' => '12:00',
                    'motivo' => 'Evaluación pediátrica de crecimiento',
                    'cancel_motivo' => 'Madre solicitó postergación por cruce de horario escolar.',
                ],
                [
                    'codigo' => 'CIT-2026-0019', 'pac_idx' => 0, 'me_idx' => 2, 'estado' => EstadoCitaModel::CANCELADA,
                    'fecha' => now()->addDays(2)->toDateString(), 'ini' => '16:00', 'fin' => '16:30',
                    'motivo' => 'Molestia en tobillo izquierdo',
                    'cancel_motivo' => 'Paciente canceló con 48h de antelación al mejorar síntomas.',
                ],
                [
                    'codigo' => 'CIT-2026-0020', 'pac_idx' => 1, 'me_idx' => 3, 'estado' => EstadoCitaModel::CANCELADA,
                    'fecha' => now()->addDays(3)->toDateString(), 'ini' => '17:00', 'fin' => '17:30',
                    'motivo' => 'Consulta ginecológica general',
                    'cancel_motivo' => 'Incompatibilidad de turno laboral no prevista.',
                ],
            ];

            foreach ($citasSeed as $cs) {
                $pId = $pacientesList[$cs['pac_idx'] % $pacientesList->count()]->id;
                $meId = $medicoEsps[$cs['me_idx'] % $medicoEsps->count()]->id;

                $cita = CitaModel::updateOrCreate(
                    ['codigo_reserva' => $cs['codigo']],
                    [
                        'paciente_id' => $pId,
                        'medico_especialidad_id' => $meId,
                        'estado_id' => $cs['estado'],
                        'fecha' => $cs['fecha'],
                        'hora_inicio' => $cs['ini'],
                        'hora_fin' => $cs['fin'],
                        'motivo_consulta' => $cs['motivo'],
                        'motivo_cancelacion' => $cs['cancel_motivo'] ?? null,
                        'fecha_registro' => now()->subDays(7),
                    ]
                );

                if ($cs['estado'] === EstadoCitaModel::ATENDIDA && isset($cs['diag'])) {
                    AtencionClinicaModel::updateOrCreate(
                        ['cita_id' => $cita->id],
                        [
                            'diagnostico' => $cs['diag'],
                            'tratamiento' => $cs['trat'] ?? null,
                            'notas_adicionales' => $cs['notas'] ?? null,
                            'fecha_atencion' => $cs['fecha'] . ' ' . $cs['fin'] . ':00',
                        ]
                    );
                }
            }
        }

        // -------------------------------------------------------------
        // 13. SINCRONIZACIÓN DE SECUENCIAS POSTGRESQL (SERIAL / PKEY)
        // -------------------------------------------------------------
        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql') {
            $tablas = [
                'roles',
                'permisos',
                'dias_semana',
                'estados_cita',
                'especialidades',
                'personas',
                'usuarios',
                'medicos',
                'medico_especialidad',
                'pacientes',
                'antecedentes_medicos',
                'horarios_atencion',
                'ausencias_medicas',
                'citas',
                'atenciones_clinicas',
                'bitacora',
            ];
            foreach ($tablas as $t) {
                \Illuminate\Support\Facades\DB::statement("SELECT setval(pg_get_serial_sequence('{$t}', 'id'), COALESCE((SELECT MAX(id) FROM {$t}), 1));");
            }
        }
    }
}

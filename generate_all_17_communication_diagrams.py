# -*- coding: utf-8 -*-
"""
Script maestro para generar los 17 Diagramas de Comunicación UML (CU-01 al CU-17)
del Sistema de Información de Citas Médicas - Clínica Vida Salud.
"""

import os
from communication_renderer import draw_diagram

output_dir = r"c:\Users\Cerezo Alex\OneDrive\Escritorio\CONTROL DE CITAS\docs\diagramas\comunicacion"
os.makedirs(output_dir, exist_ok=True)

# ==============================================================================
# CU-01: Gestionar Usuarios
# ==============================================================================
nodes_cu01 = [
    {'id': 'admin', 'type': 'actor', 'label': 'Administrador', 'x': 90, 'y': 340},
    {'id': 'vista', 'type': 'boundary', 'label': 'Usuarios.vue', 'x': 280, 'y': 340},
    {'id': 'ctrl', 'type': 'control', 'label': 'UsuarioController', 'x': 510, 'y': 340},
    {'id': 'bit_srv', 'type': 'control', 'label': 'BitacoraService', 'x': 510, 'y': 550},
    {'id': 'persona', 'type': 'entity', 'label': 'PersonaModel', 'x': 790, 'y': 170},
    {'id': 'rol', 'type': 'entity', 'label': 'RolModel', 'x': 890, 'y': 290},
    {'id': 'usuario', 'type': 'entity', 'label': 'UsuarioModel', 'x': 890, 'y': 430},
    {'id': 'bitacora', 'type': 'entity', 'label': 'bitacora', 'x': 780, 'y': 570},
]
msgs_cu01 = [
    {'from': 'admin', 'to': 'vista', 'text': '1: solicitarRegistro(ci, nombres, email, password)', 'side_off': 5, 't': 0.5, 'offset': 12},
    {'from': 'vista', 'to': 'ctrl', 'text': '2: store(Request)', 'side_off': 5, 't': 0.5, 'offset': 12},
    {'from': 'ctrl', 'to': 'persona', 'text': '3: create(personaData)', 't': 0.48, 'offset': -12},
    {'from': 'ctrl', 'to': 'rol', 'text': '4: exists:roles,id', 't': 0.5, 'offset': 10},
    {'from': 'ctrl', 'to': 'usuario', 'text': '5: create(userData, persona_id)', 't': 0.5, 'offset': 10},
    {'from': 'ctrl', 'to': 'bit_srv', 'text': "6: registrar('CREACION_USUARIO', 'Seguridad')", 't': 0.5, 'offset': -14},
    {'from': 'bit_srv', 'to': 'bitacora', 'text': '7: create(bitacoraData)', 't': 0.5, 'offset': 12},
    {'from': 'ctrl', 'to': 'vista', 'text': "8: back()->with('success')", 'side_off': -5, 't': 0.5, 'offset': 12},
    {'from': 'vista', 'to': 'admin', 'text': '9: mostrarNotificacion()', 'side_off': -5, 't': 0.5, 'offset': 12},
]

# ==============================================================================
# CU-02: Gestionar Roles y Permisos
# ==============================================================================
nodes_cu02 = [
    {'id': 'admin', 'type': 'actor', 'label': 'Administrador', 'x': 90, 'y': 340},
    {'id': 'vista', 'type': 'boundary', 'label': 'Roles.vue', 'x': 280, 'y': 340},
    {'id': 'ctrl', 'type': 'control', 'label': 'RolController', 'x': 510, 'y': 340},
    {'id': 'bit_srv', 'type': 'control', 'label': 'BitacoraService', 'x': 510, 'y': 550},
    {'id': 'rol', 'type': 'entity', 'label': 'RolModel', 'x': 810, 'y': 200},
    {'id': 'permiso', 'type': 'entity', 'label': 'PermisoModel', 'x': 900, 'y': 340},
    {'id': 'rol_perm', 'type': 'entity', 'label': 'rol_permiso', 'x': 830, 'y': 470},
    {'id': 'bitacora', 'type': 'entity', 'label': 'bitacora', 'x': 780, 'y': 580},
]
msgs_cu02 = [
    {'from': 'admin', 'to': 'vista', 'text': '1: actualizarPermisos(rol_id, permisos[])', 'side_off': 5, 't': 0.5, 'offset': 12},
    {'from': 'vista', 'to': 'ctrl', 'text': '2: update(Request, rol)', 'side_off': 5, 't': 0.5, 'offset': 12},
    {'from': 'ctrl', 'to': 'rol', 'text': '3: update(descripcion, estado)', 't': 0.48, 'offset': -12},
    {'from': 'ctrl', 'to': 'permiso', 'text': '4: exists:permisos,id', 't': 0.5, 'offset': 10},
    {'from': 'ctrl', 'to': 'rol_perm', 'text': '5: permisos()->sync(permisosIds)', 't': 0.5, 'offset': 10},
    {'from': 'ctrl', 'to': 'bit_srv', 'text': "6: registrar('MODIFICACION_ROL', 'Seguridad')", 't': 0.5, 'offset': -14},
    {'from': 'bit_srv', 'to': 'bitacora', 'text': '7: create(bitacoraData)', 't': 0.5, 'offset': 12},
    {'from': 'ctrl', 'to': 'vista', 'text': "8: back()->with('success')", 'side_off': -5, 't': 0.5, 'offset': 12},
    {'from': 'vista', 'to': 'admin', 'text': '9: notificarActualizacion()', 'side_off': -5, 't': 0.5, 'offset': 12},
]

# ==============================================================================
# CU-03: Consultar Bitácora de Auditoría
# ==============================================================================
nodes_cu03 = [
    {'id': 'admin', 'type': 'actor', 'label': 'Administrador', 'x': 90, 'y': 340},
    {'id': 'vista', 'type': 'boundary', 'label': 'Bitacora.vue', 'x': 280, 'y': 340},
    {'id': 'ctrl', 'type': 'control', 'label': 'BitacoraController', 'x': 520, 'y': 340},
    {'id': 'bitacora', 'type': 'entity', 'label': 'BitacoraModel', 'x': 810, 'y': 180},
    {'id': 'usuario', 'type': 'entity', 'label': 'UsuarioModel', 'x': 890, 'y': 300},
    {'id': 'persona', 'type': 'entity', 'label': 'PersonaModel', 'x': 890, 'y': 430},
    {'id': 'log', 'type': 'entity', 'label': 'laravel.log', 'x': 810, 'y': 560},
]
msgs_cu03 = [
    {'from': 'admin', 'to': 'vista', 'text': '1: filtrarAuditoria(buscar, modulo, fecha, usuario_id)', 'side_off': 5, 't': 0.5, 'offset': 12},
    {'from': 'vista', 'to': 'ctrl', 'text': '2: index(Request)', 'side_off': 5, 't': 0.5, 'offset': 12},
    {'from': 'ctrl', 'to': 'bitacora', 'text': "3: with(['usuario.persona'])->latest()", 't': 0.48, 'offset': -12},
    {'from': 'ctrl', 'to': 'usuario', 'text': "4: with('persona')->where('estado', true)->get()", 't': 0.5, 'offset': 10},
    {'from': 'usuario', 'to': 'persona', 'text': "5: load('persona')", 't': 0.5, 'offset': 10},
    {'from': 'ctrl', 'to': 'log', 'text': "6: File::get('storage/logs/laravel.log')", 't': 0.5, 'offset': 12},
    {'from': 'ctrl', 'to': 'vista', 'text': "7: render('Bitacora/Index', data)", 'side_off': -5, 't': 0.5, 'offset': 12},
    {'from': 'vista', 'to': 'admin', 'text': '8: mostrarRegistrosYDiffJSON()', 'side_off': -5, 't': 0.5, 'offset': 12},
]

# ==============================================================================
# CU-04: Gestionar Respaldos de Base de Datos
# ==============================================================================
nodes_cu04 = [
    {'id': 'admin', 'type': 'actor', 'label': 'Administrador', 'x': 90, 'y': 340},
    {'id': 'vista', 'type': 'boundary', 'label': 'Backups.vue', 'x': 280, 'y': 340},
    {'id': 'ctrl', 'type': 'control', 'label': 'BackupController', 'x': 520, 'y': 340},
    {'id': 'bit_srv', 'type': 'control', 'label': 'BitacoraService', 'x': 520, 'y': 540},
    {'id': 'pg', 'type': 'entity', 'label': 'pg_stat_user_tables', 'x': 830, 'y': 210},
    {'id': 'db', 'type': 'entity', 'label': 'DB_Clinica', 'x': 890, 'y': 350},
    {'id': 'bitacora', 'type': 'entity', 'label': 'bitacora', 'x': 820, 'y': 540},
]
msgs_cu04 = [
    {'from': 'admin', 'to': 'vista', 'text': '1: solicitarDescarga()', 'side_off': 5, 't': 0.5, 'offset': 12},
    {'from': 'vista', 'to': 'ctrl', 'text': '2: download()', 'side_off': 5, 't': 0.5, 'offset': 12},
    {'from': 'ctrl', 'to': 'pg', 'text': '3: select(pg_stat_user_tables)', 't': 0.48, 'offset': -12},
    {'from': 'ctrl', 'to': 'db', 'text': '4: DB::table($table)->get()', 't': 0.5, 'offset': 10},
    {'from': 'ctrl', 'to': 'bit_srv', 'text': "5: registrar('GENERACION_RESPALDO', 'Seguridad')", 't': 0.5, 'offset': -14},
    {'from': 'bit_srv', 'to': 'bitacora', 'text': '6: create(bitacoraData)', 't': 0.5, 'offset': 12},
    {'from': 'ctrl', 'to': 'vista', 'text': '7: response(sqlContent, 200, headers)', 'side_off': -5, 't': 0.5, 'offset': 12},
    {'from': 'vista', 'to': 'admin', 'text': '8: descargarArchivoSQL()', 'side_off': -5, 't': 0.5, 'offset': 12},
]

# ==============================================================================
# CU-05: Gestionar Especialidades
# ==============================================================================
nodes_cu05 = [
    {'id': 'admin', 'type': 'actor', 'label': 'Administrador', 'x': 90, 'y': 340},
    {'id': 'vista', 'type': 'boundary', 'label': 'Especialidades.vue', 'x': 290, 'y': 340},
    {'id': 'ctrl', 'type': 'control', 'label': 'EspecialidadController', 'x': 530, 'y': 340},
    {'id': 'bit_srv', 'type': 'control', 'label': 'BitacoraService', 'x': 530, 'y': 540},
    {'id': 'esp', 'type': 'entity', 'label': 'EspecialidadModel', 'x': 840, 'y': 270},
    {'id': 'bitacora', 'type': 'entity', 'label': 'bitacora', 'x': 820, 'y': 540},
]
msgs_cu05 = [
    {'from': 'admin', 'to': 'vista', 'text': '1: registrarEspecialidad(nombre, descripcion)', 'side_off': 5, 't': 0.5, 'offset': 12},
    {'from': 'vista', 'to': 'ctrl', 'text': '2: store(Request)', 'side_off': 5, 't': 0.5, 'offset': 12},
    {'from': 'ctrl', 'to': 'esp', 'text': '3: create(nombre, descripcion, estado: true)', 't': 0.5, 'offset': -12},
    {'from': 'ctrl', 'to': 'bit_srv', 'text': "4: registrar('CREACION_ESPECIALIDAD', 'Catálogos')", 't': 0.5, 'offset': -14},
    {'from': 'bit_srv', 'to': 'bitacora', 'text': '5: create(bitacoraData)', 't': 0.5, 'offset': 12},
    {'from': 'ctrl', 'to': 'vista', 'text': "6: back()->with('success')", 'side_off': -5, 't': 0.5, 'offset': 12},
    {'from': 'vista', 'to': 'admin', 'text': '7: confirmarEspecialidadCreada()', 'side_off': -5, 't': 0.5, 'offset': 12},
]

# ==============================================================================
# CU-06: Gestionar Médicos
# ==============================================================================
nodes_cu06 = [
    {'id': 'admin', 'type': 'actor', 'label': 'Administrador', 'x': 90, 'y': 340},
    {'id': 'vista', 'type': 'boundary', 'label': 'Medicos.vue', 'x': 280, 'y': 340},
    {'id': 'ctrl', 'type': 'control', 'label': 'MedicoController', 'x': 510, 'y': 340},
    {'id': 'bit_srv', 'type': 'control', 'label': 'BitacoraService', 'x': 510, 'y': 550},
    {'id': 'persona', 'type': 'entity', 'label': 'PersonaModel', 'x': 780, 'y': 170},
    {'id': 'medico', 'type': 'entity', 'label': 'MedicoModel', 'x': 890, 'y': 280},
    {'id': 'esp', 'type': 'entity', 'label': 'EspecialidadModel', 'x': 890, 'y': 410},
    {'id': 'med_esp', 'type': 'entity', 'label': 'MedicoEspecialidadModel', 'x': 800, 'y': 500},
    {'id': 'bitacora', 'type': 'entity', 'label': 'bitacora', 'x': 740, 'y': 600},
]
msgs_cu06 = [
    {'from': 'admin', 'to': 'vista', 'text': '1: registrarMedico(ci, nombres, matricula, especialidades[])', 'side_off': 5, 't': 0.5, 'offset': 12},
    {'from': 'vista', 'to': 'ctrl', 'text': '2: store(Request)', 'side_off': 5, 't': 0.5, 'offset': 12},
    {'from': 'ctrl', 'to': 'persona', 'text': '3: create(personaData)', 't': 0.48, 'offset': -12},
    {'from': 'ctrl', 'to': 'medico', 'text': '4: create(persona_id, matricula_profesional)', 't': 0.5, 'offset': -10},
    {'from': 'ctrl', 'to': 'esp', 'text': '5: exists:especialidades,id', 't': 0.5, 'offset': 10},
    {'from': 'ctrl', 'to': 'med_esp', 'text': '6: create(medico_id, especialidad_id, precio_consulta)', 't': 0.5, 'offset': 10},
    {'from': 'ctrl', 'to': 'bit_srv', 'text': "7: registrar('REGISTRO_MEDICO', 'Médicos')", 't': 0.5, 'offset': -14},
    {'from': 'bit_srv', 'to': 'bitacora', 'text': '8: create(bitacoraData)', 't': 0.5, 'offset': 12},
    {'from': 'ctrl', 'to': 'vista', 'text': "9: back()->with('success')", 'side_off': -5, 't': 0.5, 'offset': 12},
    {'from': 'vista', 'to': 'admin', 'text': '10: mostrarConfirmacion()', 'side_off': -5, 't': 0.5, 'offset': 12},
]

# ==============================================================================
# CU-07: Gestionar Perfil Médico Base
# ==============================================================================
nodes_cu07 = [
    {'id': 'medico', 'type': 'actor', 'label': 'Médico', 'x': 90, 'y': 340},
    {'id': 'vista', 'type': 'boundary', 'label': 'PerfilMedico.vue', 'x': 280, 'y': 340},
    {'id': 'ctrl', 'type': 'control', 'label': 'PerfilMedicoController', 'x': 520, 'y': 340},
    {'id': 'bit_srv', 'type': 'control', 'label': 'BitacoraService', 'x': 520, 'y': 540},
    {'id': 'med_mod', 'type': 'entity', 'label': 'MedicoModel', 'x': 810, 'y': 190},
    {'id': 'persona', 'type': 'entity', 'label': 'PersonaModel', 'x': 890, 'y': 320},
    {'id': 'med_esp', 'type': 'entity', 'label': 'MedicoEspecialidadModel', 'x': 870, 'y': 450},
    {'id': 'bitacora', 'type': 'entity', 'label': 'bitacora', 'x': 780, 'y': 570},
]
msgs_cu07 = [
    {'from': 'medico', 'to': 'vista', 'text': '1: actualizarPerfil(telefono, direccion, especialidades[precio])', 'side_off': 5, 't': 0.5, 'offset': 12},
    {'from': 'vista', 'to': 'ctrl', 'text': '2: update(Request)', 'side_off': 5, 't': 0.5, 'offset': 12},
    {'from': 'ctrl', 'to': 'med_mod', 'text': "3: where('persona_id', user->persona_id)", 't': 0.48, 'offset': -12},
    {'from': 'ctrl', 'to': 'persona', 'text': '4: update(telefono, direccion)', 't': 0.5, 'offset': 10},
    {'from': 'ctrl', 'to': 'med_esp', 'text': '5: especialidades()->sync(arancelesData)', 't': 0.5, 'offset': 10},
    {'from': 'ctrl', 'to': 'bit_srv', 'text': "6: registrar('ACTUALIZACION_PERFIL_MEDICO', 'Médicos')", 't': 0.5, 'offset': -14},
    {'from': 'bit_srv', 'to': 'bitacora', 'text': '7: create(bitacoraData)', 't': 0.5, 'offset': 12},
    {'from': 'ctrl', 'to': 'vista', 'text': "8: back()->with('success')", 'side_off': -5, 't': 0.5, 'offset': 12},
    {'from': 'vista', 'to': 'medico', 'text': '9: notificarExito()', 'side_off': -5, 't': 0.5, 'offset': 12},
]

# ==============================================================================
# CU-08: Gestionar Pacientes
# ==============================================================================
nodes_cu08 = [
    {'id': 'recep', 'type': 'actor', 'label': 'Recepcionista', 'x': 90, 'y': 340},
    {'id': 'vista', 'type': 'boundary', 'label': 'Pacientes.vue', 'x': 280, 'y': 340},
    {'id': 'ctrl', 'type': 'control', 'label': 'PacienteController', 'x': 510, 'y': 340},
    {'id': 'bit_srv', 'type': 'control', 'label': 'BitacoraService', 'x': 510, 'y': 550},
    {'id': 'persona', 'type': 'entity', 'label': 'PersonaModel', 'x': 800, 'y': 180},
    {'id': 'paciente', 'type': 'entity', 'label': 'PacienteModel', 'x': 890, 'y': 310},
    {'id': 'ant', 'type': 'entity', 'label': 'AntecedenteMedicoModel', 'x': 860, 'y': 460},
    {'id': 'bitacora', 'type': 'entity', 'label': 'bitacora', 'x': 770, 'y': 570},
]
msgs_cu08 = [
    {'from': 'recep', 'to': 'vista', 'text': '1: afiliarPaciente(ci, nombres, tipo_sangre, emergencia, antecedentes)', 'side_off': 5, 't': 0.5, 'offset': 12},
    {'from': 'vista', 'to': 'ctrl', 'text': '2: store(Request)', 'side_off': 5, 't': 0.5, 'offset': 12},
    {'from': 'ctrl', 'to': 'persona', 'text': '3: create(personaData)', 't': 0.48, 'offset': -12},
    {'from': 'ctrl', 'to': 'paciente', 'text': '4: create(persona_id, tipo_sangre, contacto_emergencia)', 't': 0.5, 'offset': 10},
    {'from': 'ctrl', 'to': 'ant', 'text': '5: antecedentes()->create(condicion, descripcion)', 't': 0.5, 'offset': 10},
    {'from': 'ctrl', 'to': 'bit_srv', 'text': "6: registrar('REGISTRO_PACIENTE', 'Pacientes')", 't': 0.5, 'offset': -14},
    {'from': 'bit_srv', 'to': 'bitacora', 'text': '7: create(bitacoraData)', 't': 0.5, 'offset': 12},
    {'from': 'ctrl', 'to': 'vista', 'text': "8: back()->with('success')", 'side_off': -5, 't': 0.5, 'offset': 12},
    {'from': 'vista', 'to': 'recep', 'text': '9: mostrarFichaCreada()', 'side_off': -5, 't': 0.5, 'offset': 12},
]

# ==============================================================================
# CU-09: Configurar Horarios Regulares de Atención
# ==============================================================================
nodes_cu09 = [
    {'id': 'admin', 'type': 'actor', 'label': 'Administrador', 'x': 90, 'y': 340},
    {'id': 'vista', 'type': 'boundary', 'label': 'Horarios.vue', 'x': 280, 'y': 340},
    {'id': 'ctrl', 'type': 'control', 'label': 'HorarioAtencionController', 'x': 510, 'y': 340},
    {'id': 'bit_srv', 'type': 'control', 'label': 'BitacoraService', 'x': 510, 'y': 550},
    {'id': 'dia', 'type': 'entity', 'label': 'DiaSemanaModel', 'x': 800, 'y': 180},
    {'id': 'med_esp', 'type': 'entity', 'label': 'MedicoEspecialidadModel', 'x': 890, 'y': 310},
    {'id': 'horario', 'type': 'entity', 'label': 'HorarioAtencionModel', 'x': 870, 'y': 450},
    {'id': 'bitacora', 'type': 'entity', 'label': 'bitacora', 'x': 770, 'y': 570},
]
msgs_cu09 = [
    {'from': 'admin', 'to': 'vista', 'text': '1: configurarTurno(med_esp_id, dia_id, inicio, fin, intervalo)', 'side_off': 5, 't': 0.5, 'offset': 12},
    {'from': 'vista', 'to': 'ctrl', 'text': '2: store(Request)', 'side_off': 5, 't': 0.5, 'offset': 12},
    {'from': 'ctrl', 'to': 'dia', 'text': '3: exists:dias_semana,id', 't': 0.48, 'offset': -12},
    {'from': 'ctrl', 'to': 'med_esp', 'text': '4: exists:medico_especialidad,id', 't': 0.5, 'offset': 10},
    {'from': 'ctrl', 'to': 'horario', 'text': '5: verificarTraslapes(med_esp_id, dia_id, horas)', 't': 0.5, 'offset': 10},
    {'from': 'ctrl', 'to': 'horario', 'text': '6: create(horarioData)', 'side_off': 10, 't': 0.65, 'offset': 20},
    {'from': 'ctrl', 'to': 'bit_srv', 'text': "7: registrar('REGISTRO_HORARIO', 'Horarios')", 't': 0.5, 'offset': -14},
    {'from': 'bit_srv', 'to': 'bitacora', 'text': '8: create(bitacoraData)', 't': 0.5, 'offset': 12},
    {'from': 'ctrl', 'to': 'vista', 'text': "9: back()->with('success')", 'side_off': -5, 't': 0.5, 'offset': 12},
    {'from': 'vista', 'to': 'admin', 'text': '10: actualizarAgendaSemanal()', 'side_off': -5, 't': 0.5, 'offset': 12},
]

# ==============================================================================
# CU-10: Gestionar Excepciones y Ausencias Médicas
# ==============================================================================
nodes_cu10 = [
    {'id': 'medico', 'type': 'actor', 'label': 'Médico', 'x': 90, 'y': 340},
    {'id': 'vista', 'type': 'boundary', 'label': 'Ausencias.vue', 'x': 280, 'y': 340},
    {'id': 'ctrl', 'type': 'control', 'label': 'AusenciaMedicaController', 'x': 520, 'y': 340},
    {'id': 'bit_srv', 'type': 'control', 'label': 'BitacoraService', 'x': 520, 'y': 540},
    {'id': 'med_mod', 'type': 'entity', 'label': 'MedicoModel', 'x': 810, 'y': 190},
    {'id': 'persona', 'type': 'entity', 'label': 'PersonaModel', 'x': 890, 'y': 320},
    {'id': 'ausencia', 'type': 'entity', 'label': 'AusenciaMedicaModel', 'x': 870, 'y': 450},
    {'id': 'bitacora', 'type': 'entity', 'label': 'bitacora', 'x': 780, 'y': 570},
]
msgs_cu10 = [
    {'from': 'medico', 'to': 'vista', 'text': '1: registrarLicencia(medico_id, fecha_inicio, fecha_fin, motivo)', 'side_off': 5, 't': 0.5, 'offset': 12},
    {'from': 'vista', 'to': 'ctrl', 'text': '2: store(Request)', 'side_off': 5, 't': 0.5, 'offset': 12},
    {'from': 'ctrl', 'to': 'med_mod', 'text': "3: with('persona')->find(medico_id)", 't': 0.48, 'offset': -12},
    {'from': 'med_mod', 'to': 'persona', 'text': '4: getNombreCompleto()', 't': 0.5, 'offset': 10},
    {'from': 'ctrl', 'to': 'ausencia', 'text': '5: create(medico_id, fecha_inicio, fecha_fin, motivo)', 't': 0.5, 'offset': 10},
    {'from': 'ctrl', 'to': 'bit_srv', 'text': "6: registrar('REGISTRO_AUSENCIA', 'Ausencias')", 't': 0.5, 'offset': -14},
    {'from': 'bit_srv', 'to': 'bitacora', 'text': '7: create(bitacoraData)', 't': 0.5, 'offset': 12},
    {'from': 'ctrl', 'to': 'vista', 'text': "8: back()->with('success')", 'side_off': -5, 't': 0.5, 'offset': 12},
    {'from': 'vista', 'to': 'medico', 'text': '9: confirmarBloqueoAgenda()', 'side_off': -5, 't': 0.5, 'offset': 12},
]

# ==============================================================================
# CU-11: Programar Cita Médica
# ==============================================================================
nodes_cu11 = [
    {'id': 'recep', 'type': 'actor', 'label': 'Recepcionista', 'x': 90, 'y': 240},
    {'id': 'paciente', 'type': 'actor', 'label': 'Paciente', 'x': 90, 'y': 460},
    {'id': 'vista', 'type': 'boundary', 'label': 'ModalProgramarCita.vue', 'x': 290, 'y': 350},
    {'id': 'ctrl', 'type': 'control', 'label': 'CitaController', 'x': 510, 'y': 350},
    {'id': 'disp', 'type': 'control', 'label': 'DisponibilidadCitaService', 'x': 510, 'y': 150},
    {'id': 'bit_srv', 'type': 'control', 'label': 'BitacoraService', 'x': 510, 'y': 550},
    {'id': 'pac_mod', 'type': 'entity', 'label': 'PacienteModel', 'x': 800, 'y': 180},
    {'id': 'med_esp', 'type': 'entity', 'label': 'MedicoEspecialidadModel', 'x': 890, 'y': 300},
    {'id': 'cita', 'type': 'entity', 'label': 'CitaModel', 'x': 890, 'y': 440},
    {'id': 'bitacora', 'type': 'entity', 'label': 'bitacora', 'x': 780, 'y': 570},
]
msgs_cu11 = [
    {'from': 'recep', 'to': 'vista', 'text': '1: seleccionarMedicoFecha(med_esp_id, fecha)', 't': 0.5, 'offset': 12},
    {'from': 'vista', 'to': 'ctrl', 'text': '2: slots(med_esp_id, fecha)', 'side_off': 6, 't': 0.5, 'offset': 12},
    {'from': 'ctrl', 'to': 'disp', 'text': '3: obtenerSlotsDisponibles(med_esp_id, fecha)', 't': 0.5, 'offset': -12},
    {'from': 'ctrl', 'to': 'vista', 'text': '4: response()->json(slots)', 'side_off': -6, 't': 0.5, 'offset': 12},
    {'from': 'paciente', 'to': 'vista', 'text': '5: elegirHorarioYMotivo(hora, motivo)', 't': 0.5, 'offset': 12},
    {'from': 'recep', 'to': 'vista', 'text': '6: confirmarReserva()', 'side_off': 6, 't': 0.6, 'offset': -12},
    {'from': 'vista', 'to': 'ctrl', 'text': '7: store(CitaRequest)', 'side_off': 10, 't': 0.5, 'offset': 14},
    {'from': 'ctrl', 'to': 'pac_mod', 'text': '8: exists:pacientes,id', 't': 0.48, 'offset': -10},
    {'from': 'ctrl', 'to': 'med_esp', 'text': '9: exists:medico_especialidad,id', 't': 0.5, 'offset': 10},
    {'from': 'ctrl', 'to': 'cita', 'text': '10: create(codigo, paciente_id, med_esp_id, horas, PROGRAMADA)', 't': 0.5, 'offset': 10},
    {'from': 'ctrl', 'to': 'bit_srv', 'text': "11: registrar('PROGRAMACION_CITA', 'Citas')", 't': 0.5, 'offset': -14},
    {'from': 'bit_srv', 'to': 'bitacora', 'text': '12: create(bitacoraData)', 't': 0.5, 'offset': 12},
    {'from': 'ctrl', 'to': 'vista', 'text': "13: back()->with('success', codigo)", 'side_off': -10, 't': 0.5, 'offset': 14},
    {'from': 'vista', 'to': 'recep', 'text': '14: entregarComprobanteReserva()', 'side_off': -6, 't': 0.5, 'offset': 12},
]

# ==============================================================================
# CU-12: Reprogramar Cita Médica
# ==============================================================================
nodes_cu12 = [
    {'id': 'recep', 'type': 'actor', 'label': 'Recepcionista', 'x': 90, 'y': 250},
    {'id': 'paciente', 'type': 'actor', 'label': 'Paciente', 'x': 90, 'y': 460},
    {'id': 'vista', 'type': 'boundary', 'label': 'ModalReprogramarCita.vue', 'x': 290, 'y': 350},
    {'id': 'ctrl', 'type': 'control', 'label': 'CitaController', 'x': 520, 'y': 350},
    {'id': 'disp', 'type': 'control', 'label': 'DisponibilidadCitaService', 'x': 520, 'y': 160},
    {'id': 'bit_srv', 'type': 'control', 'label': 'BitacoraService', 'x': 520, 'y': 550},
    {'id': 'cita', 'type': 'entity', 'label': 'CitaModel', 'x': 840, 'y': 350},
    {'id': 'bitacora', 'type': 'entity', 'label': 'bitacora', 'x': 800, 'y': 550},
]
msgs_cu12 = [
    {'from': 'paciente', 'to': 'vista', 'text': '1: solicitarCambioFecha(cita_id, nuevaFecha)', 't': 0.5, 'offset': 12},
    {'from': 'vista', 'to': 'ctrl', 'text': '2: slots(med_esp_id, nuevaFecha, cita_id)', 'side_off': 6, 't': 0.5, 'offset': 12},
    {'from': 'ctrl', 'to': 'disp', 'text': '3: obtenerSlotsDisponibles(med_esp, nuevaFecha, cita_id)', 't': 0.5, 'offset': -12},
    {'from': 'ctrl', 'to': 'vista', 'text': '4: response()->json(slots)', 'side_off': -6, 't': 0.5, 'offset': 12},
    {'from': 'recep', 'to': 'vista', 'text': '5: confirmarNuevaHora(nuevaHoraInicio, nuevaHoraFin)', 't': 0.5, 'offset': -12},
    {'from': 'vista', 'to': 'ctrl', 'text': '6: reprogramar(Request, cita)', 'side_off': 10, 't': 0.5, 'offset': 14},
    {'from': 'ctrl', 'to': 'cita', 'text': '7: update(fecha, hora_inicio, hora_fin)', 't': 0.5, 'offset': 10},
    {'from': 'ctrl', 'to': 'bit_srv', 'text': "8: registrar('REPROGRAMACION_CITA', 'Citas')", 't': 0.5, 'offset': -14},
    {'from': 'bit_srv', 'to': 'bitacora', 'text': '9: create(bitacoraData)', 't': 0.5, 'offset': 12},
    {'from': 'ctrl', 'to': 'vista', 'text': "10: back()->with('success')", 'side_off': -10, 't': 0.5, 'offset': 14},
    {'from': 'vista', 'to': 'recep', 'text': '11: notificarReprogramacion()', 'side_off': -6, 't': 0.5, 'offset': 12},
]

# ==============================================================================
# CU-13: Cancelar Cita Médica
# ==============================================================================
nodes_cu13 = [
    {'id': 'recep', 'type': 'actor', 'label': 'Recepcionista', 'x': 90, 'y': 250},
    {'id': 'paciente', 'type': 'actor', 'label': 'Paciente', 'x': 90, 'y': 460},
    {'id': 'vista', 'type': 'boundary', 'label': 'ModalCancelarCita.vue', 'x': 290, 'y': 350},
    {'id': 'ctrl', 'type': 'control', 'label': 'CitaController', 'x': 520, 'y': 350},
    {'id': 'bit_srv', 'type': 'control', 'label': 'BitacoraService', 'x': 520, 'y': 550},
    {'id': 'cita', 'type': 'entity', 'label': 'CitaModel', 'x': 820, 'y': 230},
    {'id': 'estado', 'type': 'entity', 'label': 'EstadoCitaModel', 'x': 890, 'y': 370},
    {'id': 'bitacora', 'type': 'entity', 'label': 'bitacora', 'x': 820, 'y': 550},
]
msgs_cu13 = [
    {'from': 'paciente', 'to': 'vista', 'text': '1: solicitarCancelacion(motivo)', 't': 0.5, 'offset': 12},
    {'from': 'recep', 'to': 'vista', 'text': '2: ingresarMotivo(motivo_cancelacion)', 't': 0.5, 'offset': -12},
    {'from': 'vista', 'to': 'ctrl', 'text': '3: cancelar(Request, cita)', 'side_off': 6, 't': 0.5, 'offset': 12},
    {'from': 'ctrl', 'to': 'estado', 'text': '4: find(CANCELADA: 4)', 't': 0.5, 'offset': 10},
    {'from': 'ctrl', 'to': 'cita', 'text': '5: update(estado_id: 4, motivo_cancelacion)', 't': 0.48, 'offset': -12},
    {'from': 'cita', 'to': 'bitacora', 'text': '6: tg_AuditoriaCambioEstadoCita()', 't': 0.5, 'offset': 10},
    {'from': 'ctrl', 'to': 'bit_srv', 'text': "7: registrar('CANCELACION_CITA', 'Citas')", 't': 0.5, 'offset': -14},
    {'from': 'bit_srv', 'to': 'bitacora', 'text': '8: create(bitacoraData)', 't': 0.5, 'offset': 12},
    {'from': 'ctrl', 'to': 'vista', 'text': "9: back()->with('success')", 'side_off': -6, 't': 0.5, 'offset': 12},
    {'from': 'vista', 'to': 'recep', 'text': '10: confirmarCancelacion()', 'side_off': -6, 't': 0.5, 'offset': 12},
]

# ==============================================================================
# CU-14: Registrar Atención de Paciente
# ==============================================================================
nodes_cu14 = [
    {'id': 'medico', 'type': 'actor', 'label': 'Médico', 'x': 90, 'y': 340},
    {'id': 'vista', 'type': 'boundary', 'label': 'ModalRegistrarAtencion.vue', 'x': 280, 'y': 340},
    {'id': 'ctrl', 'type': 'control', 'label': 'AtencionClinicaController', 'x': 510, 'y': 340},
    {'id': 'bit_srv', 'type': 'control', 'label': 'BitacoraService', 'x': 510, 'y': 550},
    {'id': 'cita', 'type': 'entity', 'label': 'CitaModel', 'x': 800, 'y': 190},
    {'id': 'atencion', 'type': 'entity', 'label': 'AtencionClinicaModel', 'x': 890, 'y': 330},
    {'id': 'estado', 'type': 'entity', 'label': 'EstadoCitaModel', 'x': 860, 'y': 470},
    {'id': 'bitacora', 'type': 'entity', 'label': 'bitacora', 'x': 760, 'y': 580},
]
msgs_cu14 = [
    {'from': 'medico', 'to': 'vista', 'text': '1: ingresarConsulta(diagnostico, tratamiento, notas)', 'side_off': 5, 't': 0.5, 'offset': 12},
    {'from': 'vista', 'to': 'ctrl', 'text': '2: store(Request)', 'side_off': 5, 't': 0.5, 'offset': 12},
    {'from': 'ctrl', 'to': 'cita', 'text': '3: findOrFail(cita_id)', 't': 0.48, 'offset': -12},
    {'from': 'ctrl', 'to': 'atencion', 'text': '4: create(cita_id, diagnostico, tratamiento, notas)', 't': 0.5, 'offset': 10},
    {'from': 'ctrl', 'to': 'cita', 'text': '5: update(estado_id: ATENDIDA)', 'side_off': 8, 't': 0.6, 'offset': 20},
    {'from': 'atencion', 'to': 'cita', 'text': '6: tg_AtencionFinalizaCita()', 't': 0.5, 'offset': -10},
    {'from': 'ctrl', 'to': 'bit_srv', 'text': "7: registrar('REGISTRO_ATENCION', 'Atenciones')", 't': 0.5, 'offset': -14},
    {'from': 'bit_srv', 'to': 'bitacora', 'text': '8: create(bitacoraData)', 't': 0.5, 'offset': 12},
    {'from': 'ctrl', 'to': 'vista', 'text': "9: back()->with('success')", 'side_off': -5, 't': 0.5, 'offset': 12},
    {'from': 'vista', 'to': 'medico', 'text': '10: mostrarRecetaYAtencionGuardada()', 'side_off': -5, 't': 0.5, 'offset': 12},
]

# ==============================================================================
# CU-15: Consultar Historial de Citas
# ==============================================================================
nodes_cu15 = [
    {'id': 'recep', 'type': 'actor', 'label': 'Recepcionista', 'x': 90, 'y': 340},
    {'id': 'vista', 'type': 'boundary', 'label': 'Citas.vue', 'x': 280, 'y': 340},
    {'id': 'ctrl', 'type': 'control', 'label': 'CitaController', 'x': 520, 'y': 340},
    {'id': 'cita', 'type': 'entity', 'label': 'CitaModel', 'x': 810, 'y': 170},
    {'id': 'paciente', 'type': 'entity', 'label': 'PacienteModel', 'x': 890, 'y': 290},
    {'id': 'medico', 'type': 'entity', 'label': 'MedicoModel', 'x': 890, 'y': 420},
    {'id': 'estado', 'type': 'entity', 'label': 'EstadoCitaModel', 'x': 810, 'y': 550},
]
msgs_cu15 = [
    {'from': 'recep', 'to': 'vista', 'text': '1: filtrarCitas(fecha, medico, especialidad, estado)', 'side_off': 5, 't': 0.5, 'offset': 12},
    {'from': 'vista', 'to': 'ctrl', 'text': '2: index(Request)', 'side_off': 5, 't': 0.5, 'offset': 12},
    {'from': 'ctrl', 'to': 'cita', 'text': "3: with(['paciente.persona', 'medico', 'estado', 'atencion'])", 't': 0.48, 'offset': -12},
    {'from': 'cita', 'to': 'paciente', 'text': '4: loadPaciente()', 't': 0.5, 'offset': 10},
    {'from': 'cita', 'to': 'medico', 'text': '5: loadMedico()', 't': 0.5, 'offset': 10},
    {'from': 'cita', 'to': 'estado', 'text': '6: loadEstado()', 't': 0.5, 'offset': 12},
    {'from': 'ctrl', 'to': 'vista', 'text': "7: render('Citas/Index', citasPaginadas)", 'side_off': -5, 't': 0.5, 'offset': 12},
    {'from': 'vista', 'to': 'recep', 'text': '8: renderizarAgendaYHistorial()', 'side_off': -5, 't': 0.5, 'offset': 12},
]

# ==============================================================================
# CU-16: Generar Reportes Administrativos
# ==============================================================================
nodes_cu16 = [
    {'id': 'admin', 'type': 'actor', 'label': 'Administrador', 'x': 90, 'y': 340},
    {'id': 'vista', 'type': 'boundary', 'label': 'Reportes.vue', 'x': 280, 'y': 340},
    {'id': 'ctrl', 'type': 'control', 'label': 'ReporteController', 'x': 520, 'y': 340},
    {'id': 'cita', 'type': 'entity', 'label': 'CitaModel', 'x': 810, 'y': 180},
    {'id': 'medico', 'type': 'entity', 'label': 'MedicoModel', 'x': 890, 'y': 310},
    {'id': 'esp', 'type': 'entity', 'label': 'EspecialidadModel', 'x': 860, 'y': 450},
    {'id': 'atencion', 'type': 'entity', 'label': 'AtencionClinicaModel', 'x': 780, 'y': 570},
]
msgs_cu16 = [
    {'from': 'admin', 'to': 'vista', 'text': '1: consultarMetricas(fecha_inicio, fecha_fin)', 'side_off': 5, 't': 0.5, 'offset': 12},
    {'from': 'vista', 'to': 'ctrl', 'text': '2: index(Request)', 'side_off': 5, 't': 0.5, 'offset': 12},
    {'from': 'ctrl', 'to': 'cita', 'text': "3: whereDate('fecha', rango)->count()", 't': 0.48, 'offset': -12},
    {'from': 'ctrl', 'to': 'cita', 'text': '4: sum(precio_consulta) [citasAtendidas]', 'side_off': 8, 't': 0.65, 'offset': 20},
    {'from': 'ctrl', 'to': 'medico', 'text': '5: calcularRendimientoPorMedico()', 't': 0.5, 'offset': 10},
    {'from': 'ctrl', 'to': 'esp', 'text': '6: calcularDemandaPorEspecialidad()', 't': 0.5, 'offset': 10},
    {'from': 'ctrl', 'to': 'atencion', 'text': '7: countAtencionesEfectivas()', 't': 0.5, 'offset': 12},
    {'from': 'ctrl', 'to': 'vista', 'text': "8: render('Reportes/Index', metricas, kpis)", 'side_off': -5, 't': 0.5, 'offset': 12},
    {'from': 'vista', 'to': 'admin', 'text': '9: mostrarGraficosYRendimiento()', 'side_off': -5, 't': 0.5, 'offset': 12},
]

# ==============================================================================
# CU-17: Exportar Datos a .xlsx
# ==============================================================================
nodes_cu17 = [
    {'id': 'admin', 'type': 'actor', 'label': 'Administrador', 'x': 90, 'y': 340},
    {'id': 'vista', 'type': 'boundary', 'label': 'Reportes.vue', 'x': 280, 'y': 340},
    {'id': 'ctrl', 'type': 'control', 'label': 'ReporteController', 'x': 500, 'y': 340},
    {'id': 'excel', 'type': 'control', 'label': 'ExportExcelService', 'x': 500, 'y': 150},
    {'id': 'bit_srv', 'type': 'control', 'label': 'BitacoraService', 'x': 500, 'y': 550},
    {'id': 'cita', 'type': 'entity', 'label': 'CitaModel', 'x': 810, 'y': 200},
    {'id': 'sheet', 'type': 'entity', 'label': 'Spreadsheet', 'x': 890, 'y': 330},
    {'id': 'xlsx', 'type': 'entity', 'label': 'Xlsx', 'x': 870, 'y': 460},
    {'id': 'bitacora', 'type': 'entity', 'label': 'bitacora', 'x': 760, 'y': 580},
]
msgs_cu17 = [
    {'from': 'admin', 'to': 'vista', 'text': "1: exportarExcel('exportCitas')", 'side_off': 5, 't': 0.5, 'offset': 12},
    {'from': 'vista', 'to': 'ctrl', 'text': '2: exportCitas(Request, ExportExcelService)', 'side_off': 5, 't': 0.5, 'offset': 12},
    {'from': 'ctrl', 'to': 'cita', 'text': "3: with(['paciente', 'medico', 'estado'])->get()", 't': 0.48, 'offset': -12},
    {'from': 'ctrl', 'to': 'excel', 'text': '4: exportarCitas(citas)', 't': 0.5, 'offset': -12},
    {'from': 'excel', 'to': 'sheet', 'text': '5: new Spreadsheet() & setCellValue()', 't': 0.5, 'offset': 10},
    {'from': 'excel', 'to': 'xlsx', 'text': "6: new Xlsx(spreadsheet)->save('php://output')", 't': 0.5, 'offset': 10},
    {'from': 'ctrl', 'to': 'bit_srv', 'text': "7: registrar('EXPORTACION_EXCEL', 'Reportes')", 't': 0.5, 'offset': -14},
    {'from': 'bit_srv', 'to': 'bitacora', 'text': '8: create(bitacoraData)', 't': 0.5, 'offset': 12},
    {'from': 'excel', 'to': 'vista', 'text': '9: StreamedResponse(xlsxContent)', 'side_off': -5, 't': 0.5, 'offset': 12},
    {'from': 'vista', 'to': 'admin', 'text': '10: descargarArchivoXLSX()', 'side_off': -5, 't': 0.5, 'offset': 12},
]

# Lista consolidada de los 17 casos de uso
diagramas = [
    ("CU01_Gestionar_Usuarios.png", "CU01", "Gestionar Usuarios", nodes_cu01, msgs_cu01),
    ("CU02_Gestionar_Roles_Permisos.png", "CU02", "Gestionar Roles y Permisos", nodes_cu02, msgs_cu02),
    ("CU03_Consultar_Bitacora.png", "CU03", "Consultar Bitácora de Auditoría", nodes_cu03, msgs_cu03),
    ("CU04_Gestionar_Respaldos.png", "CU04", "Gestionar Respaldos de Base de Datos", nodes_cu04, msgs_cu04),
    ("CU05_Gestionar_Especialidades.png", "CU05", "Gestionar Especialidades", nodes_cu05, msgs_cu05),
    ("CU06_Gestionar_Medicos.png", "CU06", "Gestionar Médicos", nodes_cu06, msgs_cu06),
    ("CU07_Gestionar_Perfil_Medico.png", "CU07", "Gestionar Perfil Médico Base", nodes_cu07, msgs_cu07),
    ("CU08_Gestionar_Pacientes.png", "CU08", "Gestionar Pacientes", nodes_cu08, msgs_cu08),
    ("CU09_Configurar_Horarios.png", "CU09", "Configurar Horarios Regulares de Atención", nodes_cu09, msgs_cu09),
    ("CU10_Gestionar_Ausencias_Medicas.png", "CU10", "Gestionar Excepciones y Ausencias Médicas", nodes_cu10, msgs_cu10),
    ("CU11_Programar_Cita_Medica.png", "CU11", "Programar Cita Médica", nodes_cu11, msgs_cu11),
    ("CU12_Reprogramar_Cita_Medica.png", "CU12", "Reprogramar Cita Médica", nodes_cu12, msgs_cu12),
    ("CU13_Cancelar_Cita_Medica.png", "CU13", "Cancelar Cita Médica", nodes_cu13, msgs_cu13),
    ("CU14_Registrar_Atencion_Paciente.png", "CU14", "Registrar Atención de Paciente", nodes_cu14, msgs_cu14),
    ("CU15_Consultar_Historial_Citas.png", "CU15", "Consultar Historial de Citas", nodes_cu15, msgs_cu15),
    ("CU16_Generar_Reportes_Administrativos.png", "CU16", "Generar Reportes Administrativos", nodes_cu16, msgs_cu16),
    ("CU17_Exportar_Datos_XLSX.png", "CU17", "Exportar Datos a .xlsx", nodes_cu17, msgs_cu17),
]

for filename, code, title, nodes, msgs in diagramas:
    filepath = os.path.join(output_dir, filename)
    draw_diagram(filepath, code, title, nodes, msgs)

print(f"\n[FINALIZADO CON EXITO] Se han generado los 17 diagramas en: {output_dir}")

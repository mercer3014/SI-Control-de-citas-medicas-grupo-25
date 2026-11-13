# -*- coding: utf-8 -*-
"""
Generador Maestro de los 17 Diagramas de Secuencia UML (CU-01 al CU-17)
Estructura y estilo idénticos al estándar formal de referencia (PUDS / Enterprise Architect).
Limpio, sin funciones de bajo nivel, sin texto resaltado ni recuadros de fondo.
"""

import os
from sequence_renderer import draw_sequence_diagram

output_dir_secuencia = r"c:\Users\Cerezo Alex\OneDrive\Escritorio\CONTROL DE CITAS\docs\diagramas\secuencia"
output_dir_diagramas = r"c:\Users\Cerezo Alex\OneDrive\Escritorio\CONTROL DE CITAS\docs\diagramas"
os.makedirs(output_dir_secuencia, exist_ok=True)
os.makedirs(output_dir_diagramas, exist_ok=True)

diagramas = [
    # CU-01
    {
        "filename": "CU01_Gestionar_Usuarios.png",
        "alt_filename": "cu01_gestionar_usuarios.png",
        "title": "CU01: Gestionar Usuarios",
        "actor": "Administrador",
        "lifelines": ["Vista_Usuario", "Controller_Usuario", "Usuario", "Bitacora"],
        "activations": [
            (1, 98, 150), (2, 103, 145), (3, 108, 130), (3, 130, 155, True),
            (1, 205, 260), (2, 210, 260), (3, 215, 235), (4, 240, 260),
            (1, 315, 370), (2, 320, 370), (3, 325, 345), (4, 350, 370),
        ],
        "messages": [
            {"from": 0, "to": 1, "y": 100, "label": "1 : SeleccionarUsuario"},
            {"from": 1, "to": 2, "y": 105, "label": "2 : MostrarUsuario"},
            {"from": 2, "to": 3, "y": 110, "label": "3 : ObtenerUsuario"},
            {"from": 3, "to": 3, "y": 135, "label": "4 : BuscarUsuario", "is_self": True},

            {"from": 0, "to": 1, "y": 208, "label": "5 : NuevoUsuario"},
            {"from": 1, "to": 2, "y": 213, "label": "6 : RegistrarUsuario"},
            {"from": 2, "to": 3, "y": 218, "label": "7 : GuardarUsuario"},
            {"from": 2, "to": 4, "y": 243, "label": "8 : RegistrarAcceso"},

            {"from": 0, "to": 1, "y": 318, "label": "9 : DesactivarUsuario"},
            {"from": 1, "to": 2, "y": 323, "label": "10 : ModificarEstado"},
            {"from": 2, "to": 3, "y": 328, "label": "11 : ActualizarEstado"},
            {"from": 2, "to": 4, "y": 353, "label": "12 : RegistrarBitacora"},
        ],
        "height": 450,
        "width": 800,
    },

    # CU-02
    {
        "filename": "CU02_Gestionar_Roles_Permisos.png",
        "alt_filename": "cu02_gestionar_roles_permisos.png",
        "title": "CU02: Gestionar Roles y Permisos",
        "actor": "Administrador",
        "lifelines": ["Vista_Rol", "Controller_Rol", "Rol", "Permiso"],
        "activations": [
            (1, 98, 150), (2, 103, 145), (3, 108, 130), (3, 130, 155, True),
            (1, 205, 245), (2, 210, 245), (3, 215, 245),
            (1, 290, 330), (2, 295, 330), (4, 300, 330),
        ],
        "messages": [
            {"from": 0, "to": 1, "y": 100, "label": "1 : SeleccionarRol"},
            {"from": 1, "to": 2, "y": 105, "label": "2 : MostrarPermisos"},
            {"from": 2, "to": 3, "y": 110, "label": "3 : ObtenerPermisos"},
            {"from": 3, "to": 3, "y": 135, "label": "4 : CargarPermisos", "is_self": True},

            {"from": 0, "to": 1, "y": 208, "label": "5 : NuevoRol"},
            {"from": 1, "to": 2, "y": 213, "label": "6 : RegistrarRol"},
            {"from": 2, "to": 3, "y": 218, "label": "7 : GuardarRol"},

            {"from": 0, "to": 1, "y": 293, "label": "8 : AsignarPermisos"},
            {"from": 1, "to": 2, "y": 298, "label": "9 : ModificarPermisos"},
            {"from": 2, "to": 4, "y": 303, "label": "10 : SincronizarPermisos"},
        ],
        "height": 420,
        "width": 780,
    },

    # CU-03
    {
        "filename": "CU03_Consultar_Bitacora.png",
        "alt_filename": "cu03_consultar_bitacora.png",
        "title": "CU03: Consultar Bitacora de Auditoria",
        "actor": "Administrador",
        "lifelines": ["Vista_Bitacora", "Controller_Bitacora", "Bitacora"],
        "activations": [
            (1, 98, 150), (2, 103, 145), (3, 108, 130), (3, 130, 155, True),
            (1, 205, 245), (2, 210, 245), (3, 215, 245),
            (1, 290, 345), (2, 295, 345), (2, 318, 345, True),
        ],
        "messages": [
            {"from": 0, "to": 1, "y": 100, "label": "1 : FiltrarEventos"},
            {"from": 1, "to": 2, "y": 105, "label": "2 : ConsultarAuditoria"},
            {"from": 2, "to": 3, "y": 110, "label": "3 : ObtenerRegistros"},
            {"from": 3, "to": 3, "y": 135, "label": "4 : AplicarFiltros", "is_self": True},

            {"from": 0, "to": 1, "y": 208, "label": "5 : SeleccionarEvento"},
            {"from": 1, "to": 2, "y": 213, "label": "6 : MostrarDetalle"},
            {"from": 2, "to": 3, "y": 218, "label": "7 : ObtenerDetalleJSON"},

            {"from": 0, "to": 1, "y": 293, "label": "8 : SolicitarLogsServidor"},
            {"from": 1, "to": 2, "y": 298, "label": "9 : LeerArchivoLogs"},
            {"from": 2, "to": 2, "y": 323, "label": "10 : ExtraerLineasLog", "is_self": True},
        ],
        "height": 430,
        "width": 750,
    },

    # CU-04
    {
        "filename": "CU04_Gestionar_Respaldos.png",
        "alt_filename": "cu04_gestionar_respaldos.png",
        "title": "CU04: Gestionar Respaldos de Base de Datos",
        "actor": "Administrador",
        "lifelines": ["Vista_Respaldo", "Controller_Backup", "BaseDeDatos", "Bitacora"],
        "activations": [
            (1, 98, 135), (2, 103, 135), (3, 108, 135),
            (1, 185, 245), (2, 190, 245), (3, 195, 215), (4, 220, 245),
            (1, 295, 345), (2, 300, 345), (2, 323, 348, True),
        ],
        "messages": [
            {"from": 0, "to": 1, "y": 100, "label": "1 : ConsultarEstado"},
            {"from": 1, "to": 2, "y": 105, "label": "2 : MostrarMetricas"},
            {"from": 2, "to": 3, "y": 110, "label": "3 : ObtenerEstadisticas"},

            {"from": 0, "to": 1, "y": 188, "label": "4 : SolicitarRespaldo"},
            {"from": 1, "to": 2, "y": 193, "label": "5 : GenerarRespaldo"},
            {"from": 2, "to": 3, "y": 198, "label": "6 : EjecutarDumpSQL"},
            {"from": 2, "to": 4, "y": 223, "label": "7 : RegistrarBitacora"},

            {"from": 0, "to": 1, "y": 298, "label": "8 : SubirArchivoRespaldo"},
            {"from": 1, "to": 2, "y": 303, "label": "9 : ValidarEstructura"},
            {"from": 2, "to": 2, "y": 328, "label": "10 : VerificarCompatibilidad", "is_self": True},
        ],
        "height": 430,
        "width": 780,
    },

    # CU-05
    {
        "filename": "CU05_Gestionar_Especialidades.png",
        "alt_filename": "cu05_gestionar_especialidades.png",
        "title": "CU05: Gestionar Especialidades",
        "actor": "Administrador",
        "lifelines": ["Vista_Especialidad", "Controller_Especialidad", "Especialidad"],
        "activations": [
            (1, 98, 150), (2, 103, 145), (3, 108, 130), (3, 130, 155, True),
            (1, 205, 245), (2, 210, 245), (3, 215, 245),
            (1, 290, 330), (2, 295, 330), (3, 300, 330),
        ],
        "messages": [
            {"from": 0, "to": 1, "y": 100, "label": "1 : SeleccionarEspecialidad"},
            {"from": 1, "to": 2, "y": 105, "label": "2 : MostrarEspecialidad"},
            {"from": 2, "to": 3, "y": 110, "label": "3 : ObtenerEspecialidad"},
            {"from": 3, "to": 3, "y": 135, "label": "4 : BuscarEspecialidad", "is_self": True},

            {"from": 0, "to": 1, "y": 208, "label": "5 : NuevaEspecialidad"},
            {"from": 1, "to": 2, "y": 213, "label": "6 : RegistrarEspecialidad"},
            {"from": 2, "to": 3, "y": 218, "label": "7 : GuardarEspecialidad"},

            {"from": 0, "to": 1, "y": 293, "label": "8 : EditarEspecialidad"},
            {"from": 1, "to": 2, "y": 298, "label": "9 : ModificarEspecialidad"},
            {"from": 2, "to": 3, "y": 303, "label": "10 : ActualizarEspecialidad"},
        ],
        "height": 420,
        "width": 750,
    },

    # CU-06
    {
        "filename": "CU06_Gestionar_Medicos.png",
        "alt_filename": "cu06_gestionar_medicos.png",
        "title": "CU06: Gestionar Medicos",
        "actor": "Administrador",
        "lifelines": ["Vista_Medico", "Controller_Medico", "Medico", "Especialidad"],
        "activations": [
            (1, 98, 150), (2, 103, 145), (3, 108, 130), (3, 130, 155, True),
            (1, 205, 260), (2, 210, 260), (3, 215, 235), (4, 240, 260),
            (1, 315, 355), (2, 320, 355), (3, 325, 355),
        ],
        "messages": [
            {"from": 0, "to": 1, "y": 100, "label": "1 : SeleccionarMedico"},
            {"from": 1, "to": 2, "y": 105, "label": "2 : MostrarMedico"},
            {"from": 2, "to": 3, "y": 110, "label": "3 : ObtenerMedico"},
            {"from": 3, "to": 3, "y": 135, "label": "4 : BuscarMedico", "is_self": True},

            {"from": 0, "to": 1, "y": 208, "label": "5 : NuevoMedico"},
            {"from": 1, "to": 2, "y": 213, "label": "6 : RegistrarMedico"},
            {"from": 2, "to": 3, "y": 218, "label": "7 : GuardarMedico"},
            {"from": 2, "to": 4, "y": 243, "label": "8 : AsignarEspecialidad"},

            {"from": 0, "to": 1, "y": 318, "label": "9 : EditarMedico"},
            {"from": 1, "to": 2, "y": 323, "label": "10 : ModificarMedico"},
            {"from": 2, "to": 3, "y": 328, "label": "11 : ActualizarMedico"},
        ],
        "height": 440,
        "width": 780,
    },

    # CU-07
    {
        "filename": "CU07_Gestionar_Perfil_Medico.png",
        "alt_filename": "cu07_perfil_medico_base.png",
        "title": "CU07: Gestionar Perfil Medico Base",
        "actor": "Médico",
        "lifelines": ["Vista_PerfilMedico", "Controller_PerfilMedico", "Medico"],
        "activations": [
            (1, 98, 150), (2, 103, 145), (3, 108, 130), (3, 130, 155, True),
            (1, 205, 245), (2, 210, 245), (3, 215, 245),
            (1, 290, 330), (2, 295, 330), (3, 300, 330),
        ],
        "messages": [
            {"from": 0, "to": 1, "y": 100, "label": "1 : AccederPerfil"},
            {"from": 1, "to": 2, "y": 105, "label": "2 : MostrarDatos"},
            {"from": 2, "to": 3, "y": 110, "label": "3 : ObtenerPerfil"},
            {"from": 3, "to": 3, "y": 135, "label": "4 : CargarDatosMedicos", "is_self": True},

            {"from": 0, "to": 1, "y": 208, "label": "5 : EditarBiografia"},
            {"from": 1, "to": 2, "y": 213, "label": "6 : ModificarInformacion"},
            {"from": 2, "to": 3, "y": 218, "label": "7 : ActualizarPerfil"},

            {"from": 0, "to": 1, "y": 293, "label": "8 : ConfigurarTarifa"},
            {"from": 1, "to": 2, "y": 298, "label": "9 : ModificarArancel"},
            {"from": 2, "to": 3, "y": 303, "label": "10 : GuardarArancel"},
        ],
        "height": 420,
        "width": 750,
    },

    # CU-08 (Idéntico a la imagen de referencia sd CU02: Gestionar Pacientes)
    {
        "filename": "CU08_Gestionar_Pacientes.png",
        "alt_filename": "cu08_gestionar_pacientes.png",
        "title": "CU08: Gestionar Pacientes",
        "actor": "Recepcionista",
        "lifelines": ["Vista_Paciente", "Controller_Paciente", "Paciente"],
        "activations": [
            (1, 98, 150), (2, 103, 145), (3, 108, 130), (3, 130, 155, True),
            (1, 205, 245), (2, 210, 245), (3, 215, 245),
            (1, 290, 330), (2, 295, 330), (3, 300, 330),
        ],
        "messages": [
            {"from": 0, "to": 1, "y": 100, "label": "1 : SeleccionarPaciente"},
            {"from": 1, "to": 2, "y": 105, "label": "2 : MostrarPaciente"},
            {"from": 2, "to": 3, "y": 110, "label": "3 : ObtenerPaciente"},
            {"from": 3, "to": 3, "y": 135, "label": "4 : BuscarPaciente", "is_self": True},

            {"from": 0, "to": 1, "y": 208, "label": "5 : NuevoPaciente"},
            {"from": 1, "to": 2, "y": 213, "label": "6 : RegistrarPaciente"},
            {"from": 2, "to": 3, "y": 218, "label": "7 : GuardarPaciente"},

            {"from": 0, "to": 1, "y": 293, "label": "8 : EditarPaciente"},
            {"from": 1, "to": 2, "y": 298, "label": "9 : ModificarPaciente"},
            {"from": 2, "to": 3, "y": 303, "label": "10 : ActualizarPaciente"},
        ],
        "height": 420,
        "width": 750,
    },

    # CU-09
    {
        "filename": "CU09_Configurar_Horarios.png",
        "alt_filename": "cu09_configurar_horarios.png",
        "title": "CU09: Configurar Horarios Regulares de Atencion",
        "actor": "Administrador",
        "lifelines": ["Vista_Horario", "Controller_Horario", "HorarioAtencion"],
        "activations": [
            (1, 98, 150), (2, 103, 145), (3, 108, 130), (3, 130, 155, True),
            (1, 205, 280), (2, 210, 280), (2, 233, 258, True), (3, 263, 280),
            (1, 335, 375), (2, 340, 375), (3, 345, 375),
        ],
        "messages": [
            {"from": 0, "to": 1, "y": 100, "label": "1 : ConsultarHorarios"},
            {"from": 1, "to": 2, "y": 105, "label": "2 : MostrarAgenda"},
            {"from": 2, "to": 3, "y": 110, "label": "3 : ObtenerHorarios"},
            {"from": 3, "to": 3, "y": 135, "label": "4 : CargarTurnos", "is_self": True},

            {"from": 0, "to": 1, "y": 208, "label": "5 : NuevoTurno"},
            {"from": 1, "to": 2, "y": 213, "label": "6 : RegistrarHorario"},
            {"from": 2, "to": 2, "y": 238, "label": "7 : ValidarTraslape", "is_self": True},
            {"from": 2, "to": 3, "y": 268, "label": "8 : GuardarHorario"},

            {"from": 0, "to": 1, "y": 338, "label": "9 : EditarTurno"},
            {"from": 1, "to": 2, "y": 343, "label": "10 : ModificarHorario"},
            {"from": 2, "to": 3, "y": 348, "label": "11 : ActualizarHorario"},
        ],
        "height": 450,
        "width": 750,
    },

    # CU-10
    {
        "filename": "CU10_Gestionar_Ausencias_Medicas.png",
        "alt_filename": "cu10_gestionar_ausencias_medicas.png",
        "title": "CU10: Gestionar Excepciones y Ausencias Medicas",
        "actor": "Médico",
        "lifelines": ["Vista_Ausencia", "Controller_Ausencia", "AusenciaMedica"],
        "activations": [
            (1, 98, 135), (2, 103, 135), (3, 108, 135),
            (1, 185, 255), (2, 190, 255), (2, 213, 238, True), (3, 243, 255),
            (1, 310, 350), (2, 315, 350), (3, 320, 350),
        ],
        "messages": [
            {"from": 0, "to": 1, "y": 100, "label": "1 : ConsultarAusencias"},
            {"from": 1, "to": 2, "y": 105, "label": "2 : MostrarAusencias"},
            {"from": 2, "to": 3, "y": 110, "label": "3 : ObtenerAusencias"},

            {"from": 0, "to": 1, "y": 188, "label": "4 : NuevaAusencia"},
            {"from": 1, "to": 2, "y": 193, "label": "5 : RegistrarAusencia"},
            {"from": 2, "to": 2, "y": 218, "label": "6 : ValidarFechas", "is_self": True},
            {"from": 2, "to": 3, "y": 248, "label": "7 : GuardarAusencia"},

            {"from": 0, "to": 1, "y": 313, "label": "8 : CancelarAusencia"},
            {"from": 1, "to": 2, "y": 318, "label": "9 : AnularRegistro"},
            {"from": 2, "to": 3, "y": 323, "label": "10 : EliminarAusencia"},
        ],
        "height": 430,
        "width": 750,
    },

    # CU-11
    {
        "filename": "CU11_Programar_Cita_Medica.png",
        "alt_filename": "cu11_programar_cita_medica.png",
        "title": "CU11: Programar Cita Medica",
        "actor": "Recepcionista",
        "lifelines": ["Vista_Cita", "Controller_Cita", "Disponibilidad", "Cita"],
        "activations": [
            (1, 98, 150), (2, 103, 145), (3, 108, 130), (3, 130, 155, True),
            (1, 205, 280), (2, 210, 280), (2, 233, 258, True), (4, 263, 285),
            (1, 335, 375), (2, 340, 375), (4, 345, 375),
        ],
        "messages": [
            {"from": 0, "to": 1, "y": 100, "label": "1 : SeleccionarEspecialidad"},
            {"from": 1, "to": 2, "y": 105, "label": "2 : ConsultarDisponibilidad"},
            {"from": 2, "to": 3, "y": 110, "label": "3 : ObtenerSlots"},
            {"from": 3, "to": 3, "y": 135, "label": "4 : ValidarHorarios", "is_self": True},

            {"from": 0, "to": 1, "y": 208, "label": "5 : SeleccionarHorario"},
            {"from": 1, "to": 2, "y": 213, "label": "6 : RegistrarCita"},
            {"from": 2, "to": 2, "y": 238, "label": "7 : GenerarCodigo", "is_self": True},
            {"from": 2, "to": 4, "y": 268, "label": "8 : GuardarCita"},

            {"from": 0, "to": 1, "y": 338, "label": "9 : SolicitarComprobante"},
            {"from": 1, "to": 2, "y": 343, "label": "10 : ObtenerDatosCita"},
            {"from": 2, "to": 4, "y": 348, "label": "11 : MostrarConfirmacion"},
        ],
        "height": 450,
        "width": 780,
    },

    # CU-12
    {
        "filename": "CU12_Reprogramar_Cita_Medica.png",
        "alt_filename": "cu12_reprogramar_cita_medica.png",
        "title": "CU12: Reprogramar Cita Medica",
        "actor": "Recepcionista",
        "lifelines": ["Vista_Cita", "Controller_Cita", "Disponibilidad", "Cita"],
        "activations": [
            (1, 98, 135), (2, 103, 135), (3, 108, 135),
            (1, 185, 255), (2, 190, 255), (2, 213, 238, True), (4, 243, 255),
            (1, 310, 350), (2, 315, 350), (4, 320, 350),
        ],
        "messages": [
            {"from": 0, "to": 1, "y": 100, "label": "1 : SeleccionarCita"},
            {"from": 1, "to": 2, "y": 105, "label": "2 : ConsultarNuevaFecha"},
            {"from": 2, "to": 3, "y": 110, "label": "3 : ObtenerSlotsLibres"},

            {"from": 0, "to": 1, "y": 188, "label": "4 : SeleccionarNuevoHorario"},
            {"from": 1, "to": 2, "y": 193, "label": "5 : ReprogramarCita"},
            {"from": 2, "to": 2, "y": 218, "label": "6 : ValidarDisponibilidad", "is_self": True},
            {"from": 2, "to": 4, "y": 248, "label": "7 : ActualizarHorario"},

            {"from": 0, "to": 1, "y": 313, "label": "8 : ConfirmarReprogramacion"},
            {"from": 1, "to": 2, "y": 318, "label": "9 : NotificarActualizacion"},
            {"from": 2, "to": 4, "y": 323, "label": "10 : RegistrarCambio"},
        ],
        "height": 430,
        "width": 780,
    },

    # CU-13
    {
        "filename": "CU13_Cancelar_Cita_Medica.png",
        "alt_filename": "cu13_cancelar_cita_medica.png",
        "title": "CU13: Cancelar Cita Medica",
        "actor": "Recepcionista",
        "lifelines": ["Vista_Cita", "Controller_Cita", "Cita", "Bitacora"],
        "activations": [
            (1, 98, 135), (2, 103, 135), (3, 108, 135),
            (1, 185, 280), (2, 190, 280), (2, 213, 238, True), (3, 243, 260), (4, 265, 280),
            (1, 335, 370), (2, 340, 370),
        ],
        "messages": [
            {"from": 0, "to": 1, "y": 100, "label": "1 : SeleccionarCita"},
            {"from": 1, "to": 2, "y": 105, "label": "2 : MostrarDetalleCita"},
            {"from": 2, "to": 3, "y": 110, "label": "3 : ObtenerCita"},

            {"from": 0, "to": 1, "y": 188, "label": "4 : IngresarMotivo"},
            {"from": 1, "to": 2, "y": 193, "label": "5 : CancelarCita"},
            {"from": 2, "to": 2, "y": 218, "label": "6 : ValidarMotivo", "is_self": True},
            {"from": 2, "to": 3, "y": 248, "label": "7 : ActualizarEstadoCancelada"},
            {"from": 2, "to": 4, "y": 270, "label": "8 : RegistrarBitacora"},

            {"from": 0, "to": 1, "y": 338, "label": "9 : ConfirmarLiberacion"},
            {"from": 1, "to": 2, "y": 343, "label": "10 : ActualizarAgenda"},
        ],
        "height": 450,
        "width": 780,
    },

    # CU-14
    {
        "filename": "CU14_Registrar_Atencion_Paciente.png",
        "alt_filename": "cu14_registrar_atencion_paciente.png",
        "title": "CU14: Registrar Atencion de Paciente",
        "actor": "Médico",
        "lifelines": ["Vista_Atencion", "Controller_Atencion", "AtencionClinica", "Cita"],
        "activations": [
            (1, 98, 135), (2, 103, 135), (3, 108, 135),
            (1, 185, 255), (2, 190, 255), (2, 213, 238, True), (3, 243, 255),
            (1, 310, 350), (2, 315, 350), (4, 320, 350),
        ],
        "messages": [
            {"from": 0, "to": 1, "y": 100, "label": "1 : IniciarConsulta"},
            {"from": 1, "to": 2, "y": 105, "label": "2 : MostrarExpediente"},
            {"from": 2, "to": 3, "y": 110, "label": "3 : ObtenerHistorial"},

            {"from": 0, "to": 1, "y": 188, "label": "4 : IngresarDiagnostico"},
            {"from": 1, "to": 2, "y": 193, "label": "5 : RegistrarAtencion"},
            {"from": 2, "to": 2, "y": 218, "label": "6 : ValidarDatosClinicos", "is_self": True},
            {"from": 2, "to": 3, "y": 248, "label": "7 : GuardarAtencion"},

            {"from": 0, "to": 1, "y": 313, "label": "8 : FinalizarConsulta"},
            {"from": 1, "to": 2, "y": 318, "label": "9 : CerrarAtencion"},
            {"from": 2, "to": 4, "y": 323, "label": "10 : ActualizarEstadoAtendida"},
        ],
        "height": 430,
        "width": 780,
    },

    # CU-15
    {
        "filename": "CU15_Consultar_Historial_Citas.png",
        "alt_filename": "cu15_consultar_historial_citas.png",
        "title": "CU15: Consultar Historial de Citas",
        "actor": "Recepcionista",
        "lifelines": ["Vista_Historial", "Controller_Cita", "Cita", "Paciente"],
        "activations": [
            (1, 98, 150), (2, 103, 145), (3, 108, 130), (3, 130, 155, True),
            (1, 205, 245), (2, 210, 245), (4, 215, 245),
            (1, 290, 330), (2, 295, 330), (3, 300, 330),
        ],
        "messages": [
            {"from": 0, "to": 1, "y": 100, "label": "1 : FiltrarCitas"},
            {"from": 1, "to": 2, "y": 105, "label": "2 : ConsultarHistorial"},
            {"from": 2, "to": 3, "y": 110, "label": "3 : ObtenerCitas"},
            {"from": 3, "to": 3, "y": 135, "label": "4 : CargarRelaciones", "is_self": True},

            {"from": 0, "to": 1, "y": 208, "label": "5 : SeleccionarPaciente"},
            {"from": 1, "to": 2, "y": 213, "label": "6 : MostrarDatosPaciente"},
            {"from": 2, "to": 4, "y": 218, "label": "7 : ObtenerPaciente"},

            {"from": 0, "to": 1, "y": 293, "label": "8 : VerDetalleCita"},
            {"from": 1, "to": 2, "y": 298, "label": "9 : MostrarDetalle"},
            {"from": 2, "to": 3, "y": 303, "label": "10 : CargarDetalle"},
        ],
        "height": 420,
        "width": 780,
    },

    # CU-16
    {
        "filename": "CU16_Generar_Reportes_Administrativos.png",
        "alt_filename": "cu16_generar_reportes_administrativos.png",
        "title": "CU16: Generar Reportes Administrativos",
        "actor": "Administrador",
        "lifelines": ["Vista_Reporte", "Controller_Reporte", "Reporte"],
        "activations": [
            (1, 98, 150), (2, 103, 145), (3, 108, 130), (3, 130, 155, True),
            (1, 205, 245), (2, 210, 245), (3, 215, 245),
            (1, 290, 330), (2, 295, 330), (3, 300, 330),
        ],
        "messages": [
            {"from": 0, "to": 1, "y": 100, "label": "1 : SeleccionarRango"},
            {"from": 1, "to": 2, "y": 105, "label": "2 : ConsultarMetricas"},
            {"from": 2, "to": 3, "y": 110, "label": "3 : ObtenerDatos"},
            {"from": 3, "to": 3, "y": 135, "label": "4 : CalcularTotales", "is_self": True},

            {"from": 0, "to": 1, "y": 208, "label": "5 : SeleccionarTipoReporte"},
            {"from": 1, "to": 2, "y": 213, "label": "6 : ProcesarRendimiento"},
            {"from": 2, "to": 3, "y": 218, "label": "7 : GenerarEstadisticas"},

            {"from": 0, "to": 1, "y": 293, "label": "8 : VerGraficos"},
            {"from": 1, "to": 2, "y": 298, "label": "9 : CargarKPIs"},
            {"from": 2, "to": 3, "y": 303, "label": "10 : MostrarResultados"},
        ],
        "height": 420,
        "width": 750,
    },

    # CU-17
    {
        "filename": "CU17_Exportar_Datos_XLSX.png",
        "alt_filename": "cu17_exportar_datos_xlsx.png",
        "title": "CU17: Exportar Datos a .xlsx",
        "actor": "Administrador",
        "lifelines": ["Vista_Exportar", "Controller_Reporte", "ExportExcelService", "Bitacora"],
        "activations": [
            (1, 98, 145), (2, 103, 145), (2, 125, 150, True),
            (1, 203, 255), (2, 208, 255), (3, 213, 235), (3, 235, 260, True),
            (1, 310, 350), (2, 315, 350), (4, 320, 350),
        ],
        "messages": [
            {"from": 0, "to": 1, "y": 100, "label": "1 : SeleccionarCriterios"},
            {"from": 1, "to": 2, "y": 105, "label": "2 : ConsultarDatosExportables"},
            {"from": 2, "to": 2, "y": 130, "label": "3 : ValidarRegistros", "is_self": True},

            {"from": 0, "to": 1, "y": 205, "label": "4 : SolicitarDescargaExcel"},
            {"from": 1, "to": 2, "y": 210, "label": "5 : ExportarExcel"},
            {"from": 2, "to": 3, "y": 215, "label": "6 : ConstruirArchivoXLSX"},
            {"from": 3, "to": 3, "y": 240, "label": "7 : FormatearCeldas", "is_self": True},

            {"from": 0, "to": 1, "y": 313, "label": "8 : DescargarArchivo"},
            {"from": 1, "to": 2, "y": 318, "label": "9 : RegistrarDescarga"},
            {"from": 2, "to": 4, "y": 323, "label": "10 : RegistrarBitacora"},
        ],
        "height": 430,
        "width": 780,
    },
]

for d in diagramas:
    path_sec = os.path.join(output_dir_secuencia, d["filename"])
    draw_sequence_diagram(
        filename=path_sec,
        title=d["title"],
        actor_name=d["actor"],
        lifelines=d["lifelines"],
        activations=d["activations"],
        messages=d["messages"],
        width=d.get("width", 780),
        height=d.get("height", 430)
    )
    
    path_diag = os.path.join(output_dir_diagramas, d["alt_filename"])
    draw_sequence_diagram(
        filename=path_diag,
        title=d["title"],
        actor_name=d["actor"],
        lifelines=d["lifelines"],
        activations=d["activations"],
        messages=d["messages"],
        width=d.get("width", 780),
        height=d.get("height", 430)
    )

print(f"\n[FINALIZADO CON EXITO] Se han regenerado los 17 diagramas de secuencia limpios y estandarizados en:\n - {output_dir_secuencia}\n - {output_dir_diagramas}")

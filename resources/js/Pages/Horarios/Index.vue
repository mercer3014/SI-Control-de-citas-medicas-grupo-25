<template>
  <AppLayout>
    <template #header>
      <span>Horarios de Atención Médica</span>
    </template>

    <div class="space-y-4">
      <!-- Barra Superior: Selección de Médico y Acción -->
      <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
          <div>
            <label class="block text-[11px] font-medium text-slate-500 mb-1">Médico:</label>
            <select
              v-model="currentMedicoId"
              @change="onMedicoChange"
              class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-300 bg-white text-slate-800 focus:border-blue-600 outline-none min-w-[240px]"
            >
              <option v-for="m in medicos" :key="m.id" :value="m.id">
                Dr(a). {{ m.persona?.nombre_completo }} (Mat: {{ m.matricula_profesional }})
              </option>
            </select>
          </div>

          <div v-if="selectedMedico?.especialidades?.length">
            <label class="block text-[11px] font-medium text-slate-500 mb-1">Especialidades:</label>
            <div class="flex flex-wrap gap-1.5">
              <span
                v-for="esp in selectedMedico.especialidades"
                :key="esp.id"
                class="px-2 py-0.5 rounded text-[11px] font-medium bg-blue-50 text-blue-700 border border-blue-200/70"
              >
                {{ esp.nombre }}
              </span>
            </div>
          </div>
        </div>

        <button
          type="button"
          @click="openCreateModal"
          :disabled="!selectedMedico"
          class="w-full md:w-auto px-4 py-2 bg-slate-900 hover:bg-slate-800 disabled:opacity-50 text-white rounded-lg text-xs font-semibold shadow-xs transition cursor-pointer flex items-center justify-center gap-1.5"
        >
          <AppIcon name="plus" class="w-3.5 h-3.5" />
          <span>Nuevo Horario</span>
        </button>
      </div>

      <!-- Grilla Semanal Visual de Horarios -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-7 gap-3">
        <div
          v-for="dia in dias"
          :key="dia.id"
          class="bg-white rounded-xl border border-slate-200/80 shadow-xs flex flex-col min-h-[300px]"
        >
          <!-- Encabezado del Día -->
          <div class="px-3 py-2.5 border-b border-slate-100 bg-slate-50/80 rounded-t-xl flex items-center justify-between">
            <span class="text-xs font-bold text-slate-800">{{ dia.nombre }}</span>
            <span class="text-[10px] font-mono font-medium px-1.5 py-0.5 rounded bg-slate-200 text-slate-600">
              {{ horariosPorDia(dia.id).length }}
            </span>
          </div>

          <!-- Lista de Bloques en este Día -->
          <div class="p-2 space-y-2 flex-1 overflow-y-auto">
            <div
              v-for="h in horariosPorDia(dia.id)"
              :key="h.id"
              class="p-2.5 rounded-lg border text-xs transition"
              :class="h.estado ? 'bg-slate-50/60 border-slate-200 hover:border-blue-300' : 'bg-slate-100/50 border-slate-200 text-slate-400 opacity-70'"
            >
              <div class="flex items-center justify-between gap-1 mb-1">
                <span class="font-mono font-bold text-[12px] text-slate-800">
                  {{ formatHour(h.hora_inicio) }} - {{ formatHour(h.hora_fin) }}
                </span>
                <span
                  class="w-2 h-2 rounded-full shrink-0"
                  :class="h.estado ? 'bg-emerald-500' : 'bg-slate-400'"
                  :title="h.estado ? 'Activo' : 'Inactivo'"
                ></span>
              </div>

              <div class="text-[11px] text-slate-500 mb-2 truncate">
                {{ h.especialidad_nombre }} &bull; {{ h.intervalo_minutos }} min/cita
              </div>

              <!-- Acciones del bloque -->
              <div class="flex items-center justify-end gap-1 pt-1.5 border-t border-slate-100">
                <button
                  type="button"
                  @click="confirmToggle(h)"
                  class="p-1 rounded text-slate-400 hover:text-slate-700 hover:bg-white transition cursor-pointer"
                  :title="h.estado ? 'Desactivar bloque' : 'Activar bloque'"
                >
                  <AppIcon :name="h.estado ? 'ban' : 'check'" class="w-3.5 h-3.5" />
                </button>
                <button
                  type="button"
                  @click="openEditModal(h)"
                  class="p-1 rounded text-slate-400 hover:text-blue-600 hover:bg-white transition cursor-pointer"
                  title="Editar horario"
                >
                  <AppIcon name="pencil" class="w-3.5 h-3.5" />
                </button>
                <button
                  type="button"
                  @click="confirmDelete(h)"
                  class="p-1 rounded text-slate-400 hover:text-rose-600 hover:bg-white transition cursor-pointer"
                  title="Eliminar bloque"
                >
                  <AppIcon name="trash" class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>

            <div
              v-if="horariosPorDia(dia.id).length === 0"
              class="h-full flex items-center justify-center py-8 text-center text-slate-300 text-[11px] italic"
            >
              Sin turnos
            </div>
          </div>
        </div>
      </div>

      <!-- Tabla Consolidada de Horarios con Enumeración y Filtro -->
      <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="px-4 py-3 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2.5">
          <div class="flex items-center gap-2">
            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">
              Listado Consolidado de Turnos ({{ filteredHorariosTabla.length }})
            </h4>
          </div>

          <!-- Filtros de la Tabla -->
          <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
            <select
              v-model="filtroDiaTabla"
              class="px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:border-slate-600 outline-none bg-white"
            >
              <option value="">Todos los días</option>
              <option v-for="d in dias" :key="d.id" :value="d.id">{{ d.nombre }}</option>
            </select>

            <select
              v-model="filtroEstadoTabla"
              class="px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:border-slate-600 outline-none bg-white"
            >
              <option value="">Todos los estados</option>
              <option value="1">Activos</option>
              <option value="0">Inactivos</option>
            </select>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-100/60 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
                <th class="py-2.5 px-3 text-center w-12 text-slate-500 font-mono">#</th>
                <th class="py-2.5 px-3">Día</th>
                <th class="py-2.5 px-3">Franja Horaria</th>
                <th class="py-2.5 px-3">Especialidad</th>
                <th class="py-2.5 px-3 text-center">Intervalo</th>
                <th class="py-2.5 px-3 text-center">Estado</th>
                <th class="py-2.5 px-3 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="(h, index) in filteredHorariosTabla"
                :key="h.id"
                class="hover:bg-slate-50 transition"
              >
                <td class="py-2.5 px-3 text-center font-mono text-[11px] text-slate-400 font-semibold">
                  {{ index + 1 }}
                </td>
                <td class="py-2.5 px-3 font-semibold text-slate-800">
                  {{ getDiaNombre(h.dia_id) }}
                </td>
                <td class="py-2.5 px-3 font-mono font-bold text-slate-800">
                  {{ formatHour(h.hora_inicio) }} - {{ formatHour(h.hora_fin) }}
                </td>
                <td class="py-2.5 px-3 text-slate-600">
                  {{ h.especialidad_nombre }}
                </td>
                <td class="py-2.5 px-3 text-center font-mono text-slate-600">
                  {{ h.intervalo_minutos }} min
                </td>
                <td class="py-2.5 px-3 text-center">
                  <span
                    class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-medium"
                    :class="h.estado ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200'"
                  >
                    <span class="w-1.5 h-1.5 rounded-full" :class="h.estado ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                    {{ h.estado ? 'Activo' : 'Inactivo' }}
                  </span>
                </td>
                <td class="py-2.5 px-3 text-right">
                  <div class="inline-flex items-center gap-1">
                    <button
                      type="button"
                      @click="confirmToggle(h)"
                      class="w-7 h-7 flex items-center justify-center rounded border border-slate-200 bg-white transition hover:bg-slate-100 cursor-pointer"
                      :class="h.estado ? 'text-slate-500 hover:text-amber-600' : 'text-slate-500 hover:text-emerald-600'"
                      :title="h.estado ? 'Desactivar bloque' : 'Activar bloque'"
                    >
                      <AppIcon :name="h.estado ? 'ban' : 'check'" class="w-3.5 h-3.5" />
                    </button>
                    <button
                      type="button"
                      @click="openEditModal(h)"
                      class="w-7 h-7 flex items-center justify-center rounded border border-slate-200 bg-white text-slate-500 hover:text-blue-600 hover:bg-slate-100 transition cursor-pointer"
                      title="Editar horario"
                    >
                      <AppIcon name="pencil" class="w-3.5 h-3.5" />
                    </button>
                    <button
                      type="button"
                      @click="confirmDelete(h)"
                      class="w-7 h-7 flex items-center justify-center rounded border border-slate-200 bg-white text-slate-400 hover:text-rose-600 hover:bg-rose-50 hover:border-rose-200 transition cursor-pointer"
                      title="Eliminar bloque"
                    >
                      <AppIcon name="trash" class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="filteredHorariosTabla.length === 0">
                <td colspan="7" class="py-8 text-center text-xs text-slate-400">
                  No se encontraron turnos configurados con los filtros seleccionados.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal Formulario: Agregar / Editar Horario -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        <div class="px-5 py-3.5 bg-slate-100 border-b border-slate-200 flex items-center justify-between">
          <h3 class="text-sm font-semibold text-slate-800">
            {{ isEditing ? 'Editar Franja Horaria' : 'Configurar Horario de Atención' }}
          </h3>
          <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">&times;</button>
        </div>

        <form @submit.prevent="saveHorario" class="p-5 space-y-3.5 text-xs">
          <!-- Especialidad vinculada -->
          <div>
            <label class="block text-[11px] font-medium text-slate-700 mb-1">Especialidad del Médico *</label>
            <select
              v-model="form.medico_especialidad_id"
              required
              class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:border-slate-600 outline-none"
            >
              <option
                v-for="me in selectedMedico?.medico_especialidades"
                :key="me.id"
                :value="me.id"
              >
                {{ me.especialidad?.nombre }} (Bs. {{ me.precio_consulta }})
              </option>
            </select>
          </div>

          <!-- Día de la semana -->
          <div>
            <label class="block text-[11px] font-medium text-slate-700 mb-1">Día de la semana *</label>
            <select
              v-model="form.dia_id"
              required
              class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:border-slate-600 outline-none"
            >
              <option v-for="d in dias" :key="d.id" :value="d.id">{{ d.nombre }}</option>
            </select>
          </div>

          <!-- Horarios -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] font-medium text-slate-700 mb-1">Hora Inicio *</label>
              <input
                v-model="form.hora_inicio"
                type="time"
                required
                class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:border-slate-600 outline-none"
              />
              <p v-if="form.errors.hora_inicio" class="text-[10px] text-rose-600 mt-0.5">{{ form.errors.hora_inicio }}</p>
            </div>
            <div>
              <label class="block text-[11px] font-medium text-slate-700 mb-1">Hora Fin *</label>
              <input
                v-model="form.hora_fin"
                type="time"
                required
                class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:border-slate-600 outline-none"
              />
              <p v-if="form.errors.hora_fin" class="text-[10px] text-rose-600 mt-0.5">{{ form.errors.hora_fin }}</p>
            </div>
          </div>

          <!-- Intervalo en minutos -->
          <div>
            <label class="block text-[11px] font-medium text-slate-700 mb-1">Duración estimada por cita (minutos) *</label>
            <select
              v-model.number="form.intervalo_minutos"
              required
              class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:border-slate-600 outline-none"
            >
              <option :value="15">15 minutos</option>
              <option :value="20">20 minutos</option>
              <option :value="30">30 minutos (Estándar)</option>
              <option :value="45">45 minutos</option>
              <option :value="60">60 minutos (1 hora)</option>
            </select>
          </div>

          <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
            <button
              type="button"
              @click="showModal = false"
              class="px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-lg border border-slate-300 cursor-pointer"
            >
              Cancelar
            </button>
            <button
              type="submit"
              class="px-3.5 py-1.5 text-xs font-semibold text-white bg-slate-900 hover:bg-slate-800 rounded-lg shadow-xs cursor-pointer"
            >
              {{ isEditing ? 'Guardar Cambios' : 'Registrar Horario' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Confirmación de cambio de estado -->
    <DialogModal
      :show="showConfirmToggleModal"
      title="Cambiar estado del horario"
      :message="`¿Deseas ${horarioToToggle?.estado ? 'desactivar' : 'activar'} esta franja horaria?`"
      confirm-text="Sí, cambiar estado"
      cancel-text="Cancelar"
      type="warning"
      @confirm="executeToggle"
      @close="showConfirmToggleModal = false"
    />

    <!-- Confirmación de eliminación -->
    <DialogModal
      :show="showDeleteModal"
      title="Eliminar franja horaria"
      message="¿Estás seguro de que deseas eliminar este horario? La acción no afectará citas pasadas ya registradas."
      confirm-text="Sí, eliminar"
      cancel-text="Cancelar"
      type="danger"
      @confirm="executeDelete"
      @close="showDeleteModal = false"
    />
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppIcon from '@/Components/AppIcon.vue';
import DialogModal from '@/Components/DialogModal.vue';

const props = defineProps({
  medicos: Array,
  dias: Array,
  selectedMedicoId: Number,
});

const currentMedicoId = ref(props.selectedMedicoId || props.medicos[0]?.id || null);

const selectedMedico = computed(() => {
  return props.medicos.find((m) => m.id === currentMedicoId.value) || null;
});

const onMedicoChange = () => {
  router.get('/horarios', { medico_id: currentMedicoId.value }, { preserveState: true, preserveScroll: true });
};

// Mapeo plano de horarios para el médico seleccionado
const todosLosHorarios = computed(() => {
  if (!selectedMedico.value?.medico_especialidades) return [];
  const list = [];
  selectedMedico.value.medico_especialidades.forEach((me) => {
    (me.horarios || []).forEach((h) => {
      list.push({
        ...h,
        especialidad_nombre: me.especialidad?.nombre || 'Especialidad',
        medico_especialidad_id: me.id,
      });
    });
  });
  return list;
});

const horariosPorDia = (diaId) => {
  return todosLosHorarios.value.filter((h) => Number(h.dia_id) === Number(diaId));
};

const filtroDiaTabla = ref('');
const filtroEstadoTabla = ref('');

const getDiaNombre = (diaId) => {
  return props.dias.find((d) => Number(d.id) === Number(diaId))?.nombre || `Día ${diaId}`;
};

const filteredHorariosTabla = computed(() => {
  return todosLosHorarios.value.filter((h) => {
    if (filtroDiaTabla.value && Number(h.dia_id) !== Number(filtroDiaTabla.value)) return false;
    if (filtroEstadoTabla.value !== '') {
      const wantActive = filtroEstadoTabla.value === '1';
      if (h.estado !== wantActive) return false;
    }
    return true;
  });
});

const formatHour = (timeStr) => {
  if (!timeStr) return '';
  return timeStr.substring(0, 5);
};

// Formulario Modal
const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const form = useForm({
  medico_especialidad_id: null,
  dia_id: 1,
  hora_inicio: '08:00',
  hora_fin: '12:00',
  intervalo_minutos: 30,
});

const openCreateModal = () => {
  isEditing.value = false;
  editingId.value = null;
  form.clearErrors();
  form.medico_especialidad_id = selectedMedico.value?.medico_especialidades?.[0]?.id || null;
  form.dia_id = 1;
  form.hora_inicio = '08:00';
  form.hora_fin = '12:00';
  form.intervalo_minutos = 30;
  showModal.value = true;
};

const openEditModal = (h) => {
  isEditing.value = true;
  editingId.value = h.id;
  form.clearErrors();
  form.medico_especialidad_id = h.medico_especialidad_id;
  form.dia_id = h.dia_id;
  form.hora_inicio = formatHour(h.hora_inicio);
  form.hora_fin = formatHour(h.hora_fin);
  form.intervalo_minutos = h.intervalo_minutos;
  showModal.value = true;
};

const saveHorario = () => {
  if (isEditing.value) {
    form.put(`/horarios/${editingId.value}`, {
      onSuccess: () => { showModal.value = false; },
    });
  } else {
    form.post('/horarios', {
      onSuccess: () => { showModal.value = false; },
    });
  }
};

// Acciones con Diálogos Modales
const showConfirmToggleModal = ref(false);
const horarioToToggle = ref(null);

const confirmToggle = (h) => {
  horarioToToggle.value = h;
  showConfirmToggleModal.value = true;
};

const executeToggle = () => {
  if (!horarioToToggle.value) return;
  router.patch(`/horarios/${horarioToToggle.value.id}/toggle`, {}, {
    onFinish: () => {
      showConfirmToggleModal.value = false;
      horarioToToggle.value = null;
    },
  });
};

const showDeleteModal = ref(false);
const horarioToDelete = ref(null);

const confirmDelete = (h) => {
  horarioToDelete.value = h;
  showDeleteModal.value = true;
};

const executeDelete = () => {
  if (!horarioToDelete.value) return;
  router.delete(`/horarios/${horarioToDelete.value.id}`, {
    onFinish: () => {
      showDeleteModal.value = false;
      horarioToDelete.value = null;
    },
  });
};
</script>

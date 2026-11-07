<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-150">
      <!-- Encabezado Modal -->
      <div class="px-5 py-3.5 bg-slate-100 border-b border-slate-200 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <AppIcon name="calendar" class="w-4 h-4 text-slate-700" />
          <h3 class="text-sm font-semibold text-slate-800">Programar Nueva Cita Médica</h3>
        </div>
        <button @click="$emit('close')" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">&times;</button>
      </div>

      <form @submit.prevent="submit" class="p-5 space-y-3.5 text-xs max-h-[85vh] overflow-y-auto">
        <!-- Paciente -->
        <div>
          <label class="block text-[11px] font-medium text-slate-700 mb-1">Paciente *</label>
          <select
            v-model="form.paciente_id"
            required
            class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:border-slate-600 outline-none"
          >
            <option value="" disabled>Seleccionar paciente...</option>
            <option v-for="p in pacientes" :key="p.id" :value="p.id">
              {{ p.persona?.nombre_completo }} (CI: {{ p.persona?.ci }})
            </option>
          </select>
          <p v-if="form.errors.paciente_id" class="text-[10px] text-rose-600 mt-0.5">{{ form.errors.paciente_id }}</p>
        </div>

        <!-- Médico y Especialidad -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-[11px] font-medium text-slate-700 mb-1">Médico *</label>
            <select
              v-model="selectedMedicoId"
              @change="onMedicoChange"
              required
              class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:border-slate-600 outline-none"
            >
              <option value="" disabled>Seleccionar médico...</option>
              <option v-for="m in medicos" :key="m.id" :value="m.id">
                Dr(a). {{ m.persona?.nombre_completo }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-[11px] font-medium text-slate-700 mb-1">Especialidad *</label>
            <select
              v-model="form.medico_especialidad_id"
              @change="fetchSlots"
              required
              :disabled="!especialidadesDisponibles.length"
              class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:border-slate-600 outline-none disabled:bg-slate-100"
            >
              <option value="" disabled>Seleccionar especialidad...</option>
              <option v-for="me in especialidadesDisponibles" :key="me.id" :value="me.id">
                {{ me.especialidad?.nombre }} (Bs. {{ me.precio_consulta }})
              </option>
            </select>
          </div>
        </div>

        <!-- Fecha -->
        <div>
          <label class="block text-[11px] font-medium text-slate-700 mb-1">Fecha de la Cita *</label>
          <input
            v-model="form.fecha"
            type="date"
            :min="today"
            @change="fetchSlots"
            required
            class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:border-slate-600 outline-none"
          />
          <p v-if="form.errors.fecha" class="text-[10px] text-rose-600 mt-0.5">{{ form.errors.fecha }}</p>
        </div>

        <!-- Franjas Horarias Disponibles (Slots) -->
        <div>
          <label class="block text-[11px] font-medium text-slate-700 mb-1.5">
            Horarios Disponibles para esta Fecha *
          </label>

          <div v-if="loadingSlots" class="py-4 text-center text-slate-400 text-xs flex items-center justify-center gap-2">
            <AppIcon name="clock" class="w-4 h-4 animate-spin text-blue-600" />
            <span>Consultando agenda disponible...</span>
          </div>

          <div v-else-if="!form.medico_especialidad_id" class="p-3 bg-slate-50 border border-slate-200 rounded-lg text-slate-400 text-center text-[11px]">
            Selecciona primero el médico y su especialidad para calcular turnos.
          </div>

          <div v-else-if="slots.length === 0" class="p-3 bg-rose-50 border border-rose-200 rounded-lg text-rose-700 text-center text-[11px]">
            No hay turnos disponibles para esta fecha (el médico no atiende este día o se encuentra en ausencia programada).
          </div>

          <div v-else class="grid grid-cols-3 sm:grid-cols-4 gap-1.5 max-h-36 overflow-y-auto p-1 bg-slate-50 border border-slate-200 rounded-lg">
            <button
              v-for="s in slots"
              :key="s.inicio"
              type="button"
              @click="selectSlot(s)"
              class="py-1.5 px-2 rounded text-center text-xs font-mono font-medium border transition cursor-pointer"
              :class="isSelectedSlot(s)
                ? 'bg-blue-600 text-white border-blue-600 shadow-xs'
                : 'bg-white text-slate-700 border-slate-300 hover:border-blue-400 hover:bg-blue-50/50'"
            >
              {{ s.inicio }}
            </button>
          </div>
          <p v-if="form.errors.hora_inicio" class="text-[10px] text-rose-600 mt-1">{{ form.errors.hora_inicio }}</p>
        </div>

        <!-- Motivo de Consulta -->
        <div>
          <label class="block text-[11px] font-medium text-slate-700 mb-1">Motivo de la Consulta *</label>
          <textarea
            v-model="form.motivo_consulta"
            required
            rows="2"
            placeholder="Describe brevemente la molestia o tipo de consulta..."
            class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:border-slate-600 outline-none resize-none"
          ></textarea>
          <p v-if="form.errors.motivo_consulta" class="text-[10px] text-rose-600 mt-0.5">{{ form.errors.motivo_consulta }}</p>
        </div>

        <!-- Botones de Acción -->
        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
          <button
            type="button"
            @click="$emit('close')"
            class="px-3.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-lg border border-slate-300 cursor-pointer"
          >
            Cancelar
          </button>
          <button
            type="submit"
            :disabled="!form.hora_inicio"
            class="px-4 py-1.5 text-xs font-semibold text-white bg-slate-900 hover:bg-slate-800 disabled:opacity-50 rounded-lg shadow-xs cursor-pointer flex items-center gap-1.5"
          >
            <AppIcon name="calendar" class="w-3.5 h-3.5" />
            <span>Confirmar y Reservar Cita</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppIcon.vue';

const props = defineProps({
  show: Boolean,
  medicos: Array,
  pacientes: Array,
});

const emit = defineEmits(['close']);

const today = new Date().toISOString().substring(0, 10);

const selectedMedicoId = ref('');
const loadingSlots = ref(false);
const slots = ref([]);

const form = useForm({
  paciente_id: '',
  medico_especialidad_id: '',
  fecha: today,
  hora_inicio: '',
  hora_fin: '',
  motivo_consulta: '',
});

const especialidadesDisponibles = computed(() => {
  const m = props.medicos.find((item) => item.id === Number(selectedMedicoId.value));
  return m?.medico_especialidades || [];
});

const onMedicoChange = () => {
  form.medico_especialidad_id = especialidadesDisponibles.value[0]?.id || '';
  fetchSlots();
};

const fetchSlots = async () => {
  if (!form.medico_especialidad_id || !form.fecha) {
    slots.value = [];
    return;
  }

  loadingSlots.value = true;
  form.hora_inicio = '';
  form.hora_fin = '';

  try {
    const res = await fetch(`/citas/slots-disponibles?medico_especialidad_id=${form.medico_especialidad_id}&fecha=${form.fecha}`);
    if (res.ok) {
      slots.value = await res.json();
    } else {
      slots.value = [];
    }
  } catch (err) {
    slots.value = [];
  } finally {
    loadingSlots.value = false;
  }
};

const selectSlot = (s) => {
  form.hora_inicio = s.inicio;
  form.hora_fin = s.fin;
};

const isSelectedSlot = (s) => {
  return form.hora_inicio === s.inicio;
};

const submit = () => {
  form.post('/citas', {
    onSuccess: () => {
      form.reset();
      selectedMedicoId.value = '';
      slots.value = [];
      emit('close');
    },
  });
};
</script>

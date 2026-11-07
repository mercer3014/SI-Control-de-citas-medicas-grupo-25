<template>
  <div v-if="show && cita" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95 duration-150">
      <div class="px-5 py-3.5 bg-slate-100 border-b border-slate-200 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-slate-800">
          Reprogramar Cita: {{ cita.codigo_reserva }}
        </h3>
        <button @click="$emit('close')" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">&times;</button>
      </div>

      <form @submit.prevent="requestReprogramar" class="p-5 space-y-3.5 text-xs">
        <!-- Resumen actual -->
        <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg space-y-1">
          <p><span class="text-slate-500 font-medium">Paciente:</span> <strong class="text-slate-800">{{ cita.paciente?.persona?.nombre_completo }}</strong></p>
          <p><span class="text-slate-500 font-medium">Médico:</span> <span class="text-slate-800">Dr(a). {{ cita.medico_especialidad?.medico?.persona?.nombre_completo }}</span></p>
          <p><span class="text-slate-500 font-medium">Horario actual:</span> <span class="font-mono text-slate-700">{{ cita.fecha?.substring(0, 10) }} {{ cita.hora_inicio?.substring(0, 5) }}</span></p>
        </div>

        <!-- Nueva Fecha -->
        <div>
          <label class="block text-[11px] font-medium text-slate-700 mb-1">Nueva Fecha *</label>
          <input
            v-model="form.fecha"
            type="date"
            :min="today"
            @change="fetchSlots"
            required
            class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:border-slate-600 outline-none"
          />
        </div>

        <!-- Nuevos Slots -->
        <div>
          <label class="block text-[11px] font-medium text-slate-700 mb-1.5">
            Turnos Disponibles para esta Nueva Fecha *
          </label>

          <div v-if="loadingSlots" class="py-3 text-center text-slate-400 text-xs flex items-center justify-center gap-1.5">
            <AppIcon name="clock" class="w-3.5 h-3.5 animate-spin text-blue-600" />
            <span>Consultando agenda...</span>
          </div>

          <div v-else-if="slots.length === 0" class="p-3 bg-rose-50 border border-rose-200 rounded-lg text-rose-700 text-center text-[11px]">
            No hay turnos disponibles para esta fecha.
          </div>

          <div v-else class="grid grid-cols-3 gap-1.5 max-h-32 overflow-y-auto p-1 bg-slate-50 border border-slate-200 rounded-lg">
            <button
              v-for="s in slots"
              :key="s.inicio"
              type="button"
              @click="selectSlot(s)"
              class="py-1.5 px-2 rounded text-center text-xs font-mono font-medium border transition cursor-pointer"
              :class="form.hora_inicio === s.inicio
                ? 'bg-blue-600 text-white border-blue-600 shadow-xs'
                : 'bg-white text-slate-700 border-slate-300 hover:border-blue-400'"
            >
              {{ s.inicio }}
            </button>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
          <button
            type="button"
            @click="$emit('close')"
            class="px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-lg border border-slate-300 cursor-pointer"
          >
            Cancelar
          </button>
          <button
            type="submit"
            :disabled="!form.hora_inicio"
            class="px-3.5 py-1.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 rounded-lg shadow-xs cursor-pointer"
          >
            Reprogramar Cita
          </button>
        </div>
      </form>
    </div>

    <!-- Confirmación Modal -->
    <DialogModal
      :show="showConfirm"
      title="Confirmar reprogramación de cita"
      :message="`¿Deseas cambiar la cita ${cita.codigo_reserva} para el día ${form.fecha} a las ${form.hora_inicio}?`"
      confirm-text="Sí, reprogramar"
      cancel-text="Cancelar"
      type="warning"
      @confirm="executeReprogramar"
      @close="showConfirm = false"
    />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppIcon.vue';
import DialogModal from '@/Components/DialogModal.vue';

const props = defineProps({
  show: Boolean,
  cita: Object,
});

const emit = defineEmits(['close']);

const today = new Date().toISOString().substring(0, 10);
const loadingSlots = ref(false);
const slots = ref([]);
const showConfirm = ref(false);

const form = useForm({
  fecha: today,
  hora_inicio: '',
  hora_fin: '',
});

watch(() => props.show, (val) => {
  if (val && props.cita) {
    form.fecha = props.cita.fecha ? props.cita.fecha.substring(0, 10) : today;
    form.hora_inicio = '';
    form.hora_fin = '';
    fetchSlots();
  }
});

const fetchSlots = async () => {
  if (!props.cita?.medico_especialidad_id || !form.fecha) return;
  loadingSlots.value = true;
  form.hora_inicio = '';
  form.hora_fin = '';

  try {
    const res = await fetch(`/citas/slots-disponibles?medico_especialidad_id=${props.cita.medico_especialidad_id}&fecha=${form.fecha}&excluir_cita_id=${props.cita.id}`);
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

const requestReprogramar = () => {
  showConfirm.value = true;
};

const executeReprogramar = () => {
  showConfirm.value = false;
  form.post(`/citas/${props.cita.id}/reprogramar`, {
    onSuccess: () => {
      emit('close');
    },
  });
};
</script>

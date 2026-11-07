<template>
  <div v-if="show && cita" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95 duration-150">
      <div class="px-5 py-3.5 bg-rose-50 border-b border-rose-200 flex items-center justify-between text-rose-800">
        <div class="flex items-center gap-2">
          <AppIcon name="alert" class="w-4 h-4 text-rose-600" />
          <h3 class="text-sm font-semibold">Cancelar Cita Médica</h3>
        </div>
        <button @click="$emit('close')" class="text-rose-400 hover:text-rose-600 text-lg cursor-pointer">&times;</button>
      </div>

      <form @submit.prevent="requestCancel" class="p-5 space-y-3.5 text-xs">
        <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg space-y-1">
          <p><span class="text-slate-500 font-medium">Código:</span> <span class="font-mono font-bold text-slate-800">{{ cita.codigo_reserva }}</span></p>
          <p><span class="text-slate-500 font-medium">Paciente:</span> <strong class="text-slate-800">{{ cita.paciente?.persona?.nombre_completo }}</strong></p>
          <p><span class="text-slate-500 font-medium">Horario:</span> <span class="font-mono text-slate-700">{{ cita.fecha?.substring(0, 10) }} {{ cita.hora_inicio?.substring(0, 5) }}</span></p>
        </div>

        <div>
          <label class="block text-[11px] font-medium text-slate-700 mb-1">Motivo de la Cancelación *</label>
          <textarea
            v-model="form.motivo_cancelacion"
            required
            rows="3"
            placeholder="Especifica detalladamente la razón por la que se anula la cita..."
            class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:border-rose-500 outline-none resize-none"
          ></textarea>
          <p v-if="form.errors.motivo_cancelacion" class="text-[10px] text-rose-600 mt-0.5">{{ form.errors.motivo_cancelacion }}</p>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
          <button
            type="button"
            @click="$emit('close')"
            class="px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-lg border border-slate-300 cursor-pointer"
          >
            Volver
          </button>
          <button
            type="submit"
            :disabled="!form.motivo_cancelacion || form.motivo_cancelacion.length < 5"
            class="px-3.5 py-1.5 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 disabled:opacity-50 rounded-lg shadow-xs cursor-pointer"
          >
            Confirmar Cancelación
          </button>
        </div>
      </form>
    </div>

    <!-- Confirmación Modal destructiva -->
    <DialogModal
      :show="showConfirm"
      title="¿Estás seguro de anular esta cita?"
      :message="`La cita ${cita.codigo_reserva} quedará cancelada permanentemente y el horario se liberará para otros pacientes.`"
      confirm-text="Sí, cancelar cita"
      cancel-text="Regresar"
      type="danger"
      @confirm="executeCancel"
      @close="showConfirm = false"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppIcon.vue';
import DialogModal from '@/Components/DialogModal.vue';

const props = defineProps({
  show: Boolean,
  cita: Object,
});

const emit = defineEmits(['close']);

const showConfirm = ref(false);

const form = useForm({
  motivo_cancelacion: '',
});

const requestCancel = () => {
  showConfirm.value = true;
};

const executeCancel = () => {
  showConfirm.value = false;
  form.post(`/citas/${props.cita.id}/cancelar`, {
    onSuccess: () => {
      form.reset();
      emit('close');
    },
  });
};
</script>

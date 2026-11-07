<template>
  <div v-if="show && cita" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-150">
      <div class="px-5 py-3.5 bg-slate-100 border-b border-slate-200 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <AppIcon name="user-check" class="w-4 h-4 text-emerald-600" />
          <h3 class="text-sm font-semibold text-slate-800">Registrar Atención Clínica</h3>
        </div>
        <button @click="$emit('close')" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">&times;</button>
      </div>

      <form @submit.prevent="requestSave" class="p-5 space-y-3.5 text-xs max-h-[85vh] overflow-y-auto">
        <!-- Ficha Rápida del Paciente -->
        <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg space-y-1">
          <div class="flex items-center justify-between">
            <span class="font-bold text-slate-800 text-sm">{{ cita.paciente?.persona?.nombre_completo }}</span>
            <span class="font-mono text-[11px] text-slate-500">CI: {{ cita.paciente?.persona?.ci }}</span>
          </div>
          <p class="text-slate-600">
            <span class="text-slate-500">Motivo inicial:</span> {{ cita.motivo_consulta }}
          </p>
          <div v-if="cita.paciente?.antecedentes?.length" class="mt-1 pt-1 border-t border-slate-200 text-rose-700 font-medium text-[11px]">
            &bull; Antecedentes: {{ cita.paciente.antecedentes[0].descripcion }}
          </div>
        </div>

        <!-- Diagnóstico -->
        <div>
          <label class="block text-[11px] font-medium text-slate-700 mb-1">Diagnóstico Clínico *</label>
          <textarea
            v-model="form.diagnostico"
            required
            rows="3"
            placeholder="Ingresa el diagnóstico médico y hallazgos clínicos..."
            class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:border-slate-600 outline-none resize-none"
          ></textarea>
          <p v-if="form.errors.diagnostico" class="text-[10px] text-rose-600 mt-0.5">{{ form.errors.diagnostico }}</p>
        </div>

        <!-- Tratamiento / Prescripción -->
        <div>
          <label class="block text-[11px] font-medium text-slate-700 mb-1">Tratamiento y Receta Médica</label>
          <textarea
            v-model="form.tratamiento"
            rows="3"
            placeholder="Medicamentos, dosis, frecuencia y duración del tratamiento..."
            class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:border-slate-600 outline-none resize-none font-mono text-[11px]"
          ></textarea>
        </div>

        <!-- Notas complementarias -->
        <div>
          <label class="block text-[11px] font-medium text-slate-700 mb-1">Indicaciones y Recomendaciones Generales</label>
          <textarea
            v-model="form.notas_adicionales"
            rows="2"
            placeholder="Reposo, dieta, signos de alarma o fecha sugerida de recontrol..."
            class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:border-slate-600 outline-none resize-none"
          ></textarea>
        </div>

        <!-- Acciones -->
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
            :disabled="!form.diagnostico || form.diagnostico.length < 5"
            class="px-4 py-1.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 rounded-lg shadow-xs cursor-pointer flex items-center gap-1.5"
          >
            <AppIcon name="check" class="w-3.5 h-3.5" />
            <span>Finalizar y Guardar Consulta</span>
          </button>
        </div>
      </form>
    </div>

    <!-- Confirmación Modal -->
    <DialogModal
      :show="showConfirm"
      title="Confirmar cierre de atención médica"
      message="Al guardar el diagnóstico y receta, la cita pasará a estado Atendida y se registrará formalmente en el historial del paciente."
      confirm-text="Sí, registrar atención"
      cancel-text="Revisar"
      type="info"
      @confirm="executeSave"
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

const showConfirm = ref(false);

const form = useForm({
  cita_id: '',
  diagnostico: '',
  tratamiento: '',
  notas_adicionales: '',
});

watch(() => props.show, (val) => {
  if (val && props.cita) {
    form.clearErrors();
    form.cita_id = props.cita.id;
    form.diagnostico = '';
    form.tratamiento = '';
    form.notas_adicionales = '';
  }
});

const requestSave = () => {
  showConfirm.value = true;
};

const executeSave = () => {
  showConfirm.value = false;
  form.post('/atenciones', {
    onSuccess: () => {
      form.reset();
      emit('close');
    },
  });
};
</script>

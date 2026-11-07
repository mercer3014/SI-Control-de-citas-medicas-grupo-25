<template>
  <div v-if="show && atencion" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-150">
      <div class="px-5 py-3.5 bg-slate-100 border-b border-slate-200 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <AppIcon name="clipboard" class="w-4 h-4 text-blue-600" />
          <h3 class="text-sm font-semibold text-slate-800">Informe de Consulta Clínica</h3>
        </div>
        <button @click="$emit('close')" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">&times;</button>
      </div>

      <div class="p-5 space-y-4 text-xs max-h-[85vh] overflow-y-auto">
        <!-- Diagnóstico -->
        <div>
          <span class="font-bold text-slate-800 uppercase tracking-wider text-[11px] block mb-1">Diagnóstico Médico</span>
          <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg text-slate-800 font-medium leading-relaxed">
            {{ atencion.diagnostico }}
          </div>
        </div>

        <!-- Tratamiento y Receta -->
        <div v-if="atencion.tratamiento">
          <span class="font-bold text-slate-800 uppercase tracking-wider text-[11px] block mb-1">Tratamiento y Medicación</span>
          <div class="p-3 bg-blue-50/50 border border-blue-200 rounded-lg text-slate-800 font-mono text-[11px] whitespace-pre-wrap leading-relaxed">
            {{ atencion.tratamiento }}
          </div>
        </div>

        <!-- Notas Adicionales -->
        <div v-if="atencion.notas_adicionales">
          <span class="font-bold text-slate-800 uppercase tracking-wider text-[11px] block mb-1">Indicaciones y Recomendaciones</span>
          <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg text-slate-700 leading-relaxed">
            {{ atencion.notas_adicionales }}
          </div>
        </div>

        <!-- Fecha de Atención -->
        <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
          <span>Fecha y hora de registro:</span>
          <span class="font-mono font-medium">{{ formatDate(atencion.fecha_atencion) }}</span>
        </div>

        <div class="flex justify-end pt-2">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-1.5 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition cursor-pointer"
          >
            Cerrar Informe
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import AppIcon from '@/Components/AppIcon.vue';

defineProps({
  show: Boolean,
  atencion: Object,
});

defineEmits(['close']);

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleString('es-BO');
};
</script>

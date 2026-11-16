<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-lg overflow-hidden">
      <div class="px-5 py-3.5 border-b border-slate-200 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-slate-900">
          {{ esComprobante ? 'Comprobante de Pago' : 'Pagar Consulta en Línea' }}
        </h3>
        <button type="button" @click="$emit('close')" class="text-slate-400 hover:text-slate-700 text-lg cursor-pointer">&times;</button>
      </div>

      <div v-if="cargando" class="p-8 text-center text-xs text-slate-500">Calculando el arancel de la consulta...</div>

      <div v-else-if="esComprobante" class="p-5 space-y-3 text-xs" id="comprobante-pago">
        <div class="flex items-center gap-2 text-emerald-700">
          <AppIcon name="check-circle" class="w-5 h-5" />
          <span class="font-semibold">Pago aprobado</span>
        </div>
        <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg space-y-1.5">
          <p><span class="text-slate-500">Referencia:</span> <span class="font-mono font-bold text-slate-800">{{ comprobante.referencia_transaccion }}</span></p>
          <p><span class="text-slate-500">Cita:</span> <span class="font-mono">{{ comprobante.cita?.codigo_reserva }}</span></p>
          <p><span class="text-slate-500">Médico:</span> Dr(a). {{ comprobante.cita?.medico_especialidad?.medico?.persona?.nombre_completo }}</p>
          <p><span class="text-slate-500">Especialidad:</span> {{ comprobante.cita?.medico_especialidad?.especialidad?.nombre }}</p>
          <p><span class="text-slate-500">Monto:</span> <strong>Bs. {{ Number(comprobante.monto).toFixed(2) }} ({{ comprobante.moneda }})</strong></p>
          <p><span class="text-slate-500">Método:</span> {{ comprobante.metodo_pago?.nombre }}</p>
          <p>
            <span class="text-slate-500">Estado de la cita:</span>
            <span class="ml-1 px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 font-semibold">
              {{ comprobante.cita?.estado?.nombre || 'Confirmada' }}
            </span>
          </p>
        </div>
        <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
          <button type="button" @click="$emit('close')" class="px-3 py-1.5 text-xs font-medium text-slate-600 border border-slate-300 rounded-lg cursor-pointer">Cerrar</button>
          <button type="button" @click="imprimir" class="px-3.5 py-1.5 text-xs font-semibold text-white bg-slate-900 rounded-lg cursor-pointer">Descargar comprobante</button>
        </div>
      </div>

      <div v-else class="p-5 space-y-3.5 text-xs">
        <p v-if="mensaje" class="px-3 py-2 rounded-lg bg-rose-50 border border-rose-200 text-rose-700">{{ mensaje }}</p>

        <div v-if="resumen" class="p-3 bg-slate-50 border border-slate-200 rounded-lg space-y-1">
          <p><span class="text-slate-500">Código:</span> <span class="font-mono font-bold">{{ resumen.codigo_reserva }}</span>
            <span class="ml-1 px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200">{{ resumen.estado }}</span>
          </p>
          <p><span class="text-slate-500">Paciente:</span> {{ resumen.paciente }}</p>
          <p><span class="text-slate-500">Médico:</span> Dr(a). {{ resumen.medico }} · {{ resumen.especialidad }}</p>
          <p><span class="text-slate-500">Fecha:</span> <span class="font-mono">{{ resumen.fecha }} {{ resumen.hora_inicio }}</span></p>
          <p class="pt-1 text-base font-bold text-slate-900">Bs. {{ Number(monto).toFixed(2) }}</p>
          <p class="text-[10px] text-slate-400">El cobro se procesa en la pasarela por HTTPS. Esta pantalla no solicita ni guarda datos de tarjeta.</p>
        </div>

        <form v-if="resumen" @submit.prevent="irAPagar" class="space-y-3">
          <div>
            <label class="block text-[11px] font-medium text-slate-700 mb-1">Método de pago</label>
            <select v-model="form.metodo_pago_id" required class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:border-slate-600 outline-none bg-white">
              <option value="" disabled>Seleccione tarjeta o QR</option>
              <option v-for="m in metodosDisponibles" :key="m.id" :value="m.id">{{ m.nombre }}</option>
            </select>
            <p v-if="form.errors.metodo_pago_id" class="text-[10px] text-rose-600 mt-0.5">{{ form.errors.metodo_pago_id }}</p>
          </div>
          <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
            <button type="button" @click="$emit('close')" class="px-3 py-1.5 text-xs font-medium text-slate-600 border border-slate-300 rounded-lg cursor-pointer">Cancelar</button>
            <button type="submit" :disabled="form.processing || !form.metodo_pago_id" class="px-3.5 py-1.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 rounded-lg cursor-pointer">
              Ir a pagar
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AppIcon from '@/Components/AppIcon.vue';

const props = defineProps({
  show: Boolean,
  cita: { type: Object, default: null },
  comprobante: { type: Object, default: null },
  mensajeInicial: { type: String, default: '' },
});

const emit = defineEmits(['close']);

const cargando = ref(false);
const mensaje = ref('');
const resumen = ref(null);
const monto = ref(null);
const metodosDisponibles = ref([]);

const form = useForm({
  cita_id: null,
  metodo_pago_id: '',
});

const esComprobante = computed(() => props.comprobante && props.comprobante.estado === 'Aprobado');

const cargar = async () => {
  mensaje.value = props.mensajeInicial || '';
  resumen.value = null;
  if (!props.show || esComprobante.value || !props.cita) {
    return;
  }
  cargando.value = true;
  try {
    const { data } = await axios.get(`/mis-citas/${props.cita.id}/pagar`);
    resumen.value = data.cita;
    monto.value = data.monto;
    metodosDisponibles.value = data.metodos || [];
    form.cita_id = props.cita.id;
    form.metodo_pago_id = '';
  } catch (error) {
    mensaje.value = error.response?.data?.mensaje || 'La cita no admite pago en línea';
  } finally {
    cargando.value = false;
  }
};

watch(() => [props.show, props.cita?.id, props.comprobante?.id], cargar, { immediate: true });

const irAPagar = () => {
  form.cita_id = props.cita.id;
  form.post('/pagos', {
    onSuccess: () => emit('close'),
  });
};

const imprimir = () => window.print();
</script>

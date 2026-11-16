<template>
  <AppLayout>
    <template #header>
      <span>Mis Citas Médicas</span>
    </template>

    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50/80 text-slate-500 font-semibold text-[11px] border-b border-slate-200/80">
              <th class="py-2.5 px-3">Código</th>
              <th class="py-2.5 px-3">Médico / Especialidad</th>
              <th class="py-2.5 px-3">Fecha</th>
              <th class="py-2.5 px-3 text-center">Estado</th>
              <th class="py-2.5 px-3 text-right">Acción</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="c in citas.data" :key="c.id" class="hover:bg-slate-50/50">
              <td class="py-2.5 px-3 font-mono font-bold text-slate-900">{{ c.codigo_reserva }}</td>
              <td class="py-2.5 px-3">
                <span class="font-medium text-slate-800 block">Dr(a). {{ c.medico_especialidad?.medico?.persona?.nombre_completo }}</span>
                <span class="inline-block mt-0.5 px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-50 text-blue-700 border border-blue-200/60">
                  {{ c.medico_especialidad?.especialidad?.nombre }}
                </span>
              </td>
              <td class="py-2.5 px-3 font-mono text-slate-700">
                {{ formatDate(c.fecha) }} {{ formatHour(c.hora_inicio) }}
              </td>
              <td class="py-2.5 px-3 text-center">
                <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold border" :class="badgeClass(c.estado_id)">
                  {{ c.estado?.nombre }}
                </span>
              </td>
              <td class="py-2.5 px-3 text-right">
                <button
                  v-if="puedePagar(c)"
                  type="button"
                  @click="abrirPago(c)"
                  class="px-2.5 py-1 text-[11px] font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg cursor-pointer"
                >
                  Pagar consulta
                </button>
                <span v-else-if="pagoAprobado(c)" class="text-[11px] font-medium text-emerald-700">Pagada</span>
                <span v-else class="text-[10px] text-slate-400">Sin pago en línea</span>
              </td>
            </tr>
            <tr v-if="citas.data.length === 0">
              <td colspan="5" class="py-8 text-center text-xs text-slate-400">No tiene citas registradas.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <ModalPagarConsulta
      :show="showModal"
      :cita="citaSeleccionada"
      :comprobante="comprobanteVisible"
      :mensaje-inicial="mensajeInicial"
      @close="cerrar"
    />
  </AppLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ModalPagarConsulta from './Partials/ModalPagarConsulta.vue';

const props = defineProps({
  citas: Object,
  metodos: Array,
  comprobante: { type: Object, default: null },
});

const showModal = ref(false);
const citaSeleccionada = ref(null);
const comprobanteVisible = ref(null);
const mensajeInicial = ref('');

const puedePagar = (cita) => cita.estado_id === 1 && !(cita.pagos || []).some((p) => p.estado === 'Aprobado');
const pagoAprobado = (cita) => (cita.pagos || []).some((p) => p.estado === 'Aprobado');

const abrirPago = (cita, mensaje = '') => {
  citaSeleccionada.value = cita;
  comprobanteVisible.value = null;
  mensajeInicial.value = mensaje;
  showModal.value = true;
};

const cerrar = () => {
  showModal.value = false;
  comprobanteVisible.value = null;
};

const formatDate = (value) => (value ? String(value).substring(0, 10) : '');
const formatHour = (value) => (value ? String(value).substring(0, 5) : '');

const badgeClass = (estadoId) => ({
  1: 'bg-amber-50 text-amber-700 border-amber-200',
  2: 'bg-emerald-50 text-emerald-700 border-emerald-200',
  3: 'bg-blue-50 text-blue-700 border-blue-200',
  4: 'bg-slate-100 text-slate-500 border-slate-200',
  5: 'bg-rose-50 text-rose-700 border-rose-200',
}[estadoId] || 'bg-slate-50 text-slate-600 border-slate-200');

onMounted(() => {
  if (!props.comprobante) return;
  if (props.comprobante.estado === 'Aprobado') {
    comprobanteVisible.value = props.comprobante;
    citaSeleccionada.value = props.comprobante.cita;
    showModal.value = true;
    return;
  }
  if (props.comprobante.estado === 'Rechazado' && props.comprobante.cita) {
    abrirPago(props.comprobante.cita, 'Pago rechazado, intente nuevamente');
  }
});
</script>

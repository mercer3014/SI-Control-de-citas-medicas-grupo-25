<template>
  <AppLayout>
    <template #header>
      <div class="flex items-center justify-between gap-3 w-full">
        <span>Gestión de Pagos</span>
        <span class="text-[11px] font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg px-2 py-1">
          Recaudado (mes): Bs. {{ Number(recaudadoPeriodo || 0).toFixed(2) }}
        </span>
      </div>
    </template>

    <div class="space-y-4">
      <form @submit.prevent="filtrar" class="bg-white p-3 rounded-xl border border-slate-200/80 shadow-xs flex flex-wrap gap-2 text-xs">
        <input v-model="buscar" type="search" placeholder="Buscar por referencia, cita o paciente" class="px-2.5 py-1.5 rounded-lg border border-slate-300 outline-none min-w-[220px]" />
        <select v-model="estado" class="px-2.5 py-1.5 rounded-lg border border-slate-300 bg-white outline-none">
          <option value="">Todos los estados</option>
          <option v-for="e in estados" :key="e" :value="e">{{ e }}</option>
        </select>
        <select v-model="metodoId" class="px-2.5 py-1.5 rounded-lg border border-slate-300 bg-white outline-none">
          <option value="">Todos los métodos</option>
          <option v-for="m in metodos" :key="m.id" :value="String(m.id)">{{ m.nombre }}</option>
        </select>
        <button type="submit" class="px-3 py-1.5 bg-slate-900 text-white font-semibold rounded-lg cursor-pointer">Buscar</button>
      </form>

      <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-50/80 text-slate-500 font-semibold text-[11px] border-b border-slate-200/80">
                <th class="py-2.5 px-3">Referencia</th>
                <th class="py-2.5 px-3">Cita / Paciente</th>
                <th class="py-2.5 px-3">Especialidad</th>
                <th class="py-2.5 px-3 text-right">Monto</th>
                <th class="py-2.5 px-3">Método</th>
                <th class="py-2.5 px-3">Estado</th>
                <th class="py-2.5 px-3 text-right">Acción</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="p in pagos.data" :key="p.id">
                <td class="py-2.5 px-3 font-mono text-[11px]">{{ p.referencia_transaccion || '—' }}</td>
                <td class="py-2.5 px-3">
                  <span class="font-mono font-semibold text-slate-800 block">{{ p.cita?.codigo_reserva }}</span>
                  <span class="text-slate-500">{{ p.cita?.paciente?.persona?.nombre_completo }}</span>
                </td>
                <td class="py-2.5 px-3">{{ p.cita?.medico_especialidad?.especialidad?.nombre }}</td>
                <td class="py-2.5 px-3 text-right font-mono">Bs. {{ Number(p.monto).toFixed(2) }}</td>
                <td class="py-2.5 px-3">{{ p.metodo_pago?.nombre }}</td>
                <td class="py-2.5 px-3">
                  <span class="px-1.5 py-0.5 rounded border text-[11px] font-semibold" :class="badge(p.estado)">{{ p.estado }}</span>
                </td>
                <td class="py-2.5 px-3 text-right">
                  <button
                    v-if="p.estado === 'Aprobado'"
                    type="button"
                    @click="abrirReembolso(p)"
                    class="px-2 py-1 text-[11px] font-semibold text-rose-700 border border-rose-200 bg-rose-50 rounded-lg cursor-pointer"
                  >
                    Reembolsar pago
                  </button>
                </td>
              </tr>
              <tr v-if="pagos.data.length === 0">
                <td colspan="7" class="py-8 text-center text-slate-400">No hay pagos con esos filtros.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div v-if="pagoSeleccionado" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <form @submit.prevent="confirmar" class="bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-md p-5 space-y-3 text-xs">
        <h3 class="text-sm font-semibold text-slate-900">Anular o Reembolsar Pago</h3>
        <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg space-y-1">
          <p><span class="text-slate-500">Referencia:</span> <span class="font-mono font-bold">{{ pagoSeleccionado.referencia_transaccion }}</span></p>
          <p><span class="text-slate-500">Cita:</span> {{ pagoSeleccionado.cita?.codigo_reserva }} · {{ pagoSeleccionado.cita?.paciente?.persona?.nombre_completo }}</p>
          <p><span class="text-slate-500">Monto:</span> <strong>Bs. {{ Number(pagoSeleccionado.monto).toFixed(2) }}</strong></p>
          <p><span class="text-slate-500">Estado:</span> {{ pagoSeleccionado.estado }}</p>
        </div>
        <div>
          <label class="block text-[11px] font-medium text-slate-700 mb-1">Motivo del reembolso *</label>
          <textarea v-model="form.motivo_reembolso" required minlength="5" rows="3" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 outline-none resize-none" placeholder="Describa el motivo de la anulación o reembolso"></textarea>
          <p v-if="form.errors.motivo_reembolso" class="text-[10px] text-rose-600 mt-0.5">{{ form.errors.motivo_reembolso }}</p>
        </div>
        <p class="text-[10px] text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-2.5 py-2">
          La devolución se solicita a la pasarela. Si la cita aún no fue atendida, pasará a Cancelada.
        </p>
        <div class="flex justify-end gap-2">
          <button type="button" @click="pagoSeleccionado = null" class="px-3 py-1.5 border border-slate-300 rounded-lg cursor-pointer">Cancelar</button>
          <button type="submit" :disabled="form.processing" class="px-3.5 py-1.5 font-semibold text-white bg-rose-600 rounded-lg cursor-pointer disabled:opacity-50">Confirmar reembolso</button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  pagos: Object,
  metodos: Array,
  estados: Array,
  recaudadoPeriodo: Number,
  filters: Object,
});

const buscar = ref(props.filters?.buscar || '');
const estado = ref(props.filters?.estado || '');
const metodoId = ref(props.filters?.metodo_pago_id || '');
const pagoSeleccionado = ref(null);
const form = useForm({ motivo_reembolso: '' });

const filtrar = () => {
  router.get('/pagos', {
    buscar: buscar.value,
    estado: estado.value,
    metodo_pago_id: metodoId.value,
  }, { preserveState: true, replace: true });
};

const abrirReembolso = (pago) => {
  form.reset();
  form.clearErrors();
  pagoSeleccionado.value = pago;
};

const confirmar = () => {
  form.post(`/pagos/${pagoSeleccionado.value.id}/reembolsar`, {
    preserveScroll: true,
    onSuccess: () => {
      pagoSeleccionado.value = null;
      form.reset();
    },
  });
};

const badge = (estadoPago) => ({
  Pendiente: 'bg-amber-50 text-amber-700 border-amber-200',
  Aprobado: 'bg-emerald-50 text-emerald-700 border-emerald-200',
  Rechazado: 'bg-rose-50 text-rose-700 border-rose-200',
  Reembolsado: 'bg-slate-100 text-slate-600 border-slate-200',
}[estadoPago] || 'bg-slate-50 text-slate-600 border-slate-200');
</script>

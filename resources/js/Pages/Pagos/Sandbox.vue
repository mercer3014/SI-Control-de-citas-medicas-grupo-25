<template>
  <AppLayout>
    <template #header>
      <span>Pasarela de pagos (pruebas)</span>
    </template>

    <div class="max-w-lg mx-auto bg-white rounded-xl border border-slate-200/80 shadow-xs p-5 space-y-4 text-xs">
      <p class="text-slate-600">
        Checkout simulado por HTTPS local. No se realiza ningún cobro y no se piden datos de tarjeta.
        Al confirmar, la pasarela envía un webhook firmado con HMAC.
      </p>

      <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg space-y-1">
        <p><span class="text-slate-500">Referencia:</span> <span class="font-mono font-bold">{{ pago.referencia_transaccion }}</span></p>
        <p><span class="text-slate-500">Cita:</span> {{ pago.cita?.codigo_reserva }}</p>
        <p><span class="text-slate-500">Paciente:</span> {{ pago.cita?.paciente?.persona?.nombre_completo }}</p>
        <p><span class="text-slate-500">Concepto:</span> {{ pago.cita?.medico_especialidad?.especialidad?.nombre }}</p>
        <p class="text-base font-bold text-slate-900 pt-1">Bs. {{ Number(pago.monto).toFixed(2) }} {{ pago.moneda }}</p>
        <p><span class="text-slate-500">Método:</span> {{ pago.metodo_pago?.nombre }}</p>
      </div>

      <div class="grid grid-cols-11 gap-0.5 w-40 mx-auto" aria-hidden="true">
        <span
          v-for="(on, i) in celdas"
          :key="i"
          class="aspect-square"
          :class="on ? 'bg-slate-900' : 'bg-slate-100'"
        ></span>
      </div>
      <p class="text-center text-[10px] text-slate-400">Código de referencia para el entorno de pruebas</p>

      <div class="flex justify-end gap-2">
        <form @submit.prevent="enviar('rechazado')">
          <button type="submit" :disabled="enviando" class="px-3 py-1.5 border border-rose-300 text-rose-700 rounded-lg cursor-pointer">Rechazar pago</button>
        </form>
        <form @submit.prevent="enviar('aprobado')">
          <button type="submit" :disabled="enviando" class="px-3.5 py-1.5 bg-emerald-600 text-white font-semibold rounded-lg cursor-pointer">Aprobar pago</button>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  pago: Object,
});

const enviando = ref(false);

const celdas = computed(() => {
  const refPago = props.pago.referencia_transaccion || 'SANDBOX';
  return Array.from({ length: 121 }, (_, i) => {
    const code = refPago.charCodeAt(i % refPago.length) || 0;
    return ((code + i * 3) % 5) > 1;
  });
});

const enviar = (decision) => {
  enviando.value = true;
  router.post(`/pagos/sandbox/${props.pago.referencia_transaccion}`, { decision }, {
    onFinish: () => { enviando.value = false; },
  });
};
</script>

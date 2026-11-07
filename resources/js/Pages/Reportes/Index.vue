<template>
  <AppLayout>
    <template #header>
      <span>Reportes Administrativos y Estadísticas</span>
    </template>

    <div class="space-y-5">
      <!-- Barra Superior: Filtro de Fechas y Exportación -->
      <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs flex flex-col lg:flex-row items-start lg:items-center justify-between gap-3">
        <form @submit.prevent="handleFilter" class="flex flex-wrap items-center gap-2.5 w-full lg:w-auto text-xs">
          <div class="flex items-center gap-1.5">
            <label class="text-[11px] font-medium text-slate-500">Desde:</label>
            <input
              v-model="fechaInicio"
              type="date"
              class="px-2.5 py-1.5 rounded-lg border border-slate-300 focus:border-slate-600 outline-none"
            />
          </div>

          <div class="flex items-center gap-1.5">
            <label class="text-[11px] font-medium text-slate-500">Hasta:</label>
            <input
              v-model="fechaFin"
              type="date"
              class="px-2.5 py-1.5 rounded-lg border border-slate-300 focus:border-slate-600 outline-none"
            />
          </div>

          <button
            type="submit"
            class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-lg shadow-xs transition cursor-pointer"
          >
            Actualizar Rango
          </button>
        </form>

        <!-- Botones de Exportación a Excel .xlsx -->
        <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto">
          <a
            href="/reportes/export-citas"
            target="_blank"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg text-xs shadow-xs transition cursor-pointer"
            title="Descargar libro de citas en formato Excel"
          >
            <AppIcon name="file-spreadsheet" class="w-4 h-4" />
            <span>Exportar Citas (.xlsx)</span>
          </a>

          <a
            href="/reportes/export-pacientes"
            target="_blank"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 font-medium rounded-lg text-xs shadow-xs transition cursor-pointer"
            title="Descargar padrón de pacientes en formato Excel"
          >
            <AppIcon name="file-spreadsheet" class="w-4 h-4 text-emerald-600" />
            <span>Pacientes (.xlsx)</span>
          </a>

          <a
            href="/reportes/export-general"
            target="_blank"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 font-medium rounded-lg text-xs shadow-xs transition cursor-pointer"
            title="Descargar resumen de métricas en formato Excel"
          >
            <AppIcon name="download" class="w-4 h-4 text-slate-600" />
            <span>Balance General</span>
          </a>
        </div>
      </div>

      <!-- Tarjetas de Métricas Ejecutivas (KPIs) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
        <!-- Total Citas -->
        <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs">
          <div class="flex items-center justify-between mb-2">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Citas Totales</span>
            <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600">
              <AppIcon name="calendar-days" class="w-4 h-4" />
            </div>
          </div>
          <p class="text-2xl font-bold text-slate-900">{{ metricas.total_citas }}</p>
          <span class="text-[10px] text-slate-400 mt-1 block">En el período seleccionado</span>
        </div>

        <!-- Citas Atendidas -->
        <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs">
          <div class="flex items-center justify-between mb-2">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Atendidas</span>
            <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600">
              <AppIcon name="user-check" class="w-4 h-4" />
            </div>
          </div>
          <p class="text-2xl font-bold text-emerald-700">{{ metricas.total_atendidas }}</p>
          <span class="text-[10px] text-slate-400 mt-1 block">Consultas completadas</span>
        </div>

        <!-- Citas Canceladas -->
        <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs">
          <div class="flex items-center justify-between mb-2">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Canceladas</span>
            <div class="w-8 h-8 rounded-lg bg-rose-50 flex items-center justify-center text-rose-600">
              <AppIcon name="ban" class="w-4 h-4" />
            </div>
          </div>
          <p class="text-2xl font-bold text-rose-600">{{ metricas.total_canceladas }}</p>
          <span class="text-[10px] text-slate-400 mt-1 block">Anuladas con justificación</span>
        </div>

        <!-- Tasa de Cumplimiento -->
        <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs">
          <div class="flex items-center justify-between mb-2">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Cumplimiento</span>
            <div class="w-8 h-8 rounded-lg bg-teal-50 flex items-center justify-center text-teal-600">
              <AppIcon name="activity" class="w-4 h-4" />
            </div>
          </div>
          <p class="text-2xl font-bold text-teal-800">{{ metricas.tasa_cumplimiento }}%</p>
          <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden mt-1.5">
            <div class="bg-teal-500 h-full rounded-full transition-all duration-500" :style="{ width: `${metricas.tasa_cumplimiento}%` }"></div>
          </div>
        </div>

        <!-- Ingresos Estimados -->
        <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs">
          <div class="flex items-center justify-between mb-2">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Recaudación</span>
            <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600">
              <AppIcon name="sparkle" class="w-4 h-4" />
            </div>
          </div>
          <p class="text-2xl font-bold text-indigo-700 font-mono">
            Bs. {{ formatMoney(metricas.ingresos_totales) }}
          </p>
          <span class="text-[10px] text-slate-400 mt-1 block">Por aranceles atendidos</span>
        </div>
      </div>

      <!-- Tablas de Rendimiento: Médicos y Especialidades -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Rendimiento por Médico -->
        <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
          <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200/80 flex items-center justify-between">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">
              Rendimiento por Profesional Médico
            </h3>
            <span class="text-[11px] text-slate-500 font-mono">{{ rendimientoMedicos.length }} médicos</span>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
              <thead>
                <tr class="border-b border-slate-100 text-slate-500 font-semibold text-[11px]">
                  <th class="py-2 px-3">Médico</th>
                  <th class="py-2 px-3 text-center">Citas</th>
                  <th class="py-2 px-3 text-center">Atendidas</th>
                  <th class="py-2 px-3 text-right">Recaudado</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="rm in rendimientoMedicos" :key="rm.medico_id" class="hover:bg-slate-50/50">
                  <td class="py-2.5 px-3">
                    <span class="font-medium text-slate-900 block">{{ rm.nombre }}</span>
                    <span class="text-[10px] font-mono text-slate-400">Mat: {{ rm.matricula }}</span>
                  </td>
                  <td class="py-2.5 px-3 text-center font-mono">{{ rm.total_citas }}</td>
                  <td class="py-2.5 px-3 text-center">
                    <span class="px-1.5 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-700">
                      {{ rm.atendidas }}
                    </span>
                  </td>
                  <td class="py-2.5 px-3 text-right font-mono font-semibold text-slate-800">
                    Bs. {{ formatMoney(rm.ingresos) }}
                  </td>
                </tr>

                <tr v-if="rendimientoMedicos.length === 0">
                  <td colspan="4" class="py-6 text-center text-slate-400 text-xs">
                    Sin registros médicos en este rango de fechas.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Demanda por Especialidad -->
        <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
          <div class="px-4 py-3 bg-slate-50/80 border-b border-slate-200/80 flex items-center justify-between">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">
              Demanda de Especialidades Médicas
            </h3>
            <span class="text-[11px] text-slate-500 font-mono">{{ demandaEspecialidades.length }} especialidades</span>
          </div>

          <div class="p-4 space-y-3">
            <div
              v-for="esp in demandaEspecialidades"
              :key="esp.especialidad_id"
              class="space-y-1"
            >
              <div class="flex items-center justify-between text-xs">
                <span class="font-semibold text-slate-800">{{ esp.nombre }}</span>
                <span class="font-mono text-slate-600 text-[11px]">
                  {{ esp.total_citas }} citas ({{ esp.porcentaje }}%)
                </span>
              </div>
              <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                <div
                  class="bg-blue-600 h-full rounded-full transition-all duration-500"
                  :style="{ width: `${esp.porcentaje}%` }"
                ></div>
              </div>
            </div>

            <div v-if="demandaEspecialidades.length === 0" class="py-8 text-center text-slate-400 text-xs">
              No hay citas vinculadas a especialidades en este rango.
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppIcon from '@/Components/AppIcon.vue';

const props = defineProps({
  metricas: Object,
  rendimientoMedicos: Array,
  demandaEspecialidades: Array,
  filters: Object,
});

const fechaInicio = ref(props.filters?.fecha_inicio || '');
const fechaFin = ref(props.filters?.fecha_fin || '');

const handleFilter = () => {
  router.get('/reportes', {
    fecha_inicio: fechaInicio.value,
    fecha_fin: fechaFin.value,
  }, { preserveState: true, replace: true });
};

const formatMoney = (val) => {
  if (!val) return '0.00';
  return Number(val).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>

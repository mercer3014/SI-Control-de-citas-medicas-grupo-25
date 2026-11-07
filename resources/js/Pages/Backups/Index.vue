<template>
  <AppLayout>
    <template #header>
      <span>Respaldos de Base de Datos</span>
    </template>

    <div class="space-y-4">
      <!-- Tarjeta Principal de Respaldo -->
      <div class="bg-white p-5 rounded border border-slate-200 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
            <h3 class="text-sm font-bold text-slate-800">Motor Activo: PostgreSQL 18</h3>
          </div>
          <p class="text-xs text-slate-500 mt-1">
            Base de datos: <span class="font-mono font-semibold text-slate-700">{{ dbName }}</span> &bull; Tamaño actual: <span class="font-mono font-semibold text-blue-600">{{ dbSize }}</span>
          </p>
        </div>

        <a 
          :href="'/backups/download'"
          class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-semibold shadow-xs transition cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          <span>Descargar Copia de Respaldo (.SQL)</span>
        </a>
      </div>

      <!-- Estado de las Tablas en la Base de Datos -->
      <div class="bg-white rounded border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-4 py-3 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2.5">
          <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Tablas Registradas en la Base de Datos ({{ filteredTables.length }})</h4>
          
          <!-- Filtro de Tablas -->
          <div class="relative w-full sm:w-64">
            <input 
              v-model="filtroTabla" 
              type="text" 
              placeholder="Filtrar por nombre de tabla..." 
              class="w-full pl-8 pr-3 py-1.5 text-xs rounded border border-slate-300 focus:border-slate-600 outline-none"
            />
            <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-100/60 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px]">
                <th class="py-2 px-3 text-center w-12 text-slate-500 font-mono">#</th>
                <th class="py-2 px-4">Nombre de Tabla</th>
                <th class="py-2 px-4 text-center">Registros Estimados</th>
                <th class="py-2 px-4 text-right">Espacio Ocupado</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-mono">
              <tr v-for="(t, index) in filteredTables" :key="t.table_name" class="hover:bg-slate-50">
                <td class="py-2 px-3 text-center text-slate-400 text-[11px] font-semibold">
                  {{ index + 1 }}
                </td>
                <td class="py-2 px-4 font-semibold text-slate-800 font-sans">
                  {{ t.table_name }}
                </td>
                <td class="py-2 px-4 text-center text-slate-700">
                  {{ t.row_count }}
                </td>
                <td class="py-2 px-4 text-right text-slate-500">
                  {{ t.total_size }}
                </td>
              </tr>
              <tr v-if="filteredTables.length === 0">
                <td colspan="4" class="py-6 text-center text-xs text-slate-400 font-sans">
                  No se encontraron tablas que coincidan con "{{ filtroTabla }}".
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  tables: Array,
  dbSize: String,
  dbName: String,
});

const filtroTabla = ref('');

const filteredTables = computed(() => {
  if (!filtroTabla.value) return props.tables || [];
  const term = filtroTabla.value.toLowerCase().trim();
  return (props.tables || []).filter(t => t.table_name?.toLowerCase().includes(term));
});
</script>

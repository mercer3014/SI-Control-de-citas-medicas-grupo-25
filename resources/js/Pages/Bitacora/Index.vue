<template>
  <AppLayout>
    <template #header>
      <span>Bitácora de Auditoría</span>
    </template>

    <div class="space-y-4">
      <!-- Barra de Búsqueda y Filtros -->
      <div class="bg-white p-3.5 rounded border border-slate-200 shadow-xs flex flex-wrap items-center gap-2.5">
        <!-- Búsqueda General -->
        <div class="relative w-full sm:w-64">
          <input 
            v-model="buscar" 
            type="text" 
            placeholder="Buscar por detalle, acción, IP..." 
            class="w-full pl-8 pr-3 py-1.5 text-xs rounded border border-slate-300 focus:border-slate-600 outline-none"
            @keyup.enter="handleSearch"
          />
          <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>

        <!-- Filtro Módulo -->
        <div class="w-full sm:w-36">
          <input 
            v-model="modulo" 
            type="text" 
            placeholder="Módulo"
            class="w-full px-2.5 py-1.5 text-xs rounded border border-slate-300 focus:border-slate-600 outline-none"
            @keyup.enter="handleSearch"
          />
        </div>

        <!-- Filtro Fecha -->
        <div class="w-full sm:w-36">
          <input 
            v-model="fecha" 
            type="date" 
            class="w-full px-2.5 py-1.5 text-xs rounded border border-slate-300 focus:border-slate-600 outline-none"
            @change="handleSearch"
          />
        </div>

        <!-- Filtro Usuario -->
        <div class="w-full sm:w-44">
          <select 
            v-model="usuarioId" 
            class="w-full px-2.5 py-1.5 text-xs rounded border border-slate-300 focus:border-slate-600 outline-none"
            @change="handleSearch"
          >
            <option value="">Todos los usuarios</option>
            <option v-for="u in usuarios" :key="u.id" :value="u.id">
              {{ u.username }} ({{ u.persona?.nombres }})
            </option>
          </select>
        </div>

        <button 
          @click="handleSearch"
          class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded text-xs font-semibold shadow-xs transition cursor-pointer"
        >
          Filtrar
        </button>

        <button 
          v-if="hasFilters"
          @click="resetFilters"
          class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-xs font-medium transition cursor-pointer"
        >
          Limpiar
        </button>
      </div>

      <!-- Vista Móvil: Tarjetas de Bitácora con Despliegue JSON -->
      <div class="block md:hidden space-y-3">
        <div
          v-for="(b, index) in bitacoras.data"
          :key="b.id"
          class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden"
        >
          <div
            @click="toggleRow(b.id)"
            class="p-3.5 space-y-2 cursor-pointer hover:bg-slate-50 transition"
          >
            <div class="flex items-start justify-between gap-2">
              <div class="flex items-center gap-2 min-w-0">
                <span class="w-6 h-6 rounded-md bg-slate-100 text-slate-600 font-mono text-[11px] font-bold flex items-center justify-center shrink-0">
                  #{{ ((bitacoras.current_page || 1) - 1) * (bitacoras.per_page || 20) + index + 1 }}
                </span>
                <div class="min-w-0">
                  <span class="font-medium text-slate-900 text-xs block truncate">
                    {{ b.usuario?.persona?.nombre_completo || b.usuario?.username || 'Sistema' }}
                  </span>
                  <span class="text-[10px] text-slate-500 font-mono">
                    {{ formatDateTime(b.fecha_hora) }}
                  </span>
                </div>
              </div>

              <div class="flex items-center gap-1.5 shrink-0">
                <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                  {{ b.modulo }}
                </span>
                <svg
                  class="w-4 h-4 text-slate-400 transition-transform duration-200"
                  :class="{ 'rotate-180': isExpanded(b.id) }"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
              </div>
            </div>

            <div class="text-[11px] text-slate-700 bg-slate-50/70 p-2 rounded-lg border border-slate-100">
              <div class="flex items-center justify-between mb-1">
                <span class="font-mono text-[10px] font-semibold text-slate-800 px-1.5 py-0.2 rounded bg-white border border-slate-200">
                  {{ b.accion }}
                </span>
                <span class="font-mono text-[10px] text-slate-400">
                  IP: {{ b.ip_origen || '127.0.0.1' }}
                </span>
              </div>
              <p class="text-slate-800 leading-snug">{{ b.detalle || '-' }}</p>
            </div>
          </div>

          <!-- Despliegue JSON Móvil -->
          <div v-if="isExpanded(b.id)" class="p-3 bg-slate-900 border-t border-slate-800 space-y-2">
            <div class="flex items-center justify-between text-slate-400 text-[10px] pb-1 border-b border-slate-800">
              <span class="font-mono flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                Registro #{{ b.id }}
              </span>
              <button
                type="button"
                @click.stop="copyJson(b)"
                class="text-[10px] text-slate-300 hover:text-white px-2 py-0.5 rounded bg-slate-800 hover:bg-slate-700 border border-slate-700 transition"
              >
                {{ copiedId === b.id ? '¡Copiado!' : 'Copiar JSON' }}
              </button>
            </div>
            <pre class="text-emerald-400 font-mono text-[10px] overflow-x-auto leading-relaxed p-2 bg-slate-950/70 rounded">{{ formatJson(b) }}</pre>
          </div>
        </div>

        <div v-if="bitacoras.data.length === 0" class="bg-white rounded-xl border border-slate-200 p-8 text-center text-xs text-slate-500">
          No hay registros en la bitácora que coincidan con la búsqueda.
        </div>
      </div>

      <!-- Tabla de Auditoría con Registros Desplegables (JSON) Escritorio -->
      <div class="hidden md:block bg-white rounded border border-slate-200 shadow-xs overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-100/80 border-b border-slate-200 text-slate-700 font-semibold uppercase text-[11px] tracking-wider">
              <th class="py-2.5 px-3 text-center w-12 text-slate-500 font-mono">#</th>
              <th class="py-2.5 px-3 whitespace-nowrap">Fecha y Hora</th>
              <th class="py-2.5 px-3">Usuario</th>
              <th class="py-2.5 px-3">Módulo</th>
              <th class="py-2.5 px-3">Acción</th>
              <th class="py-2.5 px-3 min-w-[280px]">Detalle</th>
              <th class="py-2.5 px-3 text-right">IP</th>
              <th class="py-2.5 px-3 text-center w-12">Log</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <template v-for="(b, index) in bitacoras.data" :key="b.id">
              <!-- Fila Principal (Click para desplegar) -->
              <tr 
                @click="toggleRow(b.id)"
                class="hover:bg-slate-50 transition cursor-pointer select-none"
                :class="{ 'bg-slate-50/70': isExpanded(b.id) }"
              >
                <!-- Enumeración -->
                <td class="py-2.5 px-3 text-center font-mono text-[11px] text-slate-400 font-semibold">
                  {{ ((bitacoras.current_page || 1) - 1) * (bitacoras.per_page || 20) + index + 1 }}
                </td>

                <!-- Fecha y Hora -->
                <td class="py-2.5 px-3 text-slate-600 whitespace-nowrap font-mono text-[11px]">
                  {{ formatDateTime(b.fecha_hora) }}
                </td>

                <!-- Usuario -->
                <td class="py-2.5 px-3">
                  <span class="font-medium text-slate-900 block">
                    {{ b.usuario?.persona?.nombre_completo || b.usuario?.username || 'Sistema' }}
                  </span>
                  <span class="text-[10px] text-slate-400 font-mono" v-if="b.usuario">
                    @{{ b.usuario?.username }}
                  </span>
                </td>

                <!-- Módulo -->
                <td class="py-2.5 px-3">
                  <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                    {{ b.modulo }}
                  </span>
                </td>

                <!-- Acción -->
                <td class="py-2.5 px-3">
                  <span class="font-mono text-[10px] font-semibold text-slate-800 px-1.5 py-0.5 rounded bg-slate-100 border border-slate-200">
                    {{ b.accion }}
                  </span>
                </td>

                <!-- Detalle -->
                <td class="py-2.5 px-3 text-slate-800 leading-snug">
                  {{ b.detalle || '-' }}
                </td>

                <!-- Dirección IP -->
                <td class="py-2.5 px-3 text-right text-slate-500 font-mono text-[11px] whitespace-nowrap">
                  {{ b.ip_origen || '127.0.0.1' }}
                </td>

                <!-- Indicador de Despliegue -->
                <td class="py-2.5 px-3 text-center">
                  <button 
                    type="button"
                    class="p-1 rounded text-slate-400 hover:text-slate-700 transition"
                    :title="isExpanded(b.id) ? 'Ocultar JSON' : 'Ver log en JSON'"
                  >
                    <svg 
                      class="w-3.5 h-3.5 transition-transform duration-200" 
                      :class="{ 'rotate-180': isExpanded(b.id) }"
                      fill="none" 
                      viewBox="0 0 24 24" 
                      stroke="currentColor" 
                      stroke-width="2"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                  </button>
                </td>
              </tr>

              <!-- Fila Desplegable: Detalle en formato JSON -->
              <tr v-if="isExpanded(b.id)">
                <td colspan="8" class="p-0 bg-slate-900 border-y border-slate-800">
                  <div class="p-4 space-y-2">
                    <div class="flex items-center justify-between text-slate-400 text-[11px] pb-1 border-b border-slate-800">
                      <span class="font-mono flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        Registro #{{ b.id }} &bull; Log estructurado (JSON)
                      </span>
                      <button 
                        type="button" 
                        @click.stop="copyJson(b)" 
                        class="text-xs text-slate-300 hover:text-white px-2 py-0.5 rounded bg-slate-800 hover:bg-slate-700 border border-slate-700 transition cursor-pointer"
                      >
                        {{ copiedId === b.id ? '¡Copiado!' : 'Copiar JSON' }}
                      </button>
                    </div>

                    <pre class="text-emerald-400 font-mono text-[11px] overflow-x-auto leading-relaxed p-2 bg-slate-950/70 rounded">{{ formatJson(b) }}</pre>
                  </div>
                </td>
              </tr>
            </template>

            <tr v-if="bitacoras.data.length === 0">
              <td colspan="8" class="py-8 text-center text-xs text-slate-500">
                No hay registros en la bitácora que coincidan con la búsqueda.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginación -->
      <div v-if="bitacoras.links.length > 3" class="flex justify-end gap-1 text-xs">
        <Link 
          v-for="(link, i) in bitacoras.links" 
          :key="i"
          :href="link.url || '#'"
          :class="[
            'px-2.5 py-1 rounded border',
            link.active ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50',
            !link.url ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer'
          ]"
          v-html="link.label"
        />
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  bitacoras: Object,
  usuarios: Array,
  filters: Object,
});

const buscar = ref(props.filters?.buscar || '');
const modulo = ref(props.filters?.modulo || '');
const fecha = ref(props.filters?.fecha || '');
const usuarioId = ref(props.filters?.usuario_id || '');

const expandedRows = ref([]);
const copiedId = ref(null);

const toggleRow = (id) => {
  const index = expandedRows.value.indexOf(id);
  if (index > -1) {
    expandedRows.value.splice(index, 1);
  } else {
    expandedRows.value.push(id);
  }
};

const isExpanded = (id) => expandedRows.value.includes(id);

const formatJson = (record) => {
  const logData = {
    id: record.id,
    fecha_hora: record.fecha_hora,
    usuario_id: record.usuario_id,
    usuario: record.usuario?.username || 'Sistema',
    nombre_responsable: record.usuario?.persona?.nombre_completo || null,
    modulo: record.modulo,
    accion: record.accion,
    detalle: record.detalle,
    ip_origen: record.ip_origen,
  };
  return JSON.stringify(logData, null, 2);
};

const copyJson = (record) => {
  navigator.clipboard.writeText(formatJson(record)).then(() => {
    copiedId.value = record.id;
    setTimeout(() => {
      copiedId.value = null;
    }, 2000);
  });
};

const hasFilters = computed(() => {
  return buscar.value || modulo.value || fecha.value || usuarioId.value;
});

const handleSearch = () => {
  router.get('/bitacora', {
    buscar: buscar.value || undefined,
    modulo: modulo.value || undefined,
    fecha: fecha.value || undefined,
    usuario_id: usuarioId.value || undefined,
  }, {
    preserveState: true,
    replace: true,
  });
};

const resetFilters = () => {
  buscar.value = '';
  modulo.value = '';
  fecha.value = '';
  usuarioId.value = '';
  router.get('/bitacora');
};

const formatDateTime = (dateString) => {
  if (!dateString) return '-';
  try {
    const d = new Date(dateString.replace(' ', 'T'));
    if (isNaN(d.getTime())) return dateString;
    return d.toLocaleString('es-BO', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
    });
  } catch (e) {
    return dateString;
  }
};
</script>

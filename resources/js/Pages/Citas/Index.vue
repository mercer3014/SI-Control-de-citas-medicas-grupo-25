<template>
  <AppLayout>
    <template #header>
      <span>Agenda y Citas Médicas</span>
    </template>

    <div class="space-y-4">
      <!-- Barra Superior: Filtros y Botón de Programar -->
      <div class="bg-white p-3.5 rounded-xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
          <!-- Búsqueda General -->
          <div class="relative w-full sm:w-56">
            <input
              v-model="buscar"
              type="text"
              placeholder="Buscar por código, paciente..."
              class="w-full pl-8 pr-3 py-1.5 text-xs rounded-lg border border-slate-300 focus:border-slate-600 outline-none"
              @keyup.enter="handleSearch"
            />
            <AppIcon name="search" class="w-4 h-4 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" />
          </div>

          <!-- Filtro Especialidad -->
          <select
            v-model="selectedEspecialidad"
            @change="handleSearch"
            class="px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:border-slate-600 outline-none"
          >
            <option value="">Todas las especialidades</option>
            <option v-for="e in especialidades" :key="e.id" :value="e.id">{{ e.nombre }}</option>
          </select>

          <!-- Filtro Médico -->
          <select
            v-model="selectedMedico"
            @change="handleSearch"
            class="px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:border-slate-600 outline-none"
          >
            <option value="">Todos los médicos</option>
            <option v-for="m in medicos" :key="m.id" :value="m.id">
              Dr(a). {{ m.persona?.nombre_completo }}
            </option>
          </select>

          <!-- Filtro Estado -->
          <select
            v-model="selectedEstado"
            @change="handleSearch"
            class="px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:border-slate-600 outline-none"
          >
            <option value="">Todos los estados</option>
            <option v-for="est in estados" :key="est.id" :value="est.id">{{ est.nombre }}</option>
          </select>

          <!-- Filtro Fecha -->
          <input
            v-model="selectedFecha"
            type="date"
            class="px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:border-slate-600 outline-none"
            @change="handleSearch"
          />

          <button
            v-if="hasFilters"
            @click="clearFilters"
            class="px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-lg border border-slate-300 transition cursor-pointer"
          >
            Limpiar
          </button>
        </div>

        <button
          type="button"
          @click="openProgramarModal"
          class="w-full md:w-auto inline-flex items-center justify-center gap-1.5 px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-semibold shadow-xs transition cursor-pointer"
        >
          <AppIcon name="plus" class="w-3.5 h-3.5" />
          <span>Programar Cita</span>
        </button>
      </div>

      <!-- Tabla de Citas Médicas -->
      <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-100/80 border-b border-slate-200 text-slate-700 font-semibold uppercase text-[11px] tracking-wider">
              <th class="py-2.5 px-3 text-center w-12 text-slate-500 font-mono">#</th>
              <th class="py-2.5 px-3">Código</th>
              <th class="py-2.5 px-3">Paciente</th>
              <th class="py-2.5 px-3">Médico / Especialidad</th>
              <th class="py-2.5 px-3 whitespace-nowrap">Fecha y Hora</th>
              <th class="py-2.5 px-3 text-center">Estado</th>
              <th class="py-2.5 px-3 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="(c, index) in citas.data"
              :key="c.id"
              class="hover:bg-slate-50 transition"
            >
              <td class="py-2.5 px-3 text-center font-mono text-[11px] text-slate-400 font-semibold">
                {{ ((citas.current_page || 1) - 1) * (citas.per_page || 15) + index + 1 }}
              </td>
              <!-- Código -->
              <td class="py-2.5 px-3 font-mono font-bold text-slate-800 text-[11px]">
                {{ c.codigo_reserva }}
              </td>

              <!-- Paciente -->
              <td class="py-2.5 px-3">
                <span class="font-semibold text-slate-900 block">{{ c.paciente?.persona?.nombre_completo }}</span>
                <span class="text-[11px] text-slate-500 font-mono">CI: {{ c.paciente?.persona?.ci }} &bull; {{ c.paciente?.persona?.telefono || 'Sin teléfono' }}</span>
              </td>

              <!-- Médico y Especialidad -->
              <td class="py-2.5 px-3">
                <span class="font-medium text-slate-800 block">Dr(a). {{ c.medico_especialidad?.medico?.persona?.nombre_completo }}</span>
                <span class="inline-block mt-0.5 px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-50 text-blue-700 border border-blue-200/60">
                  {{ c.medico_especialidad?.especialidad?.nombre }}
                </span>
              </td>

              <!-- Fecha y Hora -->
              <td class="py-2.5 px-3 whitespace-nowrap">
                <span class="font-mono font-semibold text-slate-800 block text-[11px]">
                  {{ formatDate(c.fecha) }}
                </span>
                <span class="font-mono text-[11px] text-slate-500">
                  {{ formatHour(c.hora_inicio) }} - {{ formatHour(c.hora_fin) }}
                </span>
              </td>

              <!-- Estado -->
              <td class="py-2.5 px-3 text-center">
                <span
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold border"
                  :class="badgeClass(c.estado_id)"
                >
                  <span class="w-1.5 h-1.5 rounded-full" :class="dotClass(c.estado_id)"></span>
                  {{ c.estado?.nombre }}
                </span>
              </td>

              <!-- Acciones Contextuales -->
              <td class="py-2.5 px-3 text-right">
                <div class="inline-flex items-center gap-1">
                  <!-- Reprogramar (si está Programada o Confirmada) -->
                  <button
                    v-if="c.estado_id === 1 || c.estado_id === 2"
                    type="button"
                    @click="openReprogramarModal(c)"
                    class="p-1.5 rounded-lg border border-slate-200 bg-white text-slate-600 hover:text-blue-600 hover:bg-blue-50 transition cursor-pointer"
                    title="Reprogramar cita"
                  >
                    <AppIcon name="clock" class="w-3.5 h-3.5" />
                  </button>

                  <!-- Cancelar (si está Programada o Confirmada) -->
                  <button
                    v-if="c.estado_id === 1 || c.estado_id === 2"
                    type="button"
                    @click="openCancelarModal(c)"
                    class="p-1.5 rounded-lg border border-slate-200 bg-white text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer"
                    title="Cancelar cita"
                  >
                    <AppIcon name="ban" class="w-3.5 h-3.5" />
                  </button>

                  <!-- Ver informe si ya fue atendida -->
                  <span
                    v-if="c.estado_id === 3"
                    class="text-[11px] font-medium text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200"
                    title="Consulta atendida"
                  >
                    Atendida
                  </span>

                  <!-- Motivo de cancelación si fue cancelada -->
                  <span
                    v-if="c.estado_id === 4"
                    class="text-[10px] text-slate-400 italic max-w-[120px] truncate"
                    :title="c.motivo_cancelacion"
                  >
                    {{ c.motivo_cancelacion || 'Cancelada' }}
                  </span>
                </div>
              </td>
            </tr>

            <tr v-if="citas.data.length === 0">
              <td colspan="7" class="py-8 text-center text-xs text-slate-400">
                No se encontraron citas médicas con los filtros seleccionados.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginación -->
      <div v-if="citas.links?.length > 3" class="flex justify-end gap-1 text-xs">
        <Link
          v-for="(link, i) in citas.links"
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

    <!-- Modales Flotantes -->
    <ModalProgramarCita
      :show="showProgramarModal"
      :medicos="medicos"
      :pacientes="pacientes"
      @close="showProgramarModal = false"
    />

    <ModalReprogramarCita
      :show="showReprogramarModal"
      :cita="citaSelected"
      @close="showReprogramarModal = false"
    />

    <ModalCancelarCita
      :show="showCancelarModal"
      :cita="citaSelected"
      @close="showCancelarModal = false"
    />
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppIcon from '@/Components/AppIcon.vue';
import ModalProgramarCita from './Partials/ModalProgramarCita.vue';
import ModalReprogramarCita from './Partials/ModalReprogramarCita.vue';
import ModalCancelarCita from './Partials/ModalCancelarCita.vue';

const props = defineProps({
  citas: Object,
  medicos: Array,
  especialidades: Array,
  estados: Array,
  pacientes: Array,
  filters: Object,
});

const buscar = ref(props.filters?.buscar || '');
const selectedEspecialidad = ref(props.filters?.especialidad_id || '');
const selectedMedico = ref(props.filters?.medico_id || '');
const selectedEstado = ref(props.filters?.estado_id || '');
const selectedFecha = ref(props.filters?.fecha || '');

const hasFilters = computed(() => {
  return !!(buscar.value || selectedEspecialidad.value || selectedMedico.value || selectedEstado.value || selectedFecha.value);
});

const handleSearch = () => {
  router.get('/citas', {
    buscar: buscar.value,
    especialidad_id: selectedEspecialidad.value,
    medico_id: selectedMedico.value,
    estado_id: selectedEstado.value,
    fecha: selectedFecha.value,
  }, { preserveState: true, replace: true });
};

const clearFilters = () => {
  buscar.value = '';
  selectedEspecialidad.value = '';
  selectedMedico.value = '';
  selectedEstado.value = '';
  selectedFecha.value = '';
  handleSearch();
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  return dateStr.substring(0, 10);
};

const formatHour = (hourStr) => {
  if (!hourStr) return '';
  return hourStr.substring(0, 5);
};

const badgeClass = (estadoId) => {
  switch (estadoId) {
    case 1: return 'bg-blue-50 text-blue-700 border-blue-200';
    case 2: return 'bg-emerald-50 text-emerald-700 border-emerald-200';
    case 3: return 'bg-teal-50 text-teal-800 border-teal-200';
    case 4: return 'bg-rose-50 text-rose-700 border-rose-200';
    default: return 'bg-slate-100 text-slate-600 border-slate-200';
  }
};

const dotClass = (estadoId) => {
  switch (estadoId) {
    case 1: return 'bg-blue-500';
    case 2: return 'bg-emerald-500';
    case 3: return 'bg-teal-500';
    case 4: return 'bg-rose-500';
    default: return 'bg-slate-400';
  }
};

// Modales
const showProgramarModal = ref(false);
const showReprogramarModal = ref(false);
const showCancelarModal = ref(false);
const citaSelected = ref(null);

const openProgramarModal = () => {
  showProgramarModal.value = true;
};

const openReprogramarModal = (c) => {
  citaSelected.value = c;
  showReprogramarModal.value = true;
};

const openCancelarModal = (c) => {
  citaSelected.value = c;
  showCancelarModal.value = true;
};
</script>

<template>
  <AppLayout>
    <template #header>
      <span>Registro de Atenciones Médicas</span>
    </template>

    <div class="space-y-4">
      <!-- Barra Superior: Filtro de Fecha y Médico -->
      <div class="bg-white p-3.5 rounded-xl border border-slate-200/80 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2.5 w-full sm:w-auto">
          <!-- Filtro Fecha -->
          <div class="flex items-center gap-1.5">
            <label class="text-[11px] font-medium text-slate-500">Fecha de Citas:</label>
            <input
              v-model="selectedFecha"
              type="date"
              class="px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:border-slate-600 outline-none"
              @change="handleFilter"
            />
          </div>

          <!-- Botón Rápido: Ver Todas -->
          <button
            type="button"
            @click="setFechaTodas"
            class="px-2.5 py-1.5 text-xs font-medium rounded-lg border transition cursor-pointer"
            :class="selectedFecha === 'todas' ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'"
          >
            Todas las fechas
          </button>

          <!-- Filtro Estado -->
          <select
            v-model="selectedEstado"
            @change="handleFilter"
            class="px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:border-slate-600 outline-none"
          >
            <option value="">Todos los estados</option>
            <option value="1">Pendientes de atención (Programadas)</option>
            <option value="3">Atendidas</option>
          </select>
        </div>
      </div>

      <!-- Vista Móvil: Cola de Atenciones en Tarjetas -->
      <div class="block md:hidden space-y-3">
        <div
          v-for="(c, index) in citas.data"
          :key="c.id"
          class="bg-white rounded-xl border border-slate-200/80 p-3.5 shadow-xs space-y-2.5"
        >
          <div class="flex items-start justify-between gap-2">
            <div class="flex items-center gap-2 min-w-0">
              <span class="w-6 h-6 rounded-md bg-slate-100 text-slate-600 font-mono text-[11px] font-bold flex items-center justify-center shrink-0">
                #{{ ((citas.current_page || 1) - 1) * (citas.per_page || 15) + index + 1 }}
              </span>
              <div class="min-w-0">
                <span class="font-mono font-bold text-slate-900 text-xs block">
                  {{ formatHour(c.hora_inicio) }} - {{ formatHour(c.hora_fin) }}
                </span>
                <span class="text-[10px] text-slate-500 font-mono">
                  {{ c.fecha?.substring(0, 10) }}
                </span>
              </div>
            </div>

            <!-- Popover de Acciones (···) -->
            <ActionPopover>
              <template #default="{ close }">
                <template v-if="c.estado_id === 1 || c.estado_id === 2">
                  <ActionPopoverItem
                    icon="user-check"
                    label="Atender paciente"
                    @click="openAtenderModal(c); close()"
                  />
                </template>
                <template v-else-if="c.estado_id === 3 && c.atencion">
                  <ActionPopoverItem
                    icon="clipboard"
                    label="Ver informe médico"
                    @click="openDetalleModal(c.atencion); close()"
                  />
                </template>
                <template v-else>
                  <div class="px-3 py-2 text-[11px] text-slate-400 italic">
                    Sin acciones disponibles
                  </div>
                </template>
              </template>
            </ActionPopover>
          </div>

          <!-- Paciente y Motivo -->
          <div class="bg-slate-50/70 p-2.5 rounded-lg border border-slate-100 space-y-1.5 text-[11px]">
            <div>
              <span class="text-slate-400 block text-[10px]">Paciente:</span>
              <span class="font-bold text-slate-900 block truncate">{{ c.paciente?.persona?.nombre_completo }}</span>
              <span class="text-[10px] font-mono text-slate-500">CI: {{ c.paciente?.persona?.ci }}</span>
              <div v-if="c.paciente?.antecedentes?.length" class="mt-1">
                <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                  {{ c.paciente.antecedentes[0].descripcion }}
                </span>
              </div>
            </div>

            <div class="pt-1 border-t border-slate-200/50">
              <span class="text-slate-400 block text-[10px]">Médico / Especialidad:</span>
              <span class="font-medium text-slate-800 block truncate">Dr(a). {{ c.medico_especialidad?.medico?.persona?.nombre_completo }}</span>
              <span class="text-[10px] text-slate-500">{{ c.medico_especialidad?.especialidad?.nombre }}</span>
            </div>

            <div v-if="c.motivo_consulta" class="pt-1 border-t border-slate-200/50">
              <span class="text-slate-400 block text-[10px]">Motivo de Consulta:</span>
              <span class="text-slate-700 text-[11px]">{{ c.motivo_consulta }}</span>
            </div>
          </div>

          <!-- Pie: Estado y Botón Rápido -->
          <div class="flex items-center justify-between pt-1 border-t border-slate-100 text-xs">
            <div>
              <span
                v-if="c.estado_id === 3"
                class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200"
              >
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                Atendida
              </span>
              <span
                v-else-if="c.estado_id === 4"
                class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-rose-50 text-rose-700 border border-rose-200"
              >
                Cancelada
              </span>
              <span
                v-else
                class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-blue-50 text-blue-700 border border-blue-200"
              >
                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                Pendiente
              </span>
            </div>

            <!-- Botón de acción directa si está pendiente -->
            <button
              v-if="c.estado_id === 1 || c.estado_id === 2"
              type="button"
              @click="openAtenderModal(c)"
              class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs transition cursor-pointer"
            >
              <AppIcon name="user-check" class="w-3.5 h-3.5" />
              <span>Atender</span>
            </button>
            <button
              v-else-if="c.estado_id === 3 && c.atencion"
              type="button"
              @click="openDetalleModal(c.atencion)"
              class="inline-flex items-center gap-1 px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-medium transition cursor-pointer"
            >
              <AppIcon name="clipboard" class="w-3.5 h-3.5" />
              <span>Informe</span>
            </button>
          </div>
        </div>

        <div v-if="citas.data.length === 0" class="bg-white rounded-xl border border-slate-200 p-8 text-center text-xs text-slate-400">
          No hay pacientes citados para atender en la fecha seleccionada.
        </div>
      </div>

      <!-- Vista Escritorio: Tabla de Cola de Pacientes -->
      <div class="hidden md:block bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-100/80 border-b border-slate-200 text-slate-700 font-semibold uppercase text-[11px] tracking-wider">
              <th class="py-2.5 px-3 text-center w-12 text-slate-500 font-mono">#</th>
              <th class="py-2.5 px-3">Horario</th>
              <th class="py-2.5 px-3">Paciente</th>
              <th class="py-2.5 px-3">Médico / Especialidad</th>
              <th class="py-2.5 px-3">Motivo Consulta</th>
              <th class="py-2.5 px-3 text-center">Estado</th>
              <th class="py-2.5 px-3 text-right w-16">Acción</th>
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
              <!-- Horario -->
              <td class="py-2.5 px-3 whitespace-nowrap">
                <span class="font-mono font-bold text-slate-900 block text-xs">
                  {{ formatHour(c.hora_inicio) }} - {{ formatHour(c.hora_fin) }}
                </span>
                <span class="text-[10px] text-slate-500 font-mono">
                  {{ c.fecha?.substring(0, 10) }}
                </span>
              </td>

              <!-- Paciente y Antecedentes -->
              <td class="py-2.5 px-3">
                <span class="font-semibold text-slate-900 block">{{ c.paciente?.persona?.nombre_completo }}</span>
                <span class="text-[11px] text-slate-500 font-mono">CI: {{ c.paciente?.persona?.ci }}</span>
                <div v-if="c.paciente?.antecedentes?.length" class="mt-0.5">
                  <span class="inline-block px-1.5 py-0.2 rounded text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                    {{ c.paciente.antecedentes[0].descripcion }}
                  </span>
                </div>
              </td>

              <!-- Médico / Especialidad -->
              <td class="py-2.5 px-3">
                <span class="font-medium text-slate-800 block">Dr(a). {{ c.medico_especialidad?.medico?.persona?.nombre_completo }}</span>
                <span class="text-[11px] text-slate-500">{{ c.medico_especialidad?.especialidad?.nombre }}</span>
              </td>

              <!-- Motivo Consulta -->
              <td class="py-2.5 px-3 text-slate-700 max-w-[220px]">
                {{ c.motivo_consulta }}
              </td>

              <!-- Estado -->
              <td class="py-2.5 px-3 text-center">
                <span
                  v-if="c.estado_id === 3"
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                  Atendida
                </span>
                <span
                  v-else-if="c.estado_id === 4"
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-rose-50 text-rose-700 border border-rose-200"
                >
                  Cancelada
                </span>
                <span
                  v-else
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-blue-50 text-blue-700 border border-blue-200"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                  Pendiente
                </span>
              </td>

              <!-- Acción con Popover -->
              <td class="py-2.5 px-3 text-right">
                <ActionPopover>
                  <template #default="{ close }">
                    <template v-if="c.estado_id === 1 || c.estado_id === 2">
                      <ActionPopoverItem
                        icon="user-check"
                        label="Atender paciente"
                        @click="openAtenderModal(c); close()"
                      />
                    </template>
                    <template v-else-if="c.estado_id === 3 && c.atencion">
                      <ActionPopoverItem
                        icon="clipboard"
                        label="Ver informe médico"
                        @click="openDetalleModal(c.atencion); close()"
                      />
                    </template>
                    <template v-else>
                      <div class="px-3 py-2 text-[11px] text-slate-400 italic">
                        Sin acciones disponibles
                      </div>
                    </template>
                  </template>
                </ActionPopover>
              </td>
            </tr>

            <tr v-if="citas.data.length === 0">
              <td colspan="7" class="py-8 text-center text-xs text-slate-400">
                No hay pacientes citados para atender en la fecha seleccionada.
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

    <!-- Modal Registrar Atención -->
    <ModalRegistrarAtencion
      :show="showAtenderModal"
      :cita="citaSelected"
      @close="showAtenderModal = false"
    />

    <!-- Modal Detalle Informe -->
    <ModalDetalleAtencion
      :show="showDetalleModal"
      :atencion="atencionSelected"
      @close="showDetalleModal = false"
    />
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppIcon from '@/Components/AppIcon.vue';
import ActionPopover from '@/Components/ActionPopover.vue';
import ActionPopoverItem from '@/Components/ActionPopoverItem.vue';
import ModalRegistrarAtencion from './Partials/ModalRegistrarAtencion.vue';
import ModalDetalleAtencion from './Partials/ModalDetalleAtencion.vue';

const props = defineProps({
  citas: Object,
  medicos: Array,
  filters: Object,
});

const selectedFecha = ref(props.filters?.fecha || new Date().toISOString().substring(0, 10));
const selectedEstado = ref(props.filters?.estado_id || '');

const handleFilter = () => {
  router.get('/atenciones', {
    fecha: selectedFecha.value,
    estado_id: selectedEstado.value,
  }, { preserveState: true, replace: true });
};

const setFechaTodas = () => {
  selectedFecha.value = 'todas';
  handleFilter();
};

const formatHour = (hourStr) => {
  if (!hourStr) return '';
  return hourStr.substring(0, 5);
};

// Modales
const showAtenderModal = ref(false);
const showDetalleModal = ref(false);
const citaSelected = ref(null);
const atencionSelected = ref(null);

const openAtenderModal = (c) => {
  citaSelected.value = c;
  showAtenderModal.value = true;
};

const openDetalleModal = (at) => {
  atencionSelected.value = at;
  showDetalleModal.value = true;
};
</script>

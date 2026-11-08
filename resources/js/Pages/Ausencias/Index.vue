<template>
  <AppLayout>
    <template #header>
      <span>Ausencias y Excepciones Médicas</span>
    </template>

    <div class="space-y-4">
      <!-- Barra Superior: Filtros y Botón de Registro -->
      <div class="bg-white p-3.5 rounded-xl border border-slate-200/80 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2.5 w-full sm:w-auto">
          <!-- Filtro Médico -->
          <select
            v-model="selectedMedicoId"
            @change="handleFilter"
            class="px-3 py-1.5 text-xs rounded-lg border border-slate-300 focus:border-slate-600 outline-none min-w-[200px]"
          >
            <option value="">Todos los médicos</option>
            <option v-for="m in medicos" :key="m.id" :value="m.id">
              Dr(a). {{ m.persona?.nombre_completo }}
            </option>
          </select>

          <!-- Filtro Vigencia -->
          <select
            v-model="selectedVigente"
            @change="handleFilter"
            class="px-3 py-1.5 text-xs rounded-lg border border-slate-300 focus:border-slate-600 outline-none"
          >
            <option value="">Todas las ausencias</option>
            <option value="1">Solo vigentes / futuras</option>
            <option value="0">Históricas / pasadas</option>
          </select>
        </div>

        <button
          type="button"
          @click="openCreateModal"
          class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-semibold shadow-xs transition cursor-pointer"
        >
          <AppIcon name="plus" class="w-3.5 h-3.5" />
          <span>Registrar Ausencia</span>
        </button>
      </div>

      <!-- Vista Móvil: Tarjetas de Ausencias -->
      <div class="block md:hidden space-y-3">
        <div
          v-for="(a, index) in ausencias.data"
          :key="a.id"
          class="bg-white rounded-xl border border-slate-200/80 p-3.5 shadow-xs space-y-2.5"
        >
          <div class="flex items-start justify-between gap-2">
            <div class="flex items-center gap-2 min-w-0">
              <span class="w-6 h-6 rounded-md bg-slate-100 text-slate-600 font-mono text-[11px] font-bold flex items-center justify-center shrink-0">
                #{{ ((ausencias.current_page || 1) - 1) * (ausencias.per_page || 15) + index + 1 }}
              </span>
              <div class="min-w-0">
                <span class="font-medium text-slate-900 text-xs block truncate">
                  Dr(a). {{ a.medico?.persona?.nombre_completo }}
                </span>
                <span class="text-[11px] font-mono text-slate-500">
                  Mat: {{ a.medico?.matricula_profesional }}
                </span>
              </div>
            </div>

            <!-- Popover de Acciones (···) -->
            <ActionPopover>
              <template #default="{ close }">
                <ActionPopoverItem
                  icon="trash"
                  label="Cancelar / Eliminar ausencia"
                  danger
                  @click="confirmDelete(a); close()"
                />
              </template>
            </ActionPopover>
          </div>

          <!-- Período y Motivo -->
          <div class="bg-slate-50/70 p-2.5 rounded-lg border border-slate-100 space-y-1.5 text-[11px]">
            <div>
              <span class="text-slate-400 block text-[10px]">Período de Ausencia:</span>
              <span class="font-mono font-semibold text-slate-800 text-xs block">
                {{ formatDate(a.fecha_inicio) }} &rarr; {{ formatDate(a.fecha_fin) }}
              </span>
              <span class="text-[10px] text-slate-500">
                {{ calcularDias(a.fecha_inicio, a.fecha_fin) }} día(s)
              </span>
            </div>

            <div class="pt-1 border-t border-slate-200/50">
              <span class="text-slate-400 block text-[10px]">Motivo:</span>
              <span class="text-slate-700 text-[11px]">{{ a.motivo }}</span>
            </div>
          </div>

          <!-- Pie: Estado de la Ausencia -->
          <div class="flex items-center justify-between pt-1 border-t border-slate-100 text-xs">
            <span class="text-slate-500 text-[11px]">Vigencia:</span>
            <span
              v-if="esVigente(a.fecha_fin)"
              class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-amber-50 text-amber-700 border border-amber-200"
            >
              <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
              Vigente
            </span>
            <span
              v-else
              class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-600 border border-slate-200"
            >
              Concluida
            </span>
          </div>
        </div>

        <div v-if="ausencias.data.length === 0" class="bg-white rounded-xl border border-slate-200 p-8 text-center text-xs text-slate-400">
          No se encontraron ausencias médicas registradas con los filtros seleccionados.
        </div>
      </div>

      <!-- Vista Escritorio: Tabla de Ausencias -->
      <div class="hidden md:block bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-100/80 border-b border-slate-200 text-slate-700 font-semibold uppercase text-[11px] tracking-wider">
              <th class="py-2.5 px-3 text-center w-12 text-slate-500 font-mono">#</th>
              <th class="py-2.5 px-3">Médico</th>
              <th class="py-2.5 px-3">Período de Ausencia</th>
              <th class="py-2.5 px-3">Motivo / Justificación</th>
              <th class="py-2.5 px-3 text-center">Estado</th>
              <th class="py-2.5 px-3 text-right w-16">Acción</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="(a, index) in ausencias.data"
              :key="a.id"
              class="hover:bg-slate-50 transition"
            >
              <td class="py-2.5 px-3 text-center font-mono text-[11px] text-slate-400 font-semibold">
                {{ ((ausencias.current_page || 1) - 1) * (ausencias.per_page || 15) + index + 1 }}
              </td>
              <td class="py-2.5 px-3">
                <span class="font-medium text-slate-900 block">Dr(a). {{ a.medico?.persona?.nombre_completo }}</span>
                <span class="text-[11px] text-slate-500 font-mono">Matrícula: {{ a.medico?.matricula_profesional }}</span>
              </td>
              <td class="py-2.5 px-3">
                <span class="font-mono font-semibold text-slate-800 block text-[12px]">
                  {{ formatDate(a.fecha_inicio) }} &rarr; {{ formatDate(a.fecha_fin) }}
                </span>
                <span class="text-[10px] text-slate-500">
                  {{ calcularDias(a.fecha_inicio, a.fecha_fin) }} día(s)
                </span>
              </td>
              <td class="py-2.5 px-3 text-slate-700 max-w-[280px]">
                {{ a.motivo }}
              </td>
              <td class="py-2.5 px-3 text-center">
                <span
                  v-if="esVigente(a.fecha_fin)"
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-amber-50 text-amber-700 border border-amber-200"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                  Vigente
                </span>
                <span
                  v-else
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-600 border border-slate-200"
                >
                  Concluida
                </span>
              </td>
              <td class="py-2.5 px-3 text-right">
                <ActionPopover>
                  <template #default="{ close }">
                    <ActionPopoverItem
                      icon="trash"
                      label="Cancelar ausencia"
                      danger
                      @click="confirmDelete(a); close()"
                    />
                  </template>
                </ActionPopover>
              </td>
            </tr>

            <tr v-if="ausencias.data.length === 0">
              <td colspan="6" class="py-8 text-center text-xs text-slate-400">
                No se encontraron ausencias médicas registradas con los filtros seleccionados.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginación -->
      <div v-if="ausencias.links?.length > 3" class="flex justify-end gap-1 text-xs">
        <Link
          v-for="(link, i) in ausencias.links"
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

    <!-- Modal Formulario: Registrar Ausencia -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        <div class="px-5 py-3.5 bg-slate-100 border-b border-slate-200 flex items-center justify-between">
          <h3 class="text-sm font-semibold text-slate-800">
            Registrar Ausencia Médica
          </h3>
          <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">&times;</button>
        </div>

        <form @submit.prevent="saveAusencia" class="p-5 space-y-3.5 text-xs">
          <!-- Médico -->
          <div>
            <label class="block text-[11px] font-medium text-slate-700 mb-1">Médico *</label>
            <select
              v-model="form.medico_id"
              required
              class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:border-slate-600 outline-none"
            >
              <option v-for="m in medicos" :key="m.id" :value="m.id">
                Dr(a). {{ m.persona?.nombre_completo }}
              </option>
            </select>
          </div>

          <!-- Rango de Fechas -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] font-medium text-slate-700 mb-1">Fecha de Inicio *</label>
              <input
                v-model="form.fecha_inicio"
                type="date"
                required
                class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:border-slate-600 outline-none"
              />
              <p v-if="form.errors.fecha_inicio" class="text-[10px] text-rose-600 mt-0.5">{{ form.errors.fecha_inicio }}</p>
            </div>
            <div>
              <label class="block text-[11px] font-medium text-slate-700 mb-1">Fecha de Fin *</label>
              <input
                v-model="form.fecha_fin"
                type="date"
                required
                class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:border-slate-600 outline-none"
              />
              <p v-if="form.errors.fecha_fin" class="text-[10px] text-rose-600 mt-0.5">{{ form.errors.fecha_fin }}</p>
            </div>
          </div>

          <!-- Motivo -->
          <div>
            <label class="block text-[11px] font-medium text-slate-700 mb-1">Motivo / Justificación *</label>
            <textarea
              v-model="form.motivo"
              required
              rows="3"
              placeholder="Indica el motivo de la licencia, vacaciones o congreso..."
              class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:border-slate-600 outline-none resize-none"
            ></textarea>
            <p v-if="form.errors.motivo" class="text-[10px] text-rose-600 mt-0.5">{{ form.errors.motivo }}</p>
          </div>

          <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
            <button
              type="button"
              @click="showModal = false"
              class="px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-lg border border-slate-300 cursor-pointer"
            >
              Cancelar
            </button>
            <button
              type="submit"
              class="px-3.5 py-1.5 text-xs font-semibold text-white bg-slate-900 hover:bg-slate-800 rounded-lg shadow-xs cursor-pointer"
            >
              Registrar Ausencia
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Confirmación de Eliminación -->
    <DialogModal
      :show="showDeleteModal"
      title="Cancelar ausencia médica"
      :message="`¿Estás seguro de que deseas cancelar la ausencia médica registrada? El médico volverá a estar disponible para citas en esas fechas.`"
      confirm-text="Sí, cancelar ausencia"
      cancel-text="Volver"
      type="danger"
      @confirm="executeDelete"
      @close="showDeleteModal = false"
    />
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppIcon from '@/Components/AppIcon.vue';
import DialogModal from '@/Components/DialogModal.vue';
import ActionPopover from '@/Components/ActionPopover.vue';
import ActionPopoverItem from '@/Components/ActionPopoverItem.vue';

const props = defineProps({
  ausencias: Object,
  medicos: Array,
  filters: Object,
});

const selectedMedicoId = ref(props.filters?.medico_id || '');
const selectedVigente = ref(props.filters?.vigente ?? '');

const handleFilter = () => {
  router.get('/ausencias', {
    medico_id: selectedMedicoId.value,
    vigente: selectedVigente.value,
  }, { preserveState: true, replace: true });
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleDateString('es-BO', { timeZone: 'UTC', year: 'numeric', month: 'short', day: 'numeric' });
};

const esVigente = (fechaFin) => {
  if (!fechaFin) return false;
  return new Date(fechaFin) >= new Date(new Date().toISOString().substring(0, 10));
};

const calcularDias = (inicio, fin) => {
  if (!inicio || !fin) return 1;
  const d1 = new Date(inicio);
  const d2 = new Date(fin);
  const diff = Math.round((d2 - d1) / (1000 * 60 * 60 * 24)) + 1;
  return diff > 0 ? diff : 1;
};

// Modal Form
const showModal = ref(false);
const form = useForm({
  medico_id: props.medicos[0]?.id || '',
  fecha_inicio: '',
  fecha_fin: '',
  motivo: '',
});

const openCreateModal = () => {
  form.reset();
  form.clearErrors();
  form.medico_id = props.medicos[0]?.id || '';
  showModal.value = true;
};

const saveAusencia = () => {
  form.post('/ausencias', {
    onSuccess: () => { showModal.value = false; },
  });
};

// Modal Delete
const showDeleteModal = ref(false);
const ausenciaToDelete = ref(null);

const confirmDelete = (a) => {
  ausenciaToDelete.value = a;
  showDeleteModal.value = true;
};

const executeDelete = () => {
  if (!ausenciaToDelete.value) return;
  router.delete(`/ausencias/${ausenciaToDelete.value.id}`, {
    onFinish: () => {
      showDeleteModal.value = false;
      ausenciaToDelete.value = null;
    },
  });
};
</script>

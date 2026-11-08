<template>
  <AppLayout>
    <template #header>
      <span>Médicos</span>
    </template>

    <div class="space-y-4">
      <!-- Barra Superior: Filtros y Registro -->
      <div class="bg-white p-3.5 rounded border border-slate-200 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2.5 w-full sm:w-auto">
          <!-- Buscador -->
          <div class="relative w-full sm:w-64">
            <input 
              v-model="search" 
              type="text" 
              placeholder="Buscar por nombre, CI, matrícula..." 
              class="w-full pl-8 pr-3 py-1.5 text-xs rounded border border-slate-300 focus:border-blue-600 outline-none"
              @keyup.enter="handleSearch"
            />
            <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>

          <!-- Filtro Especialidad -->
          <select 
            v-model="selectedEsp" 
            class="px-2.5 py-1.5 text-xs rounded border border-slate-300 focus:border-blue-600 outline-none"
            @change="handleSearch"
          >
            <option value="">Todas las especialidades</option>
            <option v-for="esp in especialidades" :key="esp.id" :value="esp.id">{{ esp.nombre }}</option>
          </select>

          <!-- Filtro Estado -->
          <select 
            v-model="selectedEstado" 
            class="px-2.5 py-1.5 text-xs rounded border border-slate-300 focus:border-blue-600 outline-none"
            @change="handleSearch"
          >
            <option value="">Todos los estados</option>
            <option value="1">Activos</option>
            <option value="0">Inactivos</option>
          </select>
        </div>

        <button 
          @click="openCreateModal"
          class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-semibold shadow-xs transition cursor-pointer"
        >
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
          </svg>
          <span>Registrar Médico</span>
        </button>
      </div>

      <!-- Vista Móvil: Tarjetas compactas con Toggle y Popover -->
      <div class="block md:hidden space-y-3">
        <div 
          v-for="(m, index) in medicos.data" 
          :key="m.id"
          class="bg-white rounded-xl border border-slate-200/80 p-3.5 shadow-xs space-y-2.5"
        >
          <div class="flex items-start justify-between gap-2">
            <div class="flex items-center gap-2.5 min-w-0">
              <span class="w-6 h-6 rounded-md bg-slate-100 text-slate-600 font-mono text-[11px] font-bold flex items-center justify-center shrink-0">
                #{{ ((medicos.current_page || 1) - 1) * (medicos.per_page || 15) + index + 1 }}
              </span>
              <div class="min-w-0">
                <Link :href="`/medicos/${m.id}`" class="block truncate font-bold text-slate-900 text-xs hover:text-blue-700 transition">
                  Dr(a). {{ m.persona?.nombre_completo }}
                </Link>
                <span class="text-[11px] font-mono text-blue-700 font-medium">
                  Mat: {{ m.matricula_profesional }} &bull; CI: {{ m.persona?.ci }}
                </span>
              </div>
            </div>

            <!-- Popover de Acciones (···) -->
            <ActionPopover>
              <template #default="{ close }">
                <ActionPopoverItem
                  :href="`/medicos/${m.id}`"
                  icon="eye"
                  label="Ver perfil médico"
                  @click="close"
                />
                <ActionPopoverItem
                  icon="pencil"
                  label="Editar médico"
                  @click="openEditModal(m); close()"
                />
                <ActionPopoverItem
                  :icon="m.estado ? 'ban' : 'check'"
                  :label="m.estado ? 'Desactivar médico' : 'Activar médico'"
                  @click="confirmToggle(m); close()"
                />
                <div class="border-t border-slate-100 my-0.5"></div>
                <ActionPopoverItem
                  icon="trash"
                  label="Eliminar médico"
                  danger
                  @click="confirmDelete(m); close()"
                />
              </template>
            </ActionPopover>
          </div>

          <!-- Especialidades Asignadas -->
          <div class="bg-slate-50/70 p-2.5 rounded-lg border border-slate-100 space-y-1.5 text-[11px]">
            <div>
              <span class="text-slate-400 block text-[10px]">Especialidades:</span>
              <div class="flex flex-wrap gap-1 mt-0.5">
                <span 
                  v-for="esp in m.especialidades" 
                  :key="esp.id"
                  class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-white text-slate-700 border border-slate-200"
                >
                  {{ esp.nombre }} (Bs. {{ esp.pivot.precio_consulta }})
                </span>
                <span v-if="m.especialidades.length === 0" class="text-slate-400 italic text-[10px]">
                  Sin especialidades asignadas
                </span>
              </div>
            </div>
            <div class="flex items-center justify-between pt-1 border-t border-slate-200/50">
              <span class="text-slate-400 text-[10px]">Contacto:</span>
              <span class="font-mono text-slate-700 text-[11px]">{{ m.persona?.telefono || 'Sin teléfono' }}</span>
            </div>
          </div>

          <!-- Toggle de Estado Móvil -->
          <div class="flex items-center justify-between pt-1 border-t border-slate-100 text-xs">
            <span class="text-slate-500 text-[11px]">Estado del médico:</span>
            <div class="flex items-center gap-2">
              <ToggleSwitch
                :model-value="Boolean(m.estado)"
                size="sm"
                @change="confirmToggle(m)"
              />
              <span class="text-[11px] font-semibold" :class="m.estado ? 'text-slate-800' : 'text-slate-400'">
                {{ m.estado ? 'Activo' : 'Inactivo' }}
              </span>
            </div>
          </div>
        </div>

        <div v-if="medicos.data.length === 0" class="bg-white rounded-xl border border-slate-200 p-8 text-center text-xs text-slate-500">
          No se encontraron médicos registrados.
        </div>
      </div>

      <!-- Vista Escritorio: Tabla con Toggle y Popover -->
      <div class="hidden md:block bg-white rounded border border-slate-200 shadow-xs overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-100/80 border-b border-slate-200 text-slate-700 font-semibold uppercase text-[11px] tracking-wider">
              <th class="py-2.5 px-3 text-center w-12 text-slate-500 font-mono">#</th>
              <th class="py-2.5 px-3">Médico / Matrícula</th>
              <th class="py-2.5 px-3">Documento (CI)</th>
              <th class="py-2.5 px-3">Especialidades Asignadas</th>
              <th class="py-2.5 px-3">Contacto</th>
              <th class="py-2.5 px-3 text-center w-28">Estado</th>
              <th class="py-2.5 px-3 text-right w-16">Acción</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr 
              v-for="(m, index) in medicos.data" 
              :key="m.id"
              class="hover:bg-slate-50 transition"
            >
              <td class="py-2.5 px-3 text-center font-mono text-[11px] text-slate-400 font-semibold">
                {{ ((medicos.current_page || 1) - 1) * (medicos.per_page || 15) + index + 1 }}
              </td>
              <td class="py-2.5 px-3">
                <span class="font-semibold text-slate-900 block">Dr(a). {{ m.persona?.nombre_completo }}</span>
                <span class="text-[11px] text-blue-700 font-mono font-medium">Mat: {{ m.matricula_profesional }}</span>
              </td>
              <td class="py-2.5 px-3 font-mono text-slate-600">
                {{ m.persona?.ci }}
              </td>
              <td class="py-2.5 px-3">
                <div class="flex flex-wrap gap-1">
                  <span 
                    v-for="esp in m.especialidades" 
                    :key="esp.id"
                    class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700 border border-slate-200"
                  >
                    {{ esp.nombre }} (Bs. {{ esp.pivot.precio_consulta }})
                  </span>
                  <span v-if="m.especialidades.length === 0" class="text-slate-400 italic text-[11px]">
                    Sin especialidades
                  </span>
                </div>
              </td>
              <td class="py-2.5 px-3 text-slate-600">
                <span class="block">{{ m.persona?.telefono || 'Sin teléfono' }}</span>
                <span class="text-[11px] text-slate-400 block truncate max-w-xs">{{ m.persona?.direccion || 'Sin dirección' }}</span>
              </td>
              <td class="py-2.5 px-3 text-center">
                <div class="flex items-center justify-center gap-1.5">
                  <ToggleSwitch
                    :model-value="Boolean(m.estado)"
                    size="sm"
                    @change="confirmToggle(m)"
                  />
                  <span class="text-[11px] font-medium" :class="m.estado ? 'text-slate-800' : 'text-slate-400'">
                    {{ m.estado ? 'Activo' : 'Inactivo' }}
                  </span>
                </div>
              </td>
              <td class="py-2.5 px-3 text-right">
                <div class="inline-flex items-center gap-1 justify-end">
                  <Link 
                    :href="`/medicos/${m.id}`"
                    class="w-7 h-7 flex items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition cursor-pointer"
                    title="Ver perfil completo"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                  </Link>
                  <button 
                    type="button"
                    @click="openEditModal(m)" 
                    class="w-7 h-7 flex items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition cursor-pointer"
                    title="Editar médico"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                  </button>
                  <button 
                    type="button"
                    @click="confirmDelete(m)" 
                    class="w-7 h-7 flex items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-400 hover:text-rose-600 hover:bg-rose-50 hover:border-rose-200 transition cursor-pointer"
                    title="Eliminar médico"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="medicos.data.length === 0">
              <td colspan="7" class="py-8 text-center text-xs text-slate-500">
                No se encontraron médicos registrados.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginación -->
      <div v-if="medicos.links.length > 3" class="flex justify-end gap-1 text-xs">
        <Link 
          v-for="(link, i) in medicos.links" 
          :key="i"
          :href="link.url || '#'"
          :class="[
            'px-2.5 py-1 rounded border',
            link.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50',
            !link.url ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer'
          ]"
          v-html="link.label"
        />
      </div>
    </div>

    <!-- Modal Formulario Médico -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white rounded-lg shadow-xl border border-slate-200 w-full max-w-xl overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        <div class="px-5 py-3.5 bg-slate-100 border-b border-slate-200 flex items-center justify-between">
          <h3 class="text-sm font-semibold text-slate-800">
            {{ isEditing ? 'Editar Datos del Médico' : 'Registrar Nuevo Médico' }}
          </h3>
          <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">&times;</button>
        </div>

        <form @submit.prevent="saveMedico" class="p-5 space-y-3.5 max-h-[85vh] overflow-y-auto">
          <!-- Identidad Personal -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] font-medium text-slate-700 mb-1">CI / Documento *</label>
              <input v-model="form.ci" type="text" required placeholder="Número de CI" class="w-full px-2.5 py-1.5 text-xs rounded border border-slate-300 focus:border-blue-600 outline-none" />
              <p v-if="form.errors.ci" class="text-[10px] text-rose-600 mt-0.5">{{ form.errors.ci }}</p>
            </div>
            <div>
              <label class="block text-[11px] font-medium text-slate-700 mb-1">Matrícula Profesional *</label>
              <input v-model="form.matricula_profesional" type="text" required placeholder="ej. MP-4819" class="w-full px-2.5 py-1.5 text-xs rounded border border-slate-300 focus:border-blue-600 outline-none" />
              <p v-if="form.errors.matricula_profesional" class="text-[10px] text-rose-600 mt-0.5">{{ form.errors.matricula_profesional }}</p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] font-medium text-slate-700 mb-1">Nombres *</label>
              <input v-model="form.nombres" type="text" required class="w-full px-2.5 py-1.5 text-xs rounded border border-slate-300 focus:border-blue-600 outline-none" />
            </div>
            <div>
              <label class="block text-[11px] font-medium text-slate-700 mb-1">Apellidos *</label>
              <input v-model="form.apellidos" type="text" required class="w-full px-2.5 py-1.5 text-xs rounded border border-slate-300 focus:border-blue-600 outline-none" />
            </div>
          </div>

          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="block text-[11px] font-medium text-slate-700 mb-1">Fecha Nacimiento *</label>
              <input v-model="form.fecha_nacimiento" type="date" required class="w-full px-2.5 py-1.5 text-xs rounded border border-slate-300 focus:border-blue-600 outline-none" />
            </div>
            <div>
              <label class="block text-[11px] font-medium text-slate-700 mb-1">Género *</label>
              <select v-model="form.genero" required class="w-full px-2.5 py-1.5 text-xs rounded border border-slate-300 focus:border-blue-600 outline-none">
                <option value="M">Masculino (M)</option>
                <option value="F">Femenino (F)</option>
                <option value="O">Otro (O)</option>
              </select>
            </div>
            <div>
              <label class="block text-[11px] font-medium text-slate-700 mb-1">Teléfono</label>
              <input v-model="form.telefono" type="text" placeholder="Celular" class="w-full px-2.5 py-1.5 text-xs rounded border border-slate-300 focus:border-blue-600 outline-none" />
            </div>
          </div>

          <div>
            <label class="block text-[11px] font-medium text-slate-700 mb-1">Dirección de Consultorio / Domicilio</label>
            <input v-model="form.direccion" type="text" class="w-full px-2.5 py-1.5 text-xs rounded border border-slate-300 focus:border-blue-600 outline-none" />
          </div>

          <!-- Selección de Especialidades y Aranceles -->
          <hr class="border-slate-200 my-2" />
          <div>
            <div class="flex items-center justify-between mb-1.5">
              <label class="block text-xs font-semibold text-slate-800">Especialidades Médicas y Arancel de Consulta</label>
              <span class="text-[10px] text-slate-500">Selecciona al menos una</span>
            </div>

            <div class="space-y-2 border border-slate-200 rounded p-2.5 bg-slate-50 max-h-48 overflow-y-auto">
              <div 
                v-for="esp in especialidades" 
                :key="esp.id"
                class="flex items-center justify-between gap-3 p-1.5 bg-white rounded border border-slate-200 text-xs"
              >
                <label class="flex items-center gap-2 cursor-pointer select-none">
                  <input 
                    type="checkbox" 
                    :checked="isEspecialidadSelected(esp.id)" 
                    @change="toggleEspecialidad(esp.id)"
                    class="rounded text-blue-600 focus:ring-blue-500"
                  />
                  <span class="font-medium text-slate-800">{{ esp.nombre }}</span>
                </label>

                <div v-if="isEspecialidadSelected(esp.id)" class="flex items-center gap-1.5">
                  <span class="text-[11px] text-slate-500">Precio (Bs.):</span>
                  <input 
                    type="number" 
                    step="0.01" 
                    min="0"
                    :value="getEspecialidadPrice(esp.id)" 
                    @input="updateEspecialidadPrice(esp.id, $event.target.value)"
                    placeholder="0.00"
                    class="w-20 px-2 py-1 text-xs font-mono font-medium rounded border border-slate-300 focus:border-blue-600 outline-none text-right"
                  />
                </div>
              </div>
            </div>
            <p v-if="form.errors.especialidades" class="text-[10px] text-rose-600 mt-1">{{ form.errors.especialidades }}</p>
          </div>

          <div class="px-5 py-3 bg-slate-50 border-t border-slate-200 -mx-5 -mb-5 flex justify-end gap-2.5 mt-4">
            <button type="button" @click="showModal = false" class="px-4 py-1.5 text-xs text-slate-700 bg-white border border-slate-300 rounded hover:bg-slate-100 cursor-pointer">
              Cancelar
            </button>
            <button type="submit" :disabled="form.processing" class="px-4 py-1.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded shadow-xs cursor-pointer">
              {{ isEditing ? 'Guardar Cambios' : 'Registrar Médico' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Diálogo Modal: Alternar Estado (Activar/Desactivar) -->
    <DialogModal
      :show="showConfirmModal"
      :title="medicoToToggle?.estado ? 'Desactivar Médico' : 'Activar Médico'"
      :message="medicoToToggle?.estado 
        ? `¿Confirmas la desactivación del Dr(a). ${medicoToToggle?.persona?.nombre_completo}?` 
        : `¿Confirmas la reactivación del Dr(a). ${medicoToToggle?.persona?.nombre_completo}?`"
      confirm-text="Confirmar"
      cancel-text="Cancelar"
      :type="medicoToToggle?.estado ? 'warning' : 'brand'"
      @confirm="executeToggle"
      @close="showConfirmModal = false"
    />

    <!-- Diálogo Modal: Eliminar Médico -->
    <DialogModal
      :show="showDeleteModal"
      title="Eliminar Médico"
      :message="`¿Estás seguro de que deseas eliminar al Dr(a). ${medicoToDelete?.persona?.nombre_completo}?`"
      confirm-text="Eliminar"
      cancel-text="Cancelar"
      type="danger"
      @confirm="executeDelete"
      @close="showDeleteModal = false"
    />
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DialogModal from '@/Components/DialogModal.vue';
import ToggleSwitch from '@/Components/ToggleSwitch.vue';
import ActionPopover from '@/Components/ActionPopover.vue';
import ActionPopoverItem from '@/Components/ActionPopoverItem.vue';

const props = defineProps({
  medicos: Object,
  especialidades: Array,
  filters: Object,
});

const search = ref(props.filters?.buscar || '');
const selectedEsp = ref(props.filters?.especialidad_id || '');
const selectedEstado = ref(props.filters?.estado ?? '');

const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const showConfirmModal = ref(false);
const medicoToToggle = ref(null);

const showDeleteModal = ref(false);
const medicoToDelete = ref(null);

const form = useForm({
  ci: '',
  nombres: '',
  apellidos: '',
  fecha_nacimiento: '',
  genero: 'M',
  telefono: '',
  direccion: '',
  matricula_profesional: '',
  estado: true,
  especialidades: [], // array de { id, precio }
});

const isEspecialidadSelected = (id) => {
  return form.especialidades.some(e => e.id === id);
};

const getEspecialidadPrice = (id) => {
  const item = form.especialidades.find(e => e.id === id);
  return item ? item.precio : 0;
};

const toggleEspecialidad = (id) => {
  const idx = form.especialidades.findIndex(e => e.id === id);
  if (idx > -1) {
    form.especialidades.splice(idx, 1);
  } else {
    form.especialidades.push({ id, precio: 0 });
  }
};

const updateEspecialidadPrice = (id, price) => {
  const item = form.especialidades.find(e => e.id === id);
  if (item) {
    item.precio = parseFloat(price) || 0;
  }
};

const handleSearch = () => {
  router.get('/medicos', {
    buscar: search.value,
    especialidad_id: selectedEsp.value,
    estado: selectedEstado.value,
  }, { preserveState: true });
};

const openCreateModal = () => {
  isEditing.value = false;
  editingId.value = null;
  form.reset();
  form.clearErrors();
  showModal.value = true;
};

const openEditModal = (m) => {
  isEditing.value = true;
  editingId.value = m.id;
  form.clearErrors();
  form.ci = m.persona?.ci || '';
  form.nombres = m.persona?.nombres || '';
  form.apellidos = m.persona?.apellidos || '';
  form.fecha_nacimiento = m.persona?.fecha_nacimiento ? m.persona.fecha_nacimiento.substring(0, 10) : '';
  form.genero = m.persona?.genero || 'M';
  form.telefono = m.persona?.telefono || '';
  form.direccion = m.persona?.direccion || '';
  form.matricula_profesional = m.matricula_profesional;
  form.estado = m.estado;
  form.especialidades = m.especialidades.map(esp => ({
    id: esp.id,
    precio: esp.pivot.precio_consulta || 0
  }));
  showModal.value = true;
};

const saveMedico = () => {
  if (isEditing.value) {
    form.put(`/medicos/${editingId.value}`, {
      onSuccess: () => { showModal.value = false; },
    });
  } else {
    form.post('/medicos', {
      onSuccess: () => { showModal.value = false; },
    });
  }
};

const confirmToggle = (m) => {
  medicoToToggle.value = m;
  showConfirmModal.value = true;
};

const executeToggle = () => {
  if (!medicoToToggle.value) return;
  router.delete(`/medicos/${medicoToToggle.value.id}`, {
    onFinish: () => {
      showConfirmModal.value = false;
      medicoToToggle.value = null;
    }
  });
};

const confirmDelete = (m) => {
  medicoToDelete.value = m;
  showDeleteModal.value = true;
};

const executeDelete = () => {
  if (!medicoToDelete.value) return;
  router.delete(`/medicos/${medicoToDelete.value.id}`, {
    onFinish: () => {
      showDeleteModal.value = false;
      medicoToDelete.value = null;
    }
  });
};
</script>

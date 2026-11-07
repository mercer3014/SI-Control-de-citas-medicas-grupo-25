<template>
  <AppLayout>
    <template #header>
      <span>Especialidades</span>
    </template>

    <div class="space-y-4">
      <!-- Barra Superior: Filtro y Registro -->
      <div class="bg-white p-3.5 rounded border border-slate-200 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2.5 w-full sm:w-auto">
          <!-- Buscador -->
          <div class="relative w-full sm:w-64">
            <input 
              v-model="search" 
              type="text" 
              placeholder="Buscar especialidad..." 
              class="w-full pl-8 pr-3 py-1.5 text-xs rounded border border-slate-300 focus:border-blue-600 outline-none"
              @keyup.enter="handleSearch"
            />
            <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>

          <!-- Filtro Estado -->
          <select 
            v-model="selectedEstado" 
            class="px-2.5 py-1.5 text-xs rounded border border-slate-300 focus:border-blue-600 outline-none"
            @change="handleSearch"
          >
            <option value="">Todos los estados</option>
            <option value="1">Activas</option>
            <option value="0">Inactivas</option>
          </select>
        </div>

        <button 
          @click="openCreateModal"
          class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-semibold shadow-xs transition cursor-pointer"
        >
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
          </svg>
          <span>Nueva Especialidad</span>
        </button>
      </div>

      <!-- Tabla de Especialidades -->
      <div class="bg-white rounded border border-slate-200 shadow-xs overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-100/80 border-b border-slate-200 text-slate-700 font-semibold uppercase text-[11px] tracking-wider">
              <th class="py-2.5 px-3 text-center w-12 text-slate-500 font-mono">#</th>
              <th class="py-2.5 px-3">Especialidad</th>
              <th class="py-2.5 px-3">Descripción</th>
              <th class="py-2.5 px-3 text-center">Médicos Asignados</th>
              <th class="py-2.5 px-3 text-center">Estado</th>
              <th class="py-2.5 px-3 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr 
              v-for="(esp, index) in especialidades.data" 
              :key="esp.id"
              class="hover:bg-slate-50 transition"
            >
              <td class="py-2.5 px-3 text-center font-mono text-[11px] text-slate-400 font-semibold">
                {{ ((especialidades.current_page || 1) - 1) * (especialidades.per_page || 15) + index + 1 }}
              </td>
              <td class="py-2.5 px-3 font-semibold text-slate-800">
                {{ esp.nombre }}
              </td>
              <td class="py-2.5 px-3 text-slate-600 max-w-md">
                {{ esp.descripcion || 'Sin descripción' }}
              </td>
              <td class="py-2.5 px-3 text-center font-mono font-medium text-slate-700">
                {{ esp.medicos_count }} profesional(es)
              </td>
              <td class="py-2.5 px-3 text-center">
                <span class="inline-flex items-center gap-1.5 text-xs text-slate-700">
                  <span class="w-2 h-2 rounded-full" :class="esp.estado ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                  <span>{{ esp.estado ? 'Activa' : 'Inactiva' }}</span>
                </span>
              </td>
              <td class="py-2.5 px-3 text-right">
                <div class="inline-flex items-center gap-1">
                  <button 
                    @click="openEditModal(esp)" 
                    class="w-7 h-7 flex items-center justify-center text-slate-600 hover:text-slate-900 bg-white hover:bg-slate-100 border border-slate-200 rounded transition cursor-pointer"
                    title="Editar especialidad"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                  </button>
                  <!-- Desactivar / Activar -->
                  <button 
                    @click="confirmToggle(esp)" 
                    class="w-7 h-7 flex items-center justify-center rounded border border-slate-200 bg-white transition cursor-pointer hover:bg-slate-100"
                    :class="esp.estado ? 'text-slate-500 hover:text-amber-600' : 'text-slate-500 hover:text-emerald-600'"
                    :title="esp.estado ? 'Desactivar especialidad' : 'Activar especialidad'"
                  >
                    <svg v-if="esp.estado" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                    <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </button>

                  <!-- Eliminar -->
                  <button 
                    @click="confirmDelete(esp)" 
                    class="w-7 h-7 flex items-center justify-center rounded border border-slate-200 bg-white text-slate-400 hover:text-rose-600 hover:bg-rose-50 hover:border-rose-200 transition cursor-pointer"
                    title="Eliminar especialidad"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="especialidades.data.length === 0">
              <td colspan="6" class="py-8 text-center text-xs text-slate-500">
                No se encontraron especialidades registradas.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginación -->
      <div v-if="especialidades.links.length > 3" class="flex justify-end gap-1 text-xs">
        <Link 
          v-for="(link, i) in especialidades.links" 
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

    <!-- Modal Formulario Especialidad -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white rounded-lg shadow-xl border border-slate-200 w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        <div class="px-5 py-3.5 bg-slate-100 border-b border-slate-200 flex items-center justify-between">
          <h3 class="text-sm font-semibold text-slate-800">
            {{ isEditing ? 'Editar Especialidad' : 'Nueva Especialidad Médica' }}
          </h3>
          <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">&times;</button>
        </div>

        <form @submit.prevent="saveEspecialidad" class="p-5 space-y-3.5">
          <div>
            <label class="block text-xs font-medium text-slate-700 mb-1">Nombre de la Especialidad *</label>
            <input 
              v-model="form.nombre" 
              type="text" 
              required 
              placeholder="ej. Medicina General, Pediatría..."
              class="w-full px-2.5 py-1.5 text-xs rounded border border-slate-300 focus:border-blue-600 outline-none"
              :class="{ 'border-rose-400 bg-rose-50/30': form.errors.nombre }"
            />
            <p v-if="form.errors.nombre" class="text-[10px] text-rose-600 mt-0.5">{{ form.errors.nombre }}</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-700 mb-1">Descripción</label>
            <textarea 
              v-model="form.descripcion" 
              rows="3" 
              placeholder="Descripción breve del área de atención médica..."
              class="w-full px-2.5 py-1.5 text-xs rounded border border-slate-300 focus:border-blue-600 outline-none"
            ></textarea>
          </div>

          <div class="px-5 py-3 bg-slate-50 border-t border-slate-200 -mx-5 -mb-5 flex justify-end gap-2.5 mt-4">
            <button type="button" @click="showModal = false" class="px-4 py-1.5 text-xs text-slate-700 bg-white border border-slate-300 rounded hover:bg-slate-100 cursor-pointer">
              Cancelar
            </button>
            <button type="submit" :disabled="form.processing" class="px-4 py-1.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded shadow-xs cursor-pointer">
              {{ isEditing ? 'Guardar Cambios' : 'Registrar' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Diálogo Modal: Alternar Estado (Activar/Desactivar) -->
    <DialogModal
      :show="showConfirmModal"
      :title="espToToggle?.estado ? 'Desactivar Especialidad' : 'Activar Especialidad'"
      :message="espToToggle?.estado 
        ? `¿Confirmas la desactivación de la especialidad '${espToToggle?.nombre}'?` 
        : `¿Confirmas la activación de la especialidad '${espToToggle?.nombre}'?`"
      confirm-text="Confirmar"
      cancel-text="Cancelar"
      :type="espToToggle?.estado ? 'warning' : 'brand'"
      @confirm="executeToggle"
      @close="showConfirmModal = false"
    />

    <!-- Diálogo Modal: Eliminar Especialidad -->
    <DialogModal
      :show="showDeleteModal"
      title="Eliminar Especialidad"
      :message="`¿Estás seguro de que deseas eliminar la especialidad '${espToDelete?.nombre}'?`"
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

const props = defineProps({
  especialidades: Object,
  filters: Object,
});

const search = ref(props.filters?.buscar || '');
const selectedEstado = ref(props.filters?.estado ?? '');

const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const showConfirmModal = ref(false);
const espToToggle = ref(null);

const showDeleteModal = ref(false);
const espToDelete = ref(null);

const form = useForm({
  nombre: '',
  descripcion: '',
  estado: true,
});

const handleSearch = () => {
  router.get('/especialidades', {
    buscar: search.value,
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

const openEditModal = (esp) => {
  isEditing.value = true;
  editingId.value = esp.id;
  form.clearErrors();
  form.nombre = esp.nombre;
  form.descripcion = esp.descripcion || '';
  form.estado = esp.estado;
  showModal.value = true;
};

const saveEspecialidad = () => {
  if (isEditing.value) {
    form.put(`/especialidades/${editingId.value}`, {
      onSuccess: () => { showModal.value = false; },
    });
  } else {
    form.post('/especialidades', {
      onSuccess: () => { showModal.value = false; },
    });
  }
};

const confirmToggle = (esp) => {
  espToToggle.value = esp;
  showConfirmModal.value = true;
};

const executeToggle = () => {
  if (!espToToggle.value) return;
  router.delete(`/especialidades/${espToToggle.value.id}`, {
    onFinish: () => {
      showConfirmModal.value = false;
      espToToggle.value = null;
    }
  });
};

const confirmDelete = (esp) => {
  espToDelete.value = esp;
  showDeleteModal.value = true;
};

const executeDelete = () => {
  if (!espToDelete.value) return;
  router.delete(`/especialidades/${espToDelete.value.id}`, {
    onFinish: () => {
      showDeleteModal.value = false;
      espToDelete.value = null;
    }
  });
};
</script>

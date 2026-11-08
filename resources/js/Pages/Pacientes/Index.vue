<template>
  <AppLayout>
    <template #header>Pacientes</template>

    <PageHeader
      title="Gestión de Pacientes"
      description="Filiación clínica, datos de emergencia y administración del padrón de pacientes."
      :breadcrumbs="[{ label: 'Catálogos' }, { label: 'Pacientes' }]"
    >
      <template #actions>
        <button
          type="button"
          @click="openCreateModal"
          class="btn-clinica bg-brand-600 text-white shadow-sm hover:bg-brand-700"
        >
          <AppIcon name="plus" class="h-4 w-4" :stroke-width="2.2" />
          Registrar Paciente
        </button>
      </template>
    </PageHeader>

    <!-- Filtros -->
    <FilterBar
      v-model:search="search"
      search-placeholder="Buscar por CI, nombre, apellido o teléfono…"
      :has-filters="!!(search || selectedSangre || selectedEstado !== '')"
      @apply="handleSearch"
      @clear="clearFilters"
    >
      <select v-model="selectedSangre" class="input-clinica w-auto" @change="handleSearch">
        <option value="">Tipo de sangre</option>
        <option v-for="t in tiposSangre" :key="t" :value="t">{{ t }}</option>
      </select>

      <select v-model="selectedEstado" class="input-clinica w-auto" @change="handleSearch">
        <option value="">Todos los estados</option>
        <option value="1">Activos</option>
        <option value="0">Inactivos</option>
      </select>
    </FilterBar>

    <!-- Vista Móvil: Tarjetas Responsivas con Toggle y Popover -->
    <div class="block md:hidden space-y-3">
      <div
        v-for="(p, index) in pacientes.data"
        :key="p.id"
        class="bg-white rounded-xl border border-slate-200/90 shadow-xs p-4 space-y-3"
      >
        <div class="flex items-start justify-between gap-2">
          <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-6 h-6 rounded-md bg-slate-100 text-slate-600 font-mono text-[11px] font-bold flex items-center justify-center shrink-0">
              #{{ ((pacientes.current_page || 1) - 1) * (pacientes.per_page || 15) + index + 1 }}
            </span>
            <Avatar :name="p.persona?.nombre_completo || 'P'" size="sm" class="shrink-0" />
            <div class="min-w-0">
              <Link :href="`/pacientes/${p.id}`" class="block truncate font-bold text-slate-900 text-xs hover:text-blue-700 transition">
                {{ p.persona?.nombre_completo }}
              </Link>
              <span class="text-[11px] font-mono text-slate-500">
                CI: {{ p.persona?.ci }} &bull; {{ generoLabel(p.persona?.genero) }}
              </span>
            </div>
          </div>

          <!-- Popover de Acciones (···) -->
          <ActionPopover>
            <template #default="{ close }">
              <ActionPopoverItem
                :href="`/pacientes/${p.id}`"
                icon="eye"
                label="Ver ficha clínica"
                @click="close"
              />
              <ActionPopoverItem
                icon="pencil"
                label="Editar paciente"
                @click="openEditModal(p); close()"
              />
              <ActionPopoverItem
                :icon="p.estado ? 'ban' : 'check'"
                :label="p.estado ? 'Desactivar paciente' : 'Activar paciente'"
                @click="confirmToggle(p); close()"
              />
              <div class="border-t border-slate-100 my-0.5"></div>
              <ActionPopoverItem
                icon="trash"
                label="Eliminar paciente"
                danger
                @click="confirmDelete(p); close()"
              />
            </template>
          </ActionPopover>
        </div>

        <div class="grid grid-cols-2 gap-2 text-[11px] bg-slate-50/70 p-2.5 rounded-lg border border-slate-100">
          <div>
            <span class="text-slate-400 block text-[10px]">Grupo Sanguíneo:</span>
            <span v-if="p.tipo_sangre" class="inline-flex items-center gap-1 font-mono font-bold text-rose-700">
              <AppIcon name="blood" class="h-3 w-3" />{{ p.tipo_sangre }}
            </span>
            <span v-else class="text-slate-400 italic">No registrado</span>
          </div>
          <div>
            <span class="text-slate-400 block text-[10px]">Teléfono:</span>
            <span class="font-mono text-slate-700">{{ p.persona?.telefono || 'Sin teléfono' }}</span>
          </div>
          <div class="col-span-2">
            <span class="text-slate-400 block text-[10px]">Contacto Emergencia:</span>
            <span class="text-slate-700 font-medium">{{ p.contacto_emergencia_nombre || 'No asignado' }}</span>
            <span v-if="p.contacto_emergencia_telefono" class="text-slate-400 font-mono ml-1">({{ p.contacto_emergencia_telefono }})</span>
          </div>
        </div>

        <!-- Toggle de Estado Móvil -->
        <div class="flex items-center justify-between pt-1 border-t border-slate-100 text-xs">
          <span class="text-slate-500 text-[11px]">Estado del paciente:</span>
          <div class="flex items-center gap-2">
            <ToggleSwitch
              :model-value="p.estado"
              size="sm"
              @change="confirmToggle(p)"
            />
            <span class="text-[11px] font-semibold" :class="p.estado ? 'text-slate-800' : 'text-slate-400'">
              {{ p.estado ? 'Activo' : 'Inactivo' }}
            </span>
          </div>
        </div>
      </div>

      <EmptyState
        v-if="pacientes.data.length === 0"
        icon="users"
        title="No se encontraron pacientes"
        message="Prueba ajustando los filtros de búsqueda o registra un nuevo paciente."
      >
        <button type="button" @click="openCreateModal" class="btn-clinica bg-brand-600 text-white hover:bg-brand-700">
          <AppIcon name="plus" class="h-4 w-4" :stroke-width="2.2" />
          Registrar Paciente
        </button>
      </EmptyState>
    </div>

    <!-- Vista Escritorio: Tabla -->
    <div class="hidden md:block overflow-hidden rounded-2xl border border-stone-200/80 bg-white shadow-card animate-fade-up">
      <div class="overflow-x-auto">
        <table class="table-clinica w-full">
          <thead>
            <tr>
              <th class="text-center w-12 font-mono text-stone-500">#</th>
              <th>Paciente</th>
              <th>Contacto</th>
              <th>Grupo sanguíneo</th>
              <th>Contacto de emergencia</th>
              <th class="text-center">Estado</th>
              <th class="text-right">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(p, index) in pacientes.data" :key="p.id">
              <td class="text-center font-mono text-[11px] text-stone-400 font-semibold">
                {{ ((pacientes.current_page || 1) - 1) * (pacientes.per_page || 15) + index + 1 }}
              </td>
              <td>
                <div class="flex items-center gap-3">
                  <Avatar :name="p.persona?.nombre_completo || 'P'" size="sm" />
                  <div class="min-w-0">
                    <Link :href="`/pacientes/${p.id}`" class="block truncate font-semibold text-stone-900 hover:text-brand-700 transition">
                      {{ p.persona?.nombre_completo }}
                    </Link>
                    <span class="text-[12px] text-stone-400">
                      CI {{ p.persona?.ci }} · {{ generoLabel(p.persona?.genero) }}<template v-if="p.persona?.edad != null"> · {{ p.persona.edad }} años</template>
                    </span>
                  </div>
                </div>
              </td>
              <td>
                <span class="block text-stone-700">{{ p.persona?.telefono || 'Sin teléfono' }}</span>
                <span class="block max-w-[220px] truncate text-[12px] text-stone-400">{{ p.persona?.direccion || 'Sin dirección' }}</span>
              </td>
              <td>
                <span
                  v-if="p.tipo_sangre"
                  class="inline-flex items-center gap-1 rounded-md bg-rose-50 px-2 py-0.5 font-mono text-[11.5px] font-bold text-rose-700 ring-1 ring-inset ring-rose-600/15"
                >
                  <AppIcon name="blood" class="h-3 w-3" />{{ p.tipo_sangre }}
                </span>
                <span v-else class="text-[12px] italic text-stone-400">No registrado</span>
              </td>
              <td>
                <span class="block font-medium text-stone-700">{{ p.contacto_emergencia_nombre || '—' }}</span>
                <span v-if="p.contacto_emergencia_telefono" class="text-[12px] text-stone-400">{{ p.contacto_emergencia_telefono }}</span>
              </td>
              <td class="text-center">
                <div class="flex items-center justify-center gap-2">
                  <ToggleSwitch
                    :model-value="p.estado"
                    size="sm"
                    @change="confirmToggle(p)"
                  />
                  <span class="text-[11px] font-medium" :class="p.estado ? 'text-slate-800' : 'text-slate-400'">
                    {{ p.estado ? 'Activo' : 'Inactivo' }}
                  </span>
                </div>
              </td>
              <td class="text-right">
                <ActionPopover>
                  <template #default="{ close }">
                    <ActionPopoverItem
                      :href="`/pacientes/${p.id}`"
                      icon="eye"
                      label="Ver ficha clínica"
                      @click="close"
                    />
                    <ActionPopoverItem
                      icon="pencil"
                      label="Editar paciente"
                      @click="openEditModal(p); close()"
                    />
                    <ActionPopoverItem
                      :icon="p.estado ? 'ban' : 'check'"
                      :label="p.estado ? 'Desactivar paciente' : 'Activar paciente'"
                      @click="confirmToggle(p); close()"
                    />
                    <div class="border-t border-slate-100 my-0.5"></div>
                    <ActionPopoverItem
                      icon="trash"
                      label="Eliminar paciente"
                      danger
                      @click="confirmDelete(p); close()"
                    />
                  </template>
                </ActionPopover>
              </td>
            </tr>
          </tbody>
        </table>

        <EmptyState
          v-if="pacientes.data.length === 0"
          icon="users"
          title="No se encontraron pacientes"
          message="Prueba ajustando los filtros de búsqueda o registra un nuevo paciente."
        >
          <button type="button" @click="openCreateModal" class="btn-clinica bg-brand-600 text-white hover:bg-brand-700">
            <AppIcon name="plus" class="h-4 w-4" :stroke-width="2.2" />
            Registrar Paciente
          </button>
        </EmptyState>
      </div>

      <Pagination :paginator="pacientes" />
    </div>

    <!-- Modal: Formulario de paciente -->
    <Modal
      :show="showModal"
      :title="isEditing ? 'Editar datos del paciente' : 'Registrar nuevo paciente'"
      :subtitle="isEditing ? 'Actualiza la filiación clínica' : 'Completa la ficha de filiación clínica'"
      icon="user-plus"
      size="lg"
      @close="showModal = false"
    >
      <form id="form-paciente" @submit.prevent="savePaciente" class="space-y-5">
        <!-- Datos personales -->
        <section>
          <h4 class="mb-3 flex items-center gap-2 text-[12px] font-bold uppercase tracking-wide text-stone-400">
            <AppIcon name="id-card" class="h-4 w-4" /> Datos personales
          </h4>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <FormField label="CI / Documento de identidad" :error="form.errors.ci" required>
              <template #default="{ inputClass }">
                <input v-model="form.ci" type="text" placeholder="Número de CI" :class="inputClass" />
              </template>
            </FormField>
            <FormField label="Tipo de sangre" :error="form.errors.tipo_sangre" optional>
              <template #default="{ inputClass }">
                <select v-model="form.tipo_sangre" :class="inputClass">
                  <option value="">Selecciona tipo</option>
                  <option v-for="t in tiposSangre" :key="t" :value="t">{{ t }}</option>
                </select>
              </template>
            </FormField>
            <FormField label="Nombres" :error="form.errors.nombres" required>
              <template #default="{ inputClass }">
                <input v-model="form.nombres" type="text" placeholder="Nombres del paciente" :class="inputClass" />
              </template>
            </FormField>
            <FormField label="Apellidos" :error="form.errors.apellidos" required>
              <template #default="{ inputClass }">
                <input v-model="form.apellidos" type="text" placeholder="Apellidos del paciente" :class="inputClass" />
              </template>
            </FormField>
            <FormField label="Fecha de nacimiento" :error="form.errors.fecha_nacimiento" required>
              <template #default="{ inputClass }">
                <input v-model="form.fecha_nacimiento" type="date" :class="inputClass" />
              </template>
            </FormField>
            <FormField label="Género" :error="form.errors.genero" required>
              <template #default="{ inputClass }">
                <select v-model="form.genero" :class="inputClass">
                  <option value="M">Masculino</option>
                  <option value="F">Femenino</option>
                  <option value="O">Otro</option>
                </select>
              </template>
            </FormField>
            <FormField label="Teléfono" :error="form.errors.telefono" optional>
              <template #default="{ inputClass }">
                <input v-model="form.telefono" type="text" placeholder="Celular de contacto" :class="inputClass" />
              </template>
            </FormField>
            <FormField label="Dirección de domicilio" :error="form.errors.direccion" optional>
              <template #default="{ inputClass }">
                <input v-model="form.direccion" type="text" placeholder="Zona, calle y número" :class="inputClass" />
              </template>
            </FormField>
          </div>
        </section>

        <!-- Contacto de emergencia -->
        <section class="rounded-xl border border-stone-200 bg-stone-50/60 p-4">
          <h4 class="mb-3 flex items-center gap-2 text-[12px] font-bold uppercase tracking-wide text-stone-400">
            <AppIcon name="phone" class="h-4 w-4" /> Contacto de emergencia
          </h4>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <FormField label="Nombre completo" :error="form.errors.contacto_emergencia_nombre" optional>
              <template #default="{ inputClass }">
                <input v-model="form.contacto_emergencia_nombre" type="text" placeholder="Familiar o apoderado" :class="inputClass" />
              </template>
            </FormField>
            <FormField label="Teléfono de emergencia" :error="form.errors.contacto_emergencia_telefono" optional>
              <template #default="{ inputClass }">
                <input v-model="form.contacto_emergencia_telefono" type="text" placeholder="Teléfono de contacto" :class="inputClass" />
              </template>
            </FormField>
          </div>
        </section>

        <!-- Antecedentes (solo alta) -->
        <section v-if="!isEditing">
          <FormField
            label="Antecedentes clínicos / alergias"
            :error="form.errors.antecedentes"
            optional
            hint="Enfermedades crónicas, alergias a medicamentos, cirugías previas…"
          >
            <template #default="{ inputClass }">
              <textarea v-model="form.antecedentes" rows="3" :class="inputClass" placeholder="Describe los antecedentes relevantes del paciente"></textarea>
            </template>
          </FormField>
        </section>
      </form>

      <template #footer>
        <button type="button" @click="showModal = false" class="btn-clinica border border-stone-300 bg-white text-stone-700 hover:bg-stone-50">
          Cancelar
        </button>
        <button
          type="submit"
          form="form-paciente"
          :disabled="form.processing"
          class="btn-clinica bg-brand-600 text-white hover:bg-brand-700"
        >
          <AppIcon name="check" class="h-4 w-4" :stroke-width="2.2" />
          {{ form.processing ? 'Guardando…' : (isEditing ? 'Guardar cambios' : 'Registrar paciente') }}
        </button>
      </template>
    </Modal>

    <!-- Confirmación de estado -->
    <DialogModal
      :show="showConfirmModal"
      :title="pacienteToToggle?.estado ? 'Desactivar paciente' : 'Reactivar paciente'"
      :message="pacienteToToggle?.estado
        ? `¿Confirmas la desactivación de ${pacienteToToggle?.persona?.nombre_completo}?`
        : `¿Confirmas la reactivación de ${pacienteToToggle?.persona?.nombre_completo}?`"
      :confirm-text="pacienteToToggle?.estado ? 'Desactivar' : 'Reactivar'"
      cancel-text="Cancelar"
      :type="pacienteToToggle?.estado ? 'warning' : 'brand'"
      @confirm="executeToggle"
      @close="showConfirmModal = false"
    />

    <!-- Confirmación de eliminación -->
    <DialogModal
      :show="showDeleteModal"
      title="Eliminar paciente"
      :message="`¿Estás seguro de que deseas eliminar al paciente ${pacienteToDelete?.persona?.nombre_completo}?`"
      confirm-text="Eliminar"
      cancel-text="Cancelar"
      type="danger"
      @confirm="executeDelete"
      @close="showDeleteModal = false"
    />
  </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { router, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import FilterBar from '@/Components/FilterBar.vue';
import Modal from '@/Components/Modal.vue';
import DialogModal from '@/Components/DialogModal.vue';
import FormField from '@/Components/FormField.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Pagination from '@/Components/Pagination.vue';
import Avatar from '@/Components/Avatar.vue';
import AppIcon from '@/Components/AppIcon.vue';
import ToggleSwitch from '@/Components/ToggleSwitch.vue';
import ActionPopover from '@/Components/ActionPopover.vue';
import ActionPopoverItem from '@/Components/ActionPopoverItem.vue';

const props = defineProps({
  pacientes: Object,
  filters: Object,
});

const tiposSangre = ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'];

const search = ref(props.filters?.buscar || '');
const selectedSangre = ref(props.filters?.tipo_sangre || '');
const selectedEstado = ref(props.filters?.estado ?? '');

const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const showConfirmModal = ref(false);
const pacienteToToggle = ref(null);

const showDeleteModal = ref(false);
const pacienteToDelete = ref(null);

const form = useForm({
  ci: '',
  nombres: '',
  apellidos: '',
  fecha_nacimiento: '',
  genero: 'M',
  telefono: '',
  direccion: '',
  tipo_sangre: '',
  contacto_emergencia_nombre: '',
  contacto_emergencia_telefono: '',
  antecedentes: '',
  estado: true,
});

const handleSearch = () => {
  router.get('/pacientes', {
    buscar: search.value || undefined,
    tipo_sangre: selectedSangre.value || undefined,
    estado: selectedEstado.value !== '' ? selectedEstado.value : undefined,
  }, { preserveState: true, replace: true });
};

// Búsqueda automática con debounce
let debounceTimer = null;
watch(search, () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(handleSearch, 450);
});

const clearFilters = () => {
  search.value = '';
  selectedSangre.value = '';
  selectedEstado.value = '';
  handleSearch();
};

const generoLabel = (g) => (g === 'M' ? 'Masculino' : g === 'F' ? 'Femenino' : 'Otro');

const openCreateModal = () => {
  isEditing.value = false;
  editingId.value = null;
  form.reset();
  form.clearErrors();
  showModal.value = true;
};

const openEditModal = (p) => {
  isEditing.value = true;
  editingId.value = p.id;
  form.clearErrors();
  form.ci = p.persona?.ci || '';
  form.nombres = p.persona?.nombres || '';
  form.apellidos = p.persona?.apellidos || '';
  form.fecha_nacimiento = p.persona?.fecha_nacimiento ? p.persona.fecha_nacimiento.substring(0, 10) : '';
  form.genero = p.persona?.genero || 'M';
  form.telefono = p.persona?.telefono || '';
  form.direccion = p.persona?.direccion || '';
  form.tipo_sangre = p.tipo_sangre || '';
  form.contacto_emergencia_nombre = p.contacto_emergencia_nombre || '';
  form.contacto_emergencia_telefono = p.contacto_emergencia_telefono || '';
  form.estado = p.estado;
  showModal.value = true;
};

const savePaciente = () => {
  const options = { onSuccess: () => { showModal.value = false; } };
  if (isEditing.value) {
    form.put(`/pacientes/${editingId.value}`, options);
  } else {
    form.post('/pacientes', options);
  }
};

const confirmToggle = (p) => {
  pacienteToToggle.value = p;
  showConfirmModal.value = true;
};

const executeToggle = () => {
  if (!pacienteToToggle.value) return;
  router.delete(`/pacientes/${pacienteToToggle.value.id}`, {
    onFinish: () => {
      showConfirmModal.value = false;
      pacienteToToggle.value = null;
    },
  });
};

const confirmDelete = (p) => {
  pacienteToDelete.value = p;
  showDeleteModal.value = true;
};

const executeDelete = () => {
  if (!pacienteToDelete.value) return;
  router.delete(`/pacientes/${pacienteToDelete.value.id}`, {
    onFinish: () => {
      showDeleteModal.value = false;
      pacienteToDelete.value = null;
    },
  });
};
</script>

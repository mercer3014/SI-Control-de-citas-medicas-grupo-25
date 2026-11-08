<template>
  <AppLayout>
    <template #header>
      <span>Usuarios</span>
    </template>

    <div class="space-y-4">
      <!-- Barra Superior: Filtros y Botón de Registro -->
      <div class="bg-white p-3.5 rounded border border-slate-200 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2.5 w-full sm:w-auto">
          <!-- Buscador -->
          <div class="relative w-full sm:w-64">
            <input 
              v-model="search" 
              type="text" 
              placeholder="Buscar por CI, nombre, usuario..." 
              class="w-full pl-8 pr-3 py-1.5 text-xs rounded border border-slate-300 focus:border-slate-600 outline-none"
              @keyup.enter="handleSearch"
            />
            <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>

          <!-- Filtro Rol -->
          <select 
            v-model="selectedRol" 
            class="px-2.5 py-1.5 text-xs rounded border border-slate-300 focus:border-slate-600 outline-none"
            @change="handleSearch"
          >
            <option value="">Todos los roles</option>
            <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.nombre }}</option>
          </select>

          <!-- Filtro Estado -->
          <select 
            v-model="selectedEstado" 
            class="px-2.5 py-1.5 text-xs rounded border border-slate-300 focus:border-slate-600 outline-none"
            @change="handleSearch"
          >
            <option value="">Todos los estados</option>
            <option value="1">Activos</option>
            <option value="0">Inactivos</option>
          </select>
        </div>

        <button 
          @click="openCreateModal"
          class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded text-xs font-semibold shadow-xs transition cursor-pointer"
        >
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
          </svg>
          <span>Registrar Usuario</span>
        </button>
      </div>

      <!-- Vista Móvil: Tarjetas Responsivas con Toggle y Popover -->
      <div class="block md:hidden space-y-3">
        <div
          v-for="(u, index) in usuarios.data"
          :key="u.id"
          class="bg-white rounded-xl border border-slate-200/90 shadow-xs p-4 space-y-3"
        >
          <div class="flex items-start justify-between gap-2">
            <div class="flex items-center gap-2.5 min-w-0">
              <span class="w-6 h-6 rounded-md bg-slate-100 text-slate-600 font-mono text-[11px] font-bold flex items-center justify-center shrink-0">
                #{{ ((usuarios.current_page || 1) - 1) * (usuarios.per_page || 15) + index + 1 }}
              </span>
              <div class="min-w-0">
                <h4 class="font-bold text-slate-900 text-xs truncate">{{ u.persona?.nombre_completo }}</h4>
                <span class="text-[11px] font-mono text-slate-500">@{{ u.username }}</span>
              </div>
            </div>

            <!-- Popover de Acciones (···) -->
            <ActionPopover>
              <template #default="{ close }">
                <ActionPopoverItem
                  icon="pencil"
                  label="Editar usuario"
                  @click="openEditModal(u); close()"
                />
                <ActionPopoverItem
                  v-if="u.id !== $page.props.auth?.user?.id"
                  :icon="u.estado ? 'ban' : 'check'"
                  :label="u.estado ? 'Desactivar cuenta' : 'Activar cuenta'"
                  @click="confirmToggleState(u); close()"
                />
                <div v-if="u.id !== $page.props.auth?.user?.id" class="border-t border-slate-100 my-0.5"></div>
                <ActionPopoverItem
                  v-if="u.id !== $page.props.auth?.user?.id"
                  icon="trash"
                  label="Eliminar usuario"
                  danger
                  @click="confirmDelete(u); close()"
                />
              </template>
            </ActionPopover>
          </div>

          <div class="grid grid-cols-2 gap-2 text-[11px] bg-slate-50/70 p-2.5 rounded-lg border border-slate-100">
            <div>
              <span class="text-slate-400 block text-[10px]">Documento / CI:</span>
              <span class="font-mono font-medium text-slate-700">{{ u.persona?.ci }}</span>
            </div>
            <div>
              <span class="text-slate-400 block text-[10px]">Rol Asignado:</span>
              <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-200 text-slate-700">
                {{ u.rol?.nombre }}
              </span>
            </div>
            <div class="col-span-2">
              <span class="text-slate-400 block text-[10px]">Correo Electrónico:</span>
              <span class="text-slate-600 truncate block">{{ u.email }}</span>
            </div>
          </div>

          <!-- Toggle de Estado Móvil -->
          <div class="flex items-center justify-between pt-1 border-t border-slate-100 text-xs">
            <span class="text-slate-500 text-[11px]">Estado de la cuenta:</span>
            <div class="flex items-center gap-2">
              <ToggleSwitch
                :model-value="u.estado"
                :disabled="u.id === $page.props.auth?.user?.id"
                size="sm"
                @change="confirmToggleState(u)"
              />
              <span class="text-[11px] font-semibold" :class="u.estado ? 'text-slate-800' : 'text-slate-400'">
                {{ u.estado ? 'Activo' : 'Inactivo' }}
              </span>
            </div>
          </div>
        </div>

        <div v-if="usuarios.data.length === 0" class="bg-white p-8 rounded-xl border border-slate-200 text-center text-xs text-slate-500">
          No se encontraron usuarios registrados con los criterios seleccionados.
        </div>
      </div>

      <!-- Vista Escritorio: Tabla de Usuarios con Toggle y Popover -->
      <div class="hidden md:block bg-white rounded border border-slate-200 shadow-xs overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-100/80 border-b border-slate-200 text-slate-700 font-semibold uppercase text-[11px] tracking-wider">
              <th class="py-2.5 px-3 text-center w-12 text-slate-500 font-mono">#</th>
              <th class="py-2.5 px-3">Persona / CI</th>
              <th class="py-2.5 px-3">Usuario</th>
              <th class="py-2.5 px-3">Correo</th>
              <th class="py-2.5 px-3">Rol</th>
              <th class="py-2.5 px-3 text-center">Estado</th>
              <th class="py-2.5 px-3 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr 
              v-for="(u, index) in usuarios.data" 
              :key="u.id"
              class="hover:bg-slate-50 transition"
            >
              <td class="py-2.5 px-3 text-center font-mono text-[11px] text-slate-400 font-semibold">
                {{ ((usuarios.current_page || 1) - 1) * (usuarios.per_page || 15) + index + 1 }}
              </td>
              <td class="py-2.5 px-3">
                <span class="font-medium text-slate-900 block">{{ u.persona?.nombre_completo }}</span>
                <span class="text-[11px] text-slate-500 font-mono">CI: {{ u.persona?.ci }} &bull; {{ u.persona?.telefono || 'Sin teléfono' }}</span>
              </td>
              <td class="py-2.5 px-3 font-mono font-medium text-slate-700">
                {{ u.username }}
              </td>
              <td class="py-2.5 px-3 text-slate-600">
                {{ u.email }}
              </td>
              <td class="py-2.5 px-3">
                <span class="px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                  {{ u.rol?.nombre }}
                </span>
              </td>
              <td class="py-2.5 px-3 text-center">
                <div class="flex items-center justify-center gap-2">
                  <ToggleSwitch
                    :model-value="u.estado"
                    :disabled="u.id === $page.props.auth?.user?.id"
                    size="sm"
                    @change="confirmToggleState(u)"
                  />
                  <span class="text-[11px] font-medium" :class="u.estado ? 'text-slate-800' : 'text-slate-400'">
                    {{ u.estado ? 'Activo' : 'Inactivo' }}
                  </span>
                </div>
              </td>
              <td class="py-2.5 px-3 text-right">
                <div class="inline-flex items-center gap-1 justify-end">
                  <button
                    type="button"
                    @click="openEditModal(u)"
                    class="w-7 h-7 flex items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition cursor-pointer"
                    title="Editar usuario"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                  </button>
                  <button
                    v-if="u.id !== $page.props.auth?.user?.id"
                    type="button"
                    @click="confirmDelete(u)"
                    class="w-7 h-7 flex items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-400 hover:text-rose-600 hover:bg-rose-50 hover:border-rose-200 transition cursor-pointer"
                    title="Eliminar usuario"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="usuarios.data.length === 0">
              <td colspan="7" class="py-8 text-center text-xs text-slate-500">
                No se encontraron usuarios registrados con los criterios seleccionados.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginación -->
      <div v-if="usuarios.links.length > 3" class="flex justify-end gap-1 text-xs">
        <Link 
          v-for="(link, i) in usuarios.links" 
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

    <!-- Modal Formulario: Crear / Editar Usuario -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white rounded-lg shadow-xl border border-slate-200 w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        <div class="px-5 py-3.5 bg-slate-100 border-b border-slate-200 flex items-center justify-between">
          <h3 class="text-sm font-semibold text-slate-800">
            {{ isEditing ? 'Editar Usuario' : 'Registrar Nuevo Usuario' }}
          </h3>
          <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">&times;</button>
        </div>

        <form @submit.prevent="saveUser" class="p-5 space-y-3.5 max-h-[85vh] overflow-y-auto">
          <!-- Sección Identidad Persona -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] font-medium text-slate-700 mb-1">CI / Documento *</label>
              <input v-model="form.ci" type="text" required placeholder="Número de CI" class="w-full px-2.5 py-1.5 text-xs rounded border border-slate-300 focus:border-slate-600 outline-none" />
              <p v-if="form.errors.ci" class="text-[10px] text-rose-600 mt-0.5">{{ form.errors.ci }}</p>
            </div>
            <div>
              <label class="block text-[11px] font-medium text-slate-700 mb-1">Género *</label>
              <select v-model="form.genero" required class="w-full px-2.5 py-1.5 text-xs rounded border border-slate-300 focus:border-slate-600 outline-none">
                <option value="M">Masculino (M)</option>
                <option value="F">Femenino (F)</option>
                <option value="O">Otro (O)</option>
              </select>
              <p v-if="form.errors.genero" class="text-[10px] text-rose-600 mt-0.5">{{ form.errors.genero }}</p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] font-medium text-slate-700 mb-1">Nombres *</label>
              <input v-model="form.nombres" type="text" required class="w-full px-2.5 py-1.5 text-xs rounded border border-slate-300 focus:border-slate-600 outline-none" />
              <p v-if="form.errors.nombres" class="text-[10px] text-rose-600 mt-0.5">{{ form.errors.nombres }}</p>
            </div>
            <div>
              <label class="block text-[11px] font-medium text-slate-700 mb-1">Apellidos *</label>
              <input v-model="form.apellidos" type="text" required class="w-full px-2.5 py-1.5 text-xs rounded border border-slate-300 focus:border-slate-600 outline-none" />
              <p v-if="form.errors.apellidos" class="text-[10px] text-rose-600 mt-0.5">{{ form.errors.apellidos }}</p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] font-medium text-slate-700 mb-1">Fecha de Nacimiento *</label>
              <input v-model="form.fecha_nacimiento" type="date" required class="w-full px-2.5 py-1.5 text-xs rounded border border-slate-300 focus:border-slate-600 outline-none" />
              <p v-if="form.errors.fecha_nacimiento" class="text-[10px] text-rose-600 mt-0.5">{{ form.errors.fecha_nacimiento }}</p>
            </div>
            <div>
              <label class="block text-[11px] font-medium text-slate-700 mb-1">Teléfono</label>
              <input v-model="form.telefono" type="text" placeholder="ej. 75012345" class="w-full px-2.5 py-1.5 text-xs rounded border border-slate-300 focus:border-slate-600 outline-none" />
              <p v-if="form.errors.telefono" class="text-[10px] text-rose-600 mt-0.5">{{ form.errors.telefono }}</p>
            </div>
          </div>

          <div>
            <label class="block text-[11px] font-medium text-slate-700 mb-1">Dirección de Domicilio</label>
            <input v-model="form.direccion" type="text" placeholder="Calle, Barrio o Zona" class="w-full px-2.5 py-1.5 text-xs rounded border border-slate-300 focus:border-slate-600 outline-none" />
            <p v-if="form.errors.direccion" class="text-[10px] text-rose-600 mt-0.5">{{ form.errors.direccion }}</p>
          </div>

          <!-- Sección Credenciales de Usuario -->
          <hr class="border-slate-200 my-2" />

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] font-medium text-slate-700 mb-1">Usuario *</label>
              <input v-model="form.username" type="text" required class="w-full px-2.5 py-1.5 text-xs rounded border border-slate-300 focus:border-slate-600 outline-none" />
              <p v-if="form.errors.username" class="text-[10px] text-rose-600 mt-0.5">{{ form.errors.username }}</p>
            </div>
            <div>
              <label class="block text-[11px] font-medium text-slate-700 mb-1">Rol Asignado *</label>
              <select v-model="form.rol_id" required class="w-full px-2.5 py-1.5 text-xs rounded border border-slate-300 focus:border-slate-600 outline-none">
                <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.nombre }}</option>
              </select>
              <p v-if="form.errors.rol_id" class="text-[10px] text-rose-600 mt-0.5">{{ form.errors.rol_id }}</p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] font-medium text-slate-700 mb-1">Correo Electrónico *</label>
              <input v-model="form.email" type="email" required class="w-full px-2.5 py-1.5 text-xs rounded border border-slate-300 focus:border-slate-600 outline-none" />
              <p v-if="form.errors.email" class="text-[10px] text-rose-600 mt-0.5">{{ form.errors.email }}</p>
            </div>
            <div>
              <label class="block text-[11px] font-medium text-slate-700 mb-1">
                {{ isEditing ? 'Nueva Contraseña (Opcional)' : 'Contraseña *' }}
              </label>
              <input v-model="form.password" type="password" :required="!isEditing" placeholder="••••••••" class="w-full px-2.5 py-1.5 text-xs rounded border border-slate-300 focus:border-slate-600 outline-none" />
              <p v-if="form.errors.password" class="text-[10px] text-rose-600 mt-0.5">{{ form.errors.password }}</p>
            </div>
          </div>

          <div class="px-5 py-3 bg-slate-50 border-t border-slate-200 -mx-5 -mb-5 flex justify-end gap-2.5 mt-4">
            <button type="button" @click="showModal = false" class="px-4 py-1.5 text-xs text-slate-700 bg-white border border-slate-300 rounded hover:bg-slate-100 cursor-pointer">
              Cancelar
            </button>
            <button type="submit" :disabled="form.processing" class="px-4 py-1.5 text-xs font-semibold text-white bg-slate-900 hover:bg-slate-800 rounded shadow-xs cursor-pointer">
              {{ isEditing ? 'Guardar Cambios' : 'Registrar' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Diálogo Modal: Alternar Estado (Activar/Desactivar) -->
    <DialogModal
      :show="showConfirmToggleModal"
      :title="userToToggle?.estado ? 'Desactivar Usuario' : 'Activar Usuario'"
      :message="userToToggle?.estado 
        ? `¿Confirmas la desactivación del usuario '${userToToggle?.username}'?` 
        : `¿Confirmas la activación del usuario '${userToToggle?.username}'?`"
      confirm-text="Confirmar"
      cancel-text="Cancelar"
      :type="userToToggle?.estado ? 'warning' : 'brand'"
      @confirm="executeToggleState"
      @close="showConfirmToggleModal = false"
    />

    <!-- Diálogo Modal: Eliminar Usuario (Baja Lógica) -->
    <DialogModal
      :show="showDeleteModal"
      title="Eliminar Usuario (Baja Lógica)"
      :message="`¿Estás seguro de que deseas dar de baja al usuario '${userToDelete?.username}'? Se ejecutará una baja lógica (estado inactivo), manteniendo intacto todo el historial de consultas, atenciones y auditoría.`"
      confirm-text="Confirmar Eliminación"
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
  usuarios: Object,
  roles: Array,
  filters: Object,
});

const search = ref(props.filters?.buscar || '');
const selectedRol = ref(props.filters?.rol_id || '');
const selectedEstado = ref(props.filters?.estado ?? '');

const showModal = ref(false);
const isEditing = ref(false);
const editingUserId = ref(null);

const showConfirmToggleModal = ref(false);
const userToToggle = ref(null);

const showDeleteModal = ref(false);
const userToDelete = ref(null);

const form = useForm({
  ci: '',
  nombres: '',
  apellidos: '',
  fecha_nacimiento: '',
  genero: 'M',
  telefono: '',
  direccion: '',
  username: '',
  email: '',
  password: '',
  rol_id: props.roles?.[0]?.id || 1,
  estado: true,
});

const handleSearch = () => {
  router.get('/usuarios', {
    buscar: search.value,
    rol_id: selectedRol.value,
    estado: selectedEstado.value,
  }, { preserveState: true });
};

const openCreateModal = () => {
  isEditing.value = false;
  editingUserId.value = null;
  form.reset();
  form.clearErrors();
  showModal.value = true;
};

const openEditModal = (u) => {
  isEditing.value = true;
  editingUserId.value = u.id;
  form.clearErrors();
  form.ci = u.persona?.ci || '';
  form.nombres = u.persona?.nombres || '';
  form.apellidos = u.persona?.apellidos || '';
  form.fecha_nacimiento = u.persona?.fecha_nacimiento ? u.persona.fecha_nacimiento.substring(0, 10) : '';
  form.genero = u.persona?.genero || 'M';
  form.telefono = u.persona?.telefono || '';
  form.direccion = u.persona?.direccion || '';
  form.username = u.username;
  form.email = u.email;
  form.password = '';
  form.rol_id = u.rol_id;
  form.estado = u.estado;
  showModal.value = true;
};

const saveUser = () => {
  if (isEditing.value) {
    form.put(`/usuarios/${editingUserId.value}`, {
      onSuccess: () => { showModal.value = false; },
    });
  } else {
    form.post('/usuarios', {
      onSuccess: () => { showModal.value = false; },
    });
  }
};

const confirmToggleState = (u) => {
  userToToggle.value = u;
  showConfirmToggleModal.value = true;
};

const executeToggleState = () => {
  if (!userToToggle.value) return;
  router.patch(`/usuarios/${userToToggle.value.id}/toggle`, {}, {
    onFinish: () => {
      showConfirmToggleModal.value = false;
      userToToggle.value = null;
    }
  });
};

const confirmDelete = (u) => {
  userToDelete.value = u;
  showDeleteModal.value = true;
};

const executeDelete = () => {
  if (!userToDelete.value) return;
  router.delete(`/usuarios/${userToDelete.value.id}`, {
    onFinish: () => {
      showDeleteModal.value = false;
      userToDelete.value = null;
    }
  });
};
</script>

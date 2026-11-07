<template>
  <AppLayout>
    <template #header>
      <span>Roles y Permisos</span>
    </template>

    <div class="space-y-4">
      <!-- Selector de Rol (Tabs / Botones) -->
      <div class="bg-white p-3.5 rounded border border-slate-200 shadow-xs">
        <div class="flex items-center justify-between flex-wrap gap-3">
          <div class="flex items-center gap-1.5 flex-wrap">
            <button
              v-for="r in roles"
              :key="r.id"
              type="button"
              @click="selectedRoleId = r.id"
              :class="[
                'px-4 py-2 text-xs font-semibold rounded transition cursor-pointer',
                selectedRoleId === r.id
                  ? 'bg-slate-900 text-white shadow-xs'
                  : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
              ]"
            >
              {{ r.nombre }}
              <span class="ml-1 text-[11px] opacity-75 font-normal">({{ getRolePermisosCount(r.id) }})</span>
            </button>
          </div>

          <!-- Botón Guardar Cambios -->
          <button
            type="button"
            @click="saveCurrentRole"
            :disabled="saving"
            class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded shadow-xs transition disabled:opacity-50 cursor-pointer"
          >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
            <span>{{ saving ? 'Guardando...' : 'Guardar Permisos' }}</span>
          </button>
        </div>
      </div>

      <!-- Panel de Permisos del Rol Seleccionado -->
      <div v-if="currentRole" class="bg-white rounded border border-slate-200 shadow-xs overflow-hidden">
        <!-- Encabezado del Rol Seleccionado -->
        <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <div class="flex items-center gap-2">
              <h3 class="text-sm font-bold text-slate-900">{{ currentRole.nombre }}</h3>
              <span class="text-[11px] px-2 py-0.5 rounded bg-slate-200 text-slate-700 font-mono">
                {{ currentRole.usuarios_count }} usuario(s) asignado(s)
              </span>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">{{ currentRole.descripcion }}</p>
          </div>

          <!-- Acciones de marcado masivo -->
          <div class="flex items-center gap-2 text-xs text-slate-600">
            <button
              type="button"
              @click="selectAll"
              class="hover:text-slate-900 underline cursor-pointer"
            >
              Seleccionar todos
            </button>
            <span class="text-slate-300">|</span>
            <button
              type="button"
              @click="deselectAll"
              class="hover:text-slate-900 underline cursor-pointer"
            >
              Desmarcar todos
            </button>
          </div>
        </div>

        <!-- Matriz Agrupada de Permisos por Módulo Funcional -->
        <div class="p-5 space-y-6">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-slate-200 gap-2">
            <div class="text-[11px] font-semibold text-slate-600 uppercase tracking-wider">
              Permisos del Sistema ({{ currentSelectedPermisos.length }} de {{ permisos.length }} activos)
            </div>
            <div class="relative w-full sm:w-64">
              <input
                v-model="filtroPermiso"
                type="text"
                placeholder="Buscar permisos..."
                class="w-full pl-8 pr-2.5 py-1 text-xs rounded border border-slate-300 focus:border-slate-600 outline-none"
              />
              <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
          </div>

          <!-- Secciones de Permisos Agrupadas -->
          <div
            v-for="(moduloPermisos, moduloNombre) in groupedPermisos"
            :key="moduloNombre"
            class="space-y-2.5"
          >
            <div class="flex items-center justify-between bg-slate-50/90 px-3 py-1.5 rounded border border-slate-200">
              <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-800">{{ moduloNombre }}</span>
                <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 font-mono font-medium">
                  {{ getModuloSelectedCount(moduloPermisos) }} / {{ moduloPermisos.length }}
                </span>
              </div>
              <div class="flex items-center gap-2 text-[11px] text-slate-600">
                <button
                  type="button"
                  @click="toggleGroup(moduloPermisos, true)"
                  class="hover:text-slate-900 hover:underline cursor-pointer"
                >
                  Marcar grupo
                </button>
                <span class="text-slate-300">|</span>
                <button
                  type="button"
                  @click="toggleGroup(moduloPermisos, false)"
                  class="hover:text-slate-900 hover:underline cursor-pointer"
                >
                  Desmarcar
                </button>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
              <label
                v-for="p in moduloPermisos"
                :key="p.id"
                class="flex items-start gap-2.5 p-2.5 rounded border transition cursor-pointer select-none"
                :class="isPermissionChecked(p.id)
                  ? 'bg-slate-50 border-slate-400 shadow-xs'
                  : 'bg-white border-slate-200 hover:bg-slate-50/60'"
              >
                <input
                  type="checkbox"
                  :checked="isPermissionChecked(p.id)"
                  @change="togglePermission(p.id)"
                  class="mt-0.5 rounded text-slate-900 focus:ring-slate-600 cursor-pointer"
                />
                <div class="leading-tight">
                  <span class="text-xs font-semibold text-slate-800 block">{{ p.nombre }}</span>
                  <span class="text-[11px] text-slate-500 block mt-0.5 leading-snug">{{ p.descripcion }}</span>
                </div>
              </label>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  roles: Array,
  permisos: Array,
});

const selectedRoleId = ref(props.roles?.[0]?.id || null);
const saving = ref(false);

// Mantener mapa reactivo de permisos seleccionados por cada rol
const rolePermissionsMap = reactive({});

const initMap = () => {
  props.roles?.forEach(r => {
    rolePermissionsMap[r.id] = (r.permisos || []).map(p => p.id);
  });
};

initMap();

watch(() => props.roles, () => {
  initMap();
}, { deep: true });

const currentRole = computed(() => {
  return props.roles?.find(r => r.id === selectedRoleId.value);
});

const currentSelectedPermisos = computed(() => {
  if (!selectedRoleId.value) return [];
  return rolePermissionsMap[selectedRoleId.value] || [];
});

const moduloCategorias = {
  gestionar_usuarios: 'Seguridad y Auditoría',
  gestionar_roles: 'Seguridad y Auditoría',
  ver_bitacora: 'Seguridad y Auditoría',
  gestionar_backups: 'Seguridad y Auditoría',
  gestionar_especialidades: 'Catálogos Clínicos y Personal',
  gestionar_medicos: 'Catálogos Clínicos y Personal',
  gestionar_pacientes: 'Catálogos Clínicos y Personal',
  gestionar_horarios: 'Turnos y Disponibilidad Médica',
  programar_citas: 'Agenda y Citas Médicas',
  cancelar_citas: 'Agenda y Citas Médicas',
  registrar_atencion: 'Atención Médica y Diagnóstico',
  ver_reportes: 'Reportes y Estadísticas',
};

const filtroPermiso = ref('');

const groupedPermisos = computed(() => {
  const groups = {};
  const term = filtroPermiso.value.toLowerCase().trim();
  (props.permisos || []).forEach(p => {
    if (term) {
      const match = (p.nombre && p.nombre.toLowerCase().includes(term)) ||
                    (p.descripcion && p.descripcion.toLowerCase().includes(term));
      if (!match) return;
    }
    const mod = moduloCategorias[p.nombre] || 'Otros Permisos';
    if (!groups[mod]) {
      groups[mod] = [];
    }
    groups[mod].push(p);
  });
  return groups;
});

const getModuloSelectedCount = (moduloPermisos) => {
  return moduloPermisos.filter(p => isPermissionChecked(p.id)).length;
};

const toggleGroup = (moduloPermisos, select) => {
  if (!selectedRoleId.value) return;
  const list = rolePermissionsMap[selectedRoleId.value] || [];
  moduloPermisos.forEach(p => {
    const idx = list.indexOf(p.id);
    if (select && idx === -1) {
      list.push(p.id);
    } else if (!select && idx > -1) {
      list.splice(idx, 1);
    }
  });
};

const isPermissionChecked = (permisoId) => {
  return currentSelectedPermisos.value.includes(permisoId);
};

const togglePermission = (permisoId) => {
  if (!selectedRoleId.value) return;
  const list = rolePermissionsMap[selectedRoleId.value] || [];
  const idx = list.indexOf(permisoId);
  if (idx > -1) {
    list.splice(idx, 1);
  } else {
    list.push(permisoId);
  }
};

const selectAll = () => {
  if (!selectedRoleId.value) return;
  rolePermissionsMap[selectedRoleId.value] = props.permisos.map(p => p.id);
};

const deselectAll = () => {
  if (!selectedRoleId.value) return;
  rolePermissionsMap[selectedRoleId.value] = [];
};

const getRolePermisosCount = (roleId) => {
  return (rolePermissionsMap[roleId] || []).length;
};

const saveCurrentRole = () => {
  if (!currentRole.value) return;
  saving.value = true;

  router.put(`/roles/${currentRole.value.id}`, {
    descripcion: currentRole.value.descripcion,
    estado: currentRole.value.estado,
    permisos: rolePermissionsMap[currentRole.value.id] || [],
  }, {
    preserveScroll: true,
    onFinish: () => {
      saving.value = false;
    }
  });
};
</script>

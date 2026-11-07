<template>
  <AppLayout>
    <template #header>
      <span>{{ esMiPerfil ? 'Mi Perfil Médico' : 'Perfil del Médico' }}</span>
    </template>

    <div class="space-y-4 max-w-4xl mx-auto">
      <!-- Tarjeta Principal del Médico -->
      <div class="bg-white rounded border border-slate-200 shadow-xs overflow-hidden">
        <div class="bg-[#0F2942] p-5 text-white flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
          <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded bg-blue-600 flex items-center justify-center font-bold text-lg text-white shadow-xs">
              {{ medico.persona?.nombres?.charAt(0) }}{{ medico.persona?.apellidos?.charAt(0) }}
            </div>
            <div>
              <h1 class="text-base font-bold">Dr(a). {{ medico.persona?.nombre_completo }}</h1>
              <p class="text-xs text-blue-200 font-mono mt-0.5">
                Matrícula Profesional: {{ medico.matricula_profesional }} &bull; CI: {{ medico.persona?.ci }}
              </p>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <span 
              :class="[
                'px-2.5 py-1 text-xs rounded font-semibold',
                medico.estado ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-400/30' : 'bg-rose-500/20 text-rose-300 border border-rose-400/30'
              ]"
            >
              {{ medico.estado ? 'Habilitado para Citas' : 'Inactivo' }}
            </span>

            <button
              type="button"
              @click="openEditModal"
              class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-medium transition cursor-pointer flex items-center gap-1.5 shadow-xs"
            >
              <AppIcon name="pencil" class="w-3.5 h-3.5" />
              Editar Perfil
            </button>

            <Link 
              v-if="!esMiPerfil"
              :href="'/medicos'" 
              class="px-3 py-1 bg-white/10 hover:bg-white/20 text-white rounded text-xs transition"
            >
              &larr; Volver
            </Link>
          </div>
        </div>

        <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-5 text-xs">
          <!-- Datos Personales y Contacto -->
          <div class="space-y-3">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider border-b border-slate-100 pb-1.5">
              Datos Personales y Contacto
            </h3>
            <div class="space-y-2">
              <div class="flex justify-between py-1 border-b border-slate-50">
                <span class="text-slate-500">Documento de Identidad:</span>
                <span class="font-semibold text-slate-800">{{ medico.persona?.ci }}</span>
              </div>
              <div class="flex justify-between py-1 border-b border-slate-50">
                <span class="text-slate-500">Fecha de Nacimiento:</span>
                <span class="font-semibold text-slate-800">{{ medico.persona?.fecha_nacimiento ? new Date(medico.persona.fecha_nacimiento).toLocaleDateString('es-BO') : '-' }}</span>
              </div>
              <div class="flex justify-between py-1 border-b border-slate-50">
                <span class="text-slate-500">Género:</span>
                <span class="font-semibold text-slate-800">{{ medico.persona?.genero === 'M' ? 'Masculino' : (medico.persona?.genero === 'F' ? 'Femenino' : 'Otro') }}</span>
              </div>
              <div class="flex justify-between py-1 border-b border-slate-50">
                <span class="text-slate-500">Teléfono / Celular:</span>
                <span class="font-semibold text-slate-800">{{ medico.persona?.telefono || 'No registrado' }}</span>
              </div>
              <div class="flex justify-between py-1 border-b border-slate-50">
                <span class="text-slate-500">Dirección:</span>
                <span class="font-semibold text-slate-800">{{ medico.persona?.direccion || 'No registrada' }}</span>
              </div>
            </div>
          </div>

          <!-- Especialidades Asignadas -->
          <div class="space-y-3">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider border-b border-slate-100 pb-1.5">
              Especialidades y Aranceles
            </h3>
            <div class="divide-y divide-slate-100">
              <div 
                v-for="esp in medico.especialidades" 
                :key="esp.id"
                class="py-2 flex items-center justify-between"
              >
                <div>
                  <span class="font-semibold text-slate-800 block">{{ esp.nombre }}</span>
                  <span class="text-[11px] text-slate-500">{{ esp.descripcion || 'Sin descripción' }}</span>
                </div>
                <div class="text-right">
                  <span class="font-mono font-bold text-blue-700 block">Bs. {{ esp.pivot.precio_consulta }}</span>
                  <span class="text-[10px] text-slate-400">Por consulta</span>
                </div>
              </div>

              <div v-if="!medico.especialidades || medico.especialidades.length === 0" class="py-4 text-center text-slate-400">
                No tiene especialidades vinculadas actualmente.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Flotante: Editar Perfil -->
    <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white rounded-lg shadow-xl border border-slate-200 w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        <div class="px-5 py-3.5 bg-slate-100 border-b border-slate-200 flex items-center justify-between">
          <h3 class="text-sm font-semibold text-slate-800">
            Actualizar Información del Perfil
          </h3>
          <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">&times;</button>
        </div>

        <form @submit.prevent="requestSave" class="p-5 space-y-4 max-h-[80vh] overflow-y-auto text-xs">
          <div>
            <label class="block text-[11px] font-medium text-slate-700 mb-1">Teléfono / Celular de Contacto</label>
            <input
              v-model="form.telefono"
              type="text"
              placeholder="+591 70000000"
              class="w-full px-2.5 py-1.5 text-xs rounded border border-slate-300 focus:border-slate-600 outline-none"
            />
          </div>

          <div>
            <label class="block text-[11px] font-medium text-slate-700 mb-1">Dirección / Consultorio</label>
            <input
              v-model="form.direccion"
              type="text"
              placeholder="Edificio Médico, Consultorio 204"
              class="w-full px-2.5 py-1.5 text-xs rounded border border-slate-300 focus:border-slate-600 outline-none"
            />
          </div>

          <div>
            <label class="block text-[11px] font-medium text-slate-700 mb-2">Aranceles por Especialidad (Bs.)</label>
            <div class="space-y-2 border border-slate-200 rounded p-3 bg-slate-50">
              <div
                v-for="(esp, idx) in form.especialidades"
                :key="esp.id"
                class="flex items-center justify-between gap-3 bg-white p-2 rounded border border-slate-200"
              >
                <span class="font-medium text-slate-800">{{ esp.nombre }}</span>
                <div class="flex items-center gap-1.5">
                  <span class="text-slate-500 font-mono">Bs.</span>
                  <input
                    v-model.number="form.especialidades[idx].precio"
                    type="number"
                    min="0"
                    step="0.5"
                    class="w-24 px-2 py-1 text-xs text-right font-mono font-semibold rounded border border-slate-300 focus:border-slate-600 outline-none"
                  />
                </div>
              </div>
            </div>
          </div>

          <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
            <button
              type="button"
              @click="showEditModal = false"
              class="px-3.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded border border-slate-300 cursor-pointer"
            >
              Cancelar
            </button>
            <button
              type="submit"
              class="px-3.5 py-1.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded shadow-xs cursor-pointer"
            >
              Guardar Cambios
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Confirmación Modal -->
    <DialogModal
      :show="showConfirmSaveModal"
      title="Confirmar actualización de perfil"
      message="¿Deseas guardar los cambios en la información de contacto y tarifas del perfil médico?"
      confirm-text="Sí, guardar cambios"
      cancel-text="Revisar"
      type="info"
      @confirm="executeSave"
      @close="showConfirmSaveModal = false"
    />
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppIcon from '@/Components/AppIcon.vue';
import DialogModal from '@/Components/DialogModal.vue';

const props = defineProps({
  medico: Object,
  esMiPerfil: { type: Boolean, default: false },
});

const showEditModal = ref(false);
const showConfirmSaveModal = ref(false);

const form = useForm({
  telefono: '',
  direccion: '',
  especialidades: [],
});

const openEditModal = () => {
  form.telefono = props.medico.persona?.telefono || '';
  form.direccion = props.medico.persona?.direccion || '';
  form.especialidades = (props.medico.especialidades || []).map((esp) => ({
    id: esp.id,
    nombre: esp.nombre,
    precio: Number(esp.pivot?.precio_consulta || 0),
  }));
  showEditModal.value = true;
};

const requestSave = () => {
  showConfirmSaveModal.value = true;
};

const executeSave = () => {
  showConfirmSaveModal.value = false;
  const targetUrl = props.esMiPerfil ? '/mi-perfil-medico' : `/medicos/${props.medico.id}`;
  form.put(targetUrl, {
    onSuccess: () => {
      showEditModal.value = false;
    },
  });
};
</script>

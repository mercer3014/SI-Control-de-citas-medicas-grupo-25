<template>
  <AppLayout>
    <template #header>
      <span>Ficha Clínica del Paciente</span>
    </template>

    <div class="space-y-4 max-w-4xl mx-auto">
      <div class="bg-white rounded border border-slate-200 shadow-xs overflow-hidden">
        <!-- Encabezado de la Ficha -->
        <div class="bg-[#0F2942] p-5 text-white flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
          <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded bg-blue-600 flex items-center justify-center font-bold text-lg text-white shadow-xs">
              {{ paciente.persona?.nombres?.charAt(0) }}{{ paciente.persona?.apellidos?.charAt(0) }}
            </div>
            <div>
              <h1 class="text-base font-bold">{{ paciente.persona?.nombre_completo }}</h1>
              <p class="text-xs text-blue-200 font-mono mt-0.5">
                CI: {{ paciente.persona?.ci }} &bull; Grupo Sanguíneo: <span class="text-amber-300 font-bold">{{ paciente.tipo_sangre || 'N/A' }}</span>
              </p>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <span 
              :class="[
                'px-2.5 py-1 text-xs rounded font-semibold',
                paciente.estado ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-400/30' : 'bg-rose-500/20 text-rose-300 border border-rose-400/30'
              ]"
            >
              {{ paciente.estado ? 'Paciente Activo' : 'Inactivo' }}
            </span>
            <button
              type="button"
              @click="showToggleConfirm = true"
              class="px-2.5 py-1 bg-white/10 hover:bg-white/20 text-white rounded text-xs transition cursor-pointer"
            >
              {{ paciente.estado ? 'Desactivar' : 'Reactivar' }}
            </button>
            <Link 
              :href="'/pacientes'" 
              class="px-3 py-1 bg-white/10 hover:bg-white/20 text-white rounded text-xs transition"
            >
              &larr; Volver
            </Link>
          </div>
        </div>

        <!-- Secciones de Información -->
        <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-5 text-xs">
          <!-- Datos Personales -->
          <div class="space-y-3">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider border-b border-slate-100 pb-1.5">
              Datos Personales
            </h3>
            <div class="space-y-2">
              <div class="flex justify-between py-1 border-b border-slate-50">
                <span class="text-slate-500">Documento de Identidad:</span>
                <span class="font-semibold text-slate-800">{{ paciente.persona?.ci }}</span>
              </div>
              <div class="flex justify-between py-1 border-b border-slate-50">
                <span class="text-slate-500">Fecha de Nacimiento:</span>
                <span class="font-semibold text-slate-800">{{ paciente.persona?.fecha_nacimiento ? new Date(paciente.persona.fecha_nacimiento).toLocaleDateString('es-BO') : '-' }}</span>
              </div>
              <div class="flex justify-between py-1 border-b border-slate-50">
                <span class="text-slate-500">Género:</span>
                <span class="font-semibold text-slate-800">{{ paciente.persona?.genero === 'M' ? 'Masculino' : (paciente.persona?.genero === 'F' ? 'Femenino' : 'Otro') }}</span>
              </div>
              <div class="flex justify-between py-1 border-b border-slate-50">
                <span class="text-slate-500">Teléfono:</span>
                <span class="font-semibold text-slate-800">{{ paciente.persona?.telefono || 'No registrado' }}</span>
              </div>
              <div class="flex justify-between py-1 border-b border-slate-50">
                <span class="text-slate-500">Dirección:</span>
                <span class="font-semibold text-slate-800">{{ paciente.persona?.direccion || 'No registrada' }}</span>
              </div>
            </div>
          </div>

          <!-- Contacto de Emergencia y Datos Clínicos -->
          <div class="space-y-3">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider border-b border-slate-100 pb-1.5">
              Contacto de Emergencia y Salud
            </h3>
            <div class="space-y-2">
              <div class="flex justify-between py-1 border-b border-slate-50">
                <span class="text-slate-500">Contacto de Emergencia:</span>
                <span class="font-semibold text-slate-800">{{ paciente.contacto_emergencia_nombre || 'No asignado' }}</span>
              </div>
              <div class="flex justify-between py-1 border-b border-slate-50">
                <span class="text-slate-500">Teléfono de Emergencia:</span>
                <span class="font-semibold text-slate-800">{{ paciente.contacto_emergencia_telefono || 'No asignado' }}</span>
              </div>
              <div class="flex justify-between py-1 border-b border-slate-50">
                <span class="text-slate-500">Grupo Sanguíneo:</span>
                <span class="font-bold text-rose-600">{{ paciente.tipo_sangre || 'Desconocido' }}</span>
              </div>
            </div>

            <!-- Antecedentes -->
            <div class="mt-4 pt-2 border-t border-slate-100">
              <span class="text-xs font-bold text-slate-700 block mb-1">Antecedentes Médicos y Alergias:</span>
              <div class="p-2.5 bg-slate-50 rounded border border-slate-200">
                <p v-if="paciente.antecedentes?.length" class="text-slate-700 leading-relaxed">
                  {{ paciente.antecedentes[0].descripcion }}
                </p>
                <p v-else class="text-slate-400 italic">
                  No se han registrado antecedentes patológicos ni alergias para este paciente.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Confirmación de cambio de estado -->
    <DialogModal
      :show="showToggleConfirm"
      :title="paciente.estado ? 'Desactivar paciente' : 'Reactivar paciente'"
      :message="`¿Estás seguro de que deseas ${paciente.estado ? 'dar de baja lógica' : 'reactivar'} al paciente ${paciente.persona?.nombre_completo}?`"
      :confirm-text="paciente.estado ? 'Sí, desactivar' : 'Sí, reactivar'"
      cancel-text="Cancelar"
      :type="paciente.estado ? 'warning' : 'info'"
      @confirm="executeToggle"
      @close="showToggleConfirm = false"
    />
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DialogModal from '@/Components/DialogModal.vue';

const props = defineProps({
  paciente: Object,
});

const showToggleConfirm = ref(false);

const executeToggle = () => {
  showToggleConfirm.value = false;
  router.delete(`/pacientes/${props.paciente.id}`);
};
</script>

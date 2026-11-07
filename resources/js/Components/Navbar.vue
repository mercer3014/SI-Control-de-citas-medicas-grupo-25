<template>
  <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-stone-200/70 bg-white/80 px-5 backdrop-blur-md">
    <div class="flex items-center gap-3 min-w-0">
      <button
        type="button"
        @click="$emit('toggle-sidebar')"
        class="lg:hidden -ml-1.5 flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-xs hover:bg-slate-50 hover:text-slate-900 transition cursor-pointer"
        title="Abrir menú"
      >
        <AppIcon name="menu" class="h-5 w-5" />
      </button>
      <h2 class="truncate text-[15px] font-bold tracking-tight text-stone-800">
        <slot name="title">Sistema Clínico</slot>
      </h2>
    </div>

    <div class="flex items-center gap-3">
      <!-- Fecha actual -->
      <div class="hidden md:flex items-center gap-2 text-[12px] font-medium text-stone-500">
        <AppIcon name="calendar" class="h-4 w-4 text-stone-400" />
        <span class="first-letter:uppercase">{{ fechaHoy }}</span>
      </div>

      <div class="hidden md:block h-5 w-px bg-stone-200"></div>

      <!-- Menú de usuario -->
      <div class="relative" ref="menuRef">
        <button
          type="button"
          class="flex items-center gap-2.5 rounded-xl border border-stone-200 bg-white py-1.5 pl-1.5 pr-3 transition hover:border-stone-300 hover:shadow-card cursor-pointer"
          @click="menuOpen = !menuOpen"
        >
          <Avatar :name="userName" size="sm" />
          <div class="hidden sm:block text-left leading-tight">
            <p class="max-w-[160px] truncate text-[12.5px] font-semibold text-stone-800">{{ userName }}</p>
            <p class="text-[10.5px] font-semibold uppercase tracking-wide text-brand-600">{{ userRole }}</p>
          </div>
          <AppIcon name="chevron-down" class="h-3.5 w-3.5 text-stone-400 transition-transform duration-200" :class="{ 'rotate-180': menuOpen }" />
        </button>

        <Transition name="modal">
          <div
            v-if="menuOpen"
            class="modal-panel absolute right-0 top-full mt-2 w-64 overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-modal"
          >
            <div class="border-b border-stone-100 bg-stone-50/70 px-4 py-3">
              <p class="text-[13px] font-bold text-stone-800 truncate">{{ userName }}</p>
              <p class="text-[12px] text-stone-500 truncate">{{ userEmail }}</p>
              <div class="mt-2">
                <StatusBadge :label="userRole" variant="brand" />
              </div>
            </div>
            <div class="p-1.5">
              <button
                type="button"
                class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-[13px] font-medium text-rose-600 transition hover:bg-rose-50 cursor-pointer"
                @click="requestLogout"
              >
                <AppIcon name="logout" class="h-4 w-4" />
                Cerrar sesión
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppIcon from './AppIcon.vue';
import Avatar from './Avatar.vue';
import StatusBadge from './StatusBadge.vue';

const emit = defineEmits(['request-logout', 'toggle-sidebar']);

const page = usePage();
const menuOpen = ref(false);
const menuRef = ref(null);

const userName = computed(() =>
  page.props.auth?.user?.persona?.nombre_completo || page.props.auth?.user?.username || 'Usuario'
);
const userRole = computed(() => page.props.auth?.user?.rol?.nombre || '');
const userEmail = computed(() => page.props.auth?.user?.email || '');

const fechaHoy = new Intl.DateTimeFormat('es-BO', {
  weekday: 'long',
  day: 'numeric',
  month: 'long',
  year: 'numeric',
}).format(new Date());

const requestLogout = () => {
  menuOpen.value = false;
  emit('request-logout');
};

const onClickOutside = (e) => {
  if (menuRef.value && !menuRef.value.contains(e.target)) {
    menuOpen.value = false;
  }
};

onMounted(() => document.addEventListener('click', onClickOutside));
onUnmounted(() => document.removeEventListener('click', onClickOutside));
</script>

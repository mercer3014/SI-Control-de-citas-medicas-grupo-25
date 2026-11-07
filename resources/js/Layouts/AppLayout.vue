<template>
  <div class="min-h-screen bg-surface">
    <!-- Menú Lateral Responsivo (Drawer en Móvil, Sidebar en Escritorio) -->
    <Sidebar
      :collapsed="sidebarCollapsed"
      :mobile-open="mobileSidebarOpen"
      @toggle="sidebarCollapsed = !sidebarCollapsed"
      @close-mobile="mobileSidebarOpen = false"
    />

    <!-- Contenedor Principal: En móvil ml-0 (NO empuja la pantalla); en escritorio ml-60 o ml-[68px] -->
    <div
      :class="[
        'flex min-h-screen flex-col transition-[margin] duration-300 ease-[cubic-bezier(0.22,1,0.36,1)]',
        'ml-0',
        sidebarCollapsed ? 'lg:ml-[68px]' : 'lg:ml-60'
      ]"
    >
      <Navbar
        @toggle-sidebar="mobileSidebarOpen = !mobileSidebarOpen"
        @request-logout="showLogoutModal = true"
      >
        <template #title>
          <slot name="header">Sistema de Citas Médicas</slot>
        </template>
      </Navbar>

      <!-- Contenido con transición de página -->
      <main class="flex-1 px-4 py-5 sm:px-6 sm:py-6 lg:px-8">
        <Transition name="page" mode="out-in">
          <div :key="$page.component">
            <slot />
          </div>
        </Transition>
      </main>

      <footer class="border-t border-slate-200/70 px-6 py-3 text-center text-[11px] text-slate-400">
        &copy; {{ new Date().getFullYear() }} — Clínica Vida Salud
      </footer>
    </div>

    <!-- Notificaciones -->
    <FlashToasts />

    <!-- Confirmación de cierre de sesión -->
    <DialogModal
      :show="showLogoutModal"
      title="Cerrar sesión"
      message="¿Estás seguro de que deseas salir del sistema? Cualquier operación sin guardar se perderá."
      confirm-text="Sí, cerrar sesión"
      cancel-text="Permanecer"
      type="danger"
      @confirm="handleLogout"
      @close="showLogoutModal = false"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';
import Navbar from '@/Components/Navbar.vue';
import DialogModal from '@/Components/DialogModal.vue';
import FlashToasts from '@/Components/FlashToasts.vue';

const sidebarCollapsed = ref(false);
const mobileSidebarOpen = ref(false);
const showLogoutModal = ref(false);

const handleLogout = () => {
  showLogoutModal.value = false;
  router.post('/logout');
};

let removeNavListener = null;

onMounted(() => {
  removeNavListener = router.on('navigate', () => {
    mobileSidebarOpen.value = false;
  });
});

onUnmounted(() => {
  if (removeNavListener) removeNavListener();
});
</script>

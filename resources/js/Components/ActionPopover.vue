<template>
  <div class="relative inline-block text-left" ref="containerRef">
    <!-- Botón Gatillo (···) -->
    <button
      type="button"
      @click.stop="toggleOpen"
      :aria-expanded="isOpen"
      :class="[
        'w-7 h-7 inline-flex items-center justify-center rounded-lg border transition cursor-pointer select-none',
        isOpen
          ? 'bg-slate-100 text-slate-900 border-slate-300 shadow-xs'
          : 'bg-white text-slate-500 hover:text-slate-800 hover:bg-slate-50 border-slate-200'
      ]"
      :title="title || 'Opciones'"
    >
      <AppIcon name="dots-horizontal" class="w-4 h-4" />
    </button>

    <!-- Contenido Flotante Popover -->
    <div
      v-if="isOpen"
      @click.stop
      class="absolute right-0 top-full mt-1.5 min-w-[170px] bg-white rounded-xl shadow-lg border border-slate-200/90 py-1.5 z-50 animate-in fade-in zoom-in-95 duration-100 divide-y divide-slate-100 text-xs"
      :class="align === 'left' ? 'left-0 right-auto' : 'right-0 left-auto'"
      role="menu"
    >
      <slot :close="close" />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import AppIcon from './AppIcon.vue';

const props = defineProps({
  align: {
    type: String,
    default: 'right', // 'right' | 'left'
  },
  title: {
    type: String,
    default: 'Más opciones',
  },
});

const isOpen = ref(false);
const containerRef = ref(null);

const toggleOpen = () => {
  isOpen.value = !isOpen.value;
};

const close = () => {
  isOpen.value = false;
};

const handleClickOutside = (event) => {
  if (containerRef.value && !containerRef.value.contains(event.target)) {
    close();
  }
};

const handleKeydown = (event) => {
  if (event.key === 'Escape') {
    close();
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
  document.addEventListener('keydown', handleKeydown);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside);
  document.removeEventListener('keydown', handleKeydown);
});

defineExpose({ close, isOpen });
</script>

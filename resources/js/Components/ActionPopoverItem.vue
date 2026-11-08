<template>
  <component
    :is="href ? Link : 'button'"
    :href="href"
    :type="href ? undefined : 'button'"
    :disabled="disabled"
    @click="$emit('click')"
    class="w-full text-left px-3 py-2 flex items-center gap-2.5 transition text-xs select-none disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
    :class="[
      danger
        ? 'text-rose-600 hover:bg-rose-50 hover:text-rose-700'
        : 'text-slate-700 hover:bg-slate-50 hover:text-slate-900'
    ]"
    role="menuitem"
  >
    <AppIcon v-if="icon" :name="icon" class="w-3.5 h-3.5 shrink-0" :class="danger ? 'text-rose-500' : 'text-slate-400'" />
    <span class="truncate"><slot>{{ label }}</slot></span>
  </component>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppIcon from './AppIcon.vue';

defineProps({
  icon: { type: String, default: '' },
  label: { type: String, default: '' },
  danger: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  href: { type: String, default: '' },
});

defineEmits(['click']);
</script>

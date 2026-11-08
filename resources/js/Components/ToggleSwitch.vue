<template>
  <button
    type="button"
    role="switch"
    :aria-checked="Boolean(modelValue)"
    :disabled="disabled"
    @click.stop="toggle"
    class="group relative inline-flex items-center shrink-0 cursor-pointer select-none transition-colors duration-200 ease-in-out focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
    :class="[
      size === 'sm' ? 'h-5 w-9' : 'h-6 w-11',
      modelValue ? 'bg-blue-600' : 'bg-slate-300'
    ]"
    :style="{ borderRadius: '9999px' }"
    :title="title || (modelValue ? 'Activo' : 'Inactivo')"
  >
    <!-- Thumb (círculo deslizante blanco con sombra) -->
    <span
      aria-hidden="true"
      class="pointer-events-none inline-block transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out"
      :class="[
        size === 'sm' 
          ? (modelValue ? 'translate-x-4 h-4 w-4' : 'translate-x-0.5 h-4 w-4')
          : (modelValue ? 'translate-x-5 h-5 w-5' : 'translate-x-0.5 h-5 w-5')
      ]"
    />
  </button>
</template>

<script setup>
const props = defineProps({
  modelValue: {
    type: [Boolean, Number],
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  size: {
    type: String,
    default: 'sm', // 'sm' o 'md'
  },
  title: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['update:modelValue', 'change']);

const toggle = () => {
  if (props.disabled) return;
  const newVal = !props.modelValue;
  emit('update:modelValue', newVal);
  emit('change', newVal);
};
</script>

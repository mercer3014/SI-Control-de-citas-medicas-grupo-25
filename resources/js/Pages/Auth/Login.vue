<template>
  <GuestLayout>
    <Head title="Iniciar sesión" />

    <div class="mb-7">
      <ApplicationLogo size="lg" variant="light" class="mb-5" />
      <h1 class="text-2xl font-bold tracking-tight text-slate-900">Iniciar sesión</h1>
    </div>

    <!-- Flash de éxito -->
    <div
      v-if="$page.props.flash?.success"
      class="mb-5 flex items-start gap-2.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3.5 py-2.5 text-xs font-medium text-emerald-700"
    >
      <AppIcon name="check-circle" class="mt-0.5 h-4 w-4 shrink-0" />
      <span>{{ $page.props.flash.success }}</span>
    </div>

    <form @submit.prevent="submit" class="space-y-4" novalidate>
      <FormField
        id="email"
        label="Correo electrónico"
        :error="emailFieldError"
        required
      >
        <template #default="{ id }">
          <div class="relative">
            <AppIcon name="mail" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input
              :id="id"
              v-model="form.email"
              type="email"
              required
              autofocus
              autocomplete="email"
              placeholder="nombre@correo.com"
              class="w-full rounded-lg border border-slate-300 bg-white py-2 text-xs text-slate-900 focus:border-slate-800 focus:ring-1 focus:ring-slate-800 focus:outline-none transition"
              style="padding-left: 2.5rem !important;"
            />
          </div>
        </template>
      </FormField>

      <FormField
        id="password"
        label="Contraseña"
        :error="passwordFieldError"
        required
      >
        <template #default="{ id }">
          <div class="relative">
            <AppIcon name="lock" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input
              :id="id"
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              required
              autocomplete="current-password"
              placeholder="••••••••"
              class="w-full rounded-lg border border-slate-300 bg-white py-2 text-xs text-slate-900 focus:border-slate-800 focus:ring-1 focus:ring-slate-800 focus:outline-none transition"
              style="padding-left: 2.5rem !important; padding-right: 2.5rem !important;"
            />
            <button
              type="button"
              @click="showPassword = !showPassword"
              class="absolute right-2.5 top-1/2 -translate-y-1/2 rounded p-1 text-slate-400 hover:text-slate-600 transition cursor-pointer"
              :title="showPassword ? 'Ocultar contraseña' : 'Ver contraseña'"
            >
              <AppIcon :name="showPassword ? 'eye-off' : 'eye'" class="h-4 w-4" />
            </button>
          </div>
        </template>
      </FormField>

      <div class="flex items-center justify-between pt-0.5">
        <label class="flex cursor-pointer items-center gap-2">
          <input
            v-model="form.remember"
            type="checkbox"
            class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-600 cursor-pointer"
          />
          <span class="text-xs text-slate-600">Recordarme</span>
        </label>
        <Link href="/forgot-password" class="text-xs text-slate-600 hover:text-slate-900 hover:underline">
          ¿Olvidaste tu contraseña?
        </Link>
      </div>

      <button
        type="submit"
        :disabled="form.processing"
        class="w-full py-2.5 px-4 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-lg shadow-xs transition disabled:opacity-50 cursor-pointer mt-1"
      >
        <span v-if="form.processing" class="inline-flex items-center gap-2 justify-center">
          <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
          </svg>
          Iniciando sesión...
        </span>
        <span v-else>Iniciar sesión</span>
      </button>
    </form>
  </GuestLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import FormField from '@/Components/FormField.vue';
import AppIcon from '@/Components/AppIcon.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const showPassword = ref(false);

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const emailFieldError = computed(() => {
  if (form.errors.email && form.errors.email.toLowerCase().includes('correo')) {
    return form.errors.email;
  }
  return '';
});

const passwordFieldError = computed(() => {
  return form.errors.password || form.errors.login || (form.errors.email && !form.errors.email.toLowerCase().includes('correo') ? form.errors.email : '');
});

const submit = () => {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  });
};
</script>

<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import Button from 'primevue/button'

const router = useRouter()
const auth = useAuthStore()

const cards = [
  {
    title: 'Pacientes',
    text: 'Historial, contacto y datos clínicos en un solo expediente.',
    tint: 'from-teal-400 to-emerald-600',
    to: 'patients',
  },
  {
    title: 'Doctores',
    text: 'Especialidad, cualificaciones y agenda de cada médico.',
    tint: 'from-cyan-400 to-teal-700',
    to: 'doctors',
  },
  {
    title: 'Citas',
    text: 'Horarios, estados y reportes por doctor.',
    tint: 'from-orange-400 to-rose-500',
    to: 'appointments',
  },
]
</script>

<template>
  <div class="space-y-8">
    <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-900 via-teal-600 to-cyan-500 px-5 py-10 text-white shadow-xl sm:px-8 sm:py-12">
      <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-orange-300/40 blur-2xl" />
      <div class="pointer-events-none absolute bottom-0 left-1/3 h-40 w-40 rounded-full bg-white/15 blur-2xl" />
      <p class="text-xs font-semibold uppercase tracking-[0.22em] text-teal-100">Parcial II · Nuevas tendencias</p>
      <h1 class="font-display mt-3 max-w-xl text-3xl font-bold leading-tight sm:text-4xl">Sistema de gestión médica</h1>
      <p class="mt-4 max-w-2xl text-teal-50">
        Vue 3, Pinia y PrimeVue sobre una API Laravel con Sanctum y Swagger.
      </p>
      <div class="mt-7 flex flex-wrap gap-3">
        <Button
          v-if="auth.isAuthenticated"
          label="Ir al dashboard"
          @click="router.push({ name: 'dashboard' })"
        />
        <template v-else>
          <Button label="Iniciar sesión" @click="router.push({ name: 'login' })" />
          <Button label="Registrarse" severity="contrast" @click="router.push({ name: 'register' })" />
        </template>
        <Button label="Contacto" severity="secondary" @click="router.push({ name: 'contact' })" />
      </div>
    </section>

    <div class="grid gap-4 sm:grid-cols-3">
      <button
        v-for="card in cards"
        :key="card.title"
        type="button"
        class="rounded-3xl bg-white p-5 text-left shadow-lg shadow-emerald-900/5 transition hover:-translate-y-1 hover:shadow-xl"
        @click="auth.isAuthenticated ? router.push({ name: card.to }) : router.push({ name: 'login' })"
      >
        <span
          class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br text-lg font-bold text-white shadow"
          :class="card.tint"
        >
          {{ card.title.slice(0, 1) }}
        </span>
        <h2 class="text-lg font-semibold text-emerald-950">{{ card.title }}</h2>
        <p class="mt-1 text-sm leading-relaxed text-emerald-900/70">{{ card.text }}</p>
      </button>
    </div>
  </div>
</template>

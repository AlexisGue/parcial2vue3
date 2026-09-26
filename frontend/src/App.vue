<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import Button from 'primevue/button'
import Toast from 'primevue/toast'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const menuOpen = ref(false)

const links = computed(() => {
  const base = [
    { name: 'home', label: 'Inicio' },
    { name: 'contact', label: 'Contacto' },
  ]
  if (!auth.isAuthenticated) return base
  return [
    ...base,
    { name: 'dashboard', label: 'Dashboard' },
    { name: 'patients', label: 'Pacientes' },
    { name: 'doctors', label: 'Doctores' },
    { name: 'appointments', label: 'Citas' },
  ]
})

watch(() => route.name, () => {
  menuOpen.value = false
})

function go(name) {
  menuOpen.value = false
  router.push({ name })
}

async function logout() {
  menuOpen.value = false
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="min-h-screen text-emerald-950">
    <Toast />
    <header class="sticky top-0 z-30 px-3 pt-3 sm:px-4">
      <div class="mx-auto flex max-w-6xl items-center gap-3 rounded-2xl border border-white/70 bg-emerald-950/95 px-3 py-2.5 text-white shadow-lg shadow-emerald-950/10 backdrop-blur">
        <button type="button" class="flex min-w-0 items-center gap-2 font-display font-semibold tracking-tight" title="Ir al inicio" @click="go('home')">
          <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-teal-300 text-emerald-950">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path stroke-linecap="round" d="M12 21s-7-4.4-7-10a4 4 0 0 1 7-2 4 4 0 0 1 7 2c0 5.6-7 10-7 10z" />
            </svg>
          </span>
          <span class="truncate">Clínica <span class="text-teal-300">Parcial2</span></span>
        </button>

        <nav class="ml-auto hidden items-center gap-1 md:flex">
          <button
            v-for="link in links"
            :key="link.name"
            type="button"
            class="rounded-full px-3 py-1.5 text-sm transition"
            :class="route.name === link.name ? 'bg-teal-300 font-semibold text-emerald-950' : 'text-emerald-100 hover:bg-white/10'"
            @click="go(link.name)"
          >
            {{ link.label }}
          </button>
        </nav>

        <div class="ml-auto hidden items-center gap-2 md:flex">
          <template v-if="auth.isAuthenticated">
            <span class="max-w-40 truncate rounded-full bg-white/10 px-3 py-1 text-sm text-teal-100">
              {{ auth.usuario?.name }}
            </span>
            <Button label="Salir" size="small" severity="secondary" @click="logout" />
          </template>
          <Button v-else-if="route.name !== 'login'" label="Entrar" size="small" @click="go('login')" />
        </div>

        <button
          type="button"
          class="ml-auto grid h-10 w-10 place-items-center rounded-xl bg-white/10 md:hidden"
          :aria-expanded="menuOpen"
          aria-label="Abrir menú"
          @click="menuOpen = !menuOpen"
        >
          <i :class="menuOpen ? 'pi pi-times' : 'pi pi-bars'" />
        </button>
      </div>

      <div v-if="menuOpen" class="mx-auto mt-2 max-w-6xl rounded-2xl border border-emerald-100 bg-white p-3 shadow-xl md:hidden">
        <button
          v-for="link in links"
          :key="link.name"
          type="button"
          class="block w-full rounded-xl px-3 py-3 text-left text-sm font-medium"
          :class="route.name === link.name ? 'bg-teal-50 text-emerald-900' : 'text-emerald-950 hover:bg-emerald-50'"
          @click="go(link.name)"
        >
          {{ link.label }}
        </button>
        <div class="mt-2 border-t border-emerald-100 pt-2">
          <button
            v-if="auth.isAuthenticated"
            type="button"
            class="block w-full rounded-xl px-3 py-3 text-left text-sm font-medium text-emerald-950 hover:bg-emerald-50"
            @click="logout"
          >
            Salir · {{ auth.usuario?.name }}
          </button>
          <button
            v-else-if="route.name !== 'login'"
            type="button"
            class="block w-full rounded-xl bg-emerald-950 px-3 py-3 text-left text-sm font-semibold text-white"
            @click="go('login')"
          >
            Entrar
          </button>
        </div>
      </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-6 sm:py-8">
      <RouterView />
    </main>
  </div>
</template>

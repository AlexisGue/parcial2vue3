<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api/http'
import { useAuthStore } from '@/stores/auth'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'

const auth = useAuthStore()
const router = useRouter()
const porEstado = ref([])
const porDoctor = ref([])
const mensajes = ref([])
const cargandoMensajes = ref(true)

function estado(value) {
  return {
    scheduled: 'Programada',
    confirmed: 'Confirmada',
    completed: 'Completada',
    cancelled: 'Cancelada',
    in_progress: 'En curso',
    no_show: 'No asistió',
  }[value] || value
}

onMounted(async () => {
  const reportes = Promise.all([
    api.get('/reportes/citas-por-estado'),
    api.get('/reportes/citas-por-doctor'),
  ]).then(([a, b]) => {
    porEstado.value = a.data.data ?? []
    porDoctor.value = b.data.data ?? []
  }).catch(() => {})

  const contacto = api.get('/contacto').then(({ data }) => {
    mensajes.value = Array.isArray(data.data) ? data.data : []
  }).catch(() => {
    mensajes.value = []
  }).finally(() => {
    cargandoMensajes.value = false
  })

  await Promise.all([reportes, contacto])
})
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="font-display text-2xl font-semibold sm:text-3xl">Dashboard</h1>
      <p class="text-sm text-slate-500">Hola, {{ auth.usuario?.name }}. Reportes del enunciado.</p>
    </div>

    <div class="flex flex-wrap gap-2">
      <Button label="Pacientes" @click="router.push({ name: 'patients' })" />
      <Button label="Doctores" severity="secondary" @click="router.push({ name: 'doctors' })" />
      <Button label="Citas" severity="help" @click="router.push({ name: 'appointments' })" />
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
      <Card>
        <template #title>Citas por estado</template>
        <template #content>
          <DataTable :value="porEstado" size="small">
            <Column header="Estado">
              <template #body="{ data }">{{ estado(data.estado) }}</template>
            </Column>
            <Column field="total" header="Total" />
          </DataTable>
        </template>
      </Card>
      <Card>
        <template #title>Citas por doctor</template>
        <template #content>
          <DataTable :value="porDoctor" size="small">
            <Column field="doctor" header="Doctor" />
            <Column field="total" header="Total" />
          </DataTable>
        </template>
      </Card>
    </div>

    <Card :key="cargandoMensajes ? 'cargando' : `mensajes-${mensajes.length}`">
      <template #title>Mensajes de contacto</template>
      <template #content>
        <p v-if="cargandoMensajes" class="text-sm text-slate-500">Cargando mensajes…</p>
        <p v-else-if="!mensajes.length" class="text-sm text-slate-500">Aún no hay mensajes.</p>
        <ul v-else class="space-y-3">
          <li v-for="item in mensajes" :key="item.id" class="rounded-xl bg-teal-50 px-3 py-2">
            <p class="text-sm font-medium text-emerald-900">{{ item.email }}</p>
            <p class="text-sm text-slate-700">{{ item.mensaje }}</p>
          </li>
        </ul>
      </template>
    </Card>
  </div>
</template>

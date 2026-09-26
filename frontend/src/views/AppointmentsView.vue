<script setup>
import { onMounted, reactive, ref } from 'vue'
import api from '@/api/http'
import { useAppointmentsStore } from '@/stores/appointments'
import { useToast } from 'primevue/usetoast'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import InputText from 'primevue/inputtext'
import Card from 'primevue/card'

const store = useAppointmentsStore()
const toast = useToast()
const visible = ref(false)
const patients = ref([])
const doctors = ref([])
const form = reactive({
  paciente_id: null,
  doctor_id: null,
  fecha_cita: '',
  notas: '',
})

async function loadOptions() {
  const [p, d] = await Promise.all([api.get('/pacientes'), api.get('/doctores')])
  patients.value = p.data.data.map((x) => ({ label: x.nombre, value: x.id }))
  doctors.value = d.data.data.map((x) => ({ label: x.nombre, value: x.id }))
}

function openCreate() {
  Object.assign(form, { paciente_id: null, doctor_id: null, fecha_cita: '', notas: '' })
  visible.value = true
}

async function save() {
  try {
    await store.agendar(form)
    toast.add({ severity: 'success', summary: 'Cita agendada', life: 2000 })
    visible.value = false
  } catch (e) {
    const data = e.response?.data
    const detail = data?.message
      || data?.errors?.fecha_cita?.[0]
      || data?.errors?.paciente_id?.[0]
      || 'No se pudo agendar'
    toast.add({ severity: 'error', summary: 'No se pudo agendar', detail, life: 4000 })
  }
}

async function setStatus(row, estado) {
  try {
    await store.actualizarEstado(row.id, estado)
  } catch (e) {
    toast.add({ severity: 'error', summary: 'No se pudo cambiar el estado', detail: e.response?.data?.message || 'Intenta de nuevo', life: 4000 })
  }
}

function fecha(value) {
  if (!value) return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleString('es-SV', { dateStyle: 'medium', timeStyle: 'short' })
}

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
  await Promise.all([store.obtenerCitas(), loadOptions()])
})
</script>

<template>
  <Card>
    <template #title>
      <div class="flex items-center justify-between gap-3">
        <span>Citas</span>
        <Button label="Nueva cita" icon="pi pi-plus" size="small" @click="openCreate" />
      </div>
    </template>
    <template #content>
      <DataTable :value="store.lista" :loading="store.loading" paginator :rows="8" size="small">
        <Column header="Fecha">
          <template #body="{ data }">{{ fecha(data.fecha_cita) }}</template>
        </Column>
        <Column field="paciente" header="Paciente" />
        <Column field="doctor" header="Doctor" />
        <Column header="Estado">
          <template #body="{ data }">{{ estado(data.estado) }}</template>
        </Column>
        <Column header="Acciones">
          <template #body="{ data }">
            <div class="flex flex-wrap gap-1">
              <Button label="Confirmar" size="small" text @click="setStatus(data, 'confirmed')" />
              <Button label="Completar" size="small" text severity="success" @click="setStatus(data, 'completed')" />
              <Button label="Cancelar" size="small" text severity="danger" @click="setStatus(data, 'cancelled')" />
            </div>
          </template>
        </Column>
      </DataTable>
    </template>
  </Card>

  <Dialog v-model:visible="visible" modal header="Agendar cita" class="w-full max-w-lg">
    <form class="space-y-3" @submit.prevent="save">
      <div>
        <label class="mb-1 block text-sm">Paciente</label>
        <Select v-model="form.paciente_id" :options="patients" option-label="label" option-value="value" class="w-full" required />
      </div>
      <div>
        <label class="mb-1 block text-sm">Doctor</label>
        <Select v-model="form.doctor_id" :options="doctors" option-label="label" option-value="value" class="w-full" required />
      </div>
      <div>
        <label class="mb-1 block text-sm">Fecha y hora</label>
        <InputText v-model="form.fecha_cita" type="datetime-local" class="w-full" required />
      </div>
      <div>
        <label class="mb-1 block text-sm">Notas</label>
        <Textarea v-model="form.notas" rows="3" class="w-full" />
      </div>
      <Button type="submit" label="Agendar" class="w-full" />
    </form>
  </Dialog>
</template>

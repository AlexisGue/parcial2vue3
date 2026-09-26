<script setup>
import { onMounted, reactive, ref } from 'vue'
import api from '@/api/http'
import { useToast } from 'primevue/usetoast'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Card from 'primevue/card'

const toast = useToast()
const loading = ref(false)
const rows = ref([])
const visible = ref(false)
const editing = ref(null)
const form = reactive({
  nombre: '',
  email: '',
  especialidad: '',
  cualificaciones: '',
})

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/doctores')
    rows.value = data.data
  } finally {
    loading.value = false
  }
}

function openCreate() {
  editing.value = null
  Object.assign(form, { nombre: '', email: '', especialidad: '', cualificaciones: '' })
  visible.value = true
}

function openEdit(row) {
  editing.value = row
  Object.assign(form, {
    nombre: row.nombre || '',
    email: row.email || '',
    especialidad: row.especialidad || '',
    cualificaciones: row.cualificaciones || '',
  })
  visible.value = true
}

async function save() {
  try {
    if (editing.value) {
      await api.put(`/doctores/${editing.value.id}`, form)
      toast.add({ severity: 'success', summary: 'Doctor actualizado', life: 2000 })
    } else {
      await api.post('/doctores', form)
      toast.add({ severity: 'success', summary: 'Doctor creado', life: 2000 })
    }
    visible.value = false
    await load()
  } catch (e) {
    toast.add({ severity: 'error', summary: 'No se pudo guardar', detail: e.response?.data?.message || 'Revisa correo y datos', life: 4000 })
  }
}

async function remove(row) {
  if (!confirm(`¿Eliminar a ${row.nombre}?`)) return
  try {
    await api.delete(`/doctores/${row.id}`)
    toast.add({ severity: 'success', summary: 'Doctor eliminado', life: 2000 })
    await load()
  } catch (e) {
    toast.add({ severity: 'error', summary: 'No se pudo eliminar', detail: e.response?.data?.message || 'Tiene citas asociadas', life: 4000 })
  }
}

onMounted(load)
</script>

<template>
  <Card>
    <template #title>
      <div class="flex items-center justify-between gap-3">
        <span>Doctores</span>
        <Button label="Nuevo doctor" icon="pi pi-plus" size="small" @click="openCreate" />
      </div>
    </template>
    <template #content>
      <DataTable :value="rows" :loading="loading" paginator :rows="8" size="small">
        <Column field="nombre" header="Nombre" />
        <Column field="email" header="Correo" />
        <Column field="especialidad" header="Especialidad" />
        <Column header="Acciones">
          <template #body="{ data }">
            <div class="flex gap-2">
              <Button icon="pi pi-pencil" size="small" text @click="openEdit(data)" />
              <Button icon="pi pi-trash" size="small" text severity="danger" @click="remove(data)" />
            </div>
          </template>
        </Column>
      </DataTable>
    </template>
  </Card>

  <Dialog v-model:visible="visible" modal :header="editing ? 'Editar doctor' : 'Nuevo doctor'" class="w-full max-w-lg">
    <form class="space-y-3" @submit.prevent="save">
      <div>
        <label class="mb-1 block text-sm">Nombre</label>
        <InputText v-model="form.nombre" class="w-full" required />
      </div>
      <div>
        <label class="mb-1 block text-sm">Correo</label>
        <InputText v-model="form.email" type="email" class="w-full" required />
      </div>
      <div>
        <label class="mb-1 block text-sm">Especialidad</label>
        <InputText v-model="form.especialidad" class="w-full" />
      </div>
      <div>
        <label class="mb-1 block text-sm">Cualificaciones</label>
        <Textarea v-model="form.cualificaciones" rows="3" class="w-full" />
      </div>
      <Button type="submit" label="Guardar" class="w-full" />
    </form>
  </Dialog>
</template>

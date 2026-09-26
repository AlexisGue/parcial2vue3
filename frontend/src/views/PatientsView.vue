<script setup>
import { onMounted, reactive, ref } from 'vue'
import { usePatientsStore } from '@/stores/patients'
import { useToast } from 'primevue/usetoast'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Card from 'primevue/card'

const store = usePatientsStore()
const toast = useToast()
const visible = ref(false)
const editing = ref(null)
const form = reactive({
  nombre: '',
  email: '',
  telefono: '',
  fecha_nacimiento: '',
  historial_medico: '',
})

function openCreate() {
  editing.value = null
  Object.assign(form, { nombre: '', email: '', telefono: '', fecha_nacimiento: '', historial_medico: '' })
  visible.value = true
}

function openEdit(row) {
  editing.value = row
  Object.assign(form, {
    nombre: row.nombre || '',
    email: row.email || '',
    telefono: row.telefono || '',
    fecha_nacimiento: row.fecha_nacimiento || '',
    historial_medico: row.historial_medico || '',
  })
  visible.value = true
}

async function save() {
  try {
    if (editing.value) {
      await store.actualizar(editing.value.id, form)
      toast.add({ severity: 'success', summary: 'Paciente actualizado', life: 2000 })
    } else {
      await store.crear(form)
      toast.add({ severity: 'success', summary: 'Paciente creado', life: 2000 })
    }
    visible.value = false
  } catch (e) {
    toast.add({ severity: 'error', summary: 'No se pudo guardar', detail: e.response?.data?.message || 'Revisa los datos', life: 4000 })
  }
}

async function remove(row) {
  if (!confirm(`¿Eliminar a ${row.nombre}?`)) return
  try {
    await store.eliminar(row.id)
    toast.add({ severity: 'success', summary: 'Paciente eliminado', life: 2000 })
  } catch (e) {
    toast.add({ severity: 'error', summary: 'No se pudo eliminar', detail: e.response?.data?.message || 'Tiene citas asociadas', life: 4000 })
  }
}

onMounted(() => store.obtenerPacientes())
</script>

<template>
  <Card>
    <template #title>
      <div class="flex items-center justify-between gap-3">
        <span>Pacientes</span>
        <Button label="Nuevo paciente" icon="pi pi-plus" size="small" @click="openCreate" />
      </div>
    </template>
    <template #content>
      <DataTable :value="store.lista" :loading="store.loading" paginator :rows="8" size="small">
        <Column field="nombre" header="Nombre" />
        <Column field="email" header="Correo" />
        <Column field="telefono" header="Teléfono" />
        <Column field="fecha_nacimiento" header="Nacimiento" />
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

  <Dialog v-model:visible="visible" modal :header="editing ? 'Editar paciente' : 'Nuevo paciente'" class="w-full max-w-lg">
    <form class="space-y-3" @submit.prevent="save">
      <div>
        <label class="mb-1 block text-sm">Nombre</label>
        <InputText v-model="form.nombre" class="w-full" required />
      </div>
      <div>
        <label class="mb-1 block text-sm">Correo</label>
        <InputText v-model="form.email" type="email" class="w-full" />
      </div>
      <div>
        <label class="mb-1 block text-sm">Teléfono</label>
        <InputText v-model="form.telefono" class="w-full" />
      </div>
      <div>
        <label class="mb-1 block text-sm">Fecha nacimiento</label>
        <InputText v-model="form.fecha_nacimiento" type="date" class="w-full" />
      </div>
      <div>
        <label class="mb-1 block text-sm">Historial médico</label>
        <Textarea v-model="form.historial_medico" rows="3" class="w-full" />
      </div>
      <Button type="submit" label="Guardar" class="w-full" />
    </form>
  </Dialog>
</template>

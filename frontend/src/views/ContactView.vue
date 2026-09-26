<script setup>
import { reactive, ref } from 'vue'
import api from '@/api/http'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Message from 'primevue/message'

const toast = useToast()
const loading = ref(false)
const error = ref(null)
const form = reactive({ email: '', mensaje: '' })

async function submit() {
  loading.value = true
  error.value = null
  try {
    await api.post('/contacto', form)
    toast.add({ severity: 'success', summary: 'Enviado', detail: 'Lo verás en el Dashboard', life: 3000 })
    form.email = ''
    form.mensaje = ''
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudo enviar'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="grid gap-6 lg:grid-cols-2">
    <Card>
      <template #title>Contacto</template>
      <template #subtitle>Escríbenos — el mensaje se envía a la API Laravel</template>
      <template #content>
        <Message v-if="error" severity="error" class="mb-3" :closable="false">{{ error }}</Message>
        <form class="space-y-4" @submit.prevent="submit">
          <div>
            <label class="mb-1 block text-sm">Correo</label>
            <InputText v-model="form.email" type="email" class="w-full" required />
          </div>
          <div>
            <label class="mb-1 block text-sm">Mensaje</label>
            <Textarea v-model="form.mensaje" rows="5" class="w-full" required />
          </div>
          <Button type="submit" label="Enviar" :loading="loading" />
        </form>
      </template>
    </Card>

    <Card>
      <template #title>Ubicación</template>
      <template #content>
        <p class="mb-3 text-sm text-slate-600">Mapa de la clínica (demo).</p>
        <div class="overflow-hidden rounded-xl border border-slate-200">
          <iframe
            title="Mapa clínica"
            class="h-72 w-full"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            src="https://maps.google.com/maps?q=San%20Salvador&t=&z=13&ie=UTF8&iwloc=&output=embed"
          />
        </div>
      </template>
    </Card>
  </div>
</template>

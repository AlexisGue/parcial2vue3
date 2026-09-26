<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Message from 'primevue/message'

const auth = useAuthStore()
const router = useRouter()
const loading = ref(false)
const error = ref(null)
const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

async function submit() {
  loading.value = true
  error.value = null
  try {
    await auth.registro({ ...form })
    router.push({ name: 'dashboard' })
  } catch (e) {
    const errors = e.response?.data?.errors
    const first = errors ? Object.values(errors).flat()[0] : null
    error.value = first || e.response?.data?.message || 'No se pudo registrar'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="mx-auto max-w-md">
    <Card>
      <template #title>Registro</template>
      <template #content>
        <Message v-if="error" severity="error" class="mb-3" :closable="false">{{ error }}</Message>
        <form class="space-y-4" @submit.prevent="submit">
          <div>
            <label class="mb-1 block text-sm">Nombre</label>
            <InputText v-model="form.name" class="w-full" required />
          </div>
          <div>
            <label class="mb-1 block text-sm">Correo</label>
            <InputText v-model="form.email" type="email" class="w-full" required />
          </div>
          <div>
            <label class="mb-1 block text-sm">Contraseña</label>
            <Password v-model="form.password" class="w-full" input-class="w-full" toggle-mask required />
          </div>
          <div>
            <label class="mb-1 block text-sm">Confirmar contraseña</label>
            <Password v-model="form.password_confirmation" class="w-full" input-class="w-full" :feedback="false" toggle-mask required />
          </div>
          <Button type="submit" label="Crear cuenta" class="w-full" :loading="loading" />
        </form>
      </template>
    </Card>
  </div>
</template>

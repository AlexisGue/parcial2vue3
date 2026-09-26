<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Message from 'primevue/message'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const toast = useToast()

const step = ref(1)
const loading = ref(false)
const error = ref(null)
const form = reactive({ email: '', password: '' })
const code = ref('')

async function submitLogin() {
  loading.value = true
  error.value = null
  try {
    await auth.login({ email: form.email, password: form.password })
    step.value = 2
    toast.add({
      severity: 'info',
      summary: '2FA',
      detail: `Código demo: ${auth.demoCode}`,
      life: 8000,
    })
  } catch (e) {
    error.value = e.response?.data?.message || e.response?.data?.errors?.email?.[0] || 'No se pudo iniciar sesión'
  } finally {
    loading.value = false
  }
}

async function submit2fa() {
  loading.value = true
  error.value = null
  try {
    await auth.verificar2fa(code.value)
    const redirect = route.query.redirect
    router.push(typeof redirect === 'string' ? redirect : { name: 'dashboard' })
  } catch (e) {
    error.value = e.response?.data?.errors?.code?.[0] || e.response?.data?.message || 'Código inválido'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="mx-auto max-w-md">
    <Card>
      <template #title>Iniciar sesión</template>
      <template #subtitle>Incluye verificación 2FA</template>
      <template #content>
        <Message v-if="error" severity="error" class="mb-3" :closable="false">{{ error }}</Message>

        <form v-if="step === 1" class="space-y-4" @submit.prevent="submitLogin">
          <div>
            <label class="mb-1 block text-sm">Correo</label>
            <InputText v-model="form.email" type="email" class="w-full" required />
          </div>
          <div>
            <label class="mb-1 block text-sm">Contraseña</label>
            <Password v-model="form.password" class="w-full" input-class="w-full" :feedback="false" toggle-mask required />
          </div>
          <Button type="submit" label="Continuar" class="w-full" :loading="loading" />
        </form>

        <form v-else class="space-y-4" @submit.prevent="submit2fa">
          <p class="text-sm text-slate-600">Ingresa el código de verificación de 6 dígitos.</p>
          <div>
            <label class="mb-1 block text-sm">Código 2FA</label>
            <InputText v-model="code" class="w-full" maxlength="6" required />
          </div>
          <Button type="submit" label="Verificar y entrar" class="w-full" :loading="loading" />
          <Button type="button" label="Volver" text class="w-full" @click="step = 1" />
        </form>
      </template>
    </Card>
  </div>
</template>

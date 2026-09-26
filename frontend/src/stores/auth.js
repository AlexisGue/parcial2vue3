import { defineStore } from 'pinia'
import api from '@/api/http'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    usuario: JSON.parse(localStorage.getItem('parcial_user') || 'null'),
    token: localStorage.getItem('parcial_token') || null,
    challengeId: null,
    demoCode: null,
  }),

  getters: {
    isAuthenticated: (state) => Boolean(state.token),
  },

  actions: {
    async registro(payload) {
      const { data } = await api.post('/register', payload)
      this._guardarSesion(data.data.token, data.data.user)
    },

    async login(payload) {
      const { data } = await api.post('/login', payload)
      this.challengeId = data.data.challenge_id
      this.demoCode = data.data.demo_code
      return data.data
    },

    async verificar2fa(code) {
      const { data } = await api.post('/login/verify-2fa', {
        challenge_id: this.challengeId,
        code,
      })
      this._guardarSesion(data.data.token, data.data.user)
      this.challengeId = null
      this.demoCode = null
    },

    async logout() {
      try {
        await api.post('/logout')
      } catch {
        // 401: el token ya no sirve; igual se limpia la sesión local.
      } finally {
        this.forget()
      }
    },

    async verificarToken() {
      if (!this.token) return false
      try {
        const { data } = await api.get('/me')
        this.usuario = data.data
        localStorage.setItem('parcial_user', JSON.stringify(data.data))
        return true
      } catch {
        this.forget()
        return false
      }
    },

    _guardarSesion(token, user) {
      this.token = token
      this.usuario = user
      localStorage.setItem('parcial_token', token)
      localStorage.setItem('parcial_user', JSON.stringify(user))
    },

    forget() {
      this.token = null
      this.usuario = null
      localStorage.removeItem('parcial_token')
      localStorage.removeItem('parcial_user')
    },
  },
})

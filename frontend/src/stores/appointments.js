import { defineStore } from 'pinia'
import api from '@/api/http'

export const useAppointmentsStore = defineStore('appointments', {
  state: () => ({
    lista: [],
    actual: null,
    filtros: { estado: '' },
    loading: false,
    error: null,
  }),

  actions: {
    async obtenerCitas() {
      this.loading = true
      this.error = null
      try {
        const { data } = await api.get('/citas')
        this.lista = data.data
      } catch (e) {
        this.error = e.response?.data?.message || 'Error al cargar citas'
      } finally {
        this.loading = false
      }
    },

    async agendar(payload) {
      const { data } = await api.post('/citas', payload)
      this.lista.unshift(data.data)
      return data.data
    },

    async actualizarEstado(id, estado) {
      const { data } = await api.put(`/citas/${id}`, { estado })
      const i = this.lista.findIndex((c) => c.id === id)
      if (i >= 0) this.lista[i] = data.data
      return data.data
    },
  },
})

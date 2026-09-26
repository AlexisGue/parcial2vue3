import { defineStore } from 'pinia'
import api from '@/api/http'

export const usePatientsStore = defineStore('patients', {
  state: () => ({
    lista: [],
    actual: null,
    loading: false,
    error: null,
  }),

  actions: {
    async obtenerPacientes() {
      this.loading = true
      this.error = null
      try {
        const { data } = await api.get('/pacientes')
        this.lista = data.data
      } catch (e) {
        this.error = e.response?.data?.message || 'Error al cargar pacientes'
      } finally {
        this.loading = false
      }
    },

    async crear(payload) {
      const { data } = await api.post('/pacientes', payload)
      this.lista.unshift(data.data)
      return data.data
    },

    async actualizar(id, payload) {
      const { data } = await api.put(`/pacientes/${id}`, payload)
      const i = this.lista.findIndex((p) => p.id === id)
      if (i >= 0) this.lista[i] = data.data
      return data.data
    },

    async eliminar(id) {
      await api.delete(`/pacientes/${id}`)
      this.lista = this.lista.filter((p) => p.id !== id)
    },
  },
})

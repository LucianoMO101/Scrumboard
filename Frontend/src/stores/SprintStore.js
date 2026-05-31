import { defineStore } from 'pinia'
import axios from '@/BaseURL'

export const useSprintStore = defineStore('sprint', {
  state: () => ({
    sprints: [],
    currentSprint: null,
    isLoading: false,
    error: null,
  }),

  getters: {
    getAllSprints: (state) => state.sprints,
    getCurrentSprint: (state) => state.currentSprint,
    getActiveSprint: (state) => state.sprints.find((s) => s.status === 'active'),
  },

  actions: {
    async fetchSprints(projectId) {
      this.isLoading = true
      this.error = null
      try {
        const response = await axios.get(`/projects/${projectId}/sprints`)
        this.sprints = response.data.data
        return true
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to fetch sprints'
        return false
      } finally {
        this.isLoading = false
      }
    },

    async getSprintById(sprintId) {
      this.isLoading = true
      this.error = null
      try {
        const response = await axios.get(`/sprints/${sprintId}`)
        this.currentSprint = response.data.data
        return true
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to fetch sprint'
        return false
      } finally {
        this.isLoading = false
      }
    },

    async createSprint(projectId, sprintName, description, startDate, endDate) {
      this.isLoading = true
      this.error = null
      try {
        const response = await axios.post(`/sprints`, {
          project_id: projectId,
          sprint_name: sprintName,
          description,
          start_date: startDate,
          end_date: endDate,
        })
        const newSprint = response.data.data
        if (newSprint) {
          this.sprints.push(newSprint)
        } else {
          await this.fetchSprints(projectId)
        }
        return true
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to create sprint'
        return false
      } finally {
        this.isLoading = false
      }
    },

    async updateSprint(sprintId, sprintName, description, startDate, endDate) {
      this.isLoading = true
      this.error = null
      try {
        const response = await axios.put(`/sprints/${sprintId}`, {
          sprint_name: sprintName,
          description,
          start_date: startDate,
          end_date: endDate,
        })
        const index = this.sprints.findIndex((s) => s.sprint_id === sprintId)
        if (index !== -1) {
          this.sprints[index] = response.data.data
        }
        if (this.currentSprint?.sprint_id === sprintId) {
          this.currentSprint = response.data.data
        }
        return true
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to update sprint'
        return false
      } finally {
        this.isLoading = false
      }
    },

    async startSprint(sprintId) {
      this.isLoading = true
      this.error = null
      try {
        const response = await axios.post(`/sprints/${sprintId}/start`)
        const updated = response.data.data
        const index = this.sprints.findIndex((s) => s.sprint_id === sprintId)
        if (index !== -1) {
          if (updated) {
            this.sprints[index] = updated
          } else {
            this.sprints[index] = { ...this.sprints[index], status: 'active' }
          }
        }
        if (this.currentSprint?.sprint_id === sprintId) {
          this.currentSprint = updated || { ...this.currentSprint, status: 'active' }
        }
        return true
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to start sprint'
        return false
      } finally {
        this.isLoading = false
      }
    },

    async completeSprint(sprintId) {
      this.isLoading = true
      this.error = null
      try {
        const response = await axios.post(`/sprints/${sprintId}/complete`)
        const updated = response.data.data
        const index = this.sprints.findIndex((s) => s.sprint_id === sprintId)
        if (index !== -1) {
          if (updated) {
            this.sprints[index] = updated
          } else {
            this.sprints[index] = { ...this.sprints[index], status: 'completed' }
          }
        }
        if (this.currentSprint?.sprint_id === sprintId) {
          this.currentSprint = updated || { ...this.currentSprint, status: 'completed' }
        }
        return true
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to complete sprint'
        return false
      } finally {
        this.isLoading = false
      }
    },

    async reopenSprint(sprintId) {
      this.isLoading = true
      this.error = null
      try {
        const response = await axios.post(`/sprints/${sprintId}/reopen`)
        const updated = response.data.data
        const index = this.sprints.findIndex((s) => s.sprint_id === sprintId)
        if (index !== -1) {
          this.sprints[index] = updated || { ...this.sprints[index], status: 'active' }
        }
        if (this.currentSprint?.sprint_id === sprintId) {
          this.currentSprint = updated || { ...this.currentSprint, status: 'active' }
        }
        return true
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to reopen sprint'
        return false
      } finally {
        this.isLoading = false
      }
    },

    async deleteSprint(sprintId) {
      this.isLoading = true
      this.error = null
      try {
        await axios.delete(`/sprints/${sprintId}`)
        this.sprints = this.sprints.filter((s) => s.sprint_id !== sprintId)
        if (this.currentSprint?.sprint_id === sprintId) {
          this.currentSprint = null
        }
        return true
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to delete sprint'
        return false
      } finally {
        this.isLoading = false
      }
    },
  },
})

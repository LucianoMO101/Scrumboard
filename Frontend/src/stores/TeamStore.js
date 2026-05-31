import { defineStore } from 'pinia'
import axios from '@/BaseURL'

export const useTeamStore = defineStore('team', {
  state: () => ({
    teams: [],
    currentTeam: null,
    invitations: [],
    isLoading: false,
    error: null,
  }),

  getters: {
    // Teams where the logged-in user can create projects (admin or owner)
    adminTeams: (state) => state.teams.filter((t) => t.user_role === 'admin'),
  },

  actions: {
    async fetchMyTeams() {
      this.isLoading = true
      this.error = null
      try {
        const response = await axios.get('/teams')
        this.teams = response.data.data || []
        return true
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to fetch teams'
        return false
      } finally {
        this.isLoading = false
      }
    },

    async fetchTeam(teamId) {
      this.isLoading = true
      this.error = null
      try {
        const response = await axios.get(`/teams/${teamId}`)
        this.currentTeam = response.data.data || null
        return true
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to fetch team'
        return false
      } finally {
        this.isLoading = false
      }
    },

    async createTeam(teamName, description) {
      this.isLoading = true
      this.error = null
      try {
        const response = await axios.post('/teams', { team_name: teamName, description })
        const newTeam = response.data.data
        this.teams.push(newTeam)
        return newTeam
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to create team'
        return null
      } finally {
        this.isLoading = false
      }
    },

    async updateTeam(teamId, teamName, description) {
      this.isLoading = true
      this.error = null
      try {
        const response = await axios.put(`/teams/${teamId}`, { team_name: teamName, description })
        const updated = response.data.data
        const idx = this.teams.findIndex((t) => t.team_id === teamId)
        if (idx !== -1) this.teams[idx] = updated
        if (this.currentTeam?.team_id === teamId) {
          this.currentTeam = { ...this.currentTeam, ...updated }
        }
        return updated
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to update team'
        return null
      } finally {
        this.isLoading = false
      }
    },

    async deleteTeam(teamId) {
      this.isLoading = true
      this.error = null
      try {
        await axios.delete(`/teams/${teamId}`)
        this.teams = this.teams.filter((t) => t.team_id !== teamId)
        if (this.currentTeam?.team_id === teamId) this.currentTeam = null
        return true
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to delete team'
        return false
      } finally {
        this.isLoading = false
      }
    },

    async inviteUser(teamId, email) {
      this.isLoading = true
      this.error = null
      try {
        await axios.post(`/teams/${teamId}/invite`, { email })
        return true
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to send invitation'
        return false
      } finally {
        this.isLoading = false
      }
    },

    async removeMember(teamId, userId) {
      this.isLoading = true
      this.error = null
      try {
        await axios.delete(`/teams/${teamId}/members/${userId}`)
        if (this.currentTeam?.team_id === teamId) {
          this.currentTeam.members = (this.currentTeam.members || []).filter(
            (m) => m.user_id !== userId
          )
        }
        return true
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to remove member'
        return false
      } finally {
        this.isLoading = false
      }
    },

    async fetchMyInvitations() {
      this.isLoading = true
      this.error = null
      try {
        const response = await axios.get('/users/me/invitations')
        this.invitations = response.data.data || []
        return true
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to fetch invitations'
        return false
      } finally {
        this.isLoading = false
      }
    },

    async acceptInvitation(invitationId) {
      this.isLoading = true
      this.error = null
      try {
        await axios.post(`/invitations/${invitationId}/accept`)
        this.invitations = this.invitations.filter((i) => i.invitation_id !== invitationId)
        await this.fetchMyTeams()
        return true
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to accept invitation'
        return false
      } finally {
        this.isLoading = false
      }
    },

    async declineInvitation(invitationId) {
      this.isLoading = true
      this.error = null
      try {
        await axios.post(`/invitations/${invitationId}/decline`)
        this.invitations = this.invitations.filter((i) => i.invitation_id !== invitationId)
        return true
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to decline invitation'
        return false
      } finally {
        this.isLoading = false
      }
    },
  },
})

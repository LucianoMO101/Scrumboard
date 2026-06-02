import { defineStore } from 'pinia'
import axios from '@/BaseURL'

export const useProjectStore = defineStore('project', {
  state: () => ({
    projects: [],
    currentProject: null,
    members: [],
    isLoading: false,
    error: null,
  }),

  getters: {
    getAllProjects: (state) => state.projects,
    getCurrentProject: (state) => state.currentProject,
    getProjectMembers: (state) => state.members,

    // Returns array of { team_id, team_name, projects[] } sorted by team name
    projectsByTeam: (state) => {
      const map = {}
      for (const project of state.projects) {
        const key = project.team_id
        if (!map[key]) {
          map[key] = { team_id: project.team_id, team_name: project.team_name || 'Unknown Team', projects: [] }
        }
        map[key].projects.push(project)
      }
      return Object.values(map).sort((a, b) => a.team_name.localeCompare(b.team_name))
    },
  },

  actions: {
    async fetchProjects() {
      this.isLoading = true
      this.error = null
      try {
        const response = await axios.get('/projects')
        this.projects = response.data.data || []
        return true
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to fetch projects'
        console.error('Fetch projects error:', error)
        return false
      } finally {
        this.isLoading = false  
      }
    },

    async getProjectById(projectId) {
      this.isLoading = true
      this.error = null
      try {
        const response = await axios.get(`/projects/${projectId}`)
        this.currentProject = response.data.data
        return true
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to fetch project'
        return false
      } finally {
        this.isLoading = false
      }
    },

    async createProject(projectName, description, teamId) {
      this.isLoading = true
      this.error = null
      try {
        const payload = {
          project_name: projectName,
          description,
        }
        if (teamId !== null && teamId !== undefined && teamId !== '') {
          payload.team_id = teamId
        }

        const response = await axios.post('/projects', payload)
        const newProject = response.data.data
        if (newProject) {
          this.projects.push(newProject)
        } else {
          // Fallback: refetch all projects to sync with DB
          await this.fetchProjects()
        }
        return true
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to create project'
        return false
      } finally {
        this.isLoading = false
      }
    },

    async updateProject(projectId, projectName, description) {
      this.isLoading = true
      this.error = null
      try {
        const response = await axios.put(`/projects/${projectId}`, {
          project_name: projectName,
          description,
        })
        const index = this.projects.findIndex((p) => p.project_id === projectId)
        if (index !== -1) {
          this.projects[index] = response.data.data
        }
        if (this.currentProject?.project_id === projectId) {
          this.currentProject = response.data.data
        }
        return true
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to update project'
        return false
      } finally {
        this.isLoading = false
      }
    },

    async deleteProject(projectId) {
      this.isLoading = true
      this.error = null
      try {
        await axios.delete(`/projects/${projectId}`)
        this.projects = this.projects.filter((p) => p.project_id !== projectId)
        if (this.currentProject?.project_id === projectId) {
          this.currentProject = null
        }
        return true
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to delete project'
        return false
      } finally {
        this.isLoading = false
      }
    },

    async fetchProjectMembers(projectId) {
      this.isLoading = true
      this.error = null
      try {
        const response = await axios.get(`/projects/${projectId}/members`)
        this.members = response.data.data
        return true
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to fetch members'
        return false
      } finally {
        this.isLoading = false
      }
    },
  },
})

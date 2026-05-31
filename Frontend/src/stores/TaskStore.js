import { defineStore } from 'pinia'
import axios from '@/BaseURL'

export const useTaskStore = defineStore('task', {
  state: () => ({
    tasks: [],
    currentTask: null,
    isLoading: false,
    error: null,
  }),

  getters: {
    getAllTasks: (state) => state.tasks,
    getCurrentTask: (state) => state.currentTask,
    getTasksByStatus: (state) => (status) =>
      state.tasks.filter((task) => task.status === status),
    getBacklogTasks: (state) => state.tasks.filter((t) => t.status === 'backlog'),
    getTodoTasks: (state) => state.tasks.filter((t) => t.status === 'todo'),
    getDoingTasks: (state) => state.tasks.filter((t) => t.status === 'doing'),
    getDoneTasks: (state) => state.tasks.filter((t) => t.status === 'done'),
  },

  actions: {
    async fetchSprintTasks(sprintId) {
      this.isLoading = true
      this.error = null
      try {
        const response = await axios.get(`/sprints/${sprintId}/tasks`)
        this.tasks = response.data.data || []
        return true
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to fetch tasks'
        return false
      } finally {
        this.isLoading = false
      }
    },

    async fetchProjectTasks(projectId) {
      this.isLoading = true
      this.error = null
      try {
        const response = await axios.get(`/projects/${projectId}/tasks`)
        this.tasks = response.data.data || []
        return true
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to fetch tasks'
        return false
      } finally {
        this.isLoading = false
      }
    },

    async fetchProjectBacklogTasks(projectId) {
      this.isLoading = true
      this.error = null
      try {
        const response = await axios.get(`/projects/${projectId}/backlog`)
        this.tasks = response.data.data || []
        return true
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to fetch backlog tasks'
        return false
      } finally {
        this.isLoading = false
      }
    },

    async getTaskById(taskId) {
      this.isLoading = true
      this.error = null
      try {
        const response = await axios.get(`/tasks/${taskId}`)
        this.currentTask = response.data.data
        return true
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to fetch task'
        return false
      } finally {
        this.isLoading = false
      }
    },

    async createTask(sprintId, projectId, taskName, description, assignedTo = null, status = 'backlog') {
      this.isLoading = true
      this.error = null
      try {
        const body = {
          project_id: projectId,
          task_name: taskName,
          description,
          assigned_to: assignedTo,
          status,
        }
        if (sprintId !== null) body.sprint_id = sprintId
        const response = await axios.post(`/tasks`, body)
        const newTask = response.data.data
        if (newTask) {
          this.tasks.push(newTask)
        }
        return true
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to create task'
        return false
      } finally {
        this.isLoading = false
      }
    },

    async updateTask(taskId, { taskName, description, sprintId, assignedTo, status }) {
      this.isLoading = true
      this.error = null
      try {
        const body = {
          task_name: taskName,
          description: description || '',
          assigned_to: assignedTo ?? null,
          sprint_id: sprintId ?? null,
        }
        if (status) body.status = status
        const response = await axios.put(`/tasks/${taskId}`, body)
        const updated = response.data.data
        if (updated) {
          const index = this.tasks.findIndex((t) => t.task_id === taskId)
          if (index !== -1) this.tasks[index] = updated
          if (this.currentTask?.task_id === taskId) this.currentTask = updated
        }
        return true
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to update task'
        return false
      } finally {
        this.isLoading = false
      }
    },

    async updateTaskStatus(taskId, status) {
      this.isLoading = true
      this.error = null
      try {
        const response = await axios.patch(`/tasks/${taskId}/status`, {
          status,
        })
        const index = this.tasks.findIndex((t) => t.task_id === taskId)
        if (index !== -1) {
          this.tasks[index].status = status
        }
        if (this.currentTask?.task_id === taskId) {
          this.currentTask.status = status
        }
        return true
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to update task status'
        return false
      } finally {
        this.isLoading = false
      }
    },

    async assignTask(taskId, userId) {
      this.isLoading = true
      this.error = null
      try {
        const response = await axios.post(`/tasks/${taskId}/assign`, {
          assigned_to: userId,
        })
        const index = this.tasks.findIndex((t) => t.task_id === taskId)
        if (index !== -1) {
          this.tasks[index] = response.data.data
        }
        if (this.currentTask?.task_id === taskId) {
          this.currentTask = response.data.data
        }
        return true
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to assign task'
        return false
      } finally {
        this.isLoading = false
      }
    },

    async deleteTask(taskId) {
      this.isLoading = true
      this.error = null
      try {
        await axios.delete(`/tasks/${taskId}`)
        this.tasks = this.tasks.filter((t) => t.task_id !== taskId)
        if (this.currentTask?.task_id === taskId) {
          this.currentTask = null
        }
        return true
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to delete task'
        return false
      } finally {
        this.isLoading = false
      }
    },
  },
})

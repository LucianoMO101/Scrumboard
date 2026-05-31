import { defineStore } from 'pinia'
import axios from '@/BaseURL'

export const useActivityLogStore = defineStore('activityLog', {
    state: () => ({
        logs: [],
        meta: { total: 0, page: 1, limit: 20, pages: 1 },
        isLoading: false,
        error: null,
        filters: { action: '', entity_type: '' },
    }),

    actions: {
        async fetchProjectLog(projectId, page = 1, filters = {}) {
            this.isLoading = true
            this.error = null

            const params = new URLSearchParams({ page, limit: this.meta.limit })
            if (filters.action) params.set('action', filters.action)
            if (filters.entity_type) params.set('entity_type', filters.entity_type)

            try {
                const res = await axios.get(`/projects/${projectId}/activity?${params.toString()}`)
                this.logs = res.data.data
                this.meta = res.data.meta
                this.filters = { action: filters.action || '', entity_type: filters.entity_type || '' }
            } catch (err) {
                this.error = err.response?.data?.error || 'Failed to load activity log'
            } finally {
                this.isLoading = false
            }
        },

        async loadMore(projectId) {
            if (this.meta.page >= this.meta.pages) return
            const nextPage = this.meta.page + 1
            const params = new URLSearchParams({
                page: nextPage,
                limit: this.meta.limit,
            })
            if (this.filters.action) params.set('action', this.filters.action)
            if (this.filters.entity_type) params.set('entity_type', this.filters.entity_type)

            try {
                const res = await axios.get(`/projects/${projectId}/activity?${params.toString()}`)
                this.logs = [...this.logs, ...res.data.data]
                this.meta = res.data.meta
            } catch (err) {
                this.error = err.response?.data?.error || 'Failed to load more'
            }
        },

        reset() {
            this.logs = []
            this.meta = { total: 0, page: 1, limit: 20, pages: 1 }
            this.filters = { action: '', entity_type: '' }
        },
    },
})

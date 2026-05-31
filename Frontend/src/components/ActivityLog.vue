<template>
  <div>
    <!-- Header + filters -->
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
      <h3 class="text-sm font-semibold text-gray-700">
        Activity Log
        <span class="ml-1 text-xs font-normal text-gray-400">({{ store.meta.total }} entries)</span>
      </h3>
      <div class="flex items-center gap-2">
        <select
          v-model="filterAction"
          @change="applyFilters"
          class="border border-gray-300 rounded-lg px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="">All actions</option>
          <option value="created">Created</option>
          <option value="updated">Updated</option>
          <option value="deleted">Deleted</option>
          <option value="started">Started</option>
          <option value="completed">Completed</option>
          <option value="assigned">Assigned</option>
          <option value="status_changed">Status changed</option>
        </select>
        <select
          v-model="filterEntityType"
          @change="applyFilters"
          class="border border-gray-300 rounded-lg px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="">All types</option>
          <option value="task">Task</option>
          <option value="sprint">Sprint</option>
          <option value="project">Project</option>
          <option value="member">Member</option>
        </select>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="store.isLoading && store.logs.length === 0" class="text-center py-6 text-gray-400 text-sm">
      Loading activity...
    </div>

    <!-- Empty -->
    <div v-else-if="store.logs.length === 0" class="text-center py-6 text-gray-400 text-sm italic">
      No activity recorded yet.
    </div>

    <!-- Log feed -->
    <ul v-else class="space-y-2">
      <li
        v-for="log in store.logs"
        :key="log.log_id"
        class="flex items-start gap-3 text-sm"
      >
        <!-- Icon -->
        <span class="mt-0.5 text-base shrink-0" :title="log.action">{{ actionIcon(log.action) }}</span>

        <!-- Content -->
        <div class="flex-1 min-w-0">
          <p class="text-gray-700 leading-snug">
            <span class="font-medium">{{ log.user_name }}</span>
            {{ actionLabel(log.action) }}
            <span v-if="log.entity_name" class="font-medium text-blue-600">{{ log.entity_name }}</span>
            <span v-else-if="log.details" class="text-gray-500 text-xs"> — {{ log.details }}</span>
            <span class="ml-1 text-xs text-gray-400 italic">({{ log.entity_type }})</span>
          </p>
          <p class="text-xs text-gray-400">{{ formatTime(log.created_at) }}</p>
        </div>

        <!-- Badge -->
        <span :class="actionBadgeClass(log.action)" class="shrink-0 text-xs px-2 py-0.5 rounded-full font-medium">
          {{ log.action }}
        </span>
      </li>
    </ul>

    <!-- Load more -->
    <div v-if="store.meta.page < store.meta.pages" class="text-center mt-4">
      <button
        @click="loadMore"
        :disabled="store.isLoading"
        class="text-xs text-blue-600 hover:text-blue-800 font-medium disabled:opacity-50"
      >
        {{ store.isLoading ? 'Loading...' : `Load more (${store.meta.total - store.logs.length} remaining)` }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useActivityLogStore } from '@/stores/ActivityLogStore'

const props = defineProps({
  projectId: { type: Number, required: true },
})

const store = useActivityLogStore()

const filterAction = ref('')
const filterEntityType = ref('')

// When the store resets (modal re-opened), clear the dropdowns too
watch(() => store.filters.action, (val) => { filterAction.value = val })
watch(() => store.filters.entity_type, (val) => { filterEntityType.value = val })

const applyFilters = async () => {
  await store.fetchProjectLog(props.projectId, 1, {
    action: filterAction.value,
    entity_type: filterEntityType.value,
  })
}

const loadMore = async () => {
  await store.loadMore(props.projectId)
}

const actionIcon = (action) => {
  const icons = {
    created: '✅',
    updated: '✏️',
    deleted: '🗑️',
    started: '▶️',
    completed: '🏁',
    assigned: '👤',
    status_changed: '🔄',
  }
  return icons[action] || '•'
}

const actionLabel = (action) => {
  const labels = {
    created: 'created',
    updated: 'updated',
    deleted: 'deleted',
    started: 'started sprint',
    completed: 'completed sprint',
    assigned: 'assigned',
    status_changed: 'changed status of',
  }
  return labels[action] || action
}

const actionBadgeClass = (action) => {
  const map = {
    created: 'bg-green-100 text-green-700',
    updated: 'bg-blue-100 text-blue-700',
    deleted: 'bg-red-100 text-red-700',
    started: 'bg-purple-100 text-purple-700',
    completed: 'bg-gray-100 text-gray-600',
    assigned: 'bg-yellow-100 text-yellow-700',
    status_changed: 'bg-orange-100 text-orange-700',
  }
  return map[action] || 'bg-gray-100 text-gray-600'
}

const formatTime = (ts) => {
  if (!ts) return ''
  const date = new Date(ts)
  return date.toLocaleString('nl-NL', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}
</script>

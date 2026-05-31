<template>
  <div class="min-h-screen bg-gray-100">

    <!-- Header -->
    <div class="bg-white shadow">
      <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
        <div class="flex items-center gap-4">
          <button
            @click="router.push('/projects')"
            class="text-gray-500 hover:text-gray-800 text-sm font-medium transition"
          >&larr; Back to Projects</button>
          <div>
            <h1 class="text-2xl font-bold text-gray-800">
              {{ projectStore.currentProject?.project_name || 'Kanban Board' }}
            </h1>
            <p class="text-gray-400 text-xs">Kanban Board</p>
          </div>
        </div>
        <div class="flex gap-2">
          <button
            v-if="canEdit"
            @click="openCreateTask"
            class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition"
          >+ New Task</button>
          <button
            v-if="canEdit"
            @click="openCreateSprint"
            class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700 transition"
          >+ New Sprint</button>
          <button
            @click="showMembersModal = true"
            class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition"
          >👥 Members</button>
          <button
            @click="toggleActivity"
            class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition"
          >📋 Activity</button>
        </div>
      </div>
    </div>

    <!-- Sprints panel -->
    <div class="bg-white border-b">
      <div class="max-w-7xl mx-auto px-4">
        <button
          @click="showSprintsPanel = !showSprintsPanel"
          class="flex items-center gap-2 py-3 text-sm font-medium text-gray-600 hover:text-gray-900 transition"
        >
          <span>{{ showSprintsPanel ? 'Hide' : 'Show' }} Sprints</span>
          <span class="bg-blue-100 text-blue-700 text-xs px-2 py-0.5 rounded-full font-bold">
            {{ sprintStore.sprints.length }}
          </span>
          <span class="text-gray-400 text-xs">{{ showSprintsPanel ? '&#9650;' : '&#9660;' }}</span>
        </button>

        <div v-if="showSprintsPanel" class="pb-4">
          <p v-if="sprintStore.sprints.length === 0" class="text-sm text-gray-400 italic py-2">
            No sprints yet. Click "+ New Sprint" to create one.
          </p>
          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            <div
              v-for="sprint in sprintStore.sprints"
              :key="sprint.sprint_id"
              class="border border-gray-200 rounded-lg p-3 flex items-start justify-between"
            >
              <div class="min-w-0">
                <div class="flex items-center gap-2 mb-1">
                  <span class="font-semibold text-sm text-gray-800 truncate">{{ sprint.sprint_name }}</span>
                  <span :class="statusBadgeClass(sprint.status)">{{ sprint.status }}</span>
                </div>
                <p class="text-xs text-gray-400">{{ sprint.start_date }} &rarr; {{ sprint.end_date }}</p>
              </div>
              <div class="flex items-center gap-1 ml-2 shrink-0">
                <button
                  v-if="canEdit && sprint.status === 'planned'"
                  @click="handleStartSprint(sprint)"
                  class="text-xs px-2 py-1 rounded bg-green-100 text-green-700 hover:bg-green-200 transition"
                >Start</button>
                <button
                  v-if="canEdit && sprint.status === 'active'"
                  @click="handleCompleteSprint(sprint)"
                  class="text-xs px-2 py-1 rounded bg-orange-100 text-orange-700 hover:bg-orange-200 transition"
                >Complete</button>
                <button
                  v-if="canEdit && sprint.status === 'completed'"
                  @click="handleReopenSprint(sprint)"
                  class="text-xs px-2 py-1 rounded bg-blue-100 text-blue-700 hover:bg-blue-200 transition"
                >Reopen</button>
                <button
                  v-if="canEdit"
                  @click="openEditSprint(sprint)"
                  class="text-xs px-2 py-1 rounded bg-gray-100 text-gray-600 hover:bg-gray-200 transition"
                >Edit</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filter bar -->
    <div class="bg-white border-b shadow-sm">
      <div class="max-w-7xl mx-auto px-4 py-3 flex items-center gap-4">
        <label class="text-sm font-medium text-gray-600">Filter by sprint:</label>
        <select
          v-model="sprintFilter"
          class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option :value="null">All tasks</option>
          <option value="backlog">No sprint (Backlog)</option>
          <option
            v-for="sprint in sprintStore.sprints"
            :key="sprint.sprint_id"
            :value="sprint.sprint_id"
          >{{ sprint.sprint_name }}</option>
        </select>
        <span class="text-xs text-gray-400">{{ filteredTasks.length }} task(s)</span>
      </div>
    </div>

    <!-- Kanban board -->
    <div class="max-w-7xl mx-auto px-4 py-6">
      <div v-if="taskStore.isLoading" class="text-center py-12 text-gray-400 text-sm">
        Loading tasks...
      </div>
      <KanbanBoard
        v-else
        :tasks="filteredTasks"
        :sprints="sprintStore.sprints"
        @task-click="openEditTask"
        @task-drop="handleDrop"
        @task-delete="handleDelete"
      />
    </div>

    <!-- Task modal (create + edit) -->
    <TaskModal
      :show="showTaskModal"
      :task="editingTask"
      :sprints="sprintStore.sprints"
      :project-id="projectId"
      @close="closeTaskModal"
      @saved="onTaskSaved"
      @deleted="onTaskSaved"
    />

    <!-- Sprint modal (create + edit) -->
    <SprintModal
      :show="showSprintModal"
      :sprint="editingSprint"
      :project-id="projectId"
      @close="closeSprintModal"
      @saved="onSprintSaved"
      @deleted="onSprintDeleted"
    />

    <!-- Members MODAL (overlay like TaskModal) -->
    <div
      v-if="showMembersModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
      @click.self="showMembersModal = false"
    >
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 flex flex-col max-h-[90vh]">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <h2 class="text-lg font-bold text-gray-800">Project Members</h2>
          <button @click="showMembersModal = false" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
        </div>
        <div class="overflow-y-auto px-6 py-4">
          <ProjectMembers
            v-if="projectStore.currentProject?.team_id"
            :project-id="projectId"
            :team-id="projectStore.currentProject.team_id"
          />
        </div>
      </div>
    </div>

    <!-- Activity log MODAL (overlay) -->
    <div
      v-if="showActivityModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
      @click.self="showActivityModal = false"
    >
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 flex flex-col max-h-[90vh]">
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <h2 class="text-lg font-bold text-gray-800">Activity Log</h2>
          <button @click="showActivityModal = false" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
        </div>
        <div class="overflow-y-auto px-6 py-4">
          <ActivityLog
            :project-id="projectId"
          />
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useProjectStore } from '@/stores/ProjectStore'
import { useTaskStore } from '@/stores/TaskStore'
import { useSprintStore } from '@/stores/SprintStore'
import { useActivityLogStore } from '@/stores/ActivityLogStore'
import KanbanBoard from '@/components/KanbanBoard.vue'
import TaskModal from '@/components/TaskModal.vue'
import SprintModal from '@/components/SprintModal.vue'
import ActivityLog from '@/components/ActivityLog.vue'
import ProjectMembers from '@/components/ProjectMembers.vue'

const route = useRoute()
const router = useRouter()
const projectStore = useProjectStore()
const taskStore = useTaskStore()
const sprintStore = useSprintStore()
const activityStore = useActivityLogStore()

const projectId = parseInt(route.params.id)

const showSprintsPanel = ref(false)
const showTaskModal = ref(false)
const showSprintModal = ref(false)
const showMembersModal = ref(false)
const showActivityModal = ref(false)
const editingTask = ref(null)
const editingSprint = ref(null)

const sprintFilter = ref(null)

// Role-based access: owner/editor can mutate
const canEdit = computed(() => {
  const role = projectStore.currentProject?.role
  return role === 'owner' || role === 'editor'
})

const filteredTasks = computed(() => {
  if (sprintFilter.value === null) return taskStore.tasks
  if (sprintFilter.value === 'backlog') return taskStore.tasks.filter((t) => !t.sprint_id)
  return taskStore.tasks.filter((t) => t.sprint_id === sprintFilter.value)
})

const statusBadgeClass = (status) => {
  const map = {
    planned:   'text-xs px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700',
    active:    'text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700',
    completed: 'text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500',
  }
  return map[status] || 'text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500'
}

const openCreateTask = () => {
  editingTask.value = null
  showTaskModal.value = true
}

const openEditTask = (task) => {
  editingTask.value = task
  showTaskModal.value = true
}

const closeTaskModal = () => {
  showTaskModal.value = false
  editingTask.value = null
}

const onTaskSaved = () => {}

const handleDrop = async (task, newStatus) => {
  await taskStore.updateTaskStatus(task.task_id, newStatus)
}

const handleDelete = async (task) => {
  if (confirm(`Delete "${task.task_name}"?`)) {
    await taskStore.deleteTask(task.task_id)
  }
}

const openCreateSprint = () => {
  editingSprint.value = null
  showSprintModal.value = true
}

const openEditSprint = (sprint) => {
  editingSprint.value = sprint
  showSprintModal.value = true
}

const closeSprintModal = () => {
  showSprintModal.value = false
  editingSprint.value = null
}

const onSprintSaved = () => {}

const onSprintDeleted = () => {
  const deletedId = editingSprint.value?.sprint_id
  if (deletedId) {
    taskStore.tasks = taskStore.tasks.map((t) =>
      t.sprint_id === deletedId ? { ...t, sprint_id: null } : t
    )
  }
}

const handleStartSprint = async (sprint) => {
  await sprintStore.startSprint(sprint.sprint_id)
}

const handleCompleteSprint = async (sprint) => {
  if (confirm(`Mark "${sprint.sprint_name}" as completed?`)) {
    await sprintStore.completeSprint(sprint.sprint_id)
  }
}

const handleReopenSprint = async (sprint) => {
  if (confirm(`Reopen "${sprint.sprint_name}" as active?`)) {
    await sprintStore.reopenSprint(sprint.sprint_id)
  }
}

const toggleActivity = async () => {
  showActivityModal.value = true
  activityStore.reset()
  await activityStore.fetchProjectLog(projectId)
}

onMounted(async () => {
  await projectStore.getProjectById(projectId)
  await sprintStore.fetchSprints(projectId)
  await taskStore.fetchProjectTasks(projectId)
})
</script>

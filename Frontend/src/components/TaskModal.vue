<template>
  <FormModal
    :show="show"
    :title="task ? 'Edit Task' : 'New Task'"
    @close="$emit('close')"
  >
    <div v-if="error" class="mb-4 p-3 bg-red-100 border border-red-300 text-red-700 rounded text-sm">
      {{ error }}
    </div>

    <div class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Task Name *</label>
        <input
          v-model="form.taskName"
          type="text"
          placeholder="e.g., Design login page"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
        <textarea
          v-model="form.description"
          rows="3"
          placeholder="What needs to be done?"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        ></textarea>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Sprint</label>
        <select
          v-model="form.sprintId"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option :value="null">No sprint (Backlog)</option>
          <option
            v-for="sprint in sprints"
            :key="sprint.sprint_id"
            :value="sprint.sprint_id"
          >
            {{ sprint.sprint_name }} ({{ sprint.status }})
          </option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
        <select
          v-model="form.status"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="backlog">Backlog</option>
          <option value="todo">Todo</option>
          <option value="doing">Doing</option>
          <option value="done">Done</option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Assign to</label>
        <select
          v-model="form.assignedTo"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option :value="null">Unassigned</option>
          <option
            v-for="member in members"
            :key="member.user_id"
            :value="member.user_id"
          >
            {{ member.user_name || 'User ' + member.user_id }}
          </option>
        </select>
      </div>
    </div>

    <template #footer>
      <button
        v-if="task"
        @click="handleDelete"
        :disabled="saving"
        type="button"
        class="px-4 py-2 rounded-lg text-sm font-medium bg-red-100 text-red-700 hover:bg-red-200 disabled:opacity-50 transition"
      >Delete</button>
      <div class="flex gap-3 ml-auto">
        <button
          @click="$emit('close')"
          type="button"
          class="px-4 py-2 rounded-lg text-sm font-medium bg-gray-200 text-gray-700 hover:bg-gray-300 transition"
        >Cancel</button>
        <button
          @click="handleSave"
          :disabled="saving"
          type="button"
          class="px-4 py-2 rounded-lg text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 transition"
        >{{ saving ? 'Saving...' : (task ? 'Save Changes' : 'Create Task') }}</button>
      </div>
    </template>
  </FormModal>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import FormModal from '@/components/shared/FormModal.vue'
import { useTaskStore } from '@/stores/TaskStore'
import axios from '@/BaseURL'

const props = defineProps({
  show: { type: Boolean, required: true },
  task: { type: Object, default: null },
  sprints: { type: Array, default: () => [] },
  projectId: { type: Number, required: true },
  defaultSprintId: { type: Number, default: null },
})

const emit = defineEmits(['close', 'saved', 'deleted'])

const taskStore = useTaskStore()
const saving = ref(false)
const error = ref('')
const members = ref([])

const form = ref({
  taskName: '',
  description: '',
  sprintId: null,
  assignedTo: null,
  status: 'backlog',
})

// Load project members for assign dropdown
const fetchMembers = async () => {
  try {
    const res = await axios.get(`/projects/${props.projectId}/members`)
    members.value = res.data.data || []
  } catch {
    members.value = []
  }
}

// Populate form when editing or when modal opens
watch(
  () => props.show,
  (show) => {
    if (show) {
      error.value = ''
      fetchMembers()
      if (props.task) {
        form.value = {
          taskName: props.task.task_name,
          description: props.task.description || '',
          sprintId: props.task.sprint_id ?? null,
          assignedTo: props.task.assigned_to ?? null,
          status: props.task.status,
        }
      } else {
        form.value = {
          taskName: '',
          description: '',
          sprintId: props.defaultSprintId ?? null,
          assignedTo: null,
          status: 'backlog',
        }
      }
    }
  }
)

const handleSave = async () => {
  if (!form.value.taskName.trim()) {
    error.value = 'Task name is required'
    return
  }

  saving.value = true
  error.value = ''

  let success
  if (props.task) {
    success = await taskStore.updateTask(props.task.task_id, {
      taskName: form.value.taskName,
      description: form.value.description,
      sprintId: form.value.sprintId,
      assignedTo: form.value.assignedTo,
      status: form.value.status,
    })
  } else {
    success = await taskStore.createTask(
      form.value.sprintId,
      props.projectId,
      form.value.taskName,
      form.value.description,
      form.value.assignedTo,
      form.value.status
    )
  }

  saving.value = false

  if (success) {
    emit('saved')
    emit('close')
  } else {
    error.value = taskStore.error || 'Failed to save task'
  }
}

const handleDelete = async () => {
  if (!confirm(`Delete "${props.task.task_name}"?`)) return
  saving.value = true
  const success = await taskStore.deleteTask(props.task.task_id)
  saving.value = false
  if (success) {
    emit('deleted')
    emit('close')
  } else {
    error.value = taskStore.error || 'Failed to delete task'
  }
}
</script>

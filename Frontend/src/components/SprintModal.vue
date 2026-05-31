<template>
  <FormModal
    :show="show"
    :title="sprint ? 'Edit Sprint' : 'New Sprint'"
    @close="$emit('close')"
  >
    <div v-if="error" class="mb-4 p-3 bg-red-100 border border-red-300 text-red-700 rounded text-sm">
      {{ error }}
    </div>

    <div class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Sprint Name *</label>
        <input
          v-model="form.sprintName"
          type="text"
          placeholder="e.g., Sprint 1"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
        <textarea
          v-model="form.description"
          rows="2"
          placeholder="What will be done in this sprint?"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        ></textarea>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Start Date *</label>
          <input
            v-model="form.startDate"
            type="date"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">End Date *</label>
          <input
            v-model="form.endDate"
            type="date"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
      </div>
    </div>

    <template #footer>
      <button
        v-if="sprint"
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
        >{{ saving ? 'Saving...' : (sprint ? 'Save Changes' : 'Create Sprint') }}</button>
      </div>
    </template>
  </FormModal>
</template>

<script setup>
import { ref, watch } from 'vue'
import FormModal from '@/components/shared/FormModal.vue'
import { useSprintStore } from '@/stores/SprintStore'

const props = defineProps({
  show: { type: Boolean, required: true },
  sprint: { type: Object, default: null },
  projectId: { type: Number, required: true },
})

const emit = defineEmits(['close', 'saved', 'deleted'])

const sprintStore = useSprintStore()
const saving = ref(false)
const error = ref('')

const form = ref({
  sprintName: '',
  description: '',
  startDate: '',
  endDate: '',
})

watch(
  () => props.show,
  (show) => {
    if (show) {
      error.value = ''
      if (props.sprint) {
        form.value = {
          sprintName: props.sprint.sprint_name,
          description: props.sprint.description || '',
          startDate: props.sprint.start_date,
          endDate: props.sprint.end_date,
        }
      } else {
        form.value = { sprintName: '', description: '', startDate: '', endDate: '' }
      }
    }
  }
)

const handleSave = async () => {
  if (!form.value.sprintName.trim() || !form.value.startDate || !form.value.endDate) {
    error.value = 'Sprint name, start date and end date are required'
    return
  }

  saving.value = true
  error.value = ''

  let success
  if (props.sprint) {
    success = await sprintStore.updateSprint(
      props.sprint.sprint_id,
      form.value.sprintName,
      form.value.description,
      form.value.startDate,
      form.value.endDate
    )
  } else {
    success = await sprintStore.createSprint(
      props.projectId,
      form.value.sprintName,
      form.value.description,
      form.value.startDate,
      form.value.endDate
    )
  }

  saving.value = false

  if (success) {
    emit('saved')
    emit('close')
  } else {
    error.value = sprintStore.error || 'Failed to save sprint'
  }
}

const handleDelete = async () => {
  if (!confirm(`Delete sprint "${props.sprint.sprint_name}"? Tasks in this sprint will move to the backlog.`)) return
  saving.value = true
  const success = await sprintStore.deleteSprint(props.sprint.sprint_id)
  saving.value = false
  if (success) {
    emit('deleted')
    emit('close')
  } else {
    error.value = sprintStore.error || 'Failed to delete sprint'
  }
}
</script>

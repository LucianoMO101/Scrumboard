<template>
  <div
    draggable="true"
    @dragstart="$emit('drag-start', $event, task)"
    @click="$emit('click', task)"
    :class="['p-3 rounded-lg border-l-4 cursor-pointer hover:shadow-md transition select-none group', cardClass]"
  >
    <h4 class="font-semibold text-sm text-gray-800">{{ task.task_name }}</h4>
    <p v-if="task.description" class="text-xs text-gray-500 mt-1 line-clamp-2">
      {{ task.description }}
    </p>
    <div class="flex items-center justify-between mt-2 pt-2 border-t border-gray-100">
      <span
        v-if="sprintName"
        class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 font-medium"
      >{{ sprintName }}</span>
      <span v-else class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">Backlog</span>

      <div class="flex items-center gap-2">
        <!-- Assignee avatar -->
        <span
          v-if="task.assigned_user_name"
          :title="task.assigned_user_name"
          class="w-5 h-5 rounded-full bg-indigo-500 text-white flex items-center justify-center text-xs font-bold shrink-0"
        >{{ assigneeInitials }}</span>

        <span
          class="text-xs text-gray-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition cursor-pointer"
          @click.stop="$emit('delete', task)"
        >Delete</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  task: { type: Object, required: true },
  sprints: { type: Array, default: () => [] },
})

defineEmits(['click', 'drag-start', 'delete'])

const sprintName = computed(() => {
  if (!props.task.sprint_id) return null
  const sprint = props.sprints.find((s) => s.sprint_id === props.task.sprint_id)
  return sprint ? sprint.sprint_name : `Sprint #${props.task.sprint_id}`
})

const assigneeInitials = computed(() => {
  const name = props.task.assigned_user_name
  if (!name) return ''
  return name.split(' ').map((w) => w[0]).join('').toUpperCase().slice(0, 2)
})

const cardClass = computed(() => {
  const map = {
    backlog: 'bg-yellow-50 border-yellow-400',
    todo: 'bg-blue-50 border-blue-400',
    doing: 'bg-orange-50 border-orange-400',
    done: 'bg-green-50 border-green-400',
  }
  return map[props.task.status] || 'bg-white border-gray-300'
})
</script>

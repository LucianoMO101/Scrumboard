<template>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    <div v-for="col in columns" :key="col.status" class="flex flex-col bg-white rounded-xl shadow">

      <!-- Column header -->
      <div :class="['px-4 py-3 rounded-t-xl flex items-center justify-between', col.headerClass]">
        <span class="font-bold text-sm">{{ col.label }}</span>
        <span class="text-xs font-bold bg-white bg-opacity-60 px-2 py-0.5 rounded-full">
          {{ tasksByStatus(col.status).length }}
        </span>
      </div>

      <!-- Drop zone -->
      <div
        class="p-3 flex-1 min-h-48 space-y-2"
        @drop="onDrop($event, col.status)"
        @dragover.prevent
        @dragenter.prevent
      >
        <TaskCard
          v-for="task in tasksByStatus(col.status)"
          :key="task.task_id"
          :task="task"
          :sprints="sprints"
          @click="$emit('task-click', task)"
          @drag-start="onDragStart"
          @delete="$emit('task-delete', task)"
        />

        <div
          v-if="tasksByStatus(col.status).length === 0"
          class="flex items-center justify-center h-16 border-2 border-dashed border-gray-200 rounded-lg text-gray-300 text-xs"
        >
          Drop here
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import TaskCard from '@/components/TaskCard.vue'

const props = defineProps({
  tasks: { type: Array, required: true },
  sprints: { type: Array, default: () => [] },
})

const emit = defineEmits(['task-click', 'task-drop', 'task-delete'])

const draggedTask = ref(null)

const columns = [
  { status: 'backlog', label: 'Backlog', headerClass: 'bg-gray-100 text-gray-700' },
  { status: 'todo',    label: 'Todo',    headerClass: 'bg-blue-100 text-blue-700' },
  { status: 'doing',   label: 'Doing',   headerClass: 'bg-orange-100 text-orange-700' },
  { status: 'done',    label: 'Done',    headerClass: 'bg-green-100 text-green-700' },
]

const tasksByStatus = (status) => props.tasks.filter((t) => t.status === status)

const onDragStart = (event, task) => {
  draggedTask.value = task
  event.dataTransfer.effectAllowed = 'move'
}

const onDrop = (event, status) => {
  event.preventDefault()
  if (draggedTask.value && draggedTask.value.status !== status) {
    emit('task-drop', draggedTask.value, status)
    draggedTask.value = null
  }
}
</script>

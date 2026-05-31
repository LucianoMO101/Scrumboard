<template>
  <div
    v-if="show"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
    @click.self="$emit('close')"
  >
    <div class="bg-white rounded-xl w-full shadow-2xl" :class="sizeClass">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h2 class="text-lg font-bold text-gray-800">{{ title }}</h2>
        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600 text-2xl leading-none font-light"
          type="button"
        >&times;</button>
      </div>

      <!-- Body -->
      <div class="p-6">
        <slot />
      </div>

      <!-- Footer -->
      <div v-if="$slots.footer" class="px-6 py-4 border-t border-gray-100 flex gap-3">
        <slot name="footer" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  show: { type: Boolean, required: true },
  title: { type: String, required: true },
  size: { type: String, default: 'md' },
})

defineEmits(['close'])

const sizeClass = computed(() => ({
  'max-w-sm': props.size === 'sm',
  'max-w-md': props.size === 'md',
  'max-w-lg': props.size === 'lg',
}))
</script>

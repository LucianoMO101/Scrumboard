<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow">
      <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex justify-between items-center">
          <div>
            <h1 class="text-4xl font-bold text-gray-800">Projects</h1>
            <p class="text-gray-600 mt-2">Manage your team projects and sprints</p>
          </div>
          <button
            @click="showNewProjectModal = true"
            class="bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 transition"
          >
            + New Project
          </button>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 py-8">
      <!-- Loading State -->
      <div v-if="projectStore.isLoading" class="text-center py-12">
        <p class="text-gray-600">Loading projects...</p>
      </div>

      <!-- Error State -->
      <div v-if="projectStore.error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        {{ projectStore.error }}
      </div>

      <!-- Projects Grid -->
      <div v-if="!projectStore.isLoading && projectStore.projects.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="project in projectStore.projects"
          :key="project.project_id"
          class="bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-200 cursor-pointer border border-gray-100 hover:border-blue-300 group relative overflow-hidden"
          @click="goToProject(project.project_id)"
        >
          <!-- Colored top accent bar -->
          <div class="h-1.5 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-t-xl"></div>

          <div class="p-6">
            <!-- Title row -->
            <div class="flex items-start justify-between mb-3">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center text-lg font-bold flex-shrink-0">
                  {{ project.project_name.charAt(0).toUpperCase() }}
                </div>
                <div>
                  <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-600 transition">{{ project.project_name }}</h3>
                  <span class="text-xs text-gray-400">ID #{{ project.project_id }}</span>
                </div>
              </div>
            </div>

            <!-- Description -->
            <p class="text-gray-600 text-sm mb-4 line-clamp-2 min-h-[2.5rem]">
              {{ project.description || 'No description provided.' }}
            </p>

            <!-- Footer -->
            <div class="flex justify-between items-center pt-4 border-t border-gray-100">
              <span class="text-xs text-gray-400">
                Created {{ new Date(project.created_at).toLocaleDateString('nl-NL') }}
              </span>
              <div class="flex gap-2">
                <span class="text-xs text-blue-600 font-medium group-hover:underline">
                  Open Board &rarr;
                </span>
                <button
                  @click.stop="deleteProject(project.project_id)"
                  class="text-red-400 hover:text-red-600 hover:bg-red-50 rounded px-2 py-0.5 text-xs font-medium transition"
                >
                  Delete
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="!projectStore.isLoading && projectStore.projects.length === 0" class="text-center py-12">
        <p class="text-gray-600 text-lg">No projects yet</p>
        <button
          @click="showNewProjectModal = true"
          class="mt-4 bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700"
        >
          Create your first project
        </button>
      </div>
    </div>

    <!-- New Project Modal -->
    <div v-if="showNewProjectModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-lg p-8 w-full max-w-md">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Create New Project</h2>
        <div v-if="createError" class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
          {{ createError }}
        </div>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Project Name</label>
            <input
              v-model="newProject.projectName"
              type="text"
              placeholder="e.g., Mobile App Redesign"
              class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea
              v-model="newProject.description"
              placeholder="Describe the project..."
              rows="4"
              class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            ></textarea>
          </div>

          <div class="flex gap-2 pt-4">
            <button
              @click="createProject"
              :disabled="!newProject.projectName || isCreating"
              class="flex-1 bg-blue-600 text-white py-2 rounded font-medium hover:bg-blue-700 disabled:bg-gray-400"
            >
              {{ isCreating ? 'Creating...' : 'Create' }}
            </button>
            <button
              @click="closeModal"
              class="flex-1 bg-gray-300 text-gray-800 py-2 rounded font-medium hover:bg-gray-400"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useProjectStore } from '@/stores/ProjectStore'
import { useAuthStore } from '@/stores/AuthStore'

const router = useRouter()
const projectStore = useProjectStore()
const authStore = useAuthStore()

const showNewProjectModal = ref(false)
const isCreating = ref(false)
const createError = ref('')
const newProject = ref({
  projectName: '',
  description: '',
})

const goToProject = (projectId) => {
  router.push(`/sprint/${projectId}`)
}

const createProject = async () => {
  if (!newProject.value.projectName) {
    createError.value = 'Project name is required'
    return
  }

  isCreating.value = true
  createError.value = ''
  
  console.log('Creating project:', newProject.value)
  
  try {
    const success = await projectStore.createProject(
      newProject.value.projectName,
      newProject.value.description,
      authStore.user?.default_team_id || 1
    )

    if (success) {
      showNewProjectModal.value = false
      newProject.value = { projectName: '', description: '' }
      createError.value = ''
    } else {
      createError.value = projectStore.error || 'Failed to create project'
    }
  } catch (error) {
    console.error('Error creating project:', error)
    createError.value = error.message || 'An error occurred'
  } finally {
    isCreating.value = false
  }
}

const deleteProject = async (projectId) => {
  if (confirm('Are you sure you want to delete this project?')) {
    await projectStore.deleteProject(projectId)
  }
}

const closeModal = () => {
  showNewProjectModal.value = false
  newProject.value = { projectName: '', description: '' }
  createError.value = ''
}

onMounted(async () => {
  await projectStore.fetchProjects()
})
</script>

<style scoped>
/* Custom styles if needed */
</style>

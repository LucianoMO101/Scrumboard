<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
    <!-- Header Section -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white shadow-lg">
      <div class="max-w-7xl mx-auto px-4 py-12">
        <h1 class="text-4xl font-bold">Welcome, {{ authStore.user?.firstname }}!</h1>
        <p class="text-blue-100 mt-2 text-lg">Here's your project overview</p>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 py-12">
      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
        <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition p-6 border-t-4 border-blue-500">
          <div class="flex justify-between items-start">
            <div>
              <div class="text-gray-600 text-sm font-medium">Total Projects</div>
              <div class="text-4xl font-bold text-blue-600 mt-3">{{ projectStore.projects.length }}</div>
            </div>
            <div class="text-3xl text-blue-300">&#128193;</div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition p-6 border-t-4 border-green-500">
          <div class="flex justify-between items-start">
            <div>
              <div class="text-gray-600 text-sm font-medium">Active Sprints</div>
              <div class="text-4xl font-bold text-green-600 mt-3">{{ activeSprints.length }}</div>
            </div>
            <div class="text-3xl text-green-300">&#128262;</div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition p-6 border-t-4 border-orange-500">
          <div class="flex justify-between items-start">
            <div>
              <div class="text-gray-600 text-sm font-medium">In Progress</div>
              <div class="text-4xl font-bold text-orange-600 mt-3">{{ doingTasks.length }}</div>
            </div>
            <div class="text-3xl text-orange-300">&#128260;</div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition p-6 border-t-4 border-emerald-500">
          <div class="flex justify-between items-start">
            <div>
              <div class="text-gray-600 text-sm font-medium">Completed</div>
              <div class="text-4xl font-bold text-emerald-600 mt-3">{{ doneTasks.length }}</div>
            </div>
            <div class="text-3xl text-green-300">&#10003;</div>
          </div>
        </div>
      </div>

      <!-- Team Invitations Section -->
      <div v-if="teamStore.invitations.length > 0" class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
        <div class="bg-gradient-to-r from-green-500 to-teal-600 px-8 py-5 flex justify-between items-center">
          <h2 class="text-xl font-bold text-white">
            Team Invitations
            <span class="ml-2 bg-white text-green-700 text-sm font-semibold px-2 py-0.5 rounded-full">
              {{ teamStore.invitations.length }}
            </span>
          </h2>
        </div>
        <ul class="divide-y divide-gray-100 p-4">
          <li
            v-for="inv in teamStore.invitations"
            :key="inv.invitation_id"
            class="flex items-center justify-between py-3 px-2"
          >
            <div>
              <p class="font-semibold text-gray-800">{{ inv.team_name }}</p>
              <p class="text-sm text-gray-500">Invited by {{ inv.inviter_name }}</p>
            </div>
            <div class="flex gap-2">
              <button
                @click="handleAcceptInvitation(inv.invitation_id)"
                class="bg-green-600 text-white px-4 py-1.5 rounded-lg text-sm font-medium hover:bg-green-700 transition"
              >
                Accept
              </button>
              <button
                @click="handleDeclineInvitation(inv.invitation_id)"
                class="bg-gray-200 text-gray-700 px-4 py-1.5 rounded-lg text-sm font-medium hover:bg-gray-300 transition"
              >
                Decline
              </button>
            </div>
          </li>
        </ul>
      </div>

      <!-- Projects Section — grouped per team -->
      <div class="space-y-8">
        <!-- Header bar -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
          <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-8 py-6 flex justify-between items-center">
            <h2 class="text-2xl font-bold text-white">Your Projects</h2>
            <button
              @click="openCreateProject(null)"
              class="bg-white text-blue-600 px-6 py-2 rounded-lg font-semibold hover:bg-blue-50 transition"
            >
              Create Project &rarr;
            </button>
          </div>
        </div>

        <!-- Loading -->
        <div v-if="projectStore.isLoading" class="text-center text-gray-500 py-12">
          <p>Loading your projects...</p>
        </div>

        <!-- No projects at all -->
        <div v-else-if="projectStore.projects.length === 0" class="bg-white rounded-xl shadow p-12 text-center">
          <div class="text-gray-400 text-5xl mb-4">&#128228;</div>
          <p class="text-gray-600 text-lg mb-2">No projects yet</p>
          <p class="text-gray-400 text-sm mb-6">Create a team and start your first project.</p>
          <button
            @click="openCreateProject(null)"
            class="bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 transition"
          >
            Create Your First Project
          </button>
        </div>

        <!-- Grouped by team -->
        <div v-else v-for="group in projectStore.projectsByTeam" :key="group.team_id" class="bg-white rounded-xl shadow-lg overflow-hidden">
          <!-- Team header -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 bg-green-100 text-green-700 rounded-lg flex items-center justify-center font-bold text-sm flex-shrink-0">
                {{ group.team_name.charAt(0).toUpperCase() }}
              </div>
              <h3 class="font-semibold text-gray-800 text-lg">{{ group.team_name }}</h3>
              <span class="text-xs text-gray-400 bg-gray-200 px-2 py-0.5 rounded-full">{{ group.projects.length }} project{{ group.projects.length !== 1 ? 's' : '' }}</span>
            </div>
            <div class="flex items-center gap-2">
              <button
                v-if="canCreateInTeam(group.team_id)"
                @click="openCreateProject(group.team_id)"
                class="text-sm text-blue-600 hover:text-blue-800 font-medium transition"
              >
                + Add project
              </button>
              <RouterLink :to="`/teams/${group.team_id}`" class="text-sm text-gray-400 hover:text-gray-600 transition">
                View team &rarr;
              </RouterLink>
            </div>
          </div>
          <!-- Projects grid -->
          <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <RouterLink
              v-for="project in group.projects"
              :key="project.project_id"
              :to="`/sprint/${project.project_id}`"
              class="group border-2 border-gray-200 rounded-xl p-5 hover:border-blue-400 hover:shadow-lg transition cursor-pointer flex flex-col"
            >
              <div class="flex justify-between items-start mb-2">
                <h4 class="font-bold text-gray-900 group-hover:text-blue-600 transition leading-tight">
                  {{ project.project_name }}
                </h4>
                <span class="text-xl text-gray-300 ml-2 flex-shrink-0">&#128202;</span>
              </div>
              <p class="text-sm text-gray-500 mb-4 line-clamp-2 flex-1">{{ project.description || 'No description.' }}</p>
              <div class="flex justify-between items-center pt-3 border-t border-gray-100">
                <span class="text-xs text-gray-400">{{ new Date(project.created_at).toLocaleDateString('nl-NL') }}</span>
                <span class="text-blue-600 font-medium text-xs group-hover:translate-x-1 transition">Open &rarr;</span>
              </div>
            </RouterLink>
          </div>
        </div>
      </div>

      <!-- New Project Modal -->
      <div v-if="showNewProjectModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50" @click.self="closeModal">
        <div class="bg-white rounded-xl p-8 w-full max-w-lg shadow-2xl">
          <h2 class="text-2xl font-bold text-gray-800 mb-6">Create New Project</h2>

          <div v-if="createError" class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
            {{ createError }}
          </div>

          <div class="space-y-4">
            <!-- Team selector -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Team (optional)</label>
              <div class="relative">
                <input
                  v-model="teamSearch"
                  type="text"
                  placeholder="Search your teams or leave empty for default team..."
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  @focus="showTeamDropdown = true"
                  @blur="hideTeamDropdownDelayed"
                />
                <!-- Selected team badge -->
                <div v-if="newProject.teamId" class="mt-1 flex items-center gap-2">
                  <span class="bg-blue-100 text-blue-700 text-xs font-medium px-2 py-1 rounded-full flex items-center gap-1">
                    {{ selectedTeamName }}
                    <button @click.prevent="clearTeam" class="ml-1 hover:text-blue-900">&times;</button>
                  </span>
                </div>
                <!-- Dropdown -->
                <ul
                  v-if="showTeamDropdown && filteredTeams.length > 0"
                  class="absolute z-10 w-full bg-white border border-gray-200 rounded-lg shadow-xl mt-1 max-h-48 overflow-y-auto"
                >
                  <li
                    v-for="team in filteredTeams"
                    :key="team.team_id"
                    @mousedown.prevent="selectTeam(team)"
                    class="px-4 py-2 hover:bg-blue-50 cursor-pointer text-sm text-gray-800 flex items-center gap-2"
                  >
                    <span class="w-6 h-6 bg-green-100 text-green-700 rounded font-bold text-xs flex items-center justify-center flex-shrink-0">
                      {{ team.team_name.charAt(0).toUpperCase() }}
                    </span>
                    {{ team.team_name }}
                  </li>
                </ul>
                <div
                  v-if="showTeamDropdown && filteredTeams.length === 0"
                  class="absolute z-10 w-full bg-white border border-gray-200 rounded-lg shadow-xl mt-1 px-4 py-3 text-sm text-gray-500"
                >
                  No matching teams found.
                </div>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Project Name *</label>
              <input
                v-model="newProject.projectName"
                type="text"
                placeholder="e.g., Mobile App Redesign"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
              <textarea
                v-model="newProject.description"
                placeholder="Describe the project..."
                rows="3"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              ></textarea>
            </div>

            <div class="flex gap-2 pt-2">
              <button
                @click="createProject"
                :disabled="!newProject.projectName || isCreating"
                class="flex-1 bg-blue-600 text-white py-2 rounded-lg font-medium hover:bg-blue-700 disabled:bg-gray-400 transition"
              >
                {{ isCreating ? 'Creating...' : 'Create Project' }}
              </button>
              <button @click="closeModal" class="flex-1 bg-gray-300 text-gray-800 py-2 rounded-lg font-medium hover:bg-gray-400 transition">
                Cancel
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from '@/BaseURL'
import { useAuthStore } from '@/stores/AuthStore'
import { useProjectStore } from '@/stores/ProjectStore'
import { useSprintStore } from '@/stores/SprintStore'
import { useTaskStore } from '@/stores/TaskStore'
import { useTeamStore } from '@/stores/TeamStore'

const authStore = useAuthStore()
const projectStore = useProjectStore()
const sprintStore = useSprintStore()
const taskStore = useTaskStore()
const teamStore = useTeamStore()
const router = useRouter()

// Local stats refs (populated on mount for all projects)
const dashboardSprints = ref([])
const dashboardTasks = ref([])

// Modal states
const showNewProjectModal = ref(false)
const isCreating = ref(false)
const createError = ref('')

// Team search / combobox
const teamSearch = ref('')
const showTeamDropdown = ref(false)

const newProject = ref({
  projectName: '',
  description: '',
  teamId: null,
})

const filteredTeams = computed(() => {
  const q = teamSearch.value.toLowerCase().trim()
  return teamStore.teams.filter((t) =>
    !q || t.team_name.toLowerCase().includes(q)
  )
})

const selectedTeamName = computed(() => {
  const t = teamStore.teams.find((t) => t.team_id === newProject.value.teamId)
  return t ? t.team_name : ''
})

function selectTeam(team) {
  newProject.value.teamId = team.team_id
  teamSearch.value = ''
  showTeamDropdown.value = false
}

function clearTeam() {
  newProject.value.teamId = null
  teamSearch.value = ''
}

function hideTeamDropdownDelayed() {
  setTimeout(() => { showTeamDropdown.value = false }, 150)
}

function openCreateProject(teamId) {
  newProject.value = { projectName: '', description: '', teamId: teamId || null }
  teamSearch.value = ''
  showTeamDropdown.value = false
  createError.value = ''
  showNewProjectModal.value = true
}

function canCreateInTeam(teamId) {
  return teamStore.adminTeams.some((t) => t.team_id === teamId)
}

const activeSprints = computed(() =>
  dashboardSprints.value.filter((s) => s.status === 'active')
)

const doingTasks = computed(() =>
  dashboardTasks.value.filter((t) => t.status === 'doing')
)

const doneTasks = computed(() =>
  dashboardTasks.value.filter((t) => t.status === 'done')
)

const createProject = async () => {
  if (!newProject.value.projectName) {
    createError.value = 'Project name is required'
    return
  }
  isCreating.value = true
  createError.value = ''

  try {
    const success = await projectStore.createProject(
      newProject.value.projectName,
      newProject.value.description,
      newProject.value.teamId
    )

    if (success) {
      closeModal()
    } else {
      createError.value = projectStore.error || 'Failed to create project'
    }
  } catch (error) {
    createError.value = error.message || 'An error occurred'
  } finally {
    isCreating.value = false
  }
}

const closeModal = () => {
  showNewProjectModal.value = false
  newProject.value = { projectName: '', description: '', teamId: null }
  teamSearch.value = ''
  createError.value = ''
}

const handleAcceptInvitation = async (invitationId) => {
  await teamStore.acceptInvitation(invitationId)
}

const handleDeclineInvitation = async (invitationId) => {
  await teamStore.declineInvitation(invitationId)
}

onMounted(async () => {
  // Hydrate auth if needed
  if (!authStore.isLoggedIn) {
    authStore.hydrate()
  }

  // If still not logged in, redirect
  if (!authStore.isLoggedIn) {
    router.push('/login')
    return
  }

  // Fetch teams first (needed for grouping + admin check)
  await teamStore.fetchMyTeams()

  // Fetch projects
  await projectStore.fetchProjects()

  // Fetch pending team invitations
  await teamStore.fetchMyInvitations()

  // Fetch stats for all projects in parallel
  const projectIds = projectStore.projects.map((p) => p.project_id)
  if (projectIds.length > 0) {
    const [sprintResults, taskResults] = await Promise.all([
      Promise.all(projectIds.map((id) => axios.get(`/projects/${id}/sprints`).catch(() => ({ data: { data: [] } })))),
      Promise.all(projectIds.map((id) => axios.get(`/projects/${id}/tasks`).catch(() => ({ data: { data: [] } }))))
    ])
    dashboardSprints.value = sprintResults.flatMap((r) => r.data?.data || [])
    dashboardTasks.value = taskResults.flatMap((r) => r.data?.data || [])
  }
})
</script>


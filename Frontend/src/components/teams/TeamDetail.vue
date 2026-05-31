<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow">
      <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex items-center gap-4 mb-2">
          <button @click="router.push('/teams')" class="text-gray-500 hover:text-blue-600 transition text-sm font-medium">
            &larr; Back to Teams
          </button>
        </div>
        <div class="flex justify-between items-start">
          <div>
            <h1 class="text-4xl font-bold text-gray-800">{{ team?.team_name || 'Loading...' }}</h1>
            <p class="text-gray-600 mt-1">{{ team?.description || '' }}</p>
          </div>
          <div class="flex gap-2" v-if="isOwner">
            <button
              @click="showInviteModal = true"
              class="bg-green-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-green-700 transition"
            >
              + Invite Member
            </button>
            <button
              @click="showEditModal = true"
              class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-medium hover:bg-gray-300 transition"
            >
              Edit
            </button>
            <button
              @click="handleDeleteTeam"
              class="bg-red-100 text-red-600 px-4 py-2 rounded-lg font-medium hover:bg-red-200 transition"
            >
              Delete
            </button>
          </div>
          <div v-else>
            <button
              @click="showInviteModal = true"
              class="bg-green-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-green-700 transition"
            >
              + Invite Member
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-8">
      <!-- Loading -->
      <div v-if="teamStore.isLoading" class="text-center py-12">
        <p class="text-gray-600">Loading team...</p>
      </div>

      <!-- Error -->
      <div v-if="teamStore.error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        {{ teamStore.error }}
      </div>

      <!-- Members Section -->
      <div v-if="team" class="bg-white rounded-xl shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Members ({{ members.length }})</h2>
        <div v-if="members.length === 0" class="text-gray-500 text-sm">No members yet.</div>
        <ul class="divide-y divide-gray-100">
          <li
            v-for="member in members"
            :key="member.user_id"
            class="flex items-center justify-between py-3"
          >
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0">
                {{ member.firstname.charAt(0).toUpperCase() }}{{ member.lastname.charAt(0).toUpperCase() }}
              </div>
              <div>
                <p class="font-medium text-gray-800">{{ member.firstname }} {{ member.lastname }}</p>
                <p class="text-xs text-gray-500">{{ member.email }}</p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <span
                :class="member.role === 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-600'"
                class="text-xs font-medium px-2 py-1 rounded-full"
              >
                {{ member.role === 'admin' ? 'Admin' : 'Member' }}
              </span>
              <button
                v-if="isOwner && member.user_id !== authStore.user?.user_id"
                @click="handleRemoveMember(member.user_id)"
                class="text-red-400 hover:text-red-600 text-xs font-medium transition"
              >
                Remove
              </button>
            </div>
          </li>
        </ul>
      </div>

      <!-- Projects Section -->
      <div v-if="team" class="bg-white rounded-xl shadow-md p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-semibold text-gray-800">
            Projects
            <span class="ml-2 text-sm text-gray-400 font-normal">({{ teamProjects.length }})</span>
          </h2>
          <button
            v-if="isAdminMember"
            @click="showCreateProjectModal = true"
            class="bg-blue-600 text-white px-4 py-1.5 rounded-lg text-sm font-medium hover:bg-blue-700 transition"
          >
            + New Project
          </button>
        </div>

        <div v-if="projectStore.isLoading" class="text-gray-400 text-sm">Loading...</div>
        <div v-else-if="teamProjects.length === 0" class="text-gray-500 text-sm py-4 text-center">
          No projects in this team yet.
          <span v-if="isAdminMember"> Click "+ New Project" to create one.</span>
        </div>
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <RouterLink
            v-for="project in teamProjects"
            :key="project.project_id"
            :to="`/sprint/${project.project_id}`"
            class="group border-2 border-gray-200 rounded-xl p-4 hover:border-blue-400 hover:shadow-md transition flex flex-col"
          >
            <div class="flex justify-between items-start mb-1">
              <h4 class="font-bold text-gray-900 group-hover:text-blue-600 transition text-sm leading-tight">{{ project.project_name }}</h4>
              <span class="text-lg text-gray-300 ml-2 flex-shrink-0">&#128202;</span>
            </div>
            <p class="text-xs text-gray-500 mb-3 flex-1 line-clamp-2">{{ project.description || 'No description.' }}</p>
            <span class="text-blue-600 text-xs font-medium group-hover:underline">Open &rarr;</span>
          </RouterLink>
        </div>
      </div>
    </div>

    <!-- Invite Modal -->
    <div
      v-if="showInviteModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="showInviteModal = false"
    >
      <div class="bg-white rounded-xl shadow-2xl p-8 w-full max-w-md mx-4">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Invite Member</h2>
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
          <input
            v-model="inviteEmail"
            type="email"
            placeholder="colleague@example.com"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            @keyup.enter="handleInvite"
          />
        </div>
        <div v-if="inviteError" class="text-red-600 text-sm mb-3">{{ inviteError }}</div>
        <div v-if="inviteSuccess" class="text-green-600 text-sm mb-3">Invitation sent!</div>
        <div class="flex gap-3">
          <button
            @click="showInviteModal = false; inviteError = ''; inviteSuccess = false"
            class="flex-1 border border-gray-300 text-gray-700 py-2 rounded-lg hover:bg-gray-50 transition"
          >
            Cancel
          </button>
          <button
            @click="handleInvite"
            :disabled="teamStore.isLoading"
            class="flex-1 bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 transition disabled:opacity-50"
          >
            {{ teamStore.isLoading ? 'Sending...' : 'Send Invitation' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Edit Team Modal -->
    <div
      v-if="showEditModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="showEditModal = false"
    >
      <div class="bg-white rounded-xl shadow-2xl p-8 w-full max-w-md mx-4">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Edit Team</h2>
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Team Name *</label>
          <input
            v-model="editForm.team_name"
            type="text"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
          <textarea
            v-model="editForm.description"
            rows="3"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          ></textarea>
        </div>
        <div v-if="editError" class="text-red-600 text-sm mb-4">{{ editError }}</div>
        <div class="flex gap-3">
          <button @click="showEditModal = false" class="flex-1 border border-gray-300 text-gray-700 py-2 rounded-lg hover:bg-gray-50 transition">Cancel</button>
          <button @click="handleEditTeam" :disabled="teamStore.isLoading" class="flex-1 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition disabled:opacity-50">
            {{ teamStore.isLoading ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Create Project Modal -->
    <div
      v-if="showCreateProjectModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="showCreateProjectModal = false"
    >
      <div class="bg-white rounded-xl shadow-2xl p-8 w-full max-w-md mx-4">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">New Project in {{ team?.team_name }}</h2>
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Project Name *</label>
          <input
            v-model="newProjectForm.name"
            type="text"
            placeholder="e.g., Sprint Planning App"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
          <textarea
            v-model="newProjectForm.description"
            rows="3"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          ></textarea>
        </div>
        <div v-if="newProjectError" class="text-red-600 text-sm mb-4">{{ newProjectError }}</div>
        <div class="flex gap-3">
          <button @click="showCreateProjectModal = false" class="flex-1 border border-gray-300 text-gray-700 py-2 rounded-lg hover:bg-gray-50 transition">Cancel</button>
          <button @click="handleCreateProject" :disabled="projectStore.isLoading" class="flex-1 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition disabled:opacity-50">
            {{ projectStore.isLoading ? 'Creating...' : 'Create Project' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useTeamStore } from '@/stores/TeamStore'
import { useProjectStore } from '@/stores/ProjectStore'
import { useAuthStore } from '@/stores/AuthStore'

const route = useRoute()
const router = useRouter()
const teamStore = useTeamStore()
const projectStore = useProjectStore()
const authStore = useAuthStore()

const showInviteModal = ref(false)
const showEditModal = ref(false)
const showCreateProjectModal = ref(false)
const inviteEmail = ref('')
const inviteError = ref('')
const inviteSuccess = ref(false)
const editError = ref('')
const newProjectError = ref('')
const editForm = ref({ team_name: '', description: '' })
const newProjectForm = ref({ name: '', description: '' })

const teamId = computed(() => Number(route.params.id))
const team = computed(() => teamStore.currentTeam)
const members = computed(() => teamStore.currentTeam?.members || [])
const isOwner = computed(() => team.value?.owner_id === authStore.user?.user_id)
// Admin = owner OR has admin role in team_members
const isAdminMember = computed(() => {
  if (isOwner.value) return true
  const me = members.value.find((m) => m.user_id === authStore.user?.user_id)
  return me?.role === 'admin'
})
// Projects belonging to this team (from ProjectStore)
const teamProjects = computed(() =>
  projectStore.projects.filter((p) => p.team_id === teamId.value)
)

onMounted(async () => {
  const ok = await teamStore.fetchTeam(teamId.value)
  if (ok && team.value) {
    editForm.value.team_name = team.value.team_name
    editForm.value.description = team.value.description || ''
  }
  // Also fetch projects so the projects section has data
  await projectStore.fetchProjects()
})

async function handleInvite() {
  inviteError.value = ''
  inviteSuccess.value = false
  if (!inviteEmail.value.trim()) {
    inviteError.value = 'Email is required'
    return
  }
  const ok = await teamStore.inviteUser(teamId.value, inviteEmail.value.trim())
  if (ok) {
    inviteSuccess.value = true
    inviteEmail.value = ''
    setTimeout(() => {
      showInviteModal.value = false
      inviteSuccess.value = false
    }, 1500)
  } else {
    inviteError.value = teamStore.error || 'Failed to send invitation'
  }
}

async function handleRemoveMember(userId) {
  if (!confirm('Remove this member from the team?')) return
  await teamStore.removeMember(teamId.value, userId)
  // Refresh team data
  await teamStore.fetchTeam(teamId.value)
}

async function handleEditTeam() {
  editError.value = ''
  if (!editForm.value.team_name.trim()) {
    editError.value = 'Team name is required'
    return
  }
  const updated = await teamStore.updateTeam(teamId.value, editForm.value.team_name, editForm.value.description)
  if (updated) {
    showEditModal.value = false
    await teamStore.fetchTeam(teamId.value)
  } else {
    editError.value = teamStore.error || 'Failed to update team'
  }
}

async function handleDeleteTeam() {
  if (!confirm(`Delete team "${team.value?.team_name}"? This cannot be undone.`)) return
  const ok = await teamStore.deleteTeam(teamId.value)
  if (ok) router.push('/teams')
}

async function handleCreateProject() {
  newProjectError.value = ''
  if (!newProjectForm.value.name.trim()) {
    newProjectError.value = 'Project name is required'
    return
  }
  const ok = await projectStore.createProject(
    newProjectForm.value.name,
    newProjectForm.value.description,
    teamId.value
  )
  if (ok) {
    showCreateProjectModal.value = false
    newProjectForm.value = { name: '', description: '' }
  } else {
    newProjectError.value = projectStore.error || 'Failed to create project'
  }
}
</script>

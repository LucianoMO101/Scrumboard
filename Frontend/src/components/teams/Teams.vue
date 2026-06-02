<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow">
      <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex justify-between items-center">
          <div>
            <h1 class="text-4xl font-bold text-gray-800">Teams</h1>
            <p class="text-gray-600 mt-2">Manage your teams and members</p>
          </div>
          <button
            @click="showNewTeamModal = true"
            class="bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 transition"
          >
            + New Team
          </button>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 py-8">
      <!-- Loading -->
      <div v-if="teamStore.isLoading" class="text-center py-12">
        <p class="text-gray-600">Loading teams...</p>
      </div>

      <!-- Error -->
      <div v-if="teamStore.error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        {{ teamStore.error }}
      </div>

      <!-- Pending Invitations Section -->
      <div v-if="teamStore.invitations.length > 0" class="mb-12">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
          <span class="text-2xl">📨</span>
          Pending Team Invitations
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="invitation in teamStore.invitations"
            :key="invitation.invitation_id"
            class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-xl shadow-md border-2 border-amber-200 p-6 hover:shadow-lg transition"
          >
            <div class="flex items-start justify-between mb-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-200 text-amber-700 rounded-lg flex items-center justify-center text-lg font-bold">
                  {{ invitation.team_name.charAt(0).toUpperCase() }}
                </div>
                <div>
                  <h3 class="text-lg font-bold text-gray-800">{{ invitation.team_name }}</h3>
                  <span class="text-xs text-gray-600">Invited by {{ invitation.invited_by_name || 'Team Owner' }}</span>
                </div>
              </div>
              <span class="px-3 py-1 bg-amber-200 text-amber-900 text-xs font-semibold rounded-full">Pending</span>
            </div>
            <p class="text-gray-600 text-sm mb-4">
              {{ invitation.team_description || 'No description provided.' }}
            </p>
            <div class="flex gap-3">
              <button
                @click="acceptTeamInvitation(invitation.invitation_id)"
                :disabled="teamStore.isLoading"
                class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg font-medium transition disabled:opacity-50"
              >
                ✓ Accept
              </button>
              <button
                @click="declineTeamInvitation(invitation.invitation_id)"
                :disabled="teamStore.isLoading"
                class="flex-1 border border-red-300 hover:bg-red-50 text-red-600 py-2 rounded-lg font-medium transition disabled:opacity-50"
              >
                ✕ Decline
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Teams Grid -->
      <div
        v-if="!teamStore.isLoading && teamStore.teams.length > 0"
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
      >
        <div
          v-for="team in teamStore.teams"
          :key="team.team_id"
          class="bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-200 cursor-pointer border border-gray-100 hover:border-blue-300 group overflow-hidden"
          @click="goToTeam(team.team_id)"
        >
          <div class="h-1.5 bg-gradient-to-r from-green-500 to-teal-500 rounded-t-xl"></div>
          <div class="p-6">
            <div class="flex items-start justify-between mb-3">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 text-green-700 rounded-lg flex items-center justify-center text-lg font-bold flex-shrink-0">
                  {{ team.team_name.charAt(0).toUpperCase() }}
                </div>
                <div>
                  <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-600 transition">
                    {{ team.team_name }}
                  </h3>
                  <span class="text-xs text-gray-400">
                    {{ team.owner_id === authStore.user?.user_id ? 'Owner' : 'Member' }}
                  </span>
                </div>
              </div>
            </div>
            <p class="text-gray-600 text-sm mb-4 line-clamp-2 min-h-[2.5rem]">
              {{ team.description || 'No description provided.' }}
            </p>
            <div class="flex justify-between items-center pt-4 border-t border-gray-100">
              <span class="text-xs text-gray-400">
                Created {{ new Date(team.created_at).toLocaleDateString('nl-NL') }}
              </span>
              <span class="text-xs text-green-600 font-medium group-hover:underline">
                View Team &rarr;
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-if="!teamStore.isLoading && teamStore.teams.length === 0" class="text-center py-16">
        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <span class="text-4xl">👥</span>
        </div>
        <h3 class="text-xl font-semibold text-gray-700 mb-2">No teams yet</h3>
        <p class="text-gray-500 mb-6">Create a team to collaborate with others.</p>
        <button
          @click="showNewTeamModal = true"
          class="bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 transition"
        >
          + Create your first team
        </button>
      </div>
    </div>

    <!-- New Team Modal -->
    <div
      v-if="showNewTeamModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="showNewTeamModal = false"
    >
      <div class="bg-white rounded-xl shadow-2xl p-8 w-full max-w-md mx-4">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Create New Team</h2>
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Team Name *</label>
          <input
            v-model="newTeam.team_name"
            type="text"
            placeholder="e.g. Development Team"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
          <textarea
            v-model="newTeam.description"
            rows="3"
            placeholder="Optional description..."
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          ></textarea>
        </div>
        <div v-if="formError" class="text-red-600 text-sm mb-4">{{ formError }}</div>
        <div class="flex gap-3">
          <button
            @click="showNewTeamModal = false"
            class="flex-1 border border-gray-300 text-gray-700 py-2 rounded-lg hover:bg-gray-50 transition"
          >
            Cancel
          </button>
          <button
            @click="handleCreateTeam"
            :disabled="teamStore.isLoading"
            class="flex-1 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition disabled:opacity-50"
          >
            {{ teamStore.isLoading ? 'Creating...' : 'Create Team' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useTeamStore } from '@/stores/TeamStore'
import { useAuthStore } from '@/stores/AuthStore'

const router = useRouter()
const teamStore = useTeamStore()
const authStore = useAuthStore()

const showNewTeamModal = ref(false)
const formError = ref('')
const newTeam = ref({ team_name: '', description: '' })

onMounted(async () => {
  await teamStore.fetchMyTeams()
  await teamStore.fetchMyInvitations()
})

function goToTeam(id) {
  router.push(`/teams/${id}`)
}

async function handleCreateTeam() {
  formError.value = ''
  if (!newTeam.value.team_name.trim()) {
    formError.value = 'Team name is required'
    return
  }
  const created = await teamStore.createTeam(newTeam.value.team_name, newTeam.value.description)
  if (created) {
    showNewTeamModal.value = false
    newTeam.value = { team_name: '', description: '' }
  } else {
    formError.value = teamStore.error || 'Failed to create team'
  }
}

async function acceptTeamInvitation(invitationId) {
  const success = await teamStore.acceptInvitation(invitationId)
  if (success) {
    // Invitations list will be updated automatically by the store
  }
}

async function declineTeamInvitation(invitationId) {
  const success = await teamStore.declineInvitation(invitationId)
  if (success) {
    // Invitations list will be updated automatically by the store
  }
}
</script>

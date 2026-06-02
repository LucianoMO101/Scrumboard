<template>
  <div>
    <!-- Member list -->
    <div v-if="members.length === 0 && !isLoading" class="text-sm text-gray-400 italic py-2">
      No members found.
    </div>
    <div v-if="isLoading" class="text-sm text-gray-400 py-2">Loading members...</div>

    <ul class="space-y-2 mb-4">
      <li
        v-for="member in members"
        :key="member.user_id"
        class="flex items-center justify-between gap-3 bg-gray-50 rounded-lg px-3 py-2"
      >
        <div class="flex items-center gap-2 min-w-0">
          <div class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center text-xs font-bold shrink-0">
            {{ initials(member.user_name) }}
          </div>
          <div class="min-w-0">
            <p class="text-sm font-medium text-gray-800 truncate">{{ member.user_name || 'Unknown' }}</p>
            <p v-if="member.email" class="text-xs text-gray-400 truncate">{{ member.email }}</p>
          </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
          <!-- Role selector (owner only, can't change own role if owner) -->
          <select
            v-if="isOwner && !(member.role === 'owner' && member.user_id === currentUserId)"
            :value="member.role"
            @change="changeRole(member.user_id, $event.target.value)"
            class="border border-gray-200 rounded px-2 py-1 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
          >
            <option value="viewer">Viewer</option>
            <option value="editor">Editor</option>
            <option value="owner">Owner</option>
          </select>
          <span v-else :class="roleBadgeClass(member.role)" class="text-xs px-2 py-0.5 rounded-full font-medium">
            {{ member.role }}
          </span>

          <!-- Remove button (owner only, can't remove themselves if last owner) -->
          <button
            v-if="isOwner && member.user_id !== currentUserId"
            @click="removeMember(member)"
            class="text-xs text-red-400 hover:text-red-600 transition"
            title="Remove from project"
          >&times;</button>
        </div>
      </li>
    </ul>

    <!-- Add member section (owner only) -->
    <div v-if="isOwner" class="border-t pt-3">
      <p class="text-xs font-semibold text-gray-600 mb-2">Add team member to project</p>
      <div class="flex gap-2">
        <select
          v-model="selectedUserId"
          class="flex-1 border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="">Select team member…</option>
          <option
            v-for="tm in availableTeamMembers"
            :key="tm.user_id"
            :value="tm.user_id"
          >{{ tm.name }}</option>
        </select>
        <select
          v-model="selectedRole"
          class="w-28 border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="viewer">Viewer</option>
          <option value="editor">Editor</option>
        </select>
        <button
          @click="addMember"
          :disabled="!selectedUserId || addLoading"
          class="bg-blue-600 text-white px-3 py-1.5 rounded-lg text-sm font-medium hover:bg-blue-700 transition disabled:opacity-50"
        >{{ addLoading ? '...' : 'Add' }}</button>
      </div>
      <p v-if="errorMsg" class="text-xs text-red-500 mt-1">{{ errorMsg }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from '@/BaseURL'
import { useAuthStore } from '@/stores/AuthStore'

const props = defineProps({
  projectId: { type: Number, required: true },
  teamId: { type: Number, required: true },
})

const emit = defineEmits(['members-changed'])

const authStore = useAuthStore()
const currentUserId = computed(() => authStore.user?.user_id ?? parseInt(localStorage.getItem('userId')))

const members = ref([])
const teamMembers = ref([])
const isLoading = ref(false)
const addLoading = ref(false)
const errorMsg = ref('')
const selectedUserId = ref('')
const selectedRole = ref('viewer')

const isOwner = computed(() => {
  const me = members.value.find((m) => m.user_id === currentUserId.value)
  return me?.role === 'owner'
})

// Team members NOT already in the project
const availableTeamMembers = computed(() => {
  const memberIds = new Set(members.value.map((m) => m.user_id))
  return teamMembers.value.filter((tm) => !memberIds.has(tm.user_id))
})

const fetchMembers = async () => {
  isLoading.value = true
  try {
    const res = await axios.get(`/projects/${props.projectId}/members`)
    members.value = res.data.data
  } catch {
    // ignore
  } finally {
    isLoading.value = false
  }
}

const fetchTeamMembers = async () => {
  try {
    const res = await axios.get(`/teams/${props.teamId}`)
    teamMembers.value = (res.data.data?.members || []).map((m) => ({
      user_id: m.user_id,
      name: m.name || (m.firstname && m.lastname ? `${m.firstname} ${m.lastname}` : m.email || `User ${m.user_id}`),
    }))
  } catch {
    // ignore
  }
}

const addMember = async () => {
  if (!selectedUserId.value) return
  addLoading.value = true
  errorMsg.value = ''
  try {
    await axios.post(
      `/projects/${props.projectId}/members`,
      { user_id: parseInt(selectedUserId.value), role: selectedRole.value }
    )
    selectedUserId.value = ''
    selectedRole.value = 'viewer'
    await fetchMembers()
    emit('members-changed')
  } catch (err) {
    errorMsg.value = err.response?.data?.error || 'Failed to add member'
  } finally {
    addLoading.value = false
  }
}

const changeRole = async (targetUserId, newRole) => {
  try {
    await axios.put(
      `/projects/${props.projectId}/members/${targetUserId}`,
      { role: newRole }
    )
    await fetchMembers()
    emit('members-changed')
  } catch (err) {
    alert(err.response?.data?.error || 'Failed to update role')
  }
}

const removeMember = async (member) => {
  if (!confirm(`Remove ${member.user_name || 'this user'} from the project?`)) return
  try {
    await axios.delete(`/projects/${props.projectId}/members/${member.user_id}`)
    await fetchMembers()
    emit('members-changed')
  } catch (err) {
    alert(err.response?.data?.error || 'Failed to remove member')
  }
}

const initials = (name) => {
  if (!name) return '?'
  return name
    .split(' ')
    .map((w) => w[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

const roleBadgeClass = (role) => {
  const map = {
    owner: 'bg-purple-100 text-purple-700',
    editor: 'bg-blue-100 text-blue-700',
    viewer: 'bg-gray-100 text-gray-600',
  }
  return map[role] || 'bg-gray-100 text-gray-600'
}

onMounted(async () => {
  await Promise.all([fetchMembers(), fetchTeamMembers()])
})

watch(() => props.projectId, async () => {
  await Promise.all([fetchMembers(), fetchTeamMembers()])
})
</script>

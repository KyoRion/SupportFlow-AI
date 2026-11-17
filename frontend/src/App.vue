<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { getErrorMessage } from '@/lib/api'
import { ref } from 'vue'

const auth = useAuthStore()
const router = useRouter()
const error = ref<string | null>(null)

async function logout() {
  try {
    error.value = null
    await auth.logout()
    await router.replace({ name: 'login' })
  } catch (e) {
    error.value = getErrorMessage(e)
  }
}
</script>

<template>
  <div class="app-shell">
    <header v-if="auth.user" class="app-header">
      <RouterLink to="/tickets" class="brand">
        SupportFlow AI
      </RouterLink>

      <nav>
        <RouterLink to="/tickets">Tickets</RouterLink>
        <RouterLink to="/tickets/create">New Ticket</RouterLink>
        <RouterLink to="/ai-chat">
          AI Assistant
        </RouterLink>
        <RouterLink to="/knowledge">
          Knowledge Base
        </RouterLink>
      </nav>

      <div class="user-area">
        <span>{{ auth.user.name }}</span>
        <button type="button" @click="logout">Logout</button>
      </div>
    </header>

    <p v-if="error" class="error">{{ error }}</p>

    <RouterView />
  </div>
</template>

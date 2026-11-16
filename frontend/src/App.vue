
<script setup lang="ts">
import { ref, onMounted } from 'vue'

interface HealthResponse {
  status: string
  service: string
  database: string
}

const loading = ref(true)
const error = ref<string | null>(null)
const health = ref<HealthResponse | null>(null)

onMounted(async () => {
  try {
    const response = await fetch('/api/health')

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`)
    }

    health.value = await response.json()
  } catch (e) {
    error.value =
      e instanceof Error ? e.message : 'Unknown error'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <main class="container">
    <h1>SupportFlow AI</h1>
    <p>Laravel AI & MCP Learning Project</p>

    <p v-if="loading">Connecting to backend...</p>

    <p v-else-if="error">
      Connection failed: {{ error }}
    </p>

    <div v-else-if="health">
      <h3>Backend Connected</h3>
      <pre>{{ health }}</pre>
    </div>
  </main>
</template>

<style scoped>
.container {
  max-width: 900px;
  margin: 100px auto;
  font-family: system-ui, sans-serif;
}
</style>

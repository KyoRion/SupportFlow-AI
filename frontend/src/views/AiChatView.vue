<script setup lang="ts">
import { nextTick, onMounted, ref } from 'vue'

import { useAuthStore } from '@/stores/auth'
import { useAiChatStore } from '@/stores/aiChat'

const auth = useAuthStore()
const chat = useAiChatStore()

const prompt = ref('')
const listElement = ref<HTMLElement | null>(null)

onMounted(async () => {
  if (auth.user) {
    await chat.initialize(auth.user.id)
  }
})

async function submit() {
  if (!auth.user || !prompt.value.trim()) return

  const text = prompt.value

  await chat.send(auth.user.id, text)

  if (!chat.error) {
    prompt.value = ''
  }

  await nextTick()

  listElement.value?.scrollTo({
    top: listElement.value.scrollHeight,
    behavior: 'smooth',
  })
}

function startNewChat() {
  if (!auth.user) return

  chat.newConversation(auth.user.id)
  prompt.value = ''
}
</script>

<template>
  <main class="container ai-page">
    <div class="page-heading">
      <div>
        <h1>AI Support Assistant</h1>
        <p>
          Ask questions and explore support workflows.
        </p>
      </div>

      <button
        type="button"
        :disabled="chat.busy"
        @click="startNewChat"
      >
        New Conversation
      </button>
    </div>

    <section class="panel chat-panel">
      <div ref="listElement" class="chat-messages">
        <div v-if="chat.loading">
          Loading conversation...
        </div>

        <div
          v-else-if="!chat.messages.length"
          class="empty-chat"
        >
          <h2>Welcome to SupportFlow AI</h2>
          <p>
            Ask me about tickets, customers,
            or support workflows.
          </p>
        </div>

        <div
          v-for="message in chat.messages"
          :key="message.id"
          class="chat-message"
          :class="message.role"
        >
          <strong>
            {{ message.role === 'user' ? 'You' : 'AI Assistant' }}
          </strong>

          <p>{{ message.content }}</p>
        </div>

        <div v-if="chat.busy" class="thinking">
          AI is processing your request...
        </div>
      </div>

      <p v-if="chat.error" class="error" role="alert">
        {{ chat.error }}
      </p>

      <form class="chat-input" @submit.prevent="submit">
        <textarea
          v-model="prompt"
          rows="3"
          maxlength="2000"
          placeholder="Ask the AI Assistant..."
          :disabled="chat.busy || chat.loading"
        />

        <button
          type="submit"
          :disabled="
            chat.busy ||
            chat.loading ||
            !prompt.trim()
          "
        >
          {{ chat.busy ? 'Thinking...' : 'Send' }}
        </button>
      </form>
    </section>
  </main>
</template>

<style scoped>
.ai-page {
  max-width: 1000px;
}

.chat-panel {
  display: flex;
  flex-direction: column;
  height: min(75vh, 760px);
  min-height: 450px;
}

.chat-messages {
  flex: 1;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 16px;
  padding: 12px;
}

.chat-message {
  max-width: 82%;
  padding: 14px 18px;
  border-radius: 12px;
  background: #f1f5f9;
}

.chat-message.user {
  align-self: flex-end;
  background: #dbeafe;
}

.chat-message.assistant {
  align-self: flex-start;
}

.chat-message p {
  margin-bottom: 0;
  white-space: pre-wrap;
  overflow-wrap: anywhere;
}

.empty-chat {
  margin: auto;
  text-align: center;
  color: #64748b;
}

.thinking {
  color: #64748b;
  font-style: italic;
}

.chat-input {
  display: flex;
  gap: 12px;
  border-top: 1px solid #e5e7eb;
  padding-top: 16px;
}

.chat-input button {
  align-self: end;
}

@media (max-width: 640px) {
  .chat-message {
    max-width: 95%;
  }

  .chat-input {
    flex-direction: column;
  }
}
</style>

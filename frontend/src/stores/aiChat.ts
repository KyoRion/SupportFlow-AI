import { ref } from 'vue'
import { defineStore } from 'pinia'
import axios from 'axios'

import { api, getErrorMessage } from '@/lib/api'

import type {
  ChatMessage,
  ChatResponse,
  ChatHistoryResponse,
} from '@/types/ai'

export const useAiChatStore = defineStore('aiChat', () => {
  const messages = ref<ChatMessage[]>([])
  const conversationId = ref<string | null>(null)

  const busy = ref(false)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const activeUserId = ref<number | null>(null)

  function storageKey(userId: number): string {
    return `supportflow:conversation:${userId}`
  }

  async function initialize(userId: number): Promise<void> {
    if (activeUserId.value === userId) return

    activeUserId.value = userId
    messages.value = []
    conversationId.value = null

    const savedId = localStorage.getItem(storageKey(userId))
    if (!savedId) return

    loading.value = true

    try {
      const { data } = await api.get<ChatHistoryResponse>(
        `/api/ai/conversations/${savedId}`,
      )

      conversationId.value = data.conversation_id
      messages.value = data.messages
    } catch (e) {
      if (
        axios.isAxiosError(e) &&
        [403, 404].includes(e.response?.status ?? 0)
      ) {
        localStorage.removeItem(storageKey(userId))
      } else {
        activeUserId.value = null
        error.value = getErrorMessage(e)
      }
    } finally {
      loading.value = false
    }
  }

  async function send(
    userId: number,
    text: string,
  ): Promise<void> {
    const content = text.trim()
    if (!content || busy.value) return

    busy.value = true
    error.value = null

    const tempId = crypto.randomUUID()

    messages.value.push({
      id: tempId,
      role: 'user',
      content,
    })

    try {
      const { data } = await api.post<ChatResponse>(
        '/api/ai/chat',
        {
          message: content,
          conversation_id: conversationId.value,
        },
      )

      conversationId.value = data.conversation_id

      localStorage.setItem(
        storageKey(userId),
        data.conversation_id,
      )

      messages.value.push({
        id: crypto.randomUUID(),
        role: 'assistant',
        content: data.reply,
      })
    } catch (e) {
      messages.value = messages.value.filter(
        (message) => message.id !== tempId,
      )

      error.value = getErrorMessage(e)
    } finally {
      busy.value = false
    }
  }

  function newConversation(userId: number): void {
    if (busy.value) return

    localStorage.removeItem(storageKey(userId))
    conversationId.value = null
    messages.value = []
    error.value = null
  }

  return {
    messages,
    conversationId,
    busy,
    loading,
    error,
    initialize,
    send,
    newConversation,
  }
})

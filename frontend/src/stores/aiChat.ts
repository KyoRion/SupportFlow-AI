import { ref } from 'vue'
import { defineStore } from 'pinia'
import axios from 'axios'

import { api, getErrorMessage } from '@/lib/api'

import type {
  ChatMessage,
  ChatResponse,
  ChatHistoryResponse,
} from '@/types/ai'

import { streamAiChat } from '@/lib/aiStream'
import type {
  ToolExecution,
  RunMetrics,
} from '@/types/ai-stream'

export const useAiChatStore = defineStore('aiChat', () => {
  const messages = ref<ChatMessage[]>([])
  const conversationId = ref<string | null>(null)

  const busy = ref(false)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const activeUserId = ref<number | null>(null)

  const timeline = ref<ToolExecution[]>([])
  const lastMetrics = ref<RunMetrics | null>(null)
  const activeRunId = ref<string | null>(null)

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
    timeline.value = []
    lastMetrics.value = null
    activeRunId.value = null
  }


async function sendStreaming(
  userId: number,
  text: string,
): Promise<void> {
  const content = text.trim()
  if (!content || busy.value) return

  busy.value = true
  error.value = null

  timeline.value = []
  lastMetrics.value = null
  activeRunId.value = null

  const userMessageId = crypto.randomUUID()
  const assistantMessageId = crypto.randomUUID()

  messages.value.push({
    id: userMessageId,
    role: 'user',
    content,
  })

  messages.value.push({
    id: assistantMessageId,
    role: 'assistant',
    content: '',
  })

  try {
    await streamAiChat(
      {
        message: content,
        conversation_id: conversationId.value,
      },
      (event) => {
        switch (event.type) {
          case 'run_start':
            activeRunId.value = event.run_id
            break

          case 'text_delta': {
            const message = messages.value.find(
              (item) => item.id === assistantMessageId,
            )

            if (message) {
              message.content += event.delta
            }

            break
          }

          case 'tool_start':
            timeline.value.push({
              call_id: event.call_id,
              name: event.name,
              status: 'running',
              duration_ms: null,
            })
            break

          case 'tool_end': {
            const tool = timeline.value.find(
              (item) => item.call_id === event.call_id,
            )

            if (tool) {
              tool.status = event.success
                ? 'completed'
                : 'failed'

              tool.duration_ms = event.duration_ms
            }

            break
          }

          case 'run_done': {
            const { type, ...metrics } = event

            lastMetrics.value = metrics
            conversationId.value = event.conversation_id

            if (event.conversation_id) {
              localStorage.setItem(
                storageKey(userId),
                event.conversation_id,
              )
            }

            break
          }

          case 'run_error':
            // The SSE client converts this into an exception.
            break
        }
      },
    )
  } catch (e) {
    error.value = getErrorMessage(e)

    // Keep partial content if streaming was interrupted.
    const assistant = messages.value.find(
      (item) => item.id === assistantMessageId,
    )

    if (assistant && !assistant.content) {
      messages.value = messages.value.filter(
        (item) => item.id !== assistantMessageId,
      )
    }
  } finally {
    busy.value = false
  }
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
    timeline,
    lastMetrics,
    activeRunId,
    sendStreaming,
  }
})

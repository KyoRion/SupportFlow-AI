import { api } from '@/lib/api'
import type { AiStreamEvent } from '@/types/ai-stream'

interface StreamPayload {
  message: string
  conversation_id: string | null
}

function getXsrfToken(): string {
  const cookie = document.cookie
    .split('; ')
    .find((entry) => entry.startsWith('XSRF-TOKEN='))

  return cookie
    ? decodeURIComponent(cookie.substring('XSRF-TOKEN='.length))
    : ''
}

export async function streamAiChat(
  payload: StreamPayload,
  onEvent: (event: AiStreamEvent) => void,
  signal?: AbortSignal,
): Promise<void> {
  // Refresh CSRF cookie for our Laravel session.
  await api.get('/sanctum/csrf-cookie')

  const response = await fetch('/api/ai/chat/stream', {
    method: 'POST',
    credentials: 'same-origin',
    signal,
    headers: {
      Accept: 'text/event-stream',
      'Content-Type': 'application/json',
      'X-XSRF-TOKEN': getXsrfToken(),
    },
    body: JSON.stringify(payload),
  })

  if (!response.ok) {
    throw new Error(`Streaming request failed: HTTP ${response.status}`)
  }

  if (!response.body) {
    throw new Error('Streaming response has no body.')
  }

  const reader = response.body.getReader()
  const decoder = new TextDecoder()

  let buffer = ''
  let finished = false

  function processFrame(frame: string) {
    const lines = frame.split('\n')

    const eventName = lines
      .find((line) => line.startsWith('event:'))
      ?.slice(6)
      .trim()

    const rawData = lines
      .filter((line) => line.startsWith('data:'))
      .map((line) => line.slice(5).trimStart())
      .join('\n')

    if (!eventName || !rawData) return

    const payload = JSON.parse(rawData) as Record<string, unknown>

    const event = {
      ...payload,
      type: eventName,
    } as AiStreamEvent

    onEvent(event)

    if (event.type === 'run_done') {
      finished = true
    }

    if (event.type === 'run_error') {
      throw new Error(event.message)
    }
  }

  try {
    while (true) {
      const { done, value } = await reader.read()

      if (done) break

      buffer += decoder.decode(value, { stream: true })
      buffer = buffer.replace(/\r\n/g, '\n')

      let boundary = buffer.indexOf('\n\n')

      while (boundary !== -1) {
        const frame = buffer.slice(0, boundary)
        buffer = buffer.slice(boundary + 2)

        processFrame(frame)

        boundary = buffer.indexOf('\n\n')
      }
    }

    if (!finished) {
      throw new Error(
        'AI stream ended without a completion event.',
      )
    }
  } finally {
    reader.releaseLock()
  }
}

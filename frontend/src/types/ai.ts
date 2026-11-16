export type ChatRole = 'user' | 'assistant'

export interface ChatMessage {
  id: string
  role: ChatRole
  content: string
}

export interface ChatResponse {
  conversation_id: string
  reply: string
  usage: {
    input_tokens: number
    output_tokens: number
  }
}

export interface ChatHistoryResponse {
  conversation_id: string
  messages: ChatMessage[]
}

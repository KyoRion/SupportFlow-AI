export interface ToolExecution {
  call_id: string
  name: string
  status: 'running' | 'completed' | 'failed'
  duration_ms: number | null
}

export interface RunMetrics {
  run_id: string
  conversation_id: string | null
  provider: string | null
  model: string | null
  ttft_ms: number | null
  duration_ms: number
  tool_calls: number
  usage: {
    input_tokens: number
    output_tokens: number
  }
}

export type AiStreamEvent =
  | {
      type: 'run_start'
      run_id: string
    }
  | {
      type: 'text_delta'
      delta: string
    }
  | {
      type: 'tool_start'
      call_id: string
      name: string
    }
  | {
      type: 'tool_end'
      call_id: string
      name: string
      success: boolean
      duration_ms: number | null
    }
  | ({ type: 'run_done' } & RunMetrics)
  | {
      type: 'run_error'
      run_id: string
      code: string
      message: string
    }

export type TicketStatus =
  | 'open'
  | 'in_progress'
  | 'resolved'
  | 'closed'

export type TicketPriority =
  | 'low'
  | 'medium'
  | 'high'
  | 'urgent'

export interface CustomerOption {
  id: number
  name: string
  email: string
}

export interface OrderOption {
  id: number
  customer_id: number
  order_number: string
  total_amount?: string
  currency?: string
}

export interface Staff {
  id: number
  name: string
}

export interface TicketMessage {
  id: number
  body: string
  is_internal: boolean
  author?: Staff
  created_at: string
}

export interface Ticket {
  id: number
  subject: string
  description: string

  status: TicketStatus
  priority: TicketPriority

  customer_id: number
  order_id: number | null
  created_by: number
  assigned_to: number | null

  customer?: CustomerOption
  order?: OrderOption | null
  assignee?: Staff | null
  messages?: TicketMessage[]

  resolved_at: string | null
  created_at: string
}

export interface TicketFilters {
  page?: number
  status?: TicketStatus
  priority?: TicketPriority
  search?: string
}

export interface CreateTicketPayload {
  customer_id: number
  order_id: number | null
  subject: string
  description: string
  priority: TicketPriority
}

export interface ItemResponse<T> {
  data: T
}

export interface PaginatedResponse<T> {
  data: T[]
  meta: {
    current_page: number
    last_page: number
    total: number
  }
}

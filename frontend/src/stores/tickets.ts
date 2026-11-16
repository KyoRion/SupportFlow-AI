import { ref } from 'vue'
import { defineStore } from 'pinia'
import { api } from '@/lib/api'

import type {
  Ticket,
  TicketFilters,
  CreateTicketPayload,
  ItemResponse,
  PaginatedResponse,
  TicketStatus,
  TicketPriority,
} from '@/types/ticket'

export const useTicketStore = defineStore('tickets', () => {
  const items = ref<Ticket[]>([])
  const current = ref<Ticket | null>(null)

  const listLoading = ref(false)
  const detailLoading = ref(false)

  const pagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
  })

  async function fetchTickets(
    filters: TicketFilters = {},
  ): Promise<void> {
    listLoading.value = true

    try {
      const { data } = await api.get<PaginatedResponse<Ticket>>(
        '/api/tickets',
        { params: filters },
      )

      items.value = data.data
      pagination.value = data.meta
    } finally {
      listLoading.value = false
    }
  }

  async function fetchTicket(id: number): Promise<void> {
    detailLoading.value = true
    current.value = null

    try {
      const { data } = await api.get<ItemResponse<Ticket>>(
        `/api/tickets/${id}`,
      )

      current.value = data.data
    } finally {
      detailLoading.value = false
    }
  }

  async function createTicket(
    payload: CreateTicketPayload,
  ): Promise<Ticket> {
    const { data } = await api.post<ItemResponse<Ticket>>(
      '/api/tickets',
      payload,
    )

    return data.data
  }

  async function updateTicket(
    id: number,
    payload: {
      status?: TicketStatus
      priority?: TicketPriority
      assigned_to?: number | null
    },
  ): Promise<void> {
    await api.patch(`/api/tickets/${id}`, payload)
    await fetchTicket(id)
  }

  async function addMessage(
    id: number,
    body: string,
    isInternal: boolean,
  ): Promise<void> {
    await api.post(`/api/tickets/${id}/messages`, {
      body,
      is_internal: isInternal,
    })

    await fetchTicket(id)
  }

  return {
    items,
    current,
    listLoading,
    detailLoading,
    pagination,
    fetchTickets,
    fetchTicket,
    createTicket,
    updateTicket,
    addMessage,
  }
})

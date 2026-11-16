<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useTicketStore } from '@/stores/tickets'
import { getErrorMessage } from '@/lib/api'
import { statusLabels, priorityLabels } from '@/domain/ticket-status'

import type {
  TicketStatus,
  TicketPriority,
} from '@/types/ticket'

const store = useTicketStore()

const page = ref(1)
const error = ref<string | null>(null)

const filters = reactive({
  search: '',
  status: '' as TicketStatus | '',
  priority: '' as TicketPriority | '',
})

async function load() {
  error.value = null

  try {
    await store.fetchTickets({
      page: page.value,
      search: filters.search || undefined,
      status: filters.status || undefined,
      priority: filters.priority || undefined,
    })
  } catch (e) {
    error.value = getErrorMessage(e)
  }
}

function applyFilters() {
  page.value = 1
  void load()
}

function changePage(next: number) {
  page.value = next
  void load()
}

function formatDate(value: string): string {
  return new Date(value).toLocaleString('vi-VN')
}

onMounted(() => {
  void load()
})
</script>

<template>
  <main class="container">
    <div class="page-heading">
      <div>
        <h1>Support Tickets</h1>
        <p>Manage customer requests and support activities.</p>
      </div>

      <RouterLink to="/tickets/create" class="button">
        + New Ticket
      </RouterLink>
    </div>

    <section class="panel">
      <form class="filters" @submit.prevent="applyFilters">
        <input
          v-model.trim="filters.search"
          placeholder="Search ticket subject..."
          aria-label="Search tickets"
        />

        <select v-model="filters.status">
          <option value="">All statuses</option>
          <option
            v-for="(label, status) in statusLabels"
            :key="status"
            :value="status"
          >
            {{ label }}
          </option>
        </select>

        <select v-model="filters.priority">
          <option value="">All priorities</option>
          <option
            v-for="(label, priority) in priorityLabels"
            :key="priority"
            :value="priority"
          >
            {{ label }}
          </option>
        </select>

        <button type="submit">Search</button>
      </form>

      <p v-if="error" class="error" role="alert">
        {{ error }}
      </p>

      <p v-if="store.listLoading">Loading tickets...</p>

      <div v-else class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Subject</th>
              <th>Customer</th>
              <th>Status</th>
              <th>Priority</th>
              <th>Created</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="ticket in store.items"
              :key="ticket.id"
            >
              <td>#{{ ticket.id }}</td>

              <td>
                <RouterLink
                  :to="{
                    name: 'ticket-detail',
                    params: { id: ticket.id },
                  }"
                >
                  {{ ticket.subject }}
                </RouterLink>
              </td>

              <td>{{ ticket.customer?.name ?? 'Unknown' }}</td>

              <td>
                <span class="tag">{{ statusLabels[ticket.status] }}</span>
              </td>

              <td>{{ priorityLabels[ticket.priority] }}</td>

              <td>{{ formatDate(ticket.created_at) }}</td>
            </tr>

            <tr v-if="!store.items.length">
              <td colspan="6">No tickets found.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="pagination">
        <span>
          Page {{ store.pagination.current_page }}
          of {{ store.pagination.last_page }}
          · {{ store.pagination.total }} tickets
        </span>

        <div class="actions">
          <button
            type="button"
            :disabled="page <= 1 || store.listLoading"
            @click="changePage(page - 1)"
          >
            Previous
          </button>

          <button
            type="button"
            :disabled="
              page >= store.pagination.last_page ||
              store.listLoading
            "
            @click="changePage(page + 1)"
          >
            Next
          </button>
        </div>
      </div>
    </section>
  </main>
</template>

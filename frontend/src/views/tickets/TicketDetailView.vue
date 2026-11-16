<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'

import { useAuthStore } from '@/stores/auth'
import { useTicketStore } from '@/stores/tickets'
import { getErrorMessage } from '@/lib/api'

import {
  allowedTransitions,
  statusLabels,
  priorityLabels,
} from '@/domain/ticket-status'

import type { TicketStatus } from '@/types/ticket'

const route = useRoute()
const auth = useAuthStore()
const store = useTicketStore()

const error = ref<string | null>(null)
const saving = ref(false)

const selectedStatus = ref<TicketStatus | ''>('')
const messageBody = ref('')
const isInternal = ref(false)

const ticketId = computed(() => Number(route.params.id))
const ticket = computed(() => store.current)

const canEdit = computed(() => {
  if (!ticket.value || !auth.user) return false

  return (
    ticket.value.created_by === auth.user.id ||
    ticket.value.assigned_to === auth.user.id
  )
})

const nextStatuses = computed(() =>
  ticket.value
    ? allowedTransitions[ticket.value.status]
    : [],
)

async function load() {
  error.value = null

  try {
    await store.fetchTicket(ticketId.value)
  } catch (e) {
    error.value = getErrorMessage(e)
  }
}

watch(ticketId, () => {
  void load()
}, { immediate: true })

async function updateStatus() {
  if (!selectedStatus.value) return

  saving.value = true
  error.value = null

  try {
    await store.updateTicket(ticketId.value, {
      status: selectedStatus.value,
    })

    selectedStatus.value = ''
  } catch (e) {
    error.value = getErrorMessage(e)
  } finally {
    saving.value = false
  }
}

async function submitMessage() {
  if (!messageBody.value.trim()) return

  saving.value = true
  error.value = null

  try {
    await store.addMessage(
      ticketId.value,
      messageBody.value.trim(),
      isInternal.value,
    )

    messageBody.value = ''
    isInternal.value = false
  } catch (e) {
    error.value = getErrorMessage(e)
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <main class="container">
    <div class="page-heading">
      <div>
        <RouterLink to="/tickets">All Tickets</RouterLink>
        <h1>Ticket #{{ ticketId }}</h1>
      </div>
    </div>

    <p v-if="error" class="error" role="alert">
      {{ error }}
    </p>

    <p v-if="store.detailLoading">Loading ticket...</p>

    <div v-else-if="ticket" class="detail-grid">
      <section class="panel">
        <h2>{{ ticket.subject }}</h2>
        <p>{{ ticket.description }}</p>

        <div class="ticket-meta">
          <span class="tag">
            {{ statusLabels[ticket.status] }}
          </span>

          <span>
            Priority:
            {{ priorityLabels[ticket.priority] }}
          </span>
        </div>

        <hr />

        <h2>Conversation</h2>

        <p v-if="!ticket.messages?.length">
          No messages yet.
        </p>

        <div
          v-for="message in ticket.messages"
          :key="message.id"
          class="message"
        >
          <div class="message-heading">
            <strong>
              {{ message.author?.name ?? 'Unknown' }}
            </strong>

            <span
              v-if="message.is_internal"
              class="tag"
            >
              Internal note
            </span>
          </div>

          <p>{{ message.body }}</p>

          <small>
            {{ new Date(message.created_at).toLocaleString('vi-VN') }}
          </small>
        </div>

        <form
          v-if="canEdit && ticket.status !== 'closed'"
          class="message-form"
          @submit.prevent="submitMessage"
        >
          <label for="message-body">New Message</label>

          <textarea
            id="message-body"
            v-model="messageBody"
            rows="4"
            placeholder="Write a response..."
            required
          />

          <label class="checkbox-line">
            <input
              v-model="isInternal"
              type="checkbox"
            />
            Internal note
          </label>

          <button
            type="submit"
            :disabled="saving || !messageBody.trim()"
          >
            Add Message
          </button>
        </form>
      </section>

      <aside class="panel">
        <h2>Ticket Information</h2>

        <div class="field">
          <strong>Customer</strong>
          <span>{{ ticket.customer?.name }}</span>
          <small>{{ ticket.customer?.email }}</small>
        </div>

        <div class="field">
          <strong>Related Order</strong>
          <span>
            {{ ticket.order?.order_number ?? 'None' }}
          </span>
        </div>

        <div class="field">
          <strong>Assigned To</strong>
          <span>
            {{ ticket.assignee?.name ?? 'Unassigned' }}
          </span>
        </div>

        <div
          v-if="canEdit && nextStatuses.length"
          class="field"
        >
          <label for="next-status">
            Change Status
          </label>

          <select
            id="next-status"
            v-model="selectedStatus"
          >
            <option value="" disabled>
              Select next status
            </option>

            <option
              v-for="status in nextStatuses"
              :key="status"
              :value="status"
            >
              {{ statusLabels[status] }}
            </option>
          </select>

          <button
            type="button"
            :disabled="saving || !selectedStatus"
            @click="updateStatus"
          >
            Update Status
          </button>
        </div>
      </aside>
    </div>
  </main>
</template>

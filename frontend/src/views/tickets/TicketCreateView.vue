<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'

import { api, getErrorMessage } from '@/lib/api'
import { useTicketStore } from '@/stores/tickets'

import type {
  CustomerOption,
  OrderOption,
  CreateTicketPayload,
} from '@/types/ticket'

const router = useRouter()
const store = useTicketStore()

const customers = ref<CustomerOption[]>([])
const orders = ref<OrderOption[]>([])

const saving = ref(false)
const error = ref<string | null>(null)

const form = reactive<CreateTicketPayload>({
  customer_id: 0,
  order_id: null,
  subject: '',
  description: '',
  priority: 'medium',
})

onMounted(async () => {
  try {
    const { data } = await api.get<CustomerOption[]>(
      '/api/lookups/customers',
    )

    customers.value = data
  } catch (e) {
    error.value = getErrorMessage(e)
  }
})

watch(
  () => form.customer_id,
  async (customerId) => {
    form.order_id = null
    orders.value = []

    if (!customerId) return

    try {
      const { data } = await api.get<OrderOption[]>(
        '/api/lookups/orders',
        { params: { customer_id: customerId } },
      )

      // Prevent an older request from replacing newer data.
      if (form.customer_id === customerId) {
        orders.value = data
      }
    } catch (e) {
      error.value = getErrorMessage(e)
    }
  },
)

async function submit() {
  saving.value = true
  error.value = null

  try {
    const ticket = await store.createTicket({ ...form })

    await router.push({
      name: 'ticket-detail',
      params: { id: ticket.id },
    })
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
        <h1>Create Support Ticket</h1>
        <p>Register a new customer support request.</p>
      </div>

      <RouterLink to="/tickets">Back to Tickets</RouterLink>
    </div>

    <form class="panel form-panel" @submit.prevent="submit">
      <p v-if="error" class="error" role="alert">
        {{ error }}
      </p>

      <div class="field">
        <label for="customer">Customer</label>

        <select
          id="customer"
          v-model.number="form.customer_id"
          required
        >
          <option :value="0" disabled>
            Select a customer
          </option>

          <option
            v-for="customer in customers"
            :key="customer.id"
            :value="customer.id"
          >
            {{ customer.name }} — {{ customer.email }}
          </option>
        </select>
      </div>

      <div class="field">
        <label for="order">Related Order</label>

        <select
          id="order"
          v-model="form.order_id"
          :disabled="!form.customer_id"
        >
          <option :value="null">No related order</option>

          <option
            v-for="order in orders"
            :key="order.id"
            :value="order.id"
          >
            {{ order.order_number }}
          </option>
        </select>
      </div>

      <div class="field">
        <label for="subject">Subject</label>

        <input
          id="subject"
          v-model.trim="form.subject"
          maxlength="200"
          placeholder="Describe the issue"
          required
        />
      </div>

      <div class="field">
        <label for="priority">Priority</label>

        <select id="priority" v-model="form.priority">
          <option value="low">Low</option>
          <option value="medium">Medium</option>
          <option value="high">High</option>
          <option value="urgent">Urgent</option>
        </select>
      </div>

      <div class="field">
        <label for="description">Description</label>

        <textarea
          id="description"
          v-model.trim="form.description"
          rows="6"
          maxlength="10000"
          placeholder="Explain the customer issue..."
          required
        />
      </div>

      <div class="actions">
        <RouterLink to="/tickets">Cancel</RouterLink>

        <button
          type="submit"
          :disabled="saving || !form.customer_id"
        >
          {{ saving ? 'Creating...' : 'Create Ticket' }}
        </button>
      </div>
    </form>
  </main>
</template>

import {
  createRouter,
  createWebHistory,
} from 'vue-router'

import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),

  routes: [
    {
      path: '/',
      redirect: '/tickets',
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
    },
    {
      path: '/tickets',
      name: 'tickets',
      component: () =>
        import('@/views/tickets/TicketListView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/tickets/create',
      name: 'ticket-create',
      component: () =>
        import('@/views/tickets/TicketCreateView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/tickets/:id',
      name: 'ticket-detail',
      component: () =>
        import('@/views/tickets/TicketDetailView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/ai-chat',
      name: 'ai-chat',
      component: () => import('@/views/AiChatView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/knowledge',
      name: 'knowledge',
      component: () => import('@/views/KnowledgeView.vue'),
      meta: { requiresAuth: true },
    }
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  await auth.initialize()

  if (to.meta.requiresAuth && !auth.user) {
    return { name: 'login' }
  }

  if (to.name === 'login' && auth.user) {
    return { name: 'tickets' }
  }
})

export default router

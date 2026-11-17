<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch, } from 'vue'
import { api, getErrorMessage } from '@/lib/api'

// --------------------------------------------------
// Types
// --------------------------------------------------

type DocumentStatus =
  | 'pending'
  | 'processing'
  | 'ready'
  | 'failed'

interface KnowledgeDocument {
  id: number
  title: string
  status: DocumentStatus
  chunk_count: number
  created_at: string

  // Extended metadata
  source_type?: 'text' | 'file'
  original_filename?: string | null
  error_message?: string | null
  indexed_at?: string | null
}

interface PaginatedDocuments {
  data: KnowledgeDocument[]
  current_page: number
  last_page: number
  total: number
}

interface CreatedDocument {
  id: number
  status: DocumentStatus
}

interface KnowledgeSearchResult {
  source_id: string
  document_id: number
  chunk_id: number
  title: string
  content: string
}

interface KnowledgeSearchResponse {
  data: KnowledgeSearchResult[]
  count: number
}

// --------------------------------------------------
// Document state
// --------------------------------------------------

const documents = ref<KnowledgeDocument[]>([])
const documentsLoading = ref(false)
const documentsError = ref<string | null>(null)

const currentPage = ref(1)
const lastPage = ref(1)
const totalDocuments = ref(0)

// --------------------------------------------------
// Create document form
// --------------------------------------------------

const title = ref('')
const content = ref('')
const creating = ref(false)

const createError = ref<string | null>(null)
const createSuccess = ref<string | null>(null)

const canCreate = computed(() => {
  const textLength = content.value.trim().length

  return (
    !creating.value &&
    title.value.trim().length > 0 &&
    title.value.trim().length <= 180 &&
    textLength >= 50 &&
    textLength <= 40000
  )
})

// --------------------------------------------------
// Semantic search state
// --------------------------------------------------

const searchQuery = ref('')
const searchLimit = ref(5)

const searchResults = ref<KnowledgeSearchResult[]>([])
const searching = ref(false)
const searchError = ref<string | null>(null)
const hasSearched = ref(false)

// --------------------------------------------------
// API: List documents
// --------------------------------------------------

async function loadDocuments(page = 1): Promise<void> {
  documentsLoading.value = true
  documentsError.value = null

  try {
    const { data } = await api.get<PaginatedDocuments>(
      '/api/knowledge/documents',
      {
        params: { page },
      },
    )

    documents.value = data.data
    currentPage.value = data.current_page
    lastPage.value = data.last_page
    totalDocuments.value = data.total
  } catch (error) {
    documentsError.value = getErrorMessage(error)
  } finally {
    documentsLoading.value = false
  }
}

// --------------------------------------------------
// API: Create document
// --------------------------------------------------

async function createDocument(): Promise<void> {
  if (!canCreate.value) return

  creating.value = true
  createError.value = null
  createSuccess.value = null

  try {
    const { data } = await api.post<CreatedDocument>(
      '/api/knowledge/documents',
      {
        title: title.value.trim(),
        content: content.value.trim(),
      },
    )

    createSuccess.value =
      `Document #${data.id} created successfully. ` +
      'Indexing has been queued.'

    title.value = ''
    content.value = ''
  } catch (error) {
    createError.value = getErrorMessage(error)
    return
  } finally {
    creating.value = false
  }

  await loadDocuments(1)
}

// --------------------------------------------------
// API: Semantic search
// --------------------------------------------------

async function searchKnowledge(): Promise<void> {
  const query = searchQuery.value.trim()

  if (query.length < 3 || searching.value) return

  searching.value = true
  searchError.value = null
  hasSearched.value = false
  searchResults.value = []

  try {
    const { data } =
      await api.get<KnowledgeSearchResponse>(
        '/api/knowledge/search',
        {
          params: {
            query,
            limit: searchLimit.value,
          },
        },
      )

    searchResults.value = data.data
    hasSearched.value = true
  } catch (error) {
    searchError.value = getErrorMessage(error)
  } finally {
    searching.value = false
  }
}

// --------------------------------------------------
// UI helpers
// --------------------------------------------------

function statusLabel(status: DocumentStatus): string {
  const labels: Record<DocumentStatus, string> = {
    pending: 'Pending',
    processing: 'Processing',
    ready: 'Ready',
    failed: 'Failed',
  }

  return labels[status]
}

function formatDate(value: string): string {
  const date = new Date(value)

  if (Number.isNaN(date.getTime())) return value

  return new Intl.DateTimeFormat('vi-VN', {
    dateStyle: 'short',
    timeStyle: 'short',
  }).format(date)
}

const hasIndexingDocuments = computed(() =>
  documents.value.some(
    (document) =>
      document.status === 'pending' ||
      document.status === 'processing',
  ),
)

const POLLING_INTERVAL_MS = 5000

let pollingTimer: ReturnType<typeof setInterval> | null = null

function stopPolling(): void {
  if (pollingTimer !== null) {
    clearInterval(pollingTimer)
    pollingTimer = null
  }
}

function startPolling(): void {
  if (pollingTimer !== null) return

  pollingTimer = setInterval(() => {
    // Do not poll when the tab is hidden.
    if (document.visibilityState !== 'visible') return

    // Avoid overlapping requests.
    if (documentsLoading.value) return

    void loadDocuments(currentPage.value)
  }, POLLING_INTERVAL_MS)
}

watch(
  hasIndexingDocuments,
  (hasPendingDocuments) => {
    if (hasPendingDocuments) {
      startPolling()
    } else {
      stopPolling()
    }
  },
  { immediate: true },
)

onUnmounted(() => {
  stopPolling()
})

onMounted(() => {
  void loadDocuments()
})
</script>

<template>
  <main class="container knowledge-page">
    <!-- Page heading -->
    <header class="page-heading">
      <div>
        <h1>Knowledge Base</h1>
        <p>
          Manage support knowledge and explore
          semantic search results.
        </p>
      </div>
    </header>

    <div class="knowledge-layout">
      <!-- Left column -->
      <div class="main-column">
        <!-- Create document -->
        <section class="panel kb-panel">
          <div class="section-heading">
            <div>
              <h2>Add Knowledge Document</h2>
              <p>
                Add plain text or Markdown content
                to your knowledge base.
              </p>
            </div>
          </div>

          <form
            class="document-form"
            @submit.prevent="createDocument"
          >
            <div class="form-group">
              <label for="document-title">
                Document title
              </label>

              <input
                id="document-title"
                v-model="title"
                type="text"
                maxlength="180"
                placeholder="e.g. Delivery Policy"
                :disabled="creating"
                required
              />
            </div>

            <div class="form-group">
              <label for="document-content">
                Document content
              </label>

              <textarea
                id="document-content"
                v-model="content"
                rows="12"
                maxlength="40000"
                placeholder="Paste your support policy or procedure here..."
                :disabled="creating"
                required
              />

              <div class="field-footer">
                <small>
                  Minimum 50 characters
                </small>

                <small>
                  {{ content.length.toLocaleString() }}
                  / 40,000
                </small>
              </div>
            </div>

            <div
              v-if="createError"
              class="alert alert-error"
              role="alert"
            >
              {{ createError }}
            </div>

            <div
              v-if="createSuccess"
              class="alert alert-success"
              role="status"
            >
              {{ createSuccess }}
            </div>

            <div class="form-actions">
              <button
                type="submit"
                class="btn btn-primary"
                :disabled="!canCreate"
              >
                {{
                  creating
                    ? 'Creating...'
                    : 'Create & Index Document'
                }}
              </button>
            </div>
          </form>
        </section>

        <!-- Semantic Search -->
        <section class="panel kb-panel">
          <div class="section-heading">
            <div>
              <h2>Semantic Search Playground</h2>
              <p>
                Search your indexed documents
                using natural language.
              </p>
            </div>
          </div>

          <form
            class="search-form"
            @submit.prevent="searchKnowledge"
          >
            <div class="form-group">
              <label for="search-query">
                Search query
              </label>

              <textarea
                id="search-query"
                v-model="searchQuery"
                rows="3"
                maxlength="1000"
                placeholder="What should an agent do if delivery is delayed?"
                :disabled="searching"
              />
            </div>

            <div class="search-actions">
              <div class="limit-field">
                <label for="search-limit">
                  Top K
                </label>

                <select
                  id="search-limit"
                  v-model.number="searchLimit"
                  :disabled="searching"
                >
                  <option :value="3">3</option>
                  <option :value="5">5</option>
                  <option :value="10">10</option>
                </select>
              </div>

              <button
                type="submit"
                class="btn btn-primary"
                :disabled="
                  searching ||
                  searchQuery.trim().length < 3
                "
              >
                {{
                  searching
                    ? 'Searching...'
                    : 'Search Knowledge'
                }}
              </button>
            </div>
          </form>

          <div
            v-if="searchError"
            class="alert alert-error"
            role="alert"
          >
            {{ searchError }}
          </div>

          <div
            v-if="searching"
            class="empty-state"
          >
            Generating query embedding and
            searching knowledge chunks...
          </div>

          <div
            v-else-if="
              hasSearched &&
              searchResults.length === 0
            "
            class="empty-state"
          >
            No relevant knowledge chunks found.
          </div>

          <div
            v-else-if="searchResults.length"
            class="search-results"
          >
            <div class="results-heading">
              <h3>Retrieved Sources</h3>
              <span>
                {{ searchResults.length }} results
              </span>
            </div>

            <article
              v-for="(result, index) in searchResults"
              :key="result.chunk_id"
              class="result-card"
            >
              <div class="result-header">
                <span class="result-number">
                  #{{ index + 1 }}
                </span>

                <strong>
                  {{ result.title }}
                </strong>
              </div>

              <p class="result-content">
                {{ result.content }}
              </p>

              <div class="result-footer">
                <code>{{ result.source_id }}</code>

                <small>
                  Chunk ID: {{ result.chunk_id }}
                </small>
              </div>
            </article>
          </div>
        </section>
      </div>

      <!-- Right column -->
      <aside class="sidebar-column">
        <!-- Documents -->
        <section class="panel kb-panel">
          <div class="section-heading">
            <div>
              <h2>Documents</h2>
              <p>
                {{ totalDocuments }} documents
              </p>
            </div>

            <button
              type="button"
              class="btn btn-secondary btn-small"
              :disabled="documentsLoading"
              @click="loadDocuments(currentPage)"
            >
              {{ documentsLoading ? 'Loading...' : 'Refresh' }}
            </button>
          </div>

          <div
            v-if="documentsError"
            class="alert alert-error"
            role="alert"
          >
            {{ documentsError }}
          </div>

          <div
            v-if="hasIndexingDocuments"
            class="indexing-notice"
            role="status"
          >
            Indexing in progress. Auto-refreshing every 5 seconds.
          </div>

          <div
            v-if="documentsLoading && !documents.length"
            class="empty-state"
          >
            Loading documents...
          </div>

          <div
            v-else-if="!documents.length && !documentsError"
            class="empty-state"
          >
            No documents yet. Add your first
            knowledge document.
          </div>

          <div
            v-else
            class="document-list"
          >
            <article
              v-for="document in documents"
              :key="document.id"
              class="document-item"
            >
              <div class="document-item-header">
                <strong>
                  {{ document.title }}
                </strong>

                <span
                  class="status-badge"
                  :class="`status-${document.status}`"
                >
                  {{ statusLabel(document.status) }}
                </span>
              </div>

              <div class="document-meta">
                <span>
                  ID: {{ document.id }}
                </span>

                <span>
                  {{ document.chunk_count }} chunks
                </span>
              </div>

              <small class="document-date">
                {{ formatDate(document.created_at) }}
              </small>

              <div
                v-if="document.source_type === 'file'"
                class="document-source"
              >
                File: {{ document.original_filename }}
              </div>

              <div
                v-if="document.status === 'ready'"
                class="document-ready"
              >
                Indexed successfully
                <span v-if="document.indexed_at">
                  at {{ formatDate(document.indexed_at) }}
                </span>
              </div>

              <div
                v-if="document.status === 'failed'"
                class="document-error"
                role="status"
              >
                {{
                  document.error_message ||
                  'Indexing failed. Check Laravel Queue logs.'
                }}
              </div>
            </article>
          </div>

          <!-- Pagination -->
          <div
            v-if="lastPage > 1"
            class="pagination"
          >
            <button
              type="button"
              class="btn btn-secondary btn-small"
              :disabled="
                documentsLoading ||
                currentPage <= 1
              "
              @click="loadDocuments(currentPage - 1)"
            >
              Previous
            </button>

            <span>
              {{ currentPage }} / {{ lastPage }}
            </span>

            <button
              type="button"
              class="btn btn-secondary btn-small"
              :disabled="
                documentsLoading ||
                currentPage >= lastPage
              "
              @click="loadDocuments(currentPage + 1)"
            >
              Next
            </button>
          </div>
        </section>

        <!-- Information -->
        <section class="panel kb-panel info-panel">
          <h3>How indexing works</h3>

          <p>
            1. Document is saved to PostgreSQL.
          </p>

          <p>
            2. Laravel Queue processes the document.
          </p>

          <p>
            3. Content is divided into text chunks.
          </p>

          <p>
            4. Embeddings are generated.
          </p>

          <p>
            5. Vectors are stored using pgvector.
          </p>

          <p>
            6. Search retrieves relevant chunks.
          </p>
        </section>
      </aside>
    </div>
  </main>
</template>

<style scoped>
.knowledge-page {
  padding-top: 28px;
  padding-bottom: 48px;
}

.page-heading {
  margin-bottom: 24px;
}

.page-heading h1 {
  margin: 0 0 8px;
  font-size: 28px;
  font-weight: 700;
}

.page-heading p {
  margin: 0;
  color: #64748b;
}

.knowledge-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 340px;
  align-items: start;
  gap: 24px;
}

.main-column,
.sidebar-column {
  display: flex;
  flex-direction: column;
  gap: 24px;
  min-width: 0;
}

.kb-panel {
  padding: 24px;
}

.section-heading {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 24px;
}

.section-heading h2 {
  margin: 0 0 8px;
  font-size: 18px;
}

.section-heading p {
  margin: 0;
  font-size: 13px;
  color: #64748b;
  line-height: 1.6;
}

.document-form,
.search-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group label,
.limit-field label {
  font-size: 13px;
  font-weight: 600;
}

.form-group input,
.form-group textarea,
.limit-field select {
  width: 100%;
  box-sizing: border-box;
  padding: 12px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  background: #fff;
  color: #0f172a;
  font: inherit;
  font-size: 14px;
}

.form-group textarea {
  resize: vertical;
  line-height: 1.6;
}

.form-group input:focus,
.form-group textarea:focus,
.limit-field select:focus {
  outline: 2px solid #93c5fd;
  border-color: #2563eb;
}

.field-footer,
.form-actions,
.search-actions,
.result-footer,
.document-meta,
.pagination {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
}

.field-footer {
  color: #64748b;
}

.btn {
  padding: 10px 16px;
  border: 1px solid transparent;
  border-radius: 8px;
  font: inherit;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

.btn-primary {
  color: #fff;
  background: #2563eb;
}

.btn-primary:hover:not(:disabled) {
  background: #1d4ed8;
}

.btn-secondary {
  border-color: #cbd5e1;
  color: #334155;
  background: #fff;
}

.btn-small {
  padding: 8px 12px;
  white-space: nowrap;
}

.btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.form-actions {
  justify-content: flex-end;
}

.limit-field {
  display: flex;
  align-items: center;
  gap: 8px;
}

.limit-field select {
  width: auto;
  min-width: 75px;
}

.alert {
  margin-top: 16px;
  padding: 12px;
  border-radius: 8px;
  font-size: 13px;
  line-height: 1.6;
}

.alert-error {
  background: #fef2f2;
  color: #b91c1c;
}

.alert-success {
  background: #f0fdf4;
  color: #166534;
}

.search-results {
  margin-top: 28px;
}

.results-heading {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.results-heading h3 {
  margin: 0;
  font-size: 15px;
}

.results-heading span {
  font-size: 12px;
  color: #64748b;
}

.result-card {
  margin-top: 12px;
  padding: 16px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #f8fafc;
}

.result-header {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 13px;
}

.result-number {
  color: #2563eb;
  font-weight: 700;
}

.result-content {
  margin: 12px 0;
  font-size: 13px;
  line-height: 1.75;
  white-space: pre-wrap;
  overflow-wrap: anywhere;
}

.result-footer {
  flex-wrap: wrap;
  color: #64748b;
  font-size: 12px;
}

.result-footer code {
  padding: 4px 8px;
  border-radius: 6px;
  background: #dbeafe;
  color: #1d4ed8;
}

.document-list {
  display: flex;
  flex-direction: column;
}

.document-item {
  padding: 16px 0;
  border-bottom: 1px solid #e2e8f0;
}

.document-item-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 10px;
}

.document-item-header strong {
  min-width: 0;
  overflow-wrap: anywhere;
  font-size: 13px;
  line-height: 1.5;
}

.document-meta {
  justify-content: flex-start;
  margin-top: 12px;
  color: #64748b;
  font-size: 12px;
}

.document-date {
  display: block;
  margin-top: 8px;
  color: #94a3b8;
}

.status-badge {
  flex-shrink: 0;
  padding: 5px 8px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
}

.status-pending {
  background: #f1f5f9;
  color: #475569;
}

.status-processing {
  background: #fef3c7;
  color: #92400e;
}

.status-ready {
  background: #dcfce7;
  color: #166534;
}

.status-failed {
  background: #fee2e2;
  color: #b91c1c;
}

.indexing-notice {
  padding: 12px;
  border-radius: 8px;
  background: #eff6ff;
  color: #1d4ed8;
  font-size: 12px;
  line-height: 1.6;
}

.pagination {
  margin-top: 20px;
  font-size: 12px;
}

.empty-state {
  padding: 32px 12px;
  color: #64748b;
  text-align: center;
  font-size: 13px;
  line-height: 1.7;
}

.info-panel h3 {
  margin: 0 0 16px;
  font-size: 15px;
}

.info-panel p {
  margin: 0 0 12px;
  font-size: 12px;
  color: #64748b;
  line-height: 1.7;
}


.loading-dot {
  display: inline-block;
  width: 8px;
  height: 8px;
  margin-right: 8px;
  background: #2563eb;
  border-radius: 50%;
  animation: kb-pulse 1.2s infinite;
}

@keyframes kb-pulse {
  0%, 100% { opacity: 0.35; }
  50% { opacity: 1; }
}

.document-ready {
  margin-top: 10px;
  color: #15803d;
  font-size: 12px;
}

.document-error {
  margin-top: 10px;
  padding: 10px;
  background: #fef2f2;
  color: #b91c1c;
  border-radius: 6px;
  font-size: 12px;
  line-height: 1.5;
}

.document-source {
  margin-top: 10px;
  color: #64748b;
  font-size: 12px;
  overflow-wrap: anywhere;
}

@media (max-width: 900px) {
  .knowledge-layout {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 600px) {
  .kb-panel {
    padding: 16px;
  }

  .section-heading,
  .search-actions {
    flex-wrap: wrap;
  }

  .search-actions {
    justify-content: flex-end;
  }

  .page-heading h1 {
    font-size: 24px;
  }
}
</style>

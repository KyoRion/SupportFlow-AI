# SupportFlow AI

**AI-Powered Customer Support System built with Laravel 12, Vue 3, Laravel AI SDK, and Model Context Protocol (MCP).**

SupportFlow AI is an open-source-oriented technical portfolio project demonstrating how AI Agents, Tool Calling, Retrieval-Augmented Generation (RAG), and MCP can be integrated into a modern Laravel application.

The project combines customer support ticket management with AI-assisted workflows, allowing support agents to investigate customer issues, retrieve relevant knowledge, and generate context-aware responses using real application data.

The primary focus is on **backend architecture, AI integration, reusable business services, authorization, and observability**.

## Features

### Customer Support Management

- Session-based authentication using Laravel Sanctum.
- Ticket creation, assignment, and status management.
- Ticket state machine with validated status transitions.
- Customer conversations and internal notes.
- Ticket-level authorization using Laravel Policies.
- RESTful APIs with validation and API Resources.
- Vue 3 dashboard with TypeScript and Pinia.

### AI Support Agent

- AI Agent powered by Laravel AI SDK.
- Multi-turn conversations with persistent history.
- Tool calling for retrieving authorized ticket information.
- Real-time AI response streaming using Server-Sent Events (SSE).
- Tool execution timeline and execution status tracking.
- Context-aware support recommendations.

### AI Observability

- Persistent AI execution history.
- Tool invocation monitoring.
- Response latency and Time to First Token (TTFT).
- Input and output token usage.
- Execution status and error tracking.
- PostgreSQL-backed telemetry.

### RAG & Knowledge Base

- Plain-text knowledge document management.
- Asynchronous text chunking and embedding generation.
- Vector storage with PostgreSQL and pgvector.
- Semantic similarity search.
- RAG integration with Laravel AI Agents.
- Source identifiers for retrieved knowledge.
- User-scoped retrieval and access control.
- Knowledge management and semantic search interface.

**Note:** The Knowledge Base only supports manually entered plain-text content. File uploads, PDF parsing, and OCR are not implemented.

## Model Context Protocol (MCP)

SupportFlow AI includes a Laravel MCP Server that exposes authenticated, read-only capabilities to external AI clients.

### MCP Tools

| Tool | Description |
|---|---|
| `get-ticket-details` | Retrieve authorized ticket information. |
| `search-tickets` | Search tickets by status and priority. |
| `search-knowledge-base` | Retrieve relevant knowledge using semantic vector search. |

### MCP Resources

- `supportflow://policies/demo-support` — Example customer support policy.

### MCP Prompts

- `ticket-triage` — Structured prompt for investigating support tickets.

### MCP Security

- Laravel Sanctum Bearer Token authentication.
- Scoped access tokens (`mcp:read`).
- Ticket-level authorization.
- Server-side input validation.
- Read-only tools.
- Response data minimization.

## Tech Stack

| Layer | Technologies |
|---|---|
| Backend | PHP, Laravel 12 |
| AI Framework | Laravel AI SDK |
| MCP | Laravel MCP |
| Frontend | Vue 3, TypeScript, Pinia, Vue Router, Vite |
| Database | PostgreSQL 16, pgvector |
| Cache & Queue | Redis, Laravel Queues |
| Authentication | Laravel Sanctum |
| AI | AI Agents, Tool Calling, RAG, Embeddings |
| Streaming | Server-Sent Events (SSE) |
| Testing | PHPUnit, Vitest |
| Infrastructure | Docker Compose |

---

## Getting Started

### Prerequisites

Before running the project, make sure you have:

- PHP 8.2 or later.
- Composer.
- Node.js compatible with the project's Vite version and npm.
- Docker and Docker Compose.
- Git.
- An AI provider API key for live AI requests and embedding generation.

Windows users can run the backend and development tools using WSL2.

### 1. Clone the Repository

```bash
git clone <REPOSITORY_URL>
cd SupportFlow-AI
```

Replace `<REPOSITORY_URL>` with the public GitHub repository URL.

### 2. Start PostgreSQL and Redis

From the directory containing `compose.yml` or `docker-compose.yml`, run:

```bash
docker compose up -d
```

Verify that the containers are running:

```bash
docker compose ps
```

The PostgreSQL instance must support the `pgvector` extension.

For PostgreSQL 16, a compatible Docker image is:

```yaml
image: pgvector/pgvector:pg16
```

Enable the extension if it has not already been enabled:

```sql
CREATE EXTENSION IF NOT EXISTS vector;
```

Use the database credentials and exposed ports defined in your Docker Compose configuration.

### 3. Install Backend Dependencies

```bash
cd backend

composer install

cp .env.example .env

php artisan key:generate
```

Configure `.env` for your local environment.

Example:

```dotenv
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5433
DB_DATABASE=supportflow
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

REDIS_HOST=127.0.0.1
REDIS_PORT=6380

QUEUE_CONNECTION=redis
SESSION_DRIVER=database

SANCTUM_STATEFUL_DOMAINS=localhost:5174,127.0.0.1:5174,localhost:8000,127.0.0.1:8000

OPENAI_API_KEY=your_api_key
```

**Important:** Database and Redis ports above are example host-mapped ports. Adjust them to match your Docker configuration.

The AI provider credentials must match the provider configured in the Laravel AI SDK.

OpenAI API usage requires separate API billing and is not included with a ChatGPT subscription.

### 4. Run Database Migrations

```bash
php artisan migrate
```

Optionally populate the database with demo records using the project's registered seeders:

```bash
php artisan db:seed
```

This prepares the application database, including ticket management, AI conversation storage, execution telemetry, and Knowledge Base tables.

### 5. Install Frontend Dependencies

Open another terminal:

```bash
cd frontend

npm ci
```

If the repository does not contain a compatible `package-lock.json`, use `npm install` instead.

Start Vite:

```bash
npm run dev
```

The development frontend is configured to run at:

`http://localhost:5174`

Ensure the Vite proxy forwards API, authentication, and Sanctum requests to the Laravel backend running on port 8000.

### 6. Start the Application

Run the following processes in separate terminals.

**Terminal 1 — Laravel Backend**

```bash
cd backend

php artisan serve --host=127.0.0.1 --port=8000
```

**Terminal 2 — Laravel Queue Worker**

```bash
cd backend

php artisan queue:work redis \
  --queue=knowledge,default \
  --tries=3
```

The queue worker is required for asynchronous Knowledge Base indexing and embedding generation.

**Terminal 3 — Vue Frontend**

```bash
cd frontend

npm run dev
```

### 7. Open the Application

Once all services are running, open:

| Page | Local URL |
|---|---|
| Login | `http://localhost:5174/login` |
| Ticket Management | `http://localhost:5174/tickets` |
| AI Assistant | `http://localhost:5174/ai-chat` |
| Knowledge Base | `http://localhost:5174/knowledge` |

Authentication is required to access the application features.

### 8. Demo Account

The project includes a demo support-agent account created through database seeders:

```text
Email: agent@supportflow.test
```

For security reasons, the seeder may initialize this account with a random password.

To set your own password for local development, run:

```bash
php artisan tinker
```

Then execute:

```php
$user = App\Models\User::where(
    'email',
    'agent@supportflow.test'
)->firstOrFail();

$user->forceFill([
    'password' => Illuminate\Support\Facades\Hash::make(
        'replace-with-your-local-password'
    ),
])->save();
```

Use a local-only password. Never commit real credentials or API keys to the repository.

---

## Using the Application

### Ticket Management

1. Sign in using a support-agent account.
2. Browse the ticket list.
3. Create a ticket associated with a customer.
4. View ticket details and conversation history.
5. Add messages or internal notes.
6. Update ticket status according to allowed transitions.

### AI Assistant

1. Navigate to **AI Assistant**.
2. Start a new conversation.
3. Ask the Agent about an authorized support ticket.
4. Observe the streaming AI response.
5. Inspect executed tools, latency, and token usage.
6. Continue the conversation using persistent context.

Example:

```text
Retrieve ticket #1 and explain its current
status, priority, and recommended next steps.
```

The AI Agent can retrieve actual application data through authorized Laravel tools.

### Knowledge Base & RAG

1. Navigate to **Knowledge Base**.
2. Create a document using plain-text content.
3. Wait for the indexing process to complete.
4. Verify that the document status becomes `ready`.
5. Use Semantic Search to retrieve relevant chunks.
6. Ask the AI Assistant a question based on the indexed knowledge.

Example:

```text
According to our delivery policy,
what should an agent do when an order
has been delayed for seven days?
```

The RAG pipeline follows:

```text
Plain Text
    |
    v
Text Chunking
    |
    v
Embedding Generation
    |
    v
PostgreSQL + pgvector
    |
    v
Semantic Retrieval
    |
    v
AI Agent Response
```

---

## MCP Integration Setup

External AI clients can connect to SupportFlow AI through its MCP Server.

### MCP Endpoint

```text
http://127.0.0.1:8000/mcp/supportflow
```

Transport: **Streamable HTTP**

Authentication: **Laravel Sanctum Bearer Token**

### Generate a Local MCP Token

Run:

```bash
php artisan tinker
```

Then:

```php
$user = App\Models\User::where(
    'email',
    'agent@supportflow.test'
)->firstOrFail();

$token = $user->createToken(
    'supportflow-mcp-local',
    ['mcp:read'],
    now()->addDay()
)->plainTextToken;

echo $token;
```

Store the generated token securely.

### Connect an MCP Client

Configure your MCP-compatible client with:

```text
Server URL:
http://127.0.0.1:8000/mcp/supportflow

Transport:
Streamable HTTP

Header:
Authorization: Bearer YOUR_MCP_TOKEN
```

The server can be tested using MCP Inspector or a supported external client such as Cursor.

After connecting, the client should be able to discover the exposed tools, resources, and prompts.

**Security:** Keep MCP authentication enabled. Do not expose the local MCP endpoint publicly without appropriate authentication, transport security, and access controls.

---

## Testing

### Backend Tests

From the `backend` directory:

```bash
php artisan test
```

Run Laravel Pint:

```bash
./vendor/bin/pint --test
```

### Frontend Checks

From the `frontend` directory:

```bash
npm run type-check

npm run build
```

If the project's Vitest script is configured:

```bash
npm run test:unit
```

### Testing Without AI API Credits

The project uses Laravel AI SDK testing utilities to fake AI responses and embeddings during automated tests.

This allows application logic and integrations to be tested without making live AI provider requests.

---

## Project Architecture

The application separates AI integrations and transport protocols from reusable business services.

```text
                    Vue 3 Frontend
                          |
                Laravel REST API
                          |
             +------------+------------+
             |                         |
        Laravel AI SDK            Laravel MCP
             |                         |
          AI Tools                 MCP Tools
             |                         |
             +------------+------------+
                          |
                  Business Services
                          |
             +------------+------------+
             |                         |
         PostgreSQL                  Redis
          pgvector              Cache / Queues
```

### Key Architectural Principles

- Separation of concerns between controllers, services, and tools.
- Shared business logic across REST APIs, AI tools, and MCP tools.
- Server-side authentication and authorization.
- Asynchronous processing for embedding generation.
- Persistent AI conversations and execution telemetry.
- User-scoped retrieval for knowledge access.
- Read-only external AI capabilities.

---

## Explore the Source Code

The repository is organized into separate backend and frontend applications.

### Project Structure

```text
SupportFlow-AI/
├── backend/
│   ├── app/
│   │   ├── Ai/
│   │   │   ├── Agents/
│   │   │   └── Tools/
│   │   ├── Mcp/
│   │   │   ├── Servers/
│   │   │   ├── Tools/
│   │   │   ├── Resources/
│   │   │   └── Prompts/
│   │   ├── Services/
│   │   │   ├── Ai/
│   │   │   ├── Tickets/
│   │   │   └── Knowledge/
│   │   ├── Jobs/
│   │   ├── Models/
│   │   ├── Policies/
│   │   └── Http/Controllers/
│   ├── routes/
│   ├── database/migrations/
│   └── tests/
│
├── frontend/
│   └── src/
│       ├── views/
│       ├── stores/
│       ├── router/
│       ├── lib/
│       └── types/
│
└── docker-compose.yml
```

The structure above highlights the main architectural components. File locations may vary slightly depending on the repository version.

### Recommended Files to Explore

| Area | Source Path |
|---|---|
| AI Agent | `backend/app/Ai/Agents/SupportAgent.php` |
| AI Tools | `backend/app/Ai/Tools/` |
| MCP Server | `backend/app/Mcp/Servers/SupportFlowServer.php` |
| MCP Tools | `backend/app/Mcp/Tools/` |
| Ticket Business Logic | `backend/app/Services/Tickets/` |
| Knowledge Retrieval | `backend/app/Services/Knowledge/KnowledgeRetriever.php` |
| Embedding Generation | `backend/app/Services/Knowledge/KnowledgeEmbedder.php` |
| Knowledge Indexing | `backend/app/Jobs/IndexKnowledgeDocument.php` |
| API Routes | `backend/routes/api.php` |
| MCP Routes | `backend/routes/ai.php` |
| AI Chat UI | `frontend/src/views/AiChatView.vue` |
| Knowledge Base UI | `frontend/src/views/KnowledgeView.vue` |
| AI Chat State | `frontend/src/stores/aiChat.ts` |
| Automated Tests | `backend/tests/Feature/` |

### View the Source on GitHub

You can explore the repository directly through the GitHub **Code** tab without installing or running the application.

To inspect the project locally:

```bash
git clone <REPOSITORY_URL>

cd SupportFlow-AI

code .
```

When using Windows with WSL2, run these commands inside your WSL terminal with VS Code's WSL integration configured.

### Suggested Code Reading Order

For developers interested in the AI architecture:

1. Start with `SupportAgent.php` to understand agent instructions and tool registration.
2. Explore `app/Ai/Tools/` to understand Laravel AI tool execution.
3. Review `SupportFlowServer.php` and MCP tools.
4. Inspect `TicketReader` to understand shared business logic.
5. Review `KnowledgeEmbedder` and `KnowledgeRetriever` for RAG.
6. Explore the AI streaming controller and observability services.
7. Review the Vue AI Chat and Knowledge Base views.
8. Read the automated tests to understand expected behavior.

This sequence provides an overview of how Laravel AI SDK, MCP, business services, and PostgreSQL work together.

---

## Project Scope & Limitations

SupportFlow AI is a technical demonstration and portfolio project, not a production-ready customer support SaaS platform.

Current scope:

- Ticket management and support workflows.
- AI Agent integration with persistent conversations.
- Read-only AI and MCP tools.
- Real-time AI streaming and observability.
- Plain-text Knowledge Base and RAG.
- Semantic retrieval using PostgreSQL and pgvector.
- Authenticated MCP integration with external clients.

Not included:

- File-based knowledge uploads.
- PDF processing and OCR.
- Advanced multi-tenant knowledge sharing.
- Production-scale billing and usage quotas.
- Automated customer-facing actions without approval.

The project is intended to demonstrate architecture and integration patterns that can be extended into larger AI-enabled applications.

## License

Refer to the repository's `LICENSE` file, if provided, for usage and distribution terms.
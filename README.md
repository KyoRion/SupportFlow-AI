## Features

- Session-based authentication with Laravel Sanctum
- Customer support ticket management
- Ticket state machine and authorization
- Ticket conversations and internal notes
- Vue 3 dashboard with Pinia state management
- REST API powered by Laravel 12
- PostgreSQL and Redis infrastructure

## Tech Stack

- Backend: Laravel 12, PostgreSQL, Redis
- Frontend: Vue 3, TypeScript, Pinia, Vue Router
- Authentication: Laravel Sanctum
- Testing: PHPUnit, Vitest
- Infrastructure: Docker Compose

## Model Context Protocol Integration

SupportFlow AI includes a Laravel MCP Server that
exposes authenticated, read-only support capabilities
to external AI clients.

### MCP Capabilities

Tools:
- get-ticket-details
- search-tickets

Resources:
- supportflow://policies/demo-support

Prompts:
- ticket-triage

### Security

- Laravel Sanctum bearer token authentication
- Scoped MCP access tokens
- Ticket-level authorization
- Server-side input validation
- Read-only tools
- Response data minimization
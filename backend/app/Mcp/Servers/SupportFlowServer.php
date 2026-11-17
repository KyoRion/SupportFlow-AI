<?php

namespace App\Mcp\Servers;

use App\Mcp\Prompts\TicketTriagePrompt;
use App\Mcp\Resources\DemoSupportPolicyResource;
use App\Mcp\Tools\GetTicketDetailsTool;
use App\Mcp\Tools\SearchTicketsTool;
use App\Mcp\Tools\SearchKnowledgeBaseTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('SupportFlow MCP Server')]
#[Version('1.0.0')]
#[Instructions(
    'This server provides read-only access to authorized
     SupportFlow customer support tickets.
     Use tools to retrieve actual data.
     Never assume database records exist.'
)]
class SupportFlowServer extends Server
{
    protected array $tools = [
        GetTicketDetailsTool::class,
        SearchTicketsTool::class,
        SearchKnowledgeBaseTool::class,
    ];

    protected array $resources = [
        DemoSupportPolicyResource::class,
    ];

    protected array $prompts = [
        TicketTriagePrompt::class,
    ];
}

<?php

use App\Http\Middleware\RequireMcpToken;
use App\Mcp\Servers\SupportFlowServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/supportflow', SupportFlowServer::class)
    ->middleware([
        'auth:sanctum',
        RequireMcpToken::class,
        'throttle:30,1',
    ]);

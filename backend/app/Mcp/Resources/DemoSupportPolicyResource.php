<?php

namespace App\Mcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Uri('supportflow://policies/demo-support')]
#[MimeType('text/markdown')]
#[Description(
    'Fictional customer support policy for development and testing.'
)]
class DemoSupportPolicyResource extends Resource
{
    public function handle(Request $request): Response
    {
        return Response::text(<<<'MARKDOWN'
# SupportFlow Demo Support Policy

This is a fictional policy for learning and testing.

## Delivery Complaints
- Verify the order before proposing a resolution.
- Check the recorded order status.
- Do not promise refunds without authorization.

## Ticket Priorities
- Low: General information requests.
- Medium: Standard customer issues.
- High: Time-sensitive support issues.
- Urgent: Critical incidents requiring escalation.

## Resolution
- Confirm the outcome before resolving a ticket.
- Closed tickets must not be modified by automated tools.
MARKDOWN);
    }
}

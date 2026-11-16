<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Open => $next === self::InProgress,

            self::InProgress => $next === self::Resolved,

            self::Resolved => in_array($next, [
                self::InProgress,
                self::Closed,
            ], true),

            self::Closed => false,
        };
    }
}

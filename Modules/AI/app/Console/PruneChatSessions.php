<?php

namespace Modules\AI\app\Console;

use Illuminate\Console\Command;
use Modules\AI\app\Services\ShoppingAssistant\AiChatSessionService;

class PruneChatSessions extends Command
{
    protected $signature = 'ai:prune-chat-sessions
        {--days=30 : Delete guest sessions inactive for this many days}
        {--empty-hours=24 : Delete any session with zero messages older than this many hours}';

    protected $description = 'Delete stale guest AI shopping-assistant sessions and their messages.';

    public function handle(AiChatSessionService $sessionService): int
    {
        $days = max(1, (int) $this->option('days'));

        $deleted = $sessionService->deleteInactiveGuestSessions(inactiveBefore: now()->subDays($days));

        $this->info("Pruned {$deleted} guest chat session(s) inactive for {$days}+ days.");

        $emptyHours = max(1, (int) $this->option('empty-hours'));

        $emptyDeleted = $sessionService->deleteEmptySessions(createdBefore: now()->subHours($emptyHours));

        $this->info("Pruned {$emptyDeleted} empty chat session(s) older than {$emptyHours}h.");

        return self::SUCCESS;
    }
}

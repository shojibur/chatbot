<?php

namespace App\Console\Commands;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use Illuminate\Console\Command;

class PruneChatHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chat:prune {--days=14 : Number of days of history to keep}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete chat sessions and messages older than the specified number of days';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $this->info("Pruning chat history older than {$days} days (before {$cutoff->toDateTimeString()})...");

        // Delete messages belonging to old sessions
        $messagesDeleted = ChatMessage::whereIn(
            'chat_session_id',
            ChatSession::where('last_activity_at', '<', $cutoff)
                ->orWhere(function ($query) use ($cutoff) {
                    $query->whereNull('last_activity_at')
                        ->where('created_at', '<', $cutoff);
                })
                ->select('id')
        )->delete();

        // Delete old sessions
        $sessionsDeleted = ChatSession::where('last_activity_at', '<', $cutoff)
            ->orWhere(function ($query) use ($cutoff) {
                $query->whereNull('last_activity_at')
                    ->where('created_at', '<', $cutoff);
            })
            ->delete();

        $this->info("Deleted {$sessionsDeleted} sessions and {$messagesDeleted} messages.");

        return self::SUCCESS;
    }
}

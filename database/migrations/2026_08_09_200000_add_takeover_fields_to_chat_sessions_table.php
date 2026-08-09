<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table): void {
            $table->boolean('is_human_takeover')->default(false)->after('last_activity_at');
            $table->foreignId('taken_over_by_user_id')->nullable()->after('is_human_takeover')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('taken_over_at')->nullable()->after('taken_over_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('taken_over_by_user_id');
            $table->dropColumn(['is_human_takeover', 'taken_over_at']);
        });
    }
};

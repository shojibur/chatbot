<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('session_token', 64)->unique();
            $table->string('visitor_ip', 45)->nullable();
            $table->string('visitor_identifier')->nullable();
            $table->string('page_url', 2000)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->unsignedInteger('message_count')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'created_at']);
            $table->index(['client_id', 'last_activity_at']);
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['user', 'assistant']);
            $table->longText('content');
            $table->unsignedInteger('token_count')->default(0);
            $table->boolean('from_cache')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'created_at']);
            $table->index(['chat_session_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_sessions');
    }
};

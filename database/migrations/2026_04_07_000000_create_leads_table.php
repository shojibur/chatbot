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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chat_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('contact');                        // phone OR email
            $table->text('user_request')->nullable();         // message that triggered lead capture
            $table->text('notes')->nullable();                // optional step 4
            $table->json('conversation_snapshot')->nullable(); // last N messages for context
            $table->string('trigger')->default('intent');     // intent | no_answer | manual
            $table->string('status')->default('new');         // new | contacted | closed
            $table->timestamps();

            $table->index(['client_id', 'created_at']);
            $table->index(['client_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};

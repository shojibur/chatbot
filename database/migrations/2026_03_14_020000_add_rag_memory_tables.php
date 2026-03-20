<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('knowledge_sources', function (Blueprint $table) {
            $table->string('source_hash', 64)->nullable()->after('source_url');
            $table->unsignedBigInteger('file_size')->nullable()->after('mime_type');
            $table->timestamp('content_extracted_at')->nullable()->after('chunk_count');
            $table->timestamp('processed_at')->nullable()->after('content_extracted_at');
            $table->text('processing_error')->nullable()->after('processed_at');
            $table->json('processing_meta')->nullable()->after('processing_error');

            $table->index(['client_id', 'source_hash']);
        });

        Schema::create('knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('knowledge_source_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('chunk_index');
            $table->longText('content');
            $table->string('content_hash', 64);
            $table->unsignedInteger('token_estimate')->default(0);
            $table->unsignedInteger('character_count')->default(0);
            $table->string('embedding_model')->nullable();
            $table->json('embedding')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['knowledge_source_id', 'chunk_index']);
            $table->index(['client_id', 'content_hash']);
        });

        Schema::create('conversation_caches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('cache_key', 64);
            $table->string('normalized_question', 2000);
            $table->text('question');
            $table->longText('answer');
            $table->text('context_excerpt')->nullable();
            $table->string('question_hash', 64);
            $table->string('answer_hash', 64);
            $table->unsignedInteger('hit_count')->default(0);
            $table->unsignedInteger('prompt_tokens_saved')->default(0);
            $table->unsignedInteger('completion_tokens_saved')->default(0);
            $table->unsignedInteger('total_tokens_saved')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('chat_model')->nullable();
            $table->string('embedding_model')->nullable();
            $table->json('question_embedding')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['client_id', 'cache_key']);
            $table->index(['client_id', 'question_hash']);
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
            DB::statement('ALTER TABLE knowledge_chunks ADD COLUMN embedding_vector vector(1536)');
            DB::statement('ALTER TABLE conversation_caches ADD COLUMN question_embedding_vector vector(1536)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE conversation_caches DROP COLUMN IF EXISTS question_embedding_vector');
            DB::statement('ALTER TABLE knowledge_chunks DROP COLUMN IF EXISTS embedding_vector');
        }

        Schema::dropIfExists('conversation_caches');
        Schema::dropIfExists('knowledge_chunks');

        Schema::table('knowledge_sources', function (Blueprint $table) {
            $table->dropIndex(['client_id', 'source_hash']);
            $table->dropColumn([
                'source_hash',
                'file_size',
                'content_extracted_at',
                'processed_at',
                'processing_error',
                'processing_meta',
            ]);
        });
    }
};

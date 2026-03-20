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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 8, 2)->default(0);
            $table->unsignedInteger('monthly_token_limit')->default(100000);
            $table->unsignedInteger('monthly_message_limit')->nullable();
            $table->unsignedSmallInteger('max_knowledge_sources')->default(5);
            $table->unsignedSmallInteger('max_file_upload_mb')->default(10);
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->text('business_description')->nullable()->after('website_url');
            $table->text('system_prompt')->nullable()->after('business_description');
            $table->string('chat_model')->default('gpt-4o-mini')->after('system_prompt');
            $table->string('embedding_model')->default('text-embedding-3-small')->after('chat_model');
            $table->unsignedSmallInteger('retrieval_chunk_count')->default(3)->after('embedding_model');
            $table->unsignedSmallInteger('cache_ttl_hours')->default(24)->after('retrieval_chunk_count');
            $table->boolean('prompt_caching_enabled')->default(true)->after('cache_ttl_hours');
            $table->boolean('semantic_cache_enabled')->default(true)->after('prompt_caching_enabled');
            $table->json('allowed_domains')->nullable()->after('semantic_cache_enabled');
        });

        Schema::create('knowledge_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('source_type');
            $table->string('status')->default('draft');
            $table->string('source_url')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_path')->nullable();
            $table->string('mime_type')->nullable();
            $table->longText('content')->nullable();
            $table->unsignedInteger('token_estimate')->default(0);
            $table->unsignedInteger('chunk_count')->default(0);
            $table->timestamp('last_synced_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('interaction_type');
            $table->string('model')->nullable();
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->unsignedInteger('cached_input_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->decimal('estimated_cost', 10, 4)->default(0);
            $table->text('request_excerpt')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usage_logs');
        Schema::dropIfExists('knowledge_sources');

        Schema::table('clients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_id');
            $table->dropColumn([
                'business_description',
                'system_prompt',
                'chat_model',
                'embedding_model',
                'retrieval_chunk_count',
                'cache_ttl_hours',
                'prompt_caching_enabled',
                'semantic_cache_enabled',
                'allowed_domains',
            ]);
        });

        Schema::dropIfExists('plans');
    }
};

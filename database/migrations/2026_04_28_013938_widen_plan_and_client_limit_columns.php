<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE plans ALTER COLUMN monthly_token_limit TYPE BIGINT');
            DB::statement('ALTER TABLE plans ALTER COLUMN monthly_message_limit TYPE BIGINT');
            DB::statement('ALTER TABLE plans ALTER COLUMN max_knowledge_sources TYPE BIGINT');
            DB::statement('ALTER TABLE plans ALTER COLUMN max_file_upload_mb TYPE BIGINT');
            DB::statement('ALTER TABLE clients ALTER COLUMN monthly_token_limit TYPE BIGINT');

            return;
        }

        DB::statement('ALTER TABLE plans MODIFY monthly_token_limit BIGINT UNSIGNED NOT NULL DEFAULT 100000');
        DB::statement('ALTER TABLE plans MODIFY monthly_message_limit BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE plans MODIFY max_knowledge_sources BIGINT UNSIGNED NOT NULL DEFAULT 5');
        DB::statement('ALTER TABLE plans MODIFY max_file_upload_mb BIGINT UNSIGNED NOT NULL DEFAULT 10');
        DB::statement('ALTER TABLE clients MODIFY monthly_token_limit BIGINT UNSIGNED NOT NULL DEFAULT 500000');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE plans ALTER COLUMN monthly_token_limit TYPE INTEGER');
            DB::statement('ALTER TABLE plans ALTER COLUMN monthly_message_limit TYPE INTEGER');
            DB::statement('ALTER TABLE plans ALTER COLUMN max_knowledge_sources TYPE SMALLINT');
            DB::statement('ALTER TABLE plans ALTER COLUMN max_file_upload_mb TYPE SMALLINT');
            DB::statement('ALTER TABLE clients ALTER COLUMN monthly_token_limit TYPE INTEGER');

            return;
        }

        DB::statement('ALTER TABLE plans MODIFY monthly_token_limit INT UNSIGNED NOT NULL DEFAULT 100000');
        DB::statement('ALTER TABLE plans MODIFY monthly_message_limit INT UNSIGNED NULL');
        DB::statement('ALTER TABLE plans MODIFY max_knowledge_sources SMALLINT UNSIGNED NOT NULL DEFAULT 5');
        DB::statement('ALTER TABLE plans MODIFY max_file_upload_mb SMALLINT UNSIGNED NOT NULL DEFAULT 10');
        DB::statement('ALTER TABLE clients MODIFY monthly_token_limit INT UNSIGNED NOT NULL DEFAULT 500000');
    }
};

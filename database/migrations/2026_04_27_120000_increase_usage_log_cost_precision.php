<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE usage_logs ALTER COLUMN estimated_cost TYPE numeric(14,8)');

            return;
        }

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE usage_logs MODIFY estimated_cost DECIMAL(14,8) NOT NULL DEFAULT 0');
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE usage_logs ALTER COLUMN estimated_cost TYPE numeric(10,4)');

            return;
        }

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE usage_logs MODIFY estimated_cost DECIMAL(10,4) NOT NULL DEFAULT 0');
        }
    }
};

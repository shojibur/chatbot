<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('chatbot_tone', 500)->nullable()->after('system_prompt');
            $table->text('engagement_questions')->nullable()->after('chatbot_tone');
            $table->string('expert_name', 255)->nullable()->after('engagement_questions');
            $table->string('expert_title', 255)->nullable()->after('expert_name');
            $table->string('expert_followup', 500)->nullable()->after('expert_title');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['chatbot_tone', 'engagement_questions', 'expert_name', 'expert_title', 'expert_followup']);
        });
    }
};

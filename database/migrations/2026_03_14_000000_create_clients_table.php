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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('unique_code')->unique();
            $table->string('contact_email')->nullable();
            $table->string('website_url')->nullable();
            $table->unsignedInteger('monthly_token_limit')->default(500000);
            $table->string('status')->default('draft');
            $table->string('widget_style')->default('classic');
            $table->json('widget_settings');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};

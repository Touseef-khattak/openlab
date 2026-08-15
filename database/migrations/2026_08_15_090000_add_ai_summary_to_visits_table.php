<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->text('ai_summary')->nullable();
            $table->string('ai_summary_provider', 30)->nullable();
            $table->string('ai_summary_model', 60)->nullable();
            $table->dateTime('ai_summary_generated_at')->nullable();
            $table->foreignId('ai_summary_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ai_summary_by');
            $table->dropColumn(['ai_summary', 'ai_summary_provider', 'ai_summary_model', 'ai_summary_generated_at']);
        });
    }
};

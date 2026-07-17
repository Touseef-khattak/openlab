<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reference_ranges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_parameter_id')->constrained()->cascadeOnDelete();
            // null gender / null age bounds mean the band applies to everyone.
            $table->string('gender', 10)->nullable();
            $table->unsignedSmallInteger('age_min_months')->nullable();
            $table->unsignedSmallInteger('age_max_months')->nullable();
            $table->decimal('low', 12, 4)->nullable();
            $table->decimal('high', 12, 4)->nullable();
            $table->decimal('critical_low', 12, 4)->nullable();
            $table->decimal('critical_high', 12, 4)->nullable();
            $table->string('normal_text')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reference_ranges');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every test has >= 1 parameter; a panel (CBC, LFT) has many.
        // Single-value tests get one parameter mirroring the test name.
        Schema::create('test_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('unit', 30)->nullable();
            $table->string('result_type', 10)->default('numeric');
            $table->unsignedTinyInteger('decimals')->default(2);
            $table->json('options')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_parameters');
    }
};

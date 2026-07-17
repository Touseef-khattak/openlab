<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_test_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_parameter_id')->constrained()->restrictOnDelete();
            $table->string('value')->nullable();
            // Numeric mirror of `value` so range flagging and delta checks
            // never parse strings.
            $table->decimal('value_numeric', 12, 4)->nullable();
            $table->string('flag', 15)->nullable();
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('entered_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['visit_test_id', 'test_parameter_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};

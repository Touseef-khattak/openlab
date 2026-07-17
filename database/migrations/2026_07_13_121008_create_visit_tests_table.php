<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_id')->constrained()->restrictOnDelete();
            $table->foreignId('sample_id')->nullable()->constrained()->nullOnDelete();
            // Price copied from the catalog at order time so later catalog
            // price changes never rewrite historical invoices.
            $table->decimal('price', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_tests');
    }
};

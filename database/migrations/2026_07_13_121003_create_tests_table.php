<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->decimal('price', 10, 2)->default(0);
            $table->string('specimen_type', 50)->nullable();
            $table->boolean('is_panel')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tests');
    }
};

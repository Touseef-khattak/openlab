<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('mrn', 30)->nullable()->unique();
            $table->string('name');
            $table->string('cnic', 20)->nullable()->index();
            $table->string('phone', 20)->nullable()->index();
            $table->string('gender', 10);
            $table->date('dob')->nullable();
            // Legacy records only carry an age at registration, not a birth date.
            $table->unsignedSmallInteger('age_years')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};

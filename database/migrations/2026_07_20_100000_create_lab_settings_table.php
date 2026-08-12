<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Single-row branding profile for the lab. Under DB-per-tenant this
        // simply lives once inside each tenant's database.
        Schema::create('lab_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->longText('logo')->nullable(); // base64 data URI, embeds cleanly in PDF and web
            $table->string('address')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('report_header')->nullable();
            $table->text('report_footer')->nullable();
            $table->text('receipt_footer')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_settings');
    }
};

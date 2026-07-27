<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('samples', function (Blueprint $table) {
            $table->dateTime('reported_at')->nullable()->after('collected_at');
        });
    }

    public function down(): void
    {
        Schema::table('samples', function (Blueprint $table) {
            $table->dropColumn('reported_at');
        });
    }
};

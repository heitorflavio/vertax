<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demo_sources', function (Blueprint $table) {
            $table->json('api_config')->nullable()->after('search_types');
        });
    }

    public function down(): void
    {
        Schema::table('demo_sources', function (Blueprint $table) {
            $table->dropColumn('api_config');
        });
    }
};

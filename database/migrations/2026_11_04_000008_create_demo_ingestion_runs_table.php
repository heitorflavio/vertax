<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_ingestion_runs', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('running');
            $table->unsignedSmallInteger('sources_synced')->default(0);
            $table->unsignedSmallInteger('payloads_ingested')->default(0);
            $table->unsignedSmallInteger('records_merged')->default(0);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_ingestion_runs');
    }
};

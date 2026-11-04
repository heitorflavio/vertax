<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_record_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demo_record_id')->nullable()->constrained('demo_records')->cascadeOnDelete();
            $table->foreignId('demo_source_id')->constrained('demo_sources')->cascadeOnDelete();
            $table->string('public_id');
            $table->string('external_ref')->nullable();
            $table->json('payload');
            $table->unsignedTinyInteger('reliability_score')->default(50);
            $table->unsignedTinyInteger('match_score')->default(100);
            $table->json('contributed_fields')->nullable();
            $table->timestamp('fetched_at');
            $table->timestamps();

            $table->unique(['public_id', 'demo_source_id']);
            $table->index('public_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_record_sources');
    }
};

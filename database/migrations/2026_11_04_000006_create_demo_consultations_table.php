<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demo_record_id')->constrained('demo_records')->cascadeOnDelete();
            $table->string('session_id')->nullable()->index();
            $table->boolean('plan_active')->default(false);
            $table->unsignedTinyInteger('trust_score')->default(0);
            $table->unsignedTinyInteger('sources_count')->default(0);
            $table->string('feedback')->nullable();
            $table->timestamp('feedback_at')->nullable();
            $table->string('ip_hash')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index('feedback');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_consultations');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_plan_activations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demo_plan_id')->nullable()->constrained('demo_plans')->nullOnDelete();
            $table->string('plan_slug');
            $table->string('session_id')->index();
            $table->timestamp('activated_at');
            $table->timestamp('expires_at');
            $table->string('ip_hash')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_plan_activations');
    }
};

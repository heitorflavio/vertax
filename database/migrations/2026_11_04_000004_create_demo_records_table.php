<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_records', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();
            $table->string('full_name');
            $table->string('cpf_masked');
            $table->date('birth_date');
            $table->string('mother_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->json('hidden_data')->nullable();
            $table->unsignedTinyInteger('trust_score')->default(0);
            $table->string('trust_label')->default('Baixo');
            $table->json('trust_breakdown')->nullable();
            $table->unsignedTinyInteger('sources_count')->default(0);
            $table->unsignedInteger('consultations_count')->default(0);
            $table->timestamp('last_consulted_at')->nullable();
            $table->timestamps();

            $table->index('trust_score');
            $table->index('last_consulted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_records');
    }
};

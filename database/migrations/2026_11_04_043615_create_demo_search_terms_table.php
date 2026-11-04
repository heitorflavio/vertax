<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índice de busca: um termo normalizado por tipo (nome, CPF, CNH, CNPJ,
 * e-mail, telefone) para cada registro canônico. Reconstruído na ingestão.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_search_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demo_record_id')->constrained('demo_records')->cascadeOnDelete();
            $table->string('type', 16);
            $table->string('value');
            $table->timestamps();

            $table->unique(['demo_record_id', 'type']);
            $table->index(['type', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_search_terms');
    }
};

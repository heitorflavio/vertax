<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A busca passou a consultar as fontes compatíveis no momento da busca, então
 * o índice local (montado a partir do registro mesclado) deixou de ser usado.
 * Era um dado derivado, reconstruído a cada ingestão: nada se perde.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('demo_search_terms');
    }

    public function down(): void
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
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tipos de busca que cada fonte aceita (nome, cpf, cnh, cnpj, email, phone).
 * A busca só consulta as fontes ativas compatíveis com o tipo do termo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demo_sources', function (Blueprint $table) {
            $table->json('search_types')->nullable()->after('reliability');
        });
    }

    public function down(): void
    {
        Schema::table('demo_sources', function (Blueprint $table) {
            $table->dropColumn('search_types');
        });
    }
};

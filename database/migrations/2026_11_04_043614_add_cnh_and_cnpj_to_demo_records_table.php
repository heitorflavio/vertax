<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentos opcionais do registro fictício. Não entram no cálculo de
 * completude do score (ver DemoConfig::OPTIONAL_FIELDS).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demo_records', function (Blueprint $table) {
            $table->string('cnh')->nullable()->after('cpf');
            $table->string('cnpj')->nullable()->after('cnh');
        });
    }

    public function down(): void
    {
        Schema::table('demo_records', function (Blueprint $table) {
            $table->dropColumn(['cnh', 'cnpj']);
        });
    }
};

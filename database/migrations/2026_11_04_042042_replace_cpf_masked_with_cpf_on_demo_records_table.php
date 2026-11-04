<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * O registro passa a guardar o CPF fictício completo. A versão mascarada
 * deixa de ser coluna e vira um accessor em DemoRecord (cpf_masked).
 * Rode `php artisan demo:ingest` depois para preencher a coluna nova.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demo_records', function (Blueprint $table) {
            $table->string('cpf')->nullable()->after('full_name');
        });

        Schema::table('demo_records', function (Blueprint $table) {
            $table->dropColumn('cpf_masked');
        });
    }

    public function down(): void
    {
        Schema::table('demo_records', function (Blueprint $table) {
            $table->string('cpf_masked')->nullable()->after('full_name');
        });

        Schema::table('demo_records', function (Blueprint $table) {
            $table->dropColumn('cpf');
        });
    }
};

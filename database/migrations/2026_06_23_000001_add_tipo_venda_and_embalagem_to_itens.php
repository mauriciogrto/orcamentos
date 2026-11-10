<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itens', function (Blueprint $table): void {
            $table->string('tipo_venda')->default('unitario');
            $table->decimal('embalagem_quantidade', 15, 3)->default(1);
        });

        Schema::table('orcamento_itens', function (Blueprint $table): void {
            $table->string('tipo_venda')->default('unitario');
            $table->decimal('embalagem_quantidade', 15, 3)->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('orcamento_itens', function (Blueprint $table): void {
            $table->dropColumn(['tipo_venda', 'embalagem_quantidade']);
        });

        Schema::table('itens', function (Blueprint $table): void {
            $table->dropColumn(['tipo_venda', 'embalagem_quantidade']);
        });
    }
};

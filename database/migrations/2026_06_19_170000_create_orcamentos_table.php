<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orcamentos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->unsignedSmallInteger('ano');
            $table->unsignedInteger('sequencial');
            $table->string('numero', 30);
            $table->string('status', 20)->default('rascunho');
            $table->date('data_emissao');
            $table->time('hora_emissao');
            $table->string('forma_pagamento', 20);
            $table->string('boleto_prazos', 40)->nullable();
            $table->unsignedSmallInteger('validade_dias');
            $table->text('observacoes')->nullable();
            $table->string('frete_tipo', 3);
            $table->decimal('subtotal_sem_desconto', 15, 2);
            $table->decimal('desconto_efetivo_percentual', 5, 2);
            $table->decimal('valor_total', 15, 2);
            $table->decimal('comissao_percentual', 5, 2)->default(0);
            $table->decimal('valor_comissao', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['empresa_id', 'numero']);
            $table->unique(['empresa_id', 'ano', 'sequencial']);
            $table->index(['empresa_id', 'user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orcamentos');
    }
};

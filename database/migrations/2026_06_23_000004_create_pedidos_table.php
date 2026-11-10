<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('orcamento_id')->unique()->constrained('orcamentos')->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->unsignedSmallInteger('ano');
            $table->unsignedInteger('sequencial');
            $table->string('numero', 30);
            $table->string('status', 20)->default('aberto');
            $table->date('data_pedido');
            $table->time('hora_pedido');
            $table->decimal('subtotal_sem_desconto', 15, 2)->default(0);
            $table->decimal('desconto_efetivo_percentual', 5, 2)->default(0);
            $table->decimal('total_produtos', 15, 2)->default(0);
            $table->decimal('total_impostos', 15, 2)->default(0);
            $table->decimal('valor_total', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['empresa_id', 'numero']);
            $table->unique(['empresa_id', 'ano', 'sequencial']);
            $table->index(['empresa_id', 'user_id', 'status']);
            $table->index(['empresa_id', 'cliente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};

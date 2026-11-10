<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orcamento_itens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('orcamento_id')->constrained('orcamentos')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('itens')->restrictOnDelete();
            $table->string('descricao');
            $table->unsignedInteger('quantidade');
            $table->decimal('valor_unitario', 15, 2);
            $table->decimal('desconto_percentual', 5, 2)->default(0);
            $table->decimal('valor_total', 15, 2);
            $table->timestamps();

            $table->index(['empresa_id', 'orcamento_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orcamento_itens');
    }
};

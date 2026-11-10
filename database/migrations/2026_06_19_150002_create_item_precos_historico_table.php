<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_precos_historico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('itens')->cascadeOnDelete();
            $table->decimal('preco', 15, 2);
            $table->timestamp('registrado_em');
            $table->index(['empresa_id', 'item_id', 'registrado_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_precos_historico');
    }
};

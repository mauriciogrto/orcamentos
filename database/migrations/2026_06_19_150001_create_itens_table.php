<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('descricao');
            $table->string('numero_serie')->nullable();
            $table->decimal('preco_atual', 15, 2);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['empresa_id', 'descricao']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('itens');
    }
};

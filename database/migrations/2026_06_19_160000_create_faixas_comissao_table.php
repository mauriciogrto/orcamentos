<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faixas_comissao', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->decimal('desconto_percentual', 5, 2);
            $table->decimal('comissao_percentual', 5, 2);
            $table->timestamps();

            $table->unique(['empresa_id', 'desconto_percentual']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faixas_comissao');
    }
};

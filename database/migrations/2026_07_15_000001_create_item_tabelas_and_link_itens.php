<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_tabelas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('nome');
            $table->string('arquivo_original')->nullable();
            $table->unsignedInteger('itens_importados')->default(0);
            $table->timestamp('ultima_atualizacao_em')->nullable();
            $table->foreignId('atualizado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['empresa_id', 'nome']);
            $table->index(['empresa_id', 'ultima_atualizacao_em']);
        });

        Schema::table('itens', function (Blueprint $table): void {
            $table->foreignId('item_tabela_id')
                ->nullable()
                ->after('empresa_id')
                ->constrained('item_tabelas')
                ->nullOnDelete();
        });

        $empresas = DB::table('empresas')->select('id')->get();

        foreach ($empresas as $empresa) {
            $tabelaId = DB::table('item_tabelas')->insertGetId([
                'empresa_id' => $empresa->id,
                'nome' => 'Tabela padrão',
                'itens_importados' => DB::table('itens')->where('empresa_id', $empresa->id)->count(),
                'ultima_atualizacao_em' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('itens')
                ->where('empresa_id', $empresa->id)
                ->whereNull('item_tabela_id')
                ->update(['item_tabela_id' => $tabelaId]);
        }

        Schema::table('itens', function (Blueprint $table): void {
            $table->dropUnique(['empresa_id', 'numero_serie']);
            $table->unique(['empresa_id', 'item_tabela_id', 'numero_serie']);
        });
    }

    public function down(): void
    {
        Schema::table('itens', function (Blueprint $table): void {
            $table->dropUnique(['empresa_id', 'item_tabela_id', 'numero_serie']);
            $table->unique(['empresa_id', 'numero_serie']);
            $table->dropConstrainedForeignId('item_tabela_id');
        });

        Schema::dropIfExists('item_tabelas');
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_sequencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('prefixo', 5);
            $table->unsignedInteger('ultimo_sequencial')->default(0);
            $table->timestamps();
            $table->unique(['empresa_id', 'prefixo']);
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->string('codigo', 20)->nullable()->after('user_id');
            $table->unique(['empresa_id', 'codigo']);
        });

        $contadores = [];
        DB::table('clientes')->orderBy('empresa_id')->orderBy('id')->get()->each(function (object $cliente) use (&$contadores): void {
            $cnpj = preg_replace('/\D/', '', (string) $cliente->cnpj);
            if (strlen($cnpj) !== 14) {
                return;
            }

            $prefixo = substr($cnpj, 0, 5);
            $chave = $cliente->empresa_id.'-'.$prefixo;
            $sequencial = ($contadores[$chave] ?? 0) + 1;
            $contadores[$chave] = $sequencial;

            DB::table('clientes')->where('id', $cliente->id)->update([
                'codigo' => $prefixo.'-'.str_pad((string) $sequencial, 3, '0', STR_PAD_LEFT),
            ]);
        });

        foreach ($contadores as $chave => $ultimo) {
            [$empresaId, $prefixo] = explode('-', $chave, 2);
            DB::table('cliente_sequencias')->insert([
                'empresa_id' => $empresaId,
                'prefixo' => $prefixo,
                'ultimo_sequencial' => $ultimo,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropUnique(['empresa_id', 'codigo']);
            $table->dropColumn('codigo');
        });

        Schema::dropIfExists('cliente_sequencias');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('clientes', 'nome_fantasia')) {
            return;
        }

        DB::table('clientes')
            ->whereNotNull('nome_fantasia')
            ->orderBy('id')
            ->chunkById(100, function ($clientes): void {
                foreach ($clientes as $cliente) {
                    $nomeFantasia = (string) $cliente->nome_fantasia;

                    if (mb_strlen($nomeFantasia) <= 12) {
                        continue;
                    }

                    DB::table('clientes')
                        ->where('id', $cliente->id)
                        ->update(['nome_fantasia' => mb_substr($nomeFantasia, 0, 12)]);
                }
            });
    }

    public function down(): void
    {
        // NÃ£o Ã© possÃ­vel restaurar automaticamente os caracteres truncados.
    }
};

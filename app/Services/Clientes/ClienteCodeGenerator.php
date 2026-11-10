<?php

namespace App\Services\Clientes;

use Illuminate\Support\Facades\DB;

class ClienteCodeGenerator
{
    public function next(int $empresaId, string $cnpj): string
    {
        $prefixo = substr(preg_replace('/\D/', '', $cnpj), 0, 5);
        $agora = now();

        DB::table('cliente_sequencias')->upsert([
            [
                'empresa_id' => $empresaId,
                'prefixo' => $prefixo,
                'ultimo_sequencial' => 0,
                'created_at' => $agora,
                'updated_at' => $agora,
            ],
        ], ['empresa_id', 'prefixo'], ['updated_at']);

        $contador = DB::table('cliente_sequencias')
            ->where('empresa_id', $empresaId)
            ->where('prefixo', $prefixo)
            ->lockForUpdate()
            ->first();
        $sequencial = $contador->ultimo_sequencial + 1;

        DB::table('cliente_sequencias')->where('id', $contador->id)->update([
            'ultimo_sequencial' => $sequencial,
            'updated_at' => $agora,
        ]);

        return $prefixo.'-'.str_pad((string) $sequencial, 3, '0', STR_PAD_LEFT);
    }
}

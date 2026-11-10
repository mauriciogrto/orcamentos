<?php

namespace App\Services\Orcamentos;

use App\Models\Empresa;
use App\Models\Orcamento;

class OrcamentoNumberGenerator
{
    /**
     * Must be called inside a database transaction.
     *
     * @return array{ano:int, sequencial:int, numero:string}
     */
    public function next(int $empresaId): array
    {
        Empresa::query()->whereKey($empresaId)->lockForUpdate()->firstOrFail();

        $ano = (int) now()->format('Y');
        $sequencial = ((int) Orcamento::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->where('ano', $ano)
            ->max('sequencial')) + 1;

        return [
            'ano' => $ano,
            'sequencial' => $sequencial,
            'numero' => sprintf('ORC-%d-%03d', $ano, $sequencial),
        ];
    }
}

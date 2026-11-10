<?php

namespace App\Services\Pedidos;

use App\Models\Pedido;

class PedidoNumberGenerator
{
    /**
     * @return array{ano: int, sequencial: int, numero: string}
     */
    public function next(int $empresaId): array
    {
        $ano = (int) now()->year;
        $ultimoSequencial = Pedido::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->where('ano', $ano)
            ->lockForUpdate()
            ->max('sequencial');

        $sequencial = ((int) $ultimoSequencial) + 1;

        return [
            'ano' => $ano,
            'sequencial' => $sequencial,
            'numero' => sprintf('PED-%d-%03d', $ano, $sequencial),
        ];
    }
}

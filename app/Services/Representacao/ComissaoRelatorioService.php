<?php

namespace App\Services\Representacao;

use App\Models\FaixaComissao;
use App\Models\Pedido;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class ComissaoRelatorioService
{
    /**
     * @return array<string, mixed>
     */
    public function gerar(User $usuario, string $mes, ?int $vendedorId = null): array
    {
        $inicio = CarbonImmutable::createFromFormat('!Y-m', $mes)->startOfMonth();
        $fim = $inicio->endOfMonth();

        $query = Pedido::query()
            ->with(['cliente', 'vendedor', 'orcamento'])
            ->whereBetween('data_pedido', [$inicio->toDateString(), $fim->toDateString()])
            ->orderBy('data_pedido')
            ->orderBy('numero');

        if ($vendedorId) {
            $query->where('user_id', $vendedorId);
        }

        $pedidos = $query->get();

        $resumoPorVendedor = $pedidos
            ->groupBy('user_id')
            ->map(fn (Collection $pedidosDoVendedor): array => $this->resumo($pedidosDoVendedor))
            ->sortBy(fn (array $linha): string => $linha['vendedor']?->name ?? '')
            ->values();

        $totalizador = $this->resumo($pedidos);
        if ($vendedorId === null && $resumoPorVendedor->isNotEmpty()) {
            $totalizador['valor_comissao'] = round($resumoPorVendedor->sum('valor_comissao'), 2);
            $totalizador['comissao_percentual'] = $totalizador['base_comissao'] > 0
                ? round(($totalizador['valor_comissao'] / $totalizador['base_comissao']) * 100, 2)
                : 0.0;
            $totalizador['faixa'] = $resumoPorVendedor->count() === 1 ? $resumoPorVendedor->first()['faixa'] : null;
        }

        return [
            'periodo' => [
                'mes' => $mes,
                'inicio' => $inicio,
                'fim' => $fim,
            ],
            'pedidos' => $pedidos,
            'resumoPorVendedor' => $resumoPorVendedor,
            'totalizador' => $totalizador,
            'vendedorSelecionado' => $vendedorId ? User::withTrashed()->find($vendedorId) : null,
        ];
    }

    /**
     * @param Collection<int, Pedido> $pedidos
     * @return array<string, mixed>
     */
    private function resumo(Collection $pedidos): array
    {
        $primeiro = $pedidos->first();
        $subtotalSemDesconto = $pedidos->sum(fn (Pedido $pedido): float => $this->subtotalSemDesconto($pedido));
        $baseComissao = $pedidos->sum(fn (Pedido $pedido): float => (float) $pedido->total_produtos);
        $descontoMedio = $subtotalSemDesconto > 0
            ? round((1 - ($baseComissao / $subtotalSemDesconto)) * 100, 2)
            : 0.0;
        $faixa = $this->faixaParaDesconto($descontoMedio);
        $comissaoPercentual = (float) ($faixa?->comissao_percentual ?? 0);
        $valorComissao = round($baseComissao * ($comissaoPercentual / 100), 2);

        return [
            'vendedor' => $primeiro?->vendedor,
            'quantidade' => $pedidos->count(),
            'valor_pedidos' => $pedidos->sum(fn (Pedido $pedido): float => (float) $pedido->valor_total),
            'subtotal_sem_desconto' => $subtotalSemDesconto,
            'base_comissao' => $baseComissao,
            'desconto_medio' => $descontoMedio,
            'faixa' => $faixa,
            'comissao_percentual' => $comissaoPercentual,
            'valor_comissao' => $valorComissao,
        ];
    }

    private function faixaParaDesconto(float $descontoMedio): ?FaixaComissao
    {
        return FaixaComissao::query()
            ->where('desconto_inicial_percentual', '<=', $descontoMedio)
            ->where('desconto_final_percentual', '>=', $descontoMedio)
            ->orderBy('desconto_inicial_percentual')
            ->first();
    }

    private function subtotalSemDesconto(Pedido $pedido): float
    {
        $subtotal = (float) $pedido->subtotal_sem_desconto;
        $base = (float) $pedido->total_produtos;
        $desconto = (float) $pedido->desconto_efetivo_percentual;

        if ($base <= 0) {
            return max($subtotal, 0);
        }

        if ($desconto > 0 && $desconto < 100 && $subtotal <= $base) {
            return round($base / (1 - ($desconto / 100)), 2);
        }

        return max($subtotal, $base);
    }
}

<?php

namespace App\Services\Orcamentos;

use App\Models\FaixaComissao;
use Illuminate\Support\Collection;

class OrcamentoCalculator
{
    /**
     * @param  Collection<int, array<string, mixed>>  $itens
     * @return array<string, mixed>
     */
    public function calculate(Collection $itens, int $empresaId): array
    {
        $calculados = $itens->map(function (array $item): array {
            $subtotal = round($item['quantidade'] * $item['valor_unitario'], 2);
            $valorProdutos = round($subtotal * (1 - ($item['desconto_percentual'] / 100)), 2);
            $valorIcms = $this->percentual($valorProdutos, $item['aliquota_icms']);
            $valorIpi = $this->percentual($valorProdutos, $item['aliquota_ipi']);
            $valorPis = $this->percentual($valorProdutos, $item['aliquota_pis']);
            $valorCofins = $this->percentual($valorProdutos, $item['aliquota_cofins']);
            $baseCalculoSt = $item['possui_st']
                ? round($valorProdutos * (1 + ($item['mva'] / 100)), 2)
                : 0.0;
            $valorIcmsSt = $item['possui_st']
                ? max(0, round($this->percentual($baseCalculoSt, $item['aliquota_icms']) - $valorIcms, 2))
                : 0.0;
            $valorImpostos = round($valorIcms + $valorIpi + $valorPis + $valorCofins + $valorIcmsSt, 2);
            $total = round($valorProdutos + $valorImpostos, 2);

            return [
                ...$item,
                'valor_produtos' => $valorProdutos,
                'valor_icms' => $valorIcms,
                'valor_ipi' => $valorIpi,
                'valor_pis' => $valorPis,
                'valor_cofins' => $valorCofins,
                'base_calculo_st' => $baseCalculoSt,
                'valor_icms_st' => $valorIcmsSt,
                'valor_impostos' => $valorImpostos,
                'valor_total' => $total,
            ];
        });

        $subtotal = round($calculados->sum(fn (array $item): float => $item['quantidade'] * $item['valor_unitario']), 2);
        $totalProdutos = round($calculados->sum('valor_produtos'), 2);
        $totalImpostos = round($calculados->sum('valor_impostos'), 2);
        $total = round($calculados->sum('valor_total'), 2);
        $descontoEfetivo = $subtotal > 0 ? round((1 - ($totalProdutos / $subtotal)) * 100, 2) : 0.0;
        $comissaoPercentual = (float) (FaixaComissao::query()
            ->where('empresa_id', $empresaId)
            ->where('desconto_inicial_percentual', '<=', number_format($descontoEfetivo, 2, '.', ''))
            ->where('desconto_final_percentual', '>=', number_format($descontoEfetivo, 2, '.', ''))
            ->orderByDesc('desconto_inicial_percentual')
            ->value('comissao_percentual') ?? 0);

        return [
            'subtotal_sem_desconto' => $subtotal,
            'desconto_efetivo_percentual' => $descontoEfetivo,
            'total_produtos' => $totalProdutos,
            'total_impostos' => $totalImpostos,
            'valor_total' => $total,
            'comissao_percentual' => $comissaoPercentual,
            'valor_comissao' => round($totalProdutos * ($comissaoPercentual / 100), 2),
            'itens' => $calculados,
        ];
    }

    private function percentual(float $base, float $aliquota): float
    {
        return round($base * ($aliquota / 100), 2);
    }
}

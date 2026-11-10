<?php

namespace App\Services\Orcamentos;

use App\Models\Item;
use App\Models\Orcamento;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class OrcamentoItemSnapshotService
{
    public function __construct(private readonly OrcamentoCalculator $calculator) {}

    /**
     * @return Collection<int, array{
     *     item_id:int,
     *     descricao_orcamento:string,
     *     descricao_atual:string,
     *     preco_orcamento:float,
     *     preco_atual:float
     * }>
     */
    public function priceChanges(Orcamento $orcamento): Collection
    {
        $orcamento->loadMissing('itens.item');

        return $orcamento->itens
            ->filter(function ($linha): bool {
                if (! $linha->item || $linha->item->trashed()) {
                    return false;
                }

                return round((float) $linha->valor_unitario, 2) !== round((float) $linha->item->preco_atual, 2);
            })
            ->map(fn ($linha): array => [
                'item_id' => (int) $linha->item_id,
                'descricao_orcamento' => (string) $linha->descricao,
                'descricao_atual' => (string) $linha->item->descricao,
                'preco_orcamento' => (float) $linha->valor_unitario,
                'preco_atual' => (float) $linha->item->preco_atual,
            ])
            ->values();
    }

    public function syncWithCurrentCatalog(Orcamento $orcamento): Orcamento
    {
        $orcamento->loadMissing(['cliente', 'empresa', 'itens']);

        $linhas = $orcamento->itens
            ->map(fn ($linha): array => [
                'item_id' => (int) $linha->item_id,
                'quantidade' => (int) $linha->quantidade,
                'desconto_percentual' => (float) $linha->desconto_percentual,
            ])
            ->all();

        $calculo = $this->calculateFromLines($linhas, (int) $orcamento->empresa_id);
        $orcamento->update([
            ...collect($calculo)->except('itens')->all(),
            'frete_tipo' => $this->freteAutomatico($orcamento, (float) $calculo['valor_total']),
        ]);

        $orcamento->itens()->delete();
        $orcamento->itens()->createMany($calculo['itens']->map(fn (array $item): array => [
            ...$item,
            'empresa_id' => $orcamento->empresa_id,
        ])->all());

        return $orcamento->refresh()->load('itens');
    }

    /**
     * @param  array<int, array{item_id:int, quantidade:int, desconto_percentual:float}>  $linhas
     * @return array<string, mixed>
     */
    private function calculateFromLines(array $linhas, int $empresaId): array
    {
        $catalogo = Item::query()
            ->with('tributacao')
            ->whereIn('id', collect($linhas)->pluck('item_id'))
            ->get()
            ->keyBy('id');

        $itens = collect($linhas)->map(function (array $linha) use ($catalogo): array {
            $item = $catalogo->get((int) $linha['item_id']);

            if (! $item) {
                throw ValidationException::withMessages([
                    'itens' => 'Um ou mais itens do orçamento foram excluídos do cadastro e não podem ser atualizados para virar pedido.',
                ]);
            }

            $tributacao = $item->tributacao;

            return [
                'item_id' => $item->id,
                'descricao' => $item->descricao,
                'tipo_venda' => $item->tipo_venda?->value ?? 'unitario',
                'embalagem_quantidade' => (float) ($item->embalagem_quantidade ?? 1),
                'quantidade' => (int) $linha['quantidade'],
                'valor_unitario' => (float) $item->preco_atual,
                'desconto_percentual' => (float) $linha['desconto_percentual'],
                'ncm' => $tributacao?->ncm,
                'cest' => $tributacao?->cest,
                'aliquota_icms' => (float) ($tributacao?->aliquota_icms ?? 0),
                'aliquota_ipi' => (float) ($tributacao?->aliquota_ipi ?? 0),
                'aliquota_pis' => (float) ($tributacao?->aliquota_pis ?? 0),
                'aliquota_cofins' => (float) ($tributacao?->aliquota_cofins ?? 0),
                'possui_st' => (bool) ($tributacao?->possui_st ?? false),
                'mva' => (float) ($tributacao?->mva ?? 0),
                'observacoes_fiscais' => $tributacao?->observacoes_fiscais,
            ];
        });

        return $this->calculator->calculate($itens, $empresaId);
    }

    private function freteAutomatico(Orcamento $orcamento, float $valorTotal): string
    {
        $limite = $orcamento->cliente?->frete_personalizado && $orcamento->cliente?->limite_frete_fob !== null
            ? (float) $orcamento->cliente->limite_frete_fob
            : (float) $orcamento->empresa->limite_frete_fob;

        return $valorTotal <= $limite ? 'fob' : 'cif';
    }
}

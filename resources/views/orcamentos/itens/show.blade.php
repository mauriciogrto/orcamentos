<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold">{{ $item->descricao }}</h2>
            <x-back-link :href="route('orcamentos.itens.index')" label="Voltar" />
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <div class="grid gap-4 md:grid-cols-2">
                    <p><strong>Tabela:</strong> {{ $item->tabela?->nome ?? '—' }}</p>
                    <p><strong>Número de série:</strong> {{ $item->numero_serie ?: '—' }}</p>
                    <p><strong>Tipo de venda:</strong> {{ $item->tipo_venda?->label() ?? 'Unitário' }}</p>
                    <p><strong>Embalagem:</strong> {{ number_format((float) ($item->embalagem_quantidade ?? 1), 3, ',', '.') }} {{ $item->tipo_venda?->unidade() ?? 'un.' }}</p>
                    <p><strong>Preço-base atual:</strong> R$ {{ number_format((float) $item->preco_atual, 2, ',', '.') }}</p>
                </div>
            </div>

            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="text-lg font-semibold text-gray-900">Tributação</h3>
                @if ($item->tributacao)
                    <div class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <p><span class="text-xs text-gray-500">NCM</span><br>{{ $item->tributacao->ncm ?: '—' }}</p>
                        <p><span class="text-xs text-gray-500">CEST</span><br>{{ $item->tributacao->cest ?: '—' }}</p>
                        <p><span class="text-xs text-gray-500">ICMS</span><br>{{ number_format((float) $item->tributacao->aliquota_icms, 4, ',', '.') }}%</p>
                        <p><span class="text-xs text-gray-500">IPI</span><br>{{ number_format((float) $item->tributacao->aliquota_ipi, 4, ',', '.') }}%</p>
                        <p><span class="text-xs text-gray-500">PIS</span><br>{{ number_format((float) $item->tributacao->aliquota_pis, 4, ',', '.') }}%</p>
                        <p><span class="text-xs text-gray-500">COFINS</span><br>{{ number_format((float) $item->tributacao->aliquota_cofins, 4, ',', '.') }}%</p>
                        <p><span class="text-xs text-gray-500">ST</span><br>{{ $item->tributacao->possui_st ? 'Sim' : 'Não' }}</p>
                        @if ($item->tributacao->possui_st)
                            <p><span class="text-xs text-gray-500">MVA</span><br>{{ number_format((float) $item->tributacao->mva, 4, ',', '.') }}%</p>
                        @endif
                    </div>

                    @if ($item->tributacao->possui_st && $item->tributacao->observacoes_fiscais)
                        <p class="mt-4 rounded-md bg-gray-50 p-3"><strong>Observações fiscais:</strong><br>{{ $item->tributacao->observacoes_fiscais }}</p>
                    @endif
                @else
                    <p class="mt-3 text-sm text-gray-500">Nenhuma tributação informada.</p>
                @endif
            </div>

            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                @can('viewHistory', $item)
                    @include('orcamentos.itens._historico')
                @endcan
            </div>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between">
            <div>
                <h2 class="text-xl font-semibold">{{ $pedido->numero }}</h2>
                <p class="text-sm text-gray-600">Gerado do orçamento {{ $pedido->orcamento->numero }}</p>
            </div>

            <div class="flex flex-wrap gap-3">
                <x-back-link :href="route('orcamentos.pedidos.index')" label="Voltar" />
                <a href="{{ route('orcamentos.orcamentos.show', $pedido->orcamento) }}" class="rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white">Ver orçamento</a>
                <a href="{{ route('orcamentos.pedidos.pdf', $pedido) }}" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Exportar PDF</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="rounded-md bg-green-50 p-4 text-green-800">{{ session('success') }}</div>
            @endif

            <div class="grid gap-6 bg-white p-6 shadow-sm sm:rounded-lg md:grid-cols-4">
                <div><span class="text-xs text-gray-500">Empresa</span><p>{{ $pedido->empresa->razao_social }}</p><p>{{ $pedido->empresa->cnpj }}</p></div>
                <div><span class="text-xs text-gray-500">Cliente</span><p>{{ $pedido->cliente->nome_razao_social }}</p><p>{{ $pedido->cliente->telefone }} · {{ $pedido->cliente->email }}</p></div>
                <div><span class="text-xs text-gray-500">Responsável</span><p>{{ $pedido->vendedor->name }}</p><p>{{ $pedido->vendedor->cargo }}</p></div>
                <div><span class="text-xs text-gray-500">Pedido</span><p>{{ ucfirst($pedido->status) }}</p><p>{{ $pedido->data_pedido->format('d/m/Y') }}</p><p>{{ $pedido->orcamento->finalidade?->label() ?? 'Revenda' }}</p></div>
            </div>

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left">Item</th>
                            <th class="px-6 py-3 text-right">Qtd.</th>
                            <th class="px-6 py-3 text-right">Preço-base</th>
                            <th class="px-6 py-3 text-right">Desconto</th>
                            <th class="px-6 py-3 text-right">Produtos</th>
                            <th class="px-6 py-3 text-right">Impostos</th>
                            <th class="px-6 py-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($pedido->orcamento->itens as $linha)
                            <tr>
                                <td class="px-6 py-4">
                                    <strong>{{ $linha->descricao }}</strong>
                                    <div class="mt-1 text-xs text-gray-500">
                                        {{ $linha->tipo_venda === 'metro' ? 'Metro' : 'Unitário' }} ·
                                        Embalagem {{ number_format((float) ($linha->embalagem_quantidade ?? 1), 3, ',', '.') }} {{ $linha->tipo_venda === 'metro' ? 'm' : 'un.' }}
                                    </div>
                                    <div class="mt-1 text-xs text-gray-500">
                                        @if($linha->ncm) NCM {{ $linha->ncm }} · @endif
                                        ICMS {{ number_format((float) $linha->aliquota_icms, 2, ',', '.') }}% ·
                                        IPI {{ number_format((float) $linha->aliquota_ipi, 2, ',', '.') }}% ·
                                        PIS {{ number_format((float) $linha->aliquota_pis, 2, ',', '.') }}% ·
                                        COFINS {{ number_format((float) $linha->aliquota_cofins, 2, ',', '.') }}%
                                        @if($linha->possui_st) · ST/MVA {{ number_format((float) $linha->mva, 2, ',', '.') }}%@endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">{{ $linha->quantidade }}</td>
                                <td class="px-6 py-4 text-right">R$ {{ number_format((float) $linha->valor_unitario, 2, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right">{{ number_format((float) $linha->desconto_percentual, 2, ',', '.') }}%</td>
                                <td class="px-6 py-4 text-right">R$ {{ number_format((float) $linha->valor_produtos, 2, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right">R$ {{ number_format((float) $linha->valor_impostos, 2, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right font-semibold">R$ {{ number_format((float) $linha->valor_total, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="grid gap-4 bg-indigo-50 p-6 sm:rounded-lg md:grid-cols-5">
                <div>Subtotal-base<br><strong>R$ {{ number_format((float) $pedido->subtotal_sem_desconto, 2, ',', '.') }}</strong></div>
                <div>Desconto efetivo<br><strong>{{ number_format((float) $pedido->desconto_efetivo_percentual, 2, ',', '.') }}%</strong></div>
                <div>Produtos<br><strong>R$ {{ number_format((float) $pedido->total_produtos, 2, ',', '.') }}</strong></div>
                <div>Impostos<br><strong>R$ {{ number_format((float) $pedido->total_impostos, 2, ',', '.') }}</strong></div>
                <div>Total<br><strong>R$ {{ number_format((float) $pedido->valor_total, 2, ',', '.') }}</strong></div>
            </div>
        </div>
    </div>
</x-app-layout>

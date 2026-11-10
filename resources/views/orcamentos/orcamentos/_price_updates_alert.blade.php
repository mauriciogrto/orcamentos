@if(($alteracoesItens ?? collect())->isNotEmpty())
    <div class="rounded-lg border border-amber-300 bg-amber-50 p-5 text-sm text-amber-900">
        <h3 class="font-semibold">Itens com preço atualizado no cadastro</h3>
        <p class="mt-1">
            Este orçamento foi montado com o preço antigo. Ao virar pedido, o sistema usará automaticamente o preço atual do cadastro.
        </p>

        <div class="mt-4 overflow-hidden rounded-md border border-amber-200 bg-white">
            <table class="min-w-full divide-y divide-amber-100">
                <thead class="bg-amber-100/70 text-xs uppercase tracking-wide text-amber-950">
                    <tr>
                        <th class="px-4 py-2 text-left">Item</th>
                        <th class="px-4 py-2 text-right">No orçamento</th>
                        <th class="px-4 py-2 text-right">Atual</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-amber-100">
                    @foreach($alteracoesItens as $alteracao)
                        <tr>
                            <td class="px-4 py-3">
                                <span class="font-medium">{{ $alteracao['descricao_orcamento'] }}</span>
                                @if($alteracao['descricao_orcamento'] !== $alteracao['descricao_atual'])
                                    <span class="block text-xs text-amber-700">Agora no cadastro: {{ $alteracao['descricao_atual'] }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">R$ {{ number_format((float) $alteracao['preco_orcamento'], 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right font-semibold">R$ {{ number_format((float) $alteracao['preco_atual'], 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<x-app-layout>
    @php
        $isSuperAdmin = auth()->user()->hasRole('super_admin');
    @endphp
    <x-slot name="header">
        <div class="flex justify-between">
            <div>
                <h2 class="text-xl font-semibold">{{ $orcamento->numero }}</h2>
                <p class="text-sm text-gray-600">{{ $orcamento->status->label() }}</p>
            </div>

            <div class="flex flex-wrap gap-3">
                <x-back-link :href="route('orcamentos.orcamentos.index')" label="Voltar" />
                @can('view', $orcamento)
                    <a href="{{ route('orcamentos.orcamentos.pdf', $orcamento) }}" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Exportar PDF</a>
                @endcan

                @unless($isSuperAdmin)
                    @can('update', $orcamento)
                        <a href="{{ route('orcamentos.orcamentos.edit', $orcamento) }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm text-white">Editar</a>
                    @endcan
                @endunless

                @if(! $isSuperAdmin && $orcamento->status === \App\Enums\OrcamentoStatus::Rascunho)
                    @can('transition', $orcamento)
                        <form method="POST" action="{{ route('orcamentos.orcamentos.send', $orcamento) }}" data-confirm-message="Confirma o envio manual deste orçamento?">
                            @csrf
                            <input type="hidden" name="confirmacao" value="1">
                            <button class="rounded-md bg-green-600 px-4 py-2 text-sm text-white">Marcar como enviado</button>
                        </form>
                    @endcan
                @endif

                @if(! $isSuperAdmin && $orcamento->status === \App\Enums\OrcamentoStatus::Enviado)
                    @can('transition', $orcamento)
                        <form method="POST" action="{{ route('orcamentos.orcamentos.approve', $orcamento) }}" data-confirm-message="Confirma que este orçamento foi aprovado?">
                            @csrf
                            <input type="hidden" name="confirmacao" value="1">
                            <button class="rounded-md bg-green-700 px-4 py-2 text-sm text-white">Marcar aprovado</button>
                        </form>
                        <form method="POST" action="{{ route('orcamentos.orcamentos.reject', $orcamento) }}" data-confirm-message="Confirma que este orçamento foi rejeitado?">
                            @csrf
                            <input type="hidden" name="confirmacao" value="1">
                            <button class="rounded-md bg-red-700 px-4 py-2 text-sm text-white">Marcar rejeitado</button>
                        </form>
                    @endcan
                @endif

                @if(! $isSuperAdmin && $orcamento->status === \App\Enums\OrcamentoStatus::Expirado)
                    @can('renew', $orcamento)
                        <form method="POST" action="{{ route('orcamentos.orcamentos.renew', $orcamento) }}" data-confirm-message="Renovar este orçamento, atualizar a emissão para hoje e voltar a rascunho?">
                            @csrf
                            <input type="hidden" name="confirmacao" value="1">
                            <button class="rounded-md bg-amber-600 px-4 py-2 text-sm text-white">Renovar orçamento</button>
                        </form>
                    @endcan
                @endif

                @if(! $isSuperAdmin && in_array($orcamento->status, [\App\Enums\OrcamentoStatus::Rascunho, \App\Enums\OrcamentoStatus::Enviado, \App\Enums\OrcamentoStatus::Expirado], true))
                    @can('transition', $orcamento)
                        <form method="POST" action="{{ route('orcamentos.orcamentos.finish', $orcamento) }}" data-confirm-message="Finalizar este orçamento? Ele ficará fechado para edição normal.">
                            @csrf
                            <input type="hidden" name="confirmacao" value="1">
                            <button class="rounded-md bg-slate-700 px-4 py-2 text-sm text-white">Finalizar orçamento</button>
                        </form>
                    @endcan
                @endif

                @if($orcamento->pedido)
                    @can('view', $orcamento->pedido)
                        <a href="{{ route('orcamentos.pedidos.show', $orcamento->pedido) }}" class="rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white">Ver pedido</a>
                    @endcan
                @elseif(! $isSuperAdmin && $orcamento->status !== \App\Enums\OrcamentoStatus::Rejeitado)
                    @can('convertToPedido', $orcamento)
                        <form method="POST" action="{{ route('orcamentos.orcamentos.convert-to-pedido', $orcamento) }}" data-confirm-message="Virar este orçamento em pedido? O orçamento será finalizado e o pedido aparecerá na aba Pedidos.">
                            @csrf
                            <input type="hidden" name="confirmacao" value="1">
                            <button class="rounded-md bg-blue-700 px-4 py-2 text-sm text-white">Virar pedido</button>
                        </form>
                    @endcan
                @endif

                @unless($isSuperAdmin)
                    @can('create', App\Models\Orcamento::class)
                        <form method="POST" action="{{ route('orcamentos.orcamentos.duplicate', $orcamento) }}">
                            @csrf
                            <button class="rounded-md bg-gray-800 px-4 py-2 text-sm text-white">Duplicar</button>
                        </form>
                    @endcan
                @endunless
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="rounded-md bg-green-50 p-4 text-green-800">{{ session('success') }}</div>
            @endif

            @include('orcamentos.orcamentos._price_updates_alert')

            <div class="grid gap-6 bg-white p-6 shadow-sm sm:rounded-lg md:grid-cols-3">
                <div><span class="text-xs text-gray-500">Empresa</span><p>{{ $orcamento->empresa->razao_social }}</p><p>{{ $orcamento->empresa->cnpj }}</p></div>
                <div><span class="text-xs text-gray-500">Cliente</span><p>{{ $orcamento->cliente->nome_razao_social }}</p><p>{{ $orcamento->cliente->telefone }} · {{ $orcamento->cliente->email }}</p></div>
                <div><span class="text-xs text-gray-500">Responsável</span><p>{{ $orcamento->vendedor->name }}</p><p>{{ $orcamento->vendedor->cargo }}</p></div>
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
                        @foreach($orcamento->itens as $linha)
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
                <div>Subtotal-base<br><strong>R$ {{ number_format((float) $orcamento->subtotal_sem_desconto, 2, ',', '.') }}</strong></div>
                <div>Desconto efetivo<br><strong>{{ number_format((float) $orcamento->desconto_efetivo_percentual, 2, ',', '.') }}%</strong></div>
                <div>Produtos<br><strong>R$ {{ number_format((float) $orcamento->total_produtos, 2, ',', '.') }}</strong></div>
                <div>Impostos<br><strong>R$ {{ number_format((float) $orcamento->total_impostos, 2, ',', '.') }}</strong></div>
                <div>Total<br><strong>R$ {{ number_format((float) $orcamento->valor_total, 2, ',', '.') }}</strong></div>
            </div>

            @role('admin_empresa')
                <div class="grid gap-4 bg-emerald-50 p-6 sm:rounded-lg md:grid-cols-2">
                    <div>Comissão<br><strong>{{ number_format((float) $orcamento->comissao_percentual, 2, ',', '.') }}%</strong></div>
                    <div>Valor da comissão<br><strong>R$ {{ number_format((float) $orcamento->valor_comissao, 2, ',', '.') }}</strong></div>
                </div>
            @endrole

            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <p><strong>Pagamento:</strong> {{ $orcamento->forma_pagamento->label() }} @if($orcamento->boleto_prazos)({{ $orcamento->boleto_prazos }} dias)@endif</p>
                <p><strong>Finalidade:</strong> {{ $orcamento->finalidade?->label() ?? 'Revenda' }}</p>
                <p><strong>Validade:</strong> {{ $orcamento->validade_dias }} dias</p>
                <p><strong>Frete:</strong> {{ $orcamento->frete_tipo->label() }}</p>
                @if($orcamento->observacoes)
                    <p class="mt-3"><strong>Observações:</strong> {{ $orcamento->observacoes }}</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

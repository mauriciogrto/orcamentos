<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold">Pré-validação NF-e</h2>
                <p class="mt-1 text-sm text-gray-600">Pedido {{ $pedido->numero }} · Orçamento {{ $pedido->orcamento?->numero }}</p>
            </div>
            <x-back-link :href="route('orcamentos.pedidos.show', $pedido)" label="Voltar ao pedido" />
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="rounded-md bg-green-50 p-4 text-green-800">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-red-700">
                    @foreach($errors->all() as $erro)
                        <p>{{ $erro }}</p>
                    @endforeach
                </div>
            @endif

            <div class="grid gap-4 md:grid-cols-4">
                <div class="rounded-xl bg-white p-5 shadow-sm">
                    <p class="text-xs uppercase text-slate-500">Status</p>
                    <p class="mt-2 text-lg font-bold {{ $resultado['ok'] ? 'text-emerald-700' : 'text-red-700' }}">{{ $resultado['ok'] ? 'Apto para confirmar' : 'Com pendências' }}</p>
                </div>
                <div class="rounded-xl bg-white p-5 shadow-sm">
                    <p class="text-xs uppercase text-slate-500">Produtos</p>
                    <p class="mt-2 text-lg font-bold">R$ {{ number_format((float) $resultado['pedido']['total_produtos'], 2, ',', '.') }}</p>
                </div>
                <div class="rounded-xl bg-white p-5 shadow-sm">
                    <p class="text-xs uppercase text-slate-500">Impostos</p>
                    <p class="mt-2 text-lg font-bold">R$ {{ number_format((float) $resultado['pedido']['total_impostos'], 2, ',', '.') }}</p>
                </div>
                <div class="rounded-xl bg-white p-5 shadow-sm">
                    <p class="text-xs uppercase text-slate-500">Total</p>
                    <p class="mt-2 text-lg font-bold">R$ {{ number_format((float) $resultado['pedido']['valor_total'], 2, ',', '.') }}</p>
                </div>
            </div>

            @if($resultado['erros'])
                <section class="rounded-lg border border-red-200 bg-red-50 p-5">
                    <h3 class="font-semibold text-red-900">Pendências que bloqueiam a NF-e</h3>
                    <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-red-800">
                        @foreach($resultado['erros'] as $erro)
                            <li>{{ $erro }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <div class="flex flex-wrap gap-3">
                <form method="POST" action="{{ route('fiscal.notas.validar-ncms', $pedido) }}">
                    @csrf
                    <x-secondary-button>Consultar/validar NCMs</x-secondary-button>
                </form>

                <form method="POST" action="{{ route('fiscal.notas.confirmar-precheck', $pedido) }}">
                    @csrf
                    <x-primary-button @disabled(! $resultado['ok'])>Confirmar pré-validação fiscal</x-primary-button>
                </form>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                @foreach(['empresa' => 'Empresa emitente', 'cliente' => 'Cliente destinatário'] as $grupo => $titulo)
                    <section class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div class="border-b border-slate-100 p-5">
                            <h3 class="font-semibold text-slate-900">{{ $titulo }}</h3>
                        </div>
                        <div class="divide-y">
                            @foreach($resultado[$grupo] as $check)
                                <div class="flex items-start justify-between gap-4 p-4">
                                    <div>
                                        <p class="font-medium text-slate-800">{{ $check['label'] }}</p>
                                        <p class="text-sm text-slate-600">{{ $check['value'] }}</p>
                                    </div>
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $check['ok'] ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                                        {{ $check['ok'] ? 'OK' : 'Pendente' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>

            <section class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="border-b border-slate-100 p-5">
                    <h3 class="font-semibold text-slate-900">Itens e tributação para conferência</h3>
                    <p class="mt-1 text-sm text-slate-600">O usuário/contador deve validar se o NCM e os dados fiscais realmente correspondem ao produto.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left">Item</th>
                                <th class="px-4 py-3 text-left">NCM</th>
                                <th class="px-4 py-3 text-left">CFOP/CST</th>
                                <th class="px-4 py-3 text-right">Impostos</th>
                                <th class="px-4 py-3 text-left">Pendências</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($resultado['itens'] as $item)
                                <tr class="align-top">
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-slate-900">{{ $item['descricao'] }}</p>
                                        <p class="text-xs text-slate-500">Cód.: {{ $item['codigo'] ?: '-' }} · Qtd. {{ $item['quantidade'] }} {{ $item['unidade_comercial'] }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-mono">{{ $item['ncm'] ?: '-' }}</p>
                                        <p class="text-xs {{ $item['ncm_status'] === 'ativo' ? 'text-emerald-700' : 'text-slate-500' }}">
                                            {{ $item['ncm_status'] ? ucfirst(str_replace('_', ' ', $item['ncm_status'])) : 'Não consultado' }}
                                            @if($item['ncm_validado_em'])
                                                · {{ $item['ncm_validado_em'] }}
                                            @endif
                                        </p>
                                        @if($item['ncm_descricao_oficial'])
                                            <p class="mt-1 max-w-xs text-xs text-slate-600">{{ $item['ncm_descricao_oficial'] }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <p>CFOP: {{ $item['cfop'] ?: '-' }}</p>
                                        <p>CST/CSOSN: {{ $item['cst_csosn'] ?: '-' }}</p>
                                        <p>CEST: {{ $item['cest'] ?: '-' }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <p>ICMS {{ number_format((float) $item['icms'], 2, ',', '.') }}%</p>
                                        <p>IPI {{ number_format((float) $item['ipi'], 2, ',', '.') }}%</p>
                                        <p>PIS {{ number_format((float) $item['pis'], 2, ',', '.') }}%</p>
                                        <p>COFINS {{ number_format((float) $item['cofins'], 2, ',', '.') }}%</p>
                                        @if($item['possui_st'])
                                            <p>ST/MVA {{ number_format((float) $item['mva'], 2, ',', '.') }}%</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($item['pendencias'])
                                            <ul class="list-disc space-y-1 pl-4 text-xs text-red-700">
                                                @foreach($item['pendencias'] as $pendencia)
                                                    <li>{{ $pendencia }}</li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">OK</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>

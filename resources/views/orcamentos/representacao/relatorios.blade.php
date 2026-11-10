<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Relatórios de Comissão</h2>
                <p class="mt-1 text-sm text-gray-600">Confira o cálculo mensal na tela e gere o PDF quando estiver correto.</p>
            </div>
            <x-back-link :href="route('orcamentos.representacao.index')" label="Voltar" />
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <form id="filtro-comissao-form" method="GET" action="{{ route('orcamentos.representacao.relatorios') }}" class="rounded-2xl bg-white p-6 shadow-sm">
                @if($isSuperAdmin)
                    <input type="hidden" name="empresa_id" value="{{ request('empresa_id', $empresa->id) }}">
                @endif

                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Filtro do relatório</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Ao alterar mês ou vendedor, os dados abaixo são atualizados automaticamente. O PDF continua sendo gerado manualmente.
                    </p>
                </div>

                <div class="mt-6 grid gap-5 md:grid-cols-4 md:items-end">
                    <div>
                        <x-input-label for="mes" value="Mês de referência" />
                        <x-text-input id="mes" name="mes" type="month" class="mt-1 block w-full" :value="request('mes', $periodo['mes'])" data-auto-filter required />
                        <x-input-error :messages="$errors->get('mes')" class="mt-2" />
                    </div>

                    <div class="md:col-span-2">
                        <x-input-label for="vendedor_id" value="Vendedor/responsável" />
                        <select id="vendedor_id" name="vendedor_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" data-auto-filter>
                            <option value="">Todos os responsáveis</option>
                            @foreach($vendedores as $vendedor)
                                <option value="{{ $vendedor->id }}" @selected((string) request('vendedor_id') === (string) $vendedor->id)>
                                    {{ $vendedor->name }} @if($vendedor->cargo)— {{ $vendedor->cargo }}@endif
                                    — {{ $vendedor->pedidos_no_periodo }} {{ $vendedor->pedidos_no_periodo === 1 ? 'pedido no mês' : 'pedidos no mês' }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">O filtro usa o responsável gravado no pedido finalizado.</p>
                        <x-input-error :messages="$errors->get('vendedor_id')" class="mt-2" />
                    </div>

                    <div class="rounded-xl border border-slate-700 bg-slate-900 p-3 shadow-sm">
                        <span class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-200">PDF do relatório</span>
                        <button id="aplicar-filtro" type="submit" class="inline-flex w-full items-center justify-center rounded-md border border-slate-500 bg-slate-800 px-5 py-2 text-xs font-semibold text-slate-100 shadow-sm transition hover:bg-slate-700">
                            Atualizar agora
                        </button>
                        <a
                            href="{{ route('orcamentos.representacao.comissoes.pdf', array_filter(['empresa_id' => $isSuperAdmin ? request('empresa_id', $empresa->id) : null, 'mes' => $periodo['mes'], 'vendedor_id' => request('vendedor_id')], fn ($value) => filled($value))) }}"
                            target="_blank"
                            class="mt-2 inline-flex w-full items-center justify-center rounded-md bg-emerald-500 px-5 py-2.5 text-sm font-semibold text-slate-950 shadow-sm transition hover:bg-emerald-400"
                        >
                            Gerar PDF
                        </a>
                        @if(request()->filled('mes') || request()->filled('vendedor_id'))
                            <a href="{{ route('orcamentos.representacao.relatorios', $isSuperAdmin ? ['empresa_id' => request('empresa_id', $empresa->id)] : []) }}" class="mt-2 block text-center text-xs font-semibold text-slate-200 hover:text-white">
                                Limpar filtros
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="rounded-2xl bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-1 border-b border-gray-100 pb-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Resumo do período</h3>
                        <p class="text-sm text-gray-600">
                            {{ $vendedorSelecionado?->name ? 'Filtro aplicado para '.$vendedorSelecionado->name : 'Todos os responsáveis' }}
                            · {{ $periodo['inicio']->format('m/Y') }}
                        </p>
                    </div>
                    <div class="text-left sm:text-right">
                        <span class="text-xs font-semibold uppercase text-gray-500">Comissão calculada</span>
                        <p class="text-3xl font-bold text-emerald-700">R$ {{ number_format((float) $totalizador['valor_comissao'], 2, ',', '.') }}</p>
                        <p class="text-sm font-semibold text-emerald-700">{{ number_format((float) $totalizador['comissao_percentual'], 2, ',', '.') }}%</p>
                    </div>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-4">
                    <div class="rounded-xl border border-gray-100 bg-slate-50 p-4">
                        <span class="text-xs font-semibold uppercase text-gray-500">Pedidos</span>
                        <p class="mt-2 text-2xl font-bold text-slate-900">{{ $totalizador['quantidade'] }}</p>
                    </div>
                    <div class="rounded-xl border border-gray-100 bg-slate-50 p-4">
                        <span class="text-xs font-semibold uppercase text-gray-500">Vendido sem impostos</span>
                        <p class="mt-2 text-2xl font-bold text-slate-900">R$ {{ number_format((float) $totalizador['base_comissao'], 2, ',', '.') }}</p>
                    </div>
                    <div class="rounded-xl border border-gray-100 bg-slate-50 p-4">
                        <span class="text-xs font-semibold uppercase text-gray-500">Desconto médio</span>
                        <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format((float) $totalizador['desconto_medio'], 2, ',', '.') }}%</p>
                    </div>
                    <div class="rounded-xl border border-gray-100 bg-slate-50 p-4">
                        <span class="text-xs font-semibold uppercase text-gray-500">Faixa aplicada</span>
                        <p class="mt-2 text-2xl font-bold text-slate-900">
                            @if($totalizador['faixa'])
                                {{ number_format((float) $totalizador['faixa']->desconto_inicial_percentual, 2, ',', '.') }}%
                                a
                                {{ number_format((float) $totalizador['faixa']->desconto_final_percentual, 2, ',', '.') }}%
                            @else
                                —
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Resumo por vendedor</h3>
                <p class="mt-1 text-sm text-gray-600">
                    Exemplo de leitura: no mês selecionado, o vendedor vendeu R$ X sem impostos; o desconto médio entrou em uma faixa cadastrada e gerou a comissão exibida.
                </p>

                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full divide-y text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left">Vendedor</th>
                                <th class="px-4 py-3 text-right">Pedidos</th>
                                <th class="px-4 py-3 text-right">Vendido sem impostos</th>
                                <th class="px-4 py-3 text-right">Desconto médio</th>
                                <th class="px-4 py-3 text-right">Faixa</th>
                                <th class="px-4 py-3 text-right">Comissão %</th>
                                <th class="px-4 py-3 text-right">Comissão R$</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse($resumoPorVendedor as $linha)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ $linha['vendedor']?->name ?? 'Usuário removido' }}</td>
                                    <td class="px-4 py-3 text-right">{{ $linha['quantidade'] }}</td>
                                    <td class="px-4 py-3 text-right">R$ {{ number_format((float) $linha['base_comissao'], 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right">{{ number_format((float) $linha['desconto_medio'], 2, ',', '.') }}%</td>
                                    <td class="px-4 py-3 text-right">
                                        @if($linha['faixa'])
                                            {{ number_format((float) $linha['faixa']->desconto_inicial_percentual, 2, ',', '.') }}%
                                            a
                                            {{ number_format((float) $linha['faixa']->desconto_final_percentual, 2, ',', '.') }}%
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">{{ number_format((float) $linha['comissao_percentual'], 2, ',', '.') }}%</td>
                                    <td class="px-4 py-3 text-right font-semibold text-emerald-700">R$ {{ number_format((float) $linha['valor_comissao'], 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-6 py-12 text-center text-gray-500">Nenhum pedido encontrado para o filtro selecionado.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Pedidos considerados</h3>
                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full divide-y text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left">Data</th>
                                <th class="px-4 py-3 text-left">Pedido</th>
                                <th class="px-4 py-3 text-left">Cliente</th>
                                <th class="px-4 py-3 text-left">Vendedor</th>
                                <th class="px-4 py-3 text-right">Sem impostos</th>
                                <th class="px-4 py-3 text-right">Desconto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse($pedidos as $pedido)
                                <tr>
                                    <td class="px-4 py-3">{{ $pedido->data_pedido->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3">{{ $pedido->numero }}</td>
                                    <td class="px-4 py-3">{{ $pedido->cliente?->nome_fantasia ?: $pedido->cliente?->nome_razao_social }}</td>
                                    <td class="px-4 py-3">{{ $pedido->vendedor?->name ?? 'Usuário removido' }}</td>
                                    <td class="px-4 py-3 text-right">R$ {{ number_format((float) $pedido->total_produtos, 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right">{{ number_format((float) $pedido->desconto_efetivo_percentual, 2, ',', '.') }}%</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-6 py-12 text-center text-gray-500">Nenhum pedido encontrado para o filtro selecionado.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('filtro-comissao-form');
            const submitButton = document.getElementById('aplicar-filtro');

            if (!form) {
                return;
            }

            let timer = null;

            form.querySelectorAll('[data-auto-filter]').forEach((field) => {
                field.addEventListener('change', () => {
                    clearTimeout(timer);

                    if (submitButton) {
                        submitButton.textContent = 'Atualizando...';
                        submitButton.disabled = true;
                    }

                    timer = setTimeout(() => form.requestSubmit(), 250);
                });
            });
        });
    </script>
</x-app-layout>

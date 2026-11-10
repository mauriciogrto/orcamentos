<x-app-layout>
    @php
        $isSuperAdmin = auth()->user()->hasRole('super_admin');
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold">Orçamentos</h2>
            @unless($isSuperAdmin)
                @can('create', App\Models\Orcamento::class)
                    <a href="{{ route('orcamentos.orcamentos.create') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Novo orçamento</a>
                @endcan
            @endunless
        </div>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 rounded-md bg-green-50 p-4 text-green-800">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-6 rounded-md bg-red-50 p-4 text-red-800">{{ session('error') }}</div>
            @endif

            <form method="GET" action="{{ route('orcamentos.orcamentos.index') }}" class="mb-6 grid gap-4 rounded-lg bg-white p-5 shadow-sm md:grid-cols-4">
                <div>
                    <x-input-label for="numero" value="Número" />
                    <x-text-input id="numero" name="numero" class="mt-1 block w-full" :value="request('numero')" placeholder="Ex.: 2026-001" />
                </div>
                <div>
                    <x-input-label for="cliente" value="Cliente" />
                    <x-text-input id="cliente" name="cliente" class="mt-1 block w-full" :value="request('cliente')" placeholder="Nome fantasia ou CNPJ" />
                </div>
                <div>
                    <x-input-label for="situacao" value="Situação" />
                    <select id="situacao" name="situacao" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">Todas</option>
                        <option value="aberto" @selected(request('situacao') === 'aberto')>Aberto</option>
                        <option value="finalizado" @selected(request('situacao') === 'finalizado' || request()->boolean('finalizados'))>Finalizado</option>
                    </select>
                </div>
                <div class="flex items-end gap-3">
                    <x-primary-button>Filtrar</x-primary-button>
                    <a href="{{ route('orcamentos.orcamentos.index') }}" class="pb-2 text-sm text-gray-600">Limpar</a>
                </div>
            </form>

            @if($errors->any())
                <div class="mb-6 rounded-md bg-red-50 p-4 text-red-700">
                    <p class="font-semibold">Não foi possível concluir a ação.</p>
                    <ul class="mt-2 list-disc pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="space-y-3 md:hidden">
                @forelse($orcamentos as $orcamento)
                    @php
                        $numeroCurto = preg_replace('/^ORC-/', '', $orcamento->numero);
                        $clienteNome = $orcamento->cliente->nome_fantasia ?: $orcamento->cliente->nome_razao_social;
                        $statusVisual = ($orcamento->pedido || $orcamento->status === \App\Enums\OrcamentoStatus::Finalizado) ? 'Finalizado' : 'Aberto';
                        $precosDesatualizados = $orcamento->itens->contains(fn($linha) => $linha->item && round((float) $linha->valor_unitario, 2) !== round((float) $linha->item->preco_atual, 2));
                    @endphp
                    <article class="rounded-xl bg-white p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <a class="text-base font-bold text-indigo-700" href="{{ route('orcamentos.orcamentos.show', $orcamento) }}">{{ $numeroCurto }}</a>
                                <p class="mt-1 text-sm font-medium text-slate-800">{{ $clienteNome }}</p>
                                @if(auth()->user()->can('orcamentos.visualizar_todos'))
                                    <p class="mt-1 text-xs text-slate-500">Responsável: {{ $orcamento->vendedor->name }}</p>
                                @endif
                            </div>
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusVisual === 'Finalizado' ? 'bg-slate-100 text-slate-700' : 'bg-emerald-50 text-emerald-700' }}">
                                {{ $statusVisual }}
                            </span>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-2">
                            @can('view', $orcamento)
                                <a class="inline-flex items-center justify-center rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800 ring-1 ring-inset ring-emerald-200" href="{{ route('orcamentos.orcamentos.pdf', $orcamento) }}">PDF</a>
                                @if($precosDesatualizados)
                                    <span class="inline-flex items-center justify-center rounded-lg bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800 ring-1 ring-inset ring-amber-200">Preço antigo</span>
                                @endif
                            @endcan

                            @if($orcamento->pedido)
                                @can('view', $orcamento->pedido)
                                    <a class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white" href="{{ route('orcamentos.pedidos.show', $orcamento->pedido) }}">Ver pedido</a>
                                @endcan
                            @elseif(! $isSuperAdmin && $orcamento->status->value !== \App\Enums\OrcamentoStatus::Rejeitado->value)
                                @can('convertToPedido', $orcamento)
                                    <form method="POST" action="{{ route('orcamentos.orcamentos.convert-to-pedido', $orcamento) }}" data-confirm-message="Virar este orçamento em pedido? O orçamento será finalizado e o pedido aparecerá na aba Pedidos.">
                                        @csrf
                                        <input type="hidden" name="confirmacao" value="1">
                                        <button class="w-full rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white">Virar pedido</button>
                                    </form>
                                @endcan
                            @endif

                            @unless($isSuperAdmin)
                                @can('update', $orcamento)
                                    <a class="inline-flex items-center justify-center rounded-lg bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-800 ring-1 ring-inset ring-indigo-200" href="{{ route('orcamentos.orcamentos.edit', $orcamento) }}">Editar</a>
                                @endcan

                                @unless($orcamento->pedido)
                                    @can('delete', $orcamento)
                                        <form method="POST" action="{{ route('orcamentos.orcamentos.destroy', $orcamento) }}" data-confirm-message="Confirma a exclusão deste orçamento?">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="confirmacao" value="1">
                                            <button class="w-full rounded-lg bg-red-50 px-3 py-2 text-xs font-semibold text-red-800 ring-1 ring-inset ring-red-200">Excluir</button>
                                        </form>
                                    @endcan
                                @endunless
                            @endunless
                        </div>
                    </article>
                @empty
                    <div class="rounded-xl bg-white px-6 py-12 text-center text-gray-500 shadow-sm">Nenhum orçamento encontrado.</div>
                @endforelse
            </div>

            <div class="hidden overflow-hidden bg-white shadow-sm sm:rounded-lg md:block">
                <table class="min-w-full divide-y text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">Nº</th>
                            @if($isSuperAdmin)
                                <th class="px-4 py-3 text-left">Empresa</th>
                            @endif
                            <th class="px-4 py-3 text-left">Cliente</th>
                            @if(auth()->user()->can('orcamentos.visualizar_todos'))
                                <th class="px-4 py-3 text-left">Responsável</th>
                            @endif
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($orcamentos as $orcamento)
                            @php
                                $numeroCurto = preg_replace('/^ORC-/', '', $orcamento->numero);
                                $clienteNome = $orcamento->cliente->nome_fantasia ?: $orcamento->cliente->nome_razao_social;
                                $statusVisual = ($orcamento->pedido || $orcamento->status === \App\Enums\OrcamentoStatus::Finalizado) ? 'Finalizado' : 'Aberto';
                                $precosDesatualizados = $orcamento->itens->contains(fn($linha) => $linha->item && round((float) $linha->valor_unitario, 2) !== round((float) $linha->item->preco_atual, 2));
                            @endphp
                            <tr class="align-middle">
                                <td class="px-4 py-3">
                                    <a class="font-medium text-indigo-600" href="{{ route('orcamentos.orcamentos.show', $orcamento) }}">{{ $numeroCurto }}</a>
                                </td>
                                @if($isSuperAdmin)
                                    <td class="px-4 py-3">{{ $orcamento->empresa?->nome_fantasia ?: $orcamento->empresa?->razao_social }}</td>
                                @endif
                                <td class="px-4 py-3">
                                    <span class="font-medium text-slate-800">{{ $clienteNome }}</span>
                                </td>
                                @if(auth()->user()->can('orcamentos.visualizar_todos'))
                                    <td class="px-4 py-3">{{ $orcamento->vendedor->name }}</td>
                                @endif
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusVisual === 'Finalizado' ? 'bg-slate-100 text-slate-700' : 'bg-emerald-50 text-emerald-700' }}">
                                        {{ $statusVisual }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center justify-end gap-1 rounded-xl border border-slate-200 bg-slate-50 p-1 shadow-sm">
                                        @can('view', $orcamento)
                                            <a class="inline-flex items-center rounded-lg bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-800 ring-1 ring-inset ring-emerald-200 transition hover:bg-emerald-100" href="{{ route('orcamentos.orcamentos.pdf', $orcamento) }}">PDF</a>
                                            @if($precosDesatualizados)
                                                <span class="inline-flex items-center rounded-lg bg-amber-50 px-2.5 py-1.5 text-xs font-semibold text-amber-800 ring-1 ring-inset ring-amber-200">Preço antigo</span>
                                            @endif
                                        @endcan

                                        @if($orcamento->pedido)
                                            @can('view', $orcamento->pedido)
                                                <a class="inline-flex items-center rounded-lg bg-blue-600 px-2.5 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700" href="{{ route('orcamentos.pedidos.show', $orcamento->pedido) }}">Ver pedido</a>
                                            @endcan
                                        @elseif(! $isSuperAdmin && $orcamento->status->value !== \App\Enums\OrcamentoStatus::Rejeitado->value)
                                            @can('convertToPedido', $orcamento)
                                                <form method="POST" action="{{ route('orcamentos.orcamentos.convert-to-pedido', $orcamento) }}" data-confirm-message="Virar este orçamento em pedido? O orçamento será finalizado e o pedido aparecerá na aba Pedidos.">
                                                    @csrf
                                                    <input type="hidden" name="confirmacao" value="1">
                                                    <button class="inline-flex items-center rounded-lg bg-blue-600 px-2.5 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700">Virar pedido</button>
                                                </form>
                                            @endcan
                                        @endif

                                        @unless($isSuperAdmin)
                                            @can('update', $orcamento)
                                                <a class="inline-flex items-center rounded-lg bg-indigo-50 px-2.5 py-1.5 text-xs font-semibold text-indigo-800 ring-1 ring-inset ring-indigo-200 transition hover:bg-indigo-100" href="{{ route('orcamentos.orcamentos.edit', $orcamento) }}">Editar</a>
                                            @endcan

                                            @unless($orcamento->pedido)
                                                @can('delete', $orcamento)
                                                    <form method="POST" action="{{ route('orcamentos.orcamentos.destroy', $orcamento) }}" data-confirm-message="Confirma a exclusão deste orçamento?">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="confirmacao" value="1">
                                                        <button class="inline-flex items-center rounded-lg bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-800 ring-1 ring-inset ring-red-200 transition hover:bg-red-100">Excluir</button>
                                                    </form>
                                                @endcan
                                            @endunless
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ (auth()->user()->can('orcamentos.visualizar_todos') ? 5 : 4) + ($isSuperAdmin ? 1 : 0) }}" class="px-6 py-12 text-center text-gray-500">Nenhum orçamento encontrado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $orcamentos->links() }}</div>
        </div>
    </div>
</x-app-layout>

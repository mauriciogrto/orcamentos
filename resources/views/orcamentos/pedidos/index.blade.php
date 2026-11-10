<x-app-layout>
    @php
        $isSuperAdmin = auth()->user()->hasRole('super_admin');
    @endphp

    <x-slot name="header">
        <h2 class="text-xl font-semibold">Pedidos</h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 rounded-md bg-green-50 p-4 text-green-800">{{ session('success') }}</div>
            @endif

            <form method="GET" action="{{ route('orcamentos.pedidos.index') }}" class="mb-6 grid gap-4 rounded-lg bg-white p-5 shadow-sm md:grid-cols-3">
                <div>
                    <x-input-label for="numero" value="Número do pedido" />
                    <x-text-input id="numero" name="numero" class="mt-1 block w-full" :value="request('numero')" placeholder="Ex.: 2026-001" />
                </div>
                <div>
                    <x-input-label for="cliente" value="Cliente" />
                    <x-text-input id="cliente" name="cliente" class="mt-1 block w-full" :value="request('cliente')" placeholder="Nome fantasia ou CNPJ" />
                </div>
                <div class="flex items-end gap-3">
                    <x-primary-button>Filtrar</x-primary-button>
                    <a href="{{ route('orcamentos.pedidos.index') }}" class="pb-2 text-sm text-gray-600">Limpar</a>
                </div>
            </form>

            @if($errors->any())
                <div class="mb-6 rounded-md bg-red-50 p-4 text-red-700">Os filtros informados são inválidos.</div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
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
                        @forelse($pedidos as $pedido)
                            @php
                                $numeroCurto = preg_replace('/^PED-/', '', $pedido->numero);
                                $clienteNome = $pedido->cliente->nome_fantasia ?: $pedido->cliente->nome_razao_social;
                            @endphp
                            <tr class="align-middle">
                                <td class="px-4 py-3">
                                    <a class="font-medium text-indigo-600" href="{{ route('orcamentos.pedidos.show', $pedido) }}">{{ $numeroCurto }}</a>
                                </td>
                                @if($isSuperAdmin)
                                    <td class="px-4 py-3">{{ $pedido->empresa?->nome_fantasia ?: $pedido->empresa?->razao_social }}</td>
                                @endif
                                <td class="px-4 py-3">
                                    <span class="font-medium text-slate-800">{{ $clienteNome }}</span>
                                </td>
                                @if(auth()->user()->can('orcamentos.visualizar_todos'))
                                    <td class="px-4 py-3">{{ $pedido->vendedor->name }}</td>
                                @endif
                                <td class="px-4 py-3">
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Aberto</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center rounded-xl border border-slate-200 bg-slate-50 p-1 shadow-sm">
                                        <a class="inline-flex items-center rounded-lg bg-indigo-50 px-2.5 py-1.5 text-xs font-semibold text-indigo-800 ring-1 ring-inset ring-indigo-200 transition hover:bg-indigo-100" href="{{ route('orcamentos.pedidos.show', $pedido) }}">Abrir</a>
                                        <a class="ml-1 inline-flex items-center rounded-lg bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-800 ring-1 ring-inset ring-emerald-200 transition hover:bg-emerald-100" href="{{ route('orcamentos.pedidos.pdf', $pedido) }}">PDF</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ (auth()->user()->can('orcamentos.visualizar_todos') ? 5 : 4) + ($isSuperAdmin ? 1 : 0) }}" class="px-6 py-12 text-center text-gray-500">Nenhum pedido encontrado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $pedidos->links() }}</div>
        </div>
    </div>
</x-app-layout>

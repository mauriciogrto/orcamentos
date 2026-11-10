<x-app-layout>
    @php
        $isSuperAdmin = auth()->user()->hasRole('super_admin');
    @endphp

    <x-slot name="header">
        <div class="flex justify-between">
            <h2 class="text-xl font-semibold">Clientes</h2>
            @unless($isSuperAdmin)
                @can('create', App\Models\Cliente::class)
                    <a href="{{ route('orcamentos.clientes.create') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Novo cliente</a>
                @endcan
            @endunless
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 rounded-md bg-green-50 p-4 text-green-800">{{ session('success') }}</div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            @if($isSuperAdmin)
                                <th class="px-6 py-3 text-left">Empresa</th>
                            @endif
                            <th class="px-6 py-3 text-left">Código</th>
                            <th class="px-6 py-3 text-left">Nome fantasia</th>
                            <th class="px-6 py-3 text-left">Razão social</th>
                            <th class="px-6 py-3 text-left">CNPJ</th>
                            <th class="px-6 py-3 text-left">Vendedor</th>
                            <th class="px-6 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($clientes as $cliente)
                            <tr>
                                @if($isSuperAdmin)
                                    <td class="px-6 py-4">{{ $cliente->empresa?->nome_fantasia ?: $cliente->empresa?->razao_social }}</td>
                                @endif
                                <td class="px-6 py-4 font-mono font-semibold text-gray-700">{{ $cliente->codigo ?: '—' }}</td>
                                <td class="px-6 py-4">
                                    <a class="font-medium text-indigo-600" href="{{ route('orcamentos.clientes.show', $cliente) }}">{{ $cliente->nome_fantasia ?: $cliente->nome_razao_social }}</a>
                                </td>
                                <td class="px-6 py-4">{{ $cliente->nome_razao_social }}</td>
                                <td class="px-6 py-4">{{ $cliente->cnpj ?: '—' }}</td>
                                <td class="px-6 py-4">{{ $cliente->vendedor?->name ?? 'Sem vendedor' }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-end gap-3">
                                        @unless($isSuperAdmin)
                                            @can('update', $cliente)
                                                <a class="text-indigo-600" href="{{ route('orcamentos.clientes.edit', $cliente) }}">Editar</a>
                                            @endcan
                                            @can('delete', $cliente)
                                                <form method="POST" action="{{ route('orcamentos.clientes.destroy', $cliente) }}" data-confirm-message="Confirma a exclusão deste cliente?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="confirmacao" value="1">
                                                    <button class="text-red-600">Excluir</button>
                                                </form>
                                            @endcan
                                        @else
                                            <span class="text-xs text-gray-500">Visualização</span>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ $isSuperAdmin ? 7 : 6 }}" class="px-6 py-12 text-center text-gray-500">Nenhum cliente cadastrado.</td></tr>
                        @endforelse
                    </tbody>
                </table>

                @if($clientes->hasPages())
                    <div class="p-4">{{ $clientes->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

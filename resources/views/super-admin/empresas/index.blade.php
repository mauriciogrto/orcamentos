<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Empresas</h2>
            <a href="{{ route('super-admin.empresas.create') }}"
                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                Nova empresa
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-6 py-3">Empresa</th>
                                <th class="px-6 py-3">CNPJ</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3">Orçamentos</th>
                                <th class="px-6 py-3">Usuários</th>
                                <th class="px-6 py-3 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($empresas as $empresa)
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900">{{ $empresa->razao_social }}</div>
                                        <div class="text-gray-500">{{ $empresa->nome_fantasia ?: 'Sem nome fantasia' }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-gray-600">{{ $empresa->cnpj }}</td>
                                    <td class="px-6 py-4">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $empresa->status === App\Enums\EmpresaStatus::Ativa ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                            {{ $empresa->status->label() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($empresa->moduloAtivo('orcamentos'))
                                            <span class="text-green-700">Ativo</span>
                                        @else
                                            <span class="text-gray-500">Inativo</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-gray-600">{{ $empresa->users_count }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap justify-end gap-x-3 gap-y-2">
                                            <a href="{{ route('super-admin.empresas.edit', $empresa) }}" class="text-indigo-600 hover:text-indigo-900">Editar</a>
                                            <a href="{{ route('super-admin.empresas.modulos.edit', $empresa) }}" class="text-indigo-600 hover:text-indigo-900">Módulos</a>
                                            <a href="{{ route('super-admin.empresas.usuarios.index', $empresa) }}" class="text-indigo-600 hover:text-indigo-900">Usuários</a>
                                            @if ($empresa->status !== App\Enums\EmpresaStatus::Inativa)
                                                <form method="POST" action="{{ route('super-admin.empresas.desativar', $empresa) }}"
                                                    data-confirm-message="Deseja realmente desativar esta empresa?">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">Desativar</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">Nenhuma empresa cadastrada.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($empresas->hasPages())
                    <div class="border-t border-gray-200 px-6 py-4">{{ $empresas->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

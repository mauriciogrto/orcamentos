<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Usuários</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $empresa->razao_social }}</p>
            </div>
            <div class="flex items-center gap-3">
                <x-back-link :href="route('super-admin.empresas.index')" label="Voltar" />
                <a href="{{ route('super-admin.empresas.usuarios.create', $empresa) }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Novo usuário</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-6 py-3">Nome</th>
                            <th class="px-6 py-3">E-mail</th>
                            <th class="px-6 py-3">Cargo e acesso</th>
                            <th class="px-6 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($usuarios as $usuario)
                            <tr>
                                <td class="px-6 py-4 font-medium text-gray-900">{{ $usuario->name }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $usuario->email }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $usuario->cargo ?: '—' }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-3">
                                        <form method="POST" action="{{ route('super-admin.empresas.usuarios.impersonate', [$empresa, $usuario]) }}" data-confirm-message="Acessar o sistema como {{ $usuario->name }}? Você entrará em modo manutenção e poderá voltar ao superadmin pela faixa superior.">
                                            @csrf
                                            <button type="submit" class="rounded-md bg-orange-100 px-2.5 py-1.5 text-xs font-bold uppercase tracking-wide text-orange-800 hover:bg-orange-200">
                                                Acessar como
                                            </button>
                                        </form>
                                        <a href="{{ route('super-admin.empresas.usuarios.edit', [$empresa, $usuario]) }}" class="text-indigo-600 hover:text-indigo-900">Editar</a>
                                        <form method="POST" action="{{ route('super-admin.empresas.usuarios.destroy', [$empresa, $usuario]) }}" data-confirm-message="Confirma a exclusão deste usuário? O acesso será removido, mas clientes, orçamentos e histórico serão preservados.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">Excluir</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-12 text-center text-gray-500">Nenhum usuário cadastrado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                @if ($usuarios->hasPages())
                    <div class="border-t border-gray-200 px-6 py-4">{{ $usuarios->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

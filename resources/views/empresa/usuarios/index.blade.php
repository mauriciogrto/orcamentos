<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div><h2 class="text-xl font-semibold text-gray-800">Equipe</h2><p class="mt-1 text-sm text-gray-500">{{ $empresa->razao_social }}</p></div>
            <a href="{{ route('empresa.usuarios.create') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Novo usuário</a>
        </div>
    </x-slot>

    <div class="py-12"><div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
        @if(session('success'))<div class="mb-6 rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="mb-6 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>@endif
        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"><tr><th class="px-6 py-3">Nome</th><th class="px-6 py-3">E-mail</th><th class="px-6 py-3">Cargo</th><th class="px-6 py-3 text-right">Ações</th></tr></thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($usuarios as $usuario)
                        <tr><td class="px-6 py-4 font-medium">{{ $usuario->name }}</td><td class="px-6 py-4 text-gray-600">{{ $usuario->email }}</td><td class="px-6 py-4 text-gray-600">{{ $usuario->cargo }}</td><td class="px-6 py-4"><div class="flex justify-end gap-3">
                            <a href="{{ route('empresa.usuarios.edit', $usuario) }}" class="text-indigo-600">Editar</a>
                            @if(! $usuario->is(Auth::user()))
                                <form method="POST" action="{{ route('empresa.usuarios.destroy', $usuario) }}" data-confirm-message="Confirma a exclusão deste usuário? O acesso será removido, mas todo o histórico será preservado.">@csrf @method('DELETE')<button class="text-red-600">Excluir</button></form>
                            @endif
                        </div></td></tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-12 text-center text-gray-500">Nenhum usuário cadastrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($usuarios->hasPages())<div class="border-t px-6 py-4">{{ $usuarios->links() }}</div>@endif
        </div>
    </div></div>
</x-app-layout>

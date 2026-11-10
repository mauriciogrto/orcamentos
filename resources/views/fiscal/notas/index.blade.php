<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold">Notas fiscais</h2>
                <p class="mt-1 text-sm text-gray-600">Pré-validações e futuras emissões de NF-e.</p>
            </div>
            <x-back-link :href="route('fiscal.index')" label="Voltar" />
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="rounded-md bg-green-50 p-4 text-green-800">{{ session('success') }}</div>
            @endif

            <form method="GET" action="{{ route('fiscal.notas.index') }}" class="flex flex-wrap items-end gap-3 rounded-lg bg-white p-5 shadow-sm">
                <div>
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status" class="mt-1 rounded-md border-gray-300 shadow-sm">
                        <option value="">Todos</option>
                        @foreach(['pendente' => 'Pendente', 'pre_validada' => 'Pré-validada', 'autorizada' => 'Autorizada', 'rejeitada' => 'Rejeitada', 'cancelada' => 'Cancelada'] as $valor => $label)
                            <option value="{{ $valor }}" @selected(request('status') === $valor)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <x-primary-button>Filtrar</x-primary-button>
                <a href="{{ route('fiscal.notas.index') }}" class="pb-2 text-sm text-gray-600">Limpar</a>
            </form>

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">Pedido</th>
                            <th class="px-4 py-3 text-left">Cliente</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Ambiente</th>
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($notas as $nota)
                            <tr>
                                <td class="px-4 py-3 font-semibold">{{ $nota->pedido?->numero }}</td>
                                <td class="px-4 py-3">{{ $nota->pedido?->cliente?->nome_fantasia ?: $nota->pedido?->cliente?->nome_razao_social }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">{{ ucfirst(str_replace('_', ' ', $nota->status)) }}</span>
                                </td>
                                <td class="px-4 py-3">{{ $nota->ambiente === 'producao' ? 'Produção' : 'Homologação' }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if($nota->pedido)
                                        <a href="{{ route('fiscal.notas.precheck', $nota->pedido) }}" class="rounded-lg bg-indigo-700 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-800">Abrir pré-check</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">Nenhum registro fiscal encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $notas->links() }}</div>
        </div>
    </div>
</x-app-layout>

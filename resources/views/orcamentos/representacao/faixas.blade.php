<x-app-layout>
    @php
        $isSuperAdmin = $isSuperAdmin ?? auth()->user()->hasRole('super_admin');
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Faixas de Comissão</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Configure a comissão por intervalo de desconto. Ex.: de 10% até 50% = 3% de comissão.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <x-back-link :href="route('orcamentos.representacao.index', $isSuperAdmin ? ['empresa_id' => $empresa->id] : [])" label="Voltar" />
                <a href="{{ route('orcamentos.representacao.create', $isSuperAdmin ? ['empresa_id' => $empresa->id] : []) }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Nova faixa</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-md bg-green-50 p-4 text-green-800">{{ session('success') }}</div>
            @endif

            @if($isSuperAdmin)
                <form method="GET" action="{{ route('orcamentos.representacao.faixas') }}" class="mb-6 rounded-2xl bg-white p-5 shadow-sm">
                    <x-input-label for="empresa_id" value="Empresa visualizada" />
                    <div class="mt-2 flex flex-col gap-3 sm:flex-row">
                        <select id="empresa_id" name="empresa_id" class="block w-full rounded-md border-gray-300 shadow-sm">
                            @foreach($empresas as $opcaoEmpresa)
                                <option value="{{ $opcaoEmpresa->id }}" @selected((int) $empresa->id === (int) $opcaoEmpresa->id)>
                                    {{ $opcaoEmpresa->nome_fantasia ?: $opcaoEmpresa->razao_social }}
                                </option>
                            @endforeach
                        </select>
                        <x-primary-button>Trocar empresa</x-primary-button>
                    </div>
                </form>
            @endif

            <div class="mb-4 rounded-lg bg-white p-4 text-sm text-slate-700 shadow-sm">
                Empresa: <strong>{{ $empresa->nome_fantasia ?: $empresa->razao_social }}</strong>
            </div>

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left">Desconto inicial</th>
                            <th class="px-6 py-3 text-left">Desconto final</th>
                            <th class="px-6 py-3 text-left">Comissão</th>
                            <th class="px-6 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($faixas as $faixa)
                            <tr>
                                <td class="px-6 py-4">{{ number_format((float) ($faixa->desconto_inicial_percentual ?? $faixa->desconto_percentual), 2, ',', '.') }}%</td>
                                <td class="px-6 py-4">{{ number_format((float) ($faixa->desconto_final_percentual ?? $faixa->desconto_percentual), 2, ',', '.') }}%</td>
                                <td class="px-6 py-4">{{ number_format((float) $faixa->comissao_percentual, 2, ',', '.') }}%</td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-end gap-3">
                                        <a class="text-indigo-600" href="{{ route('orcamentos.representacao.edit', $faixa) }}">Editar</a>
                                        <form method="POST" action="{{ route('orcamentos.representacao.destroy', $faixa) }}" data-confirm-message="Confirma a exclusão desta faixa?">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="confirmacao" value="1">
                                            <button class="text-red-600">Excluir</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-12 text-center text-gray-500">Nenhuma faixa cadastrada.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $faixas->links() }}</div>
        </div>
    </div>
</x-app-layout>

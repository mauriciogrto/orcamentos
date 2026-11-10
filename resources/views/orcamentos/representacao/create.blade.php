<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold">Nova faixa de comissão</h2>
            <x-back-link :href="route('orcamentos.representacao.faixas', ($isSuperAdmin ?? false) ? ['empresa_id' => $empresa->id] : [])" label="Voltar" />
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('orcamentos.representacao.store') }}">
                    @csrf
                    @if(($isSuperAdmin ?? false) && isset($empresa))
                        <input type="hidden" name="empresa_id" value="{{ $empresa->id }}">
                        <div class="mb-6 rounded-lg bg-indigo-50 p-4 text-sm text-indigo-900">
                            Empresa: <strong>{{ $empresa->nome_fantasia ?: $empresa->razao_social }}</strong>
                        </div>
                    @endif
                    @include('orcamentos.representacao._form')
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

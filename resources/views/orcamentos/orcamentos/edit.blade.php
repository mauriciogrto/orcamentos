<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold">Editar {{ $orcamento->numero }}</h2>
                <p class="text-sm text-gray-600">Status: {{ $orcamento->status->label() }}</p>
            </div>
            <div class="flex items-center gap-3">
                <x-back-link :href="route('orcamentos.orcamentos.index')" label="Voltar" />
                <a href="{{ route('orcamentos.orcamentos.pdf', $orcamento) }}" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Exportar PDF</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 rounded-md bg-green-50 p-4 text-green-800">{{ session('success') }}</div>
            @endif
            <div class="mb-6">
                @include('orcamentos.orcamentos._price_updates_alert')
            </div>
            <div class="bg-white p-4 shadow-sm sm:rounded-lg sm:p-6">
                <form method="POST" action="{{ route('orcamentos.orcamentos.update', $orcamento) }}">
                    @csrf
                    @method('PUT')
                    @include('orcamentos.orcamentos._form')
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

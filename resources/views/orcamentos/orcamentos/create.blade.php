<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold">Novo orçamento</h2>
                <p class="text-sm text-gray-600">O número será gerado ao salvar.</p>
            </div>
            <x-back-link :href="route('orcamentos.orcamentos.index')" label="Voltar" />
        </div>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="bg-white p-4 shadow-sm sm:rounded-lg sm:p-6">
                <form method="POST" action="{{ route('orcamentos.orcamentos.store') }}">
                    @csrf
                    @include('orcamentos.orcamentos._form')
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

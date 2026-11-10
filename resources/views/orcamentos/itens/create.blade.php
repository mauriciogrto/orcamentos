<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold">Novo item</h2>
            <x-back-link :href="route('orcamentos.itens.index')" label="Voltar" />
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('orcamentos.itens.store') }}" class="bg-white p-6 shadow-sm sm:rounded-lg">
                @csrf
                @include('orcamentos.itens._form')
            </form>
        </div>
    </div>
</x-app-layout>

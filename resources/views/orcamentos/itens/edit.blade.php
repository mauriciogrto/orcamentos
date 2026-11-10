<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold">Editar item</h2>
            <x-back-link :href="route('orcamentos.itens.index')" label="Voltar" />
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 rounded-md bg-green-50 p-4 text-green-800">{{ session('success') }}</div>
            @endif
            <form method="POST" action="{{ route('orcamentos.itens.update', $item) }}" class="bg-white p-6 shadow-sm sm:rounded-lg">
                @csrf
                @method('PUT')
                @include('orcamentos.itens._form')
                @include('orcamentos.itens._historico')
            </form>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold">Novo cliente</h2>
            <x-back-link :href="route('orcamentos.clientes.index')" label="Voltar" />
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('orcamentos.clientes.store') }}" class="bg-white p-6 shadow-sm sm:rounded-lg">
                @csrf
                @include('orcamentos.clientes._form')
            </form>
        </div>
    </div>
</x-app-layout>

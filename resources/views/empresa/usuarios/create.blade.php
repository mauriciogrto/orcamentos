<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold text-gray-800">Novo usuário da equipe</h2>
            <x-back-link :href="route('empresa.usuarios.index')" label="Voltar" />
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('empresa.usuarios.store') }}" class="space-y-6 bg-white p-6 shadow-sm sm:rounded-lg">
                @csrf
                @include('super-admin.empresas.usuarios._form')
                <div class="flex items-center gap-3"><x-primary-button>Criar usuário</x-primary-button></div>
            </form>
        </div>
    </div>
</x-app-layout>

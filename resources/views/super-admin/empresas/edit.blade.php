<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Editar empresa</h2>
            <x-back-link :href="route('super-admin.empresas.index')" label="Voltar" />
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('super-admin.empresas.update', $empresa) }}" enctype="multipart/form-data" class="p-6">
                    @csrf
                    @method('PUT')
                    @include('super-admin.empresas._form', ['empresa' => $empresa])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

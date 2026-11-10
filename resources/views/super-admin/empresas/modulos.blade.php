<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Módulos — {{ $empresa->razao_social }}</h2>
            <x-back-link :href="route('super-admin.empresas.index')" label="Voltar" />
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('super-admin.empresas.modulos.update', $empresa) }}" class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                @csrf
                @method('PUT')

                <p class="mb-6 text-sm text-gray-600">Ative somente os módulos contratados por esta empresa.</p>

                <div class="space-y-3">
                    @foreach ($modulos as $modulo)
                        <label class="flex items-center justify-between rounded-lg border border-gray-200 p-4">
                            <span>
                                <span class="block font-medium text-gray-900">{{ $modulo->nome }}</span>
                                <span class="text-sm text-gray-500">{{ $modulo->slug }}</span>
                            </span>
                            <input type="checkbox" name="modulos[{{ $modulo->id }}]" value="1"
                                @checked(in_array($modulo->id, old('modulos') ? array_keys(old('modulos')) : $ativos))
                                class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        </label>
                    @endforeach
                </div>

                <x-input-error :messages="$errors->get('modulos')" class="mt-2" />

                <div class="mt-6 flex items-center gap-3">
                    <x-primary-button>Salvar módulos</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

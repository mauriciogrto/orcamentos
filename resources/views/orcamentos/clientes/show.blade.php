<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold">{{ $cliente->nome_fantasia ?: $cliente->nome_razao_social }}</h2>
                <p class="text-sm text-gray-600">{{ $cliente->nome_razao_social }}</p>
            </div>
            <x-back-link :href="route('orcamentos.clientes.index')" label="Voltar" />
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            <div class="space-y-6 bg-white p-6 shadow-sm sm:rounded-lg">
                <div class="space-y-3">
                    <p><strong>Código:</strong> {{ $cliente->codigo ?: '—' }}</p>
                    <p><strong>Nome fantasia:</strong> {{ $cliente->nome_fantasia ?: '—' }}</p>
                    <p><strong>Razão social:</strong> {{ $cliente->nome_razao_social }}</p>
                    <p><strong>CNPJ:</strong> {{ $cliente->cnpj ?: '—' }}</p>
                    <p><strong>Telefone:</strong> {{ $cliente->telefone ?: '—' }}</p>
                    <p><strong>E-mail:</strong> {{ $cliente->email ?: '—' }}</p>
                    <p><strong>Vendedor:</strong> {{ $cliente->vendedor?->name ?? 'Sem vendedor' }}</p>
                </div>

                <div class="border-t border-gray-200 pt-4">
                    <h3 class="text-sm font-semibold text-gray-900">Endereço</h3>
                    <div class="mt-3 space-y-2 text-sm text-gray-700">
                        <p><strong>CEP:</strong> {{ $cliente->cep ?: '—' }}</p>
                        <p>
                            <strong>Logradouro:</strong>
                            {{ collect([$cliente->logradouro, $cliente->numero])->filter()->implode(', ') ?: '—' }}
                            @if($cliente->complemento)
                                - {{ $cliente->complemento }}
                            @endif
                        </p>
                        <p><strong>Bairro:</strong> {{ $cliente->bairro ?: '—' }}</p>
                        <p><strong>Cidade/UF:</strong> {{ collect([$cliente->cidade, $cliente->uf])->filter()->implode(' / ') ?: '—' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

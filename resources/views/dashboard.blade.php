<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">Dashboard</h2></x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if($panorama === null)
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg"><div class="p-6 text-gray-900">Você está conectado.</div></div>
            @else
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">Panorama geral da empresa</h3>
                    <p class="text-sm text-gray-600">Orçamentos abertos consideram os status Rascunho e Enviado.</p>
                </div>
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y text-sm">
                        <thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left">Vendedor</th><th class="px-6 py-3 text-right">Clientes</th><th class="px-6 py-3 text-right">Orçamentos abertos</th><th class="px-6 py-3 text-right">Valor em aberto</th></tr></thead>
                        <tbody class="divide-y">
                            @forelse($panorama as $vendedor)
                                <tr><td class="px-6 py-4"><div class="font-medium">{{ $vendedor->name }}</div><div class="text-xs text-gray-500">{{ $vendedor->email }}</div></td><td class="px-6 py-4 text-right">{{ $vendedor->clientes_count }}</td><td class="px-6 py-4 text-right">{{ $vendedor->orcamentos_abertos_count }}</td><td class="px-6 py-4 text-right">R$ {{ number_format((float) ($vendedor->orcamentos_abertos_total ?? 0), 2, ',', '.') }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="px-6 py-12 text-center text-gray-500">Nenhum vendedor cadastrado.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-gray-50 font-semibold"><tr><td class="px-6 py-3">Total</td><td class="px-6 py-3 text-right">{{ $totais['clientes'] }}</td><td class="px-6 py-3 text-right">{{ $totais['orcamentos_abertos'] }}</td><td class="px-6 py-3 text-right">R$ {{ number_format((float) $totais['valor_aberto'], 2, ',', '.') }}</td></tr></tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold">Fiscal / NF-e</h2>
                <p class="mt-1 text-sm text-gray-600">Configuração fiscal, pré-validação e vínculo Pedido → NF-e.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="rounded-md bg-green-50 p-4 text-green-800">{{ session('success') }}</div>
            @endif

            @role('super_admin')
                <form method="GET" action="{{ route('fiscal.index') }}" class="rounded-lg bg-white p-5 shadow-sm">
                    <x-input-label for="empresa_id" value="Empresa em análise" />
                    <div class="mt-2 flex gap-3">
                        <select id="empresa_id" name="empresa_id" class="block w-full rounded-md border-gray-300 shadow-sm">
                            @foreach($empresas as $opcao)
                                <option value="{{ $opcao->id }}" @selected($empresa->id === $opcao->id)>{{ $opcao->razao_social }}</option>
                            @endforeach
                        </select>
                        <x-primary-button>Trocar</x-primary-button>
                    </div>
                </form>
            @endrole

            <div class="grid gap-4 md:grid-cols-3">
                <a href="{{ route('fiscal.configuracao.edit') }}" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md">
                    <p class="text-sm font-semibold text-indigo-700">Configuração fiscal</p>
                    <h3 class="mt-2 text-lg font-bold text-slate-900">Empresa, certificado e numeração</h3>
                    <p class="mt-2 text-sm text-slate-600">Ambiente, A1, IE, regime tributário, série, próximo número e CFOP padrão.</p>
                </a>

                <a href="{{ route('fiscal.notas.index') }}" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md">
                    <p class="text-sm font-semibold text-indigo-700">Notas fiscais</p>
                    <h3 class="mt-2 text-lg font-bold text-slate-900">Pré-validações e histórico</h3>
                    <p class="mt-2 text-sm text-slate-600">Acompanhe pedidos pré-validados e futuras NF-e autorizadas/rejeitadas.</p>
                </a>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-semibold text-indigo-700">Status atual</p>
                    <h3 class="mt-2 text-lg font-bold text-slate-900">{{ $empresa->razao_social }}</h3>
                    <p class="mt-2 text-sm text-slate-600">
                        Ambiente: <strong>{{ $configuracao?->ambiente_nfe === 'producao' ? 'Produção' : 'Homologação' }}</strong><br>
                        Certificado: <strong>{{ $configuracao?->certificado_a1_path ? 'configurado' : 'pendente' }}</strong>
                    </p>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-slate-100 p-5">
                        <h3 class="font-semibold text-slate-900">Pedidos aguardando pré-validação</h3>
                    </div>
                    <div class="divide-y">
                        @forelse($pedidosSemNota as $pedido)
                            <div class="flex items-center justify-between gap-4 p-5">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $pedido->numero }}</p>
                                    <p class="text-sm text-slate-600">{{ $pedido->cliente?->nome_fantasia ?: $pedido->cliente?->nome_razao_social }} · R$ {{ number_format((float) $pedido->valor_total, 2, ',', '.') }}</p>
                                </div>
                                <a href="{{ route('fiscal.notas.precheck', $pedido) }}" class="rounded-lg bg-indigo-700 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-800">Pré-validar</a>
                            </div>
                        @empty
                            <p class="p-5 text-sm text-slate-500">Nenhum pedido pendente.</p>
                        @endforelse
                    </div>
                </section>

                <section class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-slate-100 p-5">
                        <h3 class="font-semibold text-slate-900">Últimas movimentações fiscais</h3>
                    </div>
                    <div class="divide-y">
                        @forelse($notas as $nota)
                            <div class="flex items-center justify-between gap-4 p-5">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $nota->pedido?->numero }}</p>
                                    <p class="text-sm text-slate-600">{{ ucfirst(str_replace('_', ' ', $nota->status)) }} · {{ $nota->updated_at->format('d/m/Y H:i') }}</p>
                                </div>
                                <a href="{{ route('fiscal.notas.precheck', $nota->pedido) }}" class="text-sm font-semibold text-indigo-700 hover:text-indigo-900">Abrir</a>
                            </div>
                        @empty
                            <p class="p-5 text-sm text-slate-500">Nenhuma nota/precheck registrado.</p>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>

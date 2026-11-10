<x-app-layout>
    @php
        $isSuperAdmin = $isSuperAdmin ?? auth()->user()->hasRole('super_admin');
    @endphp

    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold">Representação</h2>
            <p class="mt-1 text-sm text-gray-600">
                Central de regras comerciais: frete, comissões mensais e faixas de comissão por desconto.
            </p>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-md bg-green-50 p-4 text-green-800">{{ session('success') }}</div>
            @endif

            @if($isSuperAdmin)
                <form method="GET" action="{{ route('orcamentos.representacao.index') }}" class="mb-6 rounded-2xl bg-white p-5 shadow-sm">
                    <x-input-label for="empresa_id" value="Empresa visualizada" />
                    <div class="mt-2 flex flex-col gap-3 sm:flex-row">
                        <select id="empresa_id" name="empresa_id" class="block w-full rounded-md border-gray-300 shadow-sm">
                            @foreach($empresas as $opcaoEmpresa)
                                <option value="{{ $opcaoEmpresa->id }}" @selected((int) $empresa->id === (int) $opcaoEmpresa->id)>
                                    {{ $opcaoEmpresa->nome_fantasia ?: $opcaoEmpresa->razao_social }}
                                </option>
                            @endforeach
                        </select>
                        <x-primary-button>Trocar empresa</x-primary-button>
                    </div>
                </form>
            @endif

            <div class="grid gap-6 md:grid-cols-3">
                <a
                    href="{{ route('orcamentos.representacao.frete', $isSuperAdmin ? ['empresa_id' => $empresa->id] : []) }}"
                    class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-indigo-200 hover:shadow-md"
                >
                    <h3 class="text-lg font-bold text-slate-900">Configurações de Frete</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Defina pedido mínimo e o limite que muda automaticamente o frete entre FOB e CIF.
                    </p>
                </a>

                <a
                    href="{{ route('orcamentos.representacao.relatorios', $isSuperAdmin ? ['empresa_id' => $empresa->id] : []) }}"
                    class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-emerald-200 hover:shadow-md"
                >
                    <h3 class="text-lg font-bold text-slate-900">Relatórios de Comissão</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Gere o PDF mensal por vendedor ou de toda a equipe, considerando pedidos finalizados.
                    </p>
                </a>

                <a
                    href="{{ route('orcamentos.representacao.faixas', $isSuperAdmin ? ['empresa_id' => $empresa->id] : []) }}"
                    class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-violet-200 hover:shadow-md"
                >
                    <h3 class="text-lg font-bold text-slate-900">Faixas de Comissão</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Cadastre os intervalos de desconto e a comissão aplicada em cada faixa.
                    </p>
                    @if($faixasCount)
                        <p class="mt-5 text-xs font-semibold text-slate-500">{{ $faixasCount }} faixa(s) cadastrada(s)</p>
                    @endif
                </a>
            </div>

            @if($isSuperAdmin)
                <div class="mt-6 rounded-lg bg-amber-50 p-4 text-sm text-amber-800">
                    Como superadmin, você está visualizando e alterando as regras da empresa selecionada acima.
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

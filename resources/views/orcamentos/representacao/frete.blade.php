<x-app-layout>
    @php
        $isSuperAdmin = $isSuperAdmin ?? auth()->user()->hasRole('super_admin');
        $clientesFreteBusca = $clientesFreteDisponiveis->map(fn ($cliente) => [
            'id' => $cliente->id,
            'nome' => $cliente->nome_fantasia ?: $cliente->nome_razao_social,
            'razao_social' => $cliente->nome_razao_social,
            'cnpj' => $cliente->cnpj,
            'limite_frete_fob' => $cliente->limite_frete_fob !== null ? (float) $cliente->limite_frete_fob : null,
            'frete_personalizado' => (bool) $cliente->frete_personalizado,
        ])->values();
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Configurações de Frete</h2>
                <p class="mt-1 text-sm text-gray-600">Regras automáticas de pedido mínimo, FOB e CIF.</p>
            </div>
            <x-back-link :href="route('orcamentos.representacao.index', $isSuperAdmin ? ['empresa_id' => $empresa->id] : [])" label="Voltar" />
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-md bg-green-50 p-4 text-green-800">{{ session('success') }}</div>
            @endif

            @if($isSuperAdmin)
                <form method="GET" action="{{ route('orcamentos.representacao.frete') }}" class="mb-6 rounded-2xl bg-white p-5 shadow-sm">
                    <x-input-label for="empresa_id_filtro" value="Empresa visualizada" />
                    <div class="mt-2 flex flex-col gap-3 sm:flex-row">
                        <select id="empresa_id_filtro" name="empresa_id" class="block w-full rounded-md border-gray-300 shadow-sm">
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

            <form method="POST" action="{{ route('orcamentos.representacao.configuracao.update') }}" class="rounded-2xl bg-white p-6 shadow-sm">
                @csrf
                @method('PATCH')
                @if($isSuperAdmin)
                    <input type="hidden" name="empresa_id" value="{{ $empresa->id }}">
                @endif

                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Regras do pedido e do frete</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Empresa: <strong>{{ $empresa->nome_fantasia ?: $empresa->razao_social }}</strong>.
                        Esta é a regra padrão da empresa. Se um cliente tiver regra própria no cadastro dele, a regra do cliente será usada no orçamento.
                    </p>
                </div>

                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <div>
                        <x-input-label for="pedido_minimo" value="Valor mínimo do pedido (R$)" />
                        <x-text-input id="pedido_minimo" name="pedido_minimo" type="number" min="0" step="0.01" class="mt-1 block w-full" :value="old('pedido_minimo', $empresa->pedido_minimo)" required />
                        <p class="mt-1 text-xs text-gray-500">Abaixo deste valor, o orçamento exibe apenas um aviso.</p>
                        <x-input-error :messages="$errors->get('pedido_minimo')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="limite_frete_fob" value="Limite para frete FOB (R$)" />
                        <x-text-input id="limite_frete_fob" name="limite_frete_fob" type="number" min="0" step="0.01" class="mt-1 block w-full" :value="old('limite_frete_fob', $empresa->limite_frete_fob)" required />
                        <p class="mt-1 text-xs text-gray-500">Regra padrão: até este valor, FOB; acima deste valor, CIF. Pode ser sobrescrita individualmente no cadastro do cliente.</p>
                        <x-input-error :messages="$errors->get('limite_frete_fob')" class="mt-2" />
                    </div>
                </div>

                <x-input-error :messages="$errors->get('empresa_id')" class="mt-4" />

                <div class="mt-6 flex justify-end">
                    <x-primary-button>Salvar regras</x-primary-button>
                </div>
            </form>

            <div class="mt-6 rounded-2xl bg-white p-6 shadow-sm" x-data="freteClienteConfig(@js($clientesFreteBusca))">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Exceções por cliente</h3>
                    <p class="mt-1 text-sm text-gray-600">Busque um cliente e defina uma regra própria. Até o limite informado será FOB; acima será CIF.</p>
                </div>

                <form method="POST" action="{{ route('orcamentos.representacao.configuracao.cliente-frete.update') }}" class="mt-5 rounded-xl border border-indigo-100 bg-indigo-50/60 p-4">
                    @csrf
                    @method('PATCH')
                    @if($isSuperAdmin)
                        <input type="hidden" name="empresa_id" value="{{ $empresa->id }}">
                    @endif
                    <input type="hidden" name="cliente_id" :value="clienteId">

                    <div class="grid gap-4 lg:grid-cols-[1.4fr_0.8fr_auto] lg:items-end">
                        <div class="relative">
                            <x-input-label for="busca_cliente_frete" value="Buscar cliente" />
                            <x-text-input id="busca_cliente_frete" type="search" x-model="busca" @input="clienteId = ''; clienteSelecionado = null" class="mt-1 block w-full" placeholder="Digite nome, razão social ou CNPJ" autocomplete="off" />
                            <div x-show="busca.trim().length > 0 && resultados.length > 0" x-cloak class="absolute left-0 right-0 z-30 mt-1 max-h-72 overflow-y-auto rounded-md border border-gray-200 bg-white shadow-lg">
                                <template x-for="cliente in resultados" :key="cliente.id">
                                    <button type="button" @click="selecionar(cliente)" class="block w-full border-b px-4 py-3 text-left last:border-0 hover:bg-indigo-50">
                                        <span class="block text-sm font-semibold text-gray-900" x-text="cliente.nome"></span>
                                        <span class="text-xs text-gray-500" x-text="[cliente.razao_social, cliente.cnpj].filter(Boolean).join(' - ')"></span>
                                    </button>
                                </template>
                            </div>
                            <p x-show="busca.trim().length > 0 && resultados.length === 0" x-cloak class="mt-2 text-sm text-gray-500">Nenhum cliente encontrado.</p>
                            <p x-show="clienteSelecionado" x-cloak class="mt-2 rounded-md border border-indigo-200 bg-white px-3 py-2 text-sm text-indigo-900">
                                Cliente selecionado: <strong x-text="clienteSelecionado?.nome"></strong>
                            </p>
                            <x-input-error :messages="$errors->get('cliente_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="limite_frete_fob_cliente" value="Limite FOB do cliente (R$)" />
                            <x-text-input id="limite_frete_fob_cliente" name="limite_frete_fob" type="number" min="0" step="0.01" class="mt-1 block w-full" placeholder="Ex.: 2000,00" required />
                            <p class="mt-1 text-xs text-gray-600">Até esse valor: FOB. Acima: CIF.</p>
                            <x-input-error :messages="$errors->get('limite_frete_fob')" class="mt-2" />
                        </div>

                        <button class="inline-flex min-h-[42px] items-center justify-center rounded-md bg-indigo-700 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-600">
                            Salvar regra
                        </button>
                    </div>
                </form>

                <div class="mt-6">
                    <h4 class="text-sm font-bold uppercase tracking-wide text-gray-500">Clientes com regra diferente</h4>
                    <p class="mt-1 text-sm text-gray-600">Clientes abaixo usam regra diferente da regra padrão da empresa.</p>
                </div>

                <div class="mt-5 overflow-hidden rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left">Cliente</th>
                                <th class="px-4 py-3 text-left">CNPJ</th>
                                <th class="px-4 py-3 text-left">Regra</th>
                                <th class="px-4 py-3 text-right">Ação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y bg-white">
                            @forelse($clientesFretePersonalizado as $clienteFrete)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $clienteFrete->nome_fantasia ?: $clienteFrete->nome_razao_social }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $clienteFrete->cnpj ?: '—' }}</td>
                                    <td class="px-4 py-3 text-gray-700">FOB até R$ {{ number_format((float) $clienteFrete->limite_frete_fob, 2, ',', '.') }}; acima CIF</td>
                                    <td class="px-4 py-3 text-right">
                                        <form method="POST" action="{{ route('orcamentos.representacao.configuracao.cliente-frete.destroy', $clienteFrete) }}" data-confirm-message="Remover a regra individual de frete deste cliente? Ele voltará a usar a regra padrão da empresa.">
                                            @csrf
                                            @method('DELETE')
                                            <button class="font-semibold text-red-700 hover:text-red-900">Remover regra</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-gray-500">Nenhum cliente com regra de frete própria.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @once
        <script>
            function freteClienteConfig(clientes) {
                return {
                    clientes,
                    busca: '',
                    clienteId: '',
                    clienteSelecionado: null,
                    get resultados() {
                        const termo = this.normalizar(this.busca);
                        const cnpj = this.busca.replace(/\D/g, '');

                        if (!termo) {
                            return [];
                        }

                        return this.clientes
                            .filter((cliente) => {
                                return this.normalizar(cliente.nome).includes(termo)
                                    || this.normalizar(cliente.razao_social).includes(termo)
                                    || (cnpj && String(cliente.cnpj || '').replace(/\D/g, '').includes(cnpj));
                            })
                            .slice(0, 10);
                    },
                    selecionar(cliente) {
                        this.clienteSelecionado = cliente;
                        this.clienteId = cliente.id;
                        this.busca = [cliente.nome, cliente.cnpj].filter(Boolean).join(' - ');

                        this.$nextTick(() => {
                            const campoLimite = document.getElementById('limite_frete_fob_cliente');
                            if (campoLimite && cliente.limite_frete_fob !== null) {
                                campoLimite.value = Number(cliente.limite_frete_fob).toFixed(2);
                            }
                        });
                    },
                    normalizar(valor) {
                        return String(valor || '')
                            .normalize('NFD')
                            .replace(/[\u0300-\u036f]/g, '')
                            .toLowerCase()
                            .trim();
                    },
                };
            }
        </script>
    @endonce
</x-app-layout>

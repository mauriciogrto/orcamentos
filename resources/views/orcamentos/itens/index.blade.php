<x-app-layout>
    @php
        $isSuperAdmin = auth()->user()->hasRole('super_admin');
    @endphp

    <x-slot name="header">
        <div class="flex justify-between">
            <h2 class="text-xl font-semibold">Itens e produtos</h2>
            @unless($isSuperAdmin)
                @can('create', App\Models\Item::class)
                    <a href="{{ route('orcamentos.itens.create', $tabelaSelecionada ? ['tabela_id' => $tabelaSelecionada->id] : []) }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Novo item</a>
                @endcan
            @endunless
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 rounded-md bg-green-50 p-4 text-green-800">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="mb-6 rounded-md bg-red-50 p-4 text-red-700">
                    <p class="font-semibold">Não foi possível processar a tabela.</p>
                    <ul class="mt-2 list-disc pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="GET" action="{{ route('orcamentos.itens.index') }}" class="mb-6 grid gap-3 rounded-xl bg-white p-4 shadow-sm md:grid-cols-[1fr_auto_auto] md:items-end">
                @if($tabelaSelecionada)
                    <input type="hidden" name="tabela_id" value="{{ $tabelaSelecionada->id }}">
                @endif
                <div>
                    <x-input-label for="busca" value="Pesquisar item" />
                    <x-text-input id="busca" name="busca" type="search" class="mt-1 block w-full" :value="request('busca')" placeholder="Código, nome ou descrição" />
                </div>
                <x-primary-button class="justify-center">Pesquisar</x-primary-button>
                <a href="{{ route('orcamentos.itens.index', $tabelaSelecionada ? ['tabela_id' => $tabelaSelecionada->id] : []) }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Limpar</a>
            </form>

            <div class="mb-6 grid gap-4 md:grid-cols-2">
                <div class="rounded-xl bg-white p-4 shadow-sm" x-data="{ criarTabela: {{ $errors->any() && old('origem') === 'criar_tabela' ? 'true' : 'false' }} }">
                    <div class="flex flex-col gap-3">
                        <form method="GET" action="{{ route('orcamentos.itens.index') }}" class="flex-1">
                            <x-input-label for="tabela_id" value="Tabela selecionada" class="text-xs font-bold uppercase tracking-wide text-slate-700" />
                            <select id="tabela_id" name="tabela_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" onchange="this.form.submit()">
                                @foreach($tabelas as $tabela)
                                    <option value="{{ $tabela->id }}" @selected($tabelaSelecionada?->id === $tabela->id)>
                                        {{ $tabela->nome }} — {{ $tabela->itens_count }} {{ $tabela->itens_count === 1 ? 'item' : 'itens' }}
                                    </option>
                                @endforeach
                            </select>
                        </form>

                        <div class="flex flex-wrap gap-2">
                            @can('create', App\Models\Item::class)
                                <a href="{{ route('orcamentos.itens.modelo') }}" class="inline-flex items-center rounded-md border border-indigo-200 bg-indigo-50 px-2.5 py-1.5 text-xs font-semibold text-indigo-800 hover:bg-indigo-100">
                                    Baixar modelo vazio
                                </a>
                            @endcan

                            @if($tabelaSelecionada)
                                <a href="{{ route('orcamentos.itens.exportar-tabela', $tabelaSelecionada) }}" class="inline-flex items-center rounded-md border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-100">
                                    Baixar tabela atual
                                </a>
                                @can('create', App\Models\Item::class)
                                    <form method="POST" action="{{ route('orcamentos.itens.toggle-tabela-ativa', $tabelaSelecionada) }}" data-confirm-message="{{ $tabelaSelecionada->ativo ? 'Desativar esta tabela para novos orçamentos?' : 'Ativar esta tabela para uso nos orçamentos?' }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="ativo" value="{{ $tabelaSelecionada->ativo ? 0 : 1 }}">
                                        <button class="inline-flex items-center rounded-md border {{ $tabelaSelecionada->ativo ? 'border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100' : 'border-emerald-200 bg-emerald-50 text-emerald-800 hover:bg-emerald-100' }} px-2.5 py-1.5 text-xs font-semibold">
                                            {{ $tabelaSelecionada->ativo ? 'Desativar tabela' : 'Ativar tabela' }}
                                        </button>
                                    </form>
                                    <button type="button" @click="criarTabela = ! criarTabela" class="inline-flex items-center rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-800 hover:bg-slate-50">
                                        Criar tabela
                                    </button>
                                    @unless($tabelaSelecionada->nome === 'Tabela padrão')
                                    <form method="POST" action="{{ route('orcamentos.itens.destroy-tabela', $tabelaSelecionada) }}" data-confirm-message="Confirma a exclusão desta tabela? Os itens vinculados a ela também serão removidos da listagem.">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="confirmacao" value="1">
                                        <button class="inline-flex items-center rounded-md border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-800 hover:bg-red-100">
                                            Excluir tabela
                                        </button>
                                    </form>
                                    @endunless
                                @endcan
                            @endif
                        </div>
                    </div>

                    @can('create', App\Models\Item::class)
                        <form
                            x-show="criarTabela"
                            x-cloak
                            method="POST"
                            action="{{ route('orcamentos.itens.store-tabela') }}"
                            class="mt-3 rounded-md border border-slate-200 bg-slate-50 p-3"
                        >
                            @csrf
                            <input type="hidden" name="origem" value="criar_tabela">
                            @if($isSuperAdmin)
                                <input type="hidden" name="empresa_id" value="{{ old('empresa_id', $tabelaSelecionada?->empresa_id) }}">
                            @endif
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <div class="flex-1">
                                    <x-input-label for="nome_tabela_interface" value="Nome da tabela" />
                                    <x-text-input id="nome_tabela_interface" name="nome_tabela" class="mt-1 block w-full" :value="old('origem') === 'criar_tabela' ? old('nome_tabela') : ''" placeholder="Ex.: Tabela Agosto 2026" required />
                                    @if(old('origem') === 'criar_tabela')
                                        <x-input-error :messages="$errors->get('nome_tabela')" class="mt-2" />
                                    @endif
                                </div>
                                <div class="flex items-end gap-2">
                                    <button class="inline-flex items-center rounded-md bg-slate-900 px-3 py-2 text-xs font-bold text-white hover:bg-slate-800">
                                        Criar
                                    </button>
                                    <button type="button" @click="criarTabela = false" class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                        Cancelar
                                    </button>
                                </div>
                            </div>
                        </form>
                    @endcan

                    @if($tabelaSelecionada)
                        <div class="mt-3 rounded-md border border-slate-200 bg-slate-50 p-3 text-xs text-slate-700">
                            <p>
                                <span class="font-semibold">Status:</span>
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-bold {{ $tabelaSelecionada->ativo ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">
                                    {{ $tabelaSelecionada->ativo ? 'Ativa para orçamentos' : 'Desativada para orçamentos' }}
                                </span>
                            </p>
                            <p class="mt-1">
                                <span class="font-semibold">Atualização:</span>
                                {{ $tabelaSelecionada->ultima_atualizacao_em?->format('d/m/Y H:i') ?? 'Ainda sem atualização registrada' }}
                                @if($tabelaSelecionada->atualizadoPor)
                                    por {{ $tabelaSelecionada->atualizadoPor->name }}
                                @endif
                            </p>
                            @if($tabelaSelecionada->arquivo_original)
                                <p class="mt-1 truncate"><span class="font-semibold">Arquivo:</span> {{ $tabelaSelecionada->arquivo_original }}</p>
                            @endif
                        </div>

                    @endif
                </div>

                @can('create', App\Models\Item::class)
                    <form method="POST" action="{{ route('orcamentos.itens.importar-tabela') }}" enctype="multipart/form-data" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        @csrf
                        <input type="hidden" name="origem" value="importar_tabela">
                        <h3 class="text-xs font-bold uppercase tracking-wide text-slate-700">Carregar tabela no sistema</h3>

                        @if($isSuperAdmin)
                            <div class="mt-3">
                                <x-input-label for="empresa_id" value="Empresa da tabela" />
                                <select id="empresa_id" name="empresa_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                    <option value="">Selecione a empresa</option>
                                    @foreach($empresas as $empresaOpcao)
                                        <option value="{{ $empresaOpcao->id }}" @selected((string) old('empresa_id', $tabelaSelecionada?->empresa_id) === (string) $empresaOpcao->id)>
                                            {{ $empresaOpcao->nome_fantasia ?: $empresaOpcao->razao_social }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('empresa_id')" class="mt-2" />
                            </div>
                        @endif

                        <div class="mt-3">
                            <x-input-label for="nome_tabela" value="Nome da nova tabela" />
                            <x-text-input id="nome_tabela" name="nome_tabela" class="mt-1 block w-full" :value="old('nome_tabela')" placeholder="Ex.: Tabela Julho 2026" required />
                        </div>

                        <div class="mt-3">
                            <x-input-label for="arquivo" value="Planilha CSV ou Excel (.xlsx)" />
                            <input id="arquivo" name="arquivo" type="file" accept=".csv,.txt,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-50 p-2 text-sm text-gray-700" required>
                        </div>

                        <button class="mt-4 inline-flex items-center justify-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-500">
                            Carregar
                        </button>
                    </form>
                @endcan
            </div>

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            @if($isSuperAdmin)
                                <th class="px-6 py-3 text-left">Empresa</th>
                            @endif
                            <th class="px-6 py-3 text-left">Tabela</th>
                            <th class="px-6 py-3 text-left">Código</th>
                            <th class="px-6 py-3 text-left">Nome</th>
                            <th class="px-6 py-3 text-left">Tipo</th>
                            <th class="px-6 py-3 text-left">Embalagem</th>
                            <th class="px-6 py-3 text-left">Preço-base sem impostos</th>
                            <th class="px-6 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($itens as $item)
                            <tr>
                                @if($isSuperAdmin)
                                    <td class="px-6 py-4">{{ $item->empresa?->nome_fantasia ?: $item->empresa?->razao_social }}</td>
                                @endif
                                <td class="px-6 py-4">{{ $item->tabela?->nome ?? '—' }}</td>
                                <td class="px-6 py-4 font-mono text-sm font-semibold text-gray-700">{{ $item->numero_serie ?: '—' }}</td>
                                <td class="px-6 py-4"><a class="font-medium text-indigo-600" href="{{ route('orcamentos.itens.show', $item) }}">{{ $item->descricao }}</a></td>
                                <td class="px-6 py-4">{{ $item->tipo_venda?->label() ?? 'Unitário' }}</td>
                                <td class="px-6 py-4">{{ number_format((float) ($item->embalagem_quantidade ?? 1), 3, ',', '.') }} {{ $item->tipo_venda?->unidade() ?? 'un.' }}</td>
                                <td class="px-6 py-4">R$ {{ number_format((float) $item->preco_atual, 2, ',', '.') }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-end gap-3">
                                        @unless($isSuperAdmin)
                                            @can('update', $item)
                                                <a class="text-indigo-600" href="{{ route('orcamentos.itens.edit', $item) }}">Editar</a>
                                            @endcan
                                            @can('delete', $item)
                                                <form method="POST" action="{{ route('orcamentos.itens.destroy', $item) }}" data-confirm-message="Confirma a exclusão deste item?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="confirmacao" value="1">
                                                    <button class="text-red-600">Excluir</button>
                                                </form>
                                            @endcan
                                        @else
                                            <span class="text-xs text-gray-500">Visualização</span>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ $isSuperAdmin ? 8 : 7 }}" class="px-6 py-12 text-center text-gray-500">Nenhum item cadastrado nesta tabela.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $itens->links() }}</div>
        </div>
    </div>
</x-app-layout>

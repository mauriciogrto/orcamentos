@php
    $orcamento ??= null;
    $clienteSelecionado = old('cliente_id', $orcamento?->cliente_id);
    $formaSelecionada = old('forma_pagamento', $orcamento?->forma_pagamento?->value ?? 'pix');
    $finalidadeSelecionada = old('finalidade', $orcamento?->finalidade?->value ?? 'revenda');
    $pedidoMinimo = (float) $empresa->pedido_minimo;
    $limiteFreteFob = (float) $empresa->limite_frete_fob;
    $responsavelSelecionado = old('responsavel_id', $orcamento?->user_id ?? Auth::id());
    $draftKey = 'orcamento-form:user:'.Auth::id().':'.($orcamento?->id ? 'edit:'.$orcamento->id : 'create');
    $linhas = old('itens', $orcamento?->itens->map(fn($linha) => ['item_id' => $linha->item_id, 'quantidade' => $linha->quantidade, 'desconto_percentual' => $linha->desconto_percentual])->all() ?? []);
    $clientesParaJs = $clientes->map(fn($cliente) => ['id' => $cliente->id, 'codigo' => $cliente->codigo, 'nome' => $cliente->nome_fantasia ?: $cliente->nome_razao_social, 'cnpj' => $cliente->cnpj, 'telefone' => $cliente->telefone, 'email' => $cliente->email, 'frete_personalizado' => (bool) $cliente->frete_personalizado, 'limite_frete_fob' => $cliente->limite_frete_fob !== null ? (float) $cliente->limite_frete_fob : null])->values();
    $itensParaJs = $catalogoItens->map(fn($item) => [
        'id' => $item->id, 'codigo' => $item->numero_serie, 'descricao' => $item->descricao,
        'tipo_venda' => $item->tipo_venda?->value ?? 'unitario',
        'tipo_label' => $item->tipo_venda?->label() ?? 'Unitário',
        'unidade' => $item->tipo_venda?->unidade() ?? 'un.',
        'embalagem_quantidade' => (float) ($item->embalagem_quantidade ?? 1),
        'preco' => (float) $item->preco_atual, 'ncm' => $item->tributacao?->ncm,
        'icms' => (float) ($item->tributacao?->aliquota_icms ?? 0),
        'ipi' => (float) ($item->tributacao?->aliquota_ipi ?? 0),
        'pis' => (float) ($item->tributacao?->aliquota_pis ?? 0),
        'cofins' => (float) ($item->tributacao?->aliquota_cofins ?? 0),
        'possui_st' => (bool) ($item->tributacao?->possui_st ?? false),
        'mva' => (float) ($item->tributacao?->mva ?? 0),
    ])->values();
    $clienteSelecionadoJs = (string) $clienteSelecionado;
    $buscarItensUrl = route('orcamentos.orcamentos.buscar-itens');
@endphp
<div x-data="orcamentoForm()" @submit="confirmarSalvamento($event)" class="space-y-6 sm:space-y-8">
    @if($errors->any())<div class="rounded-md bg-red-50 p-4 text-sm text-red-700"><p class="font-semibold">Revise os campos informados.</p><ul class="mt-2 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <section class="rounded-lg border p-4 sm:p-5"><h3 class="font-semibold text-gray-900">Dados da empresa</h3><div class="mt-4 grid gap-4 md:grid-cols-2">
        <div><span class="text-xs text-gray-500">Razão social</span><p>{{ $empresa->razao_social }}</p></div><div><span class="text-xs text-gray-500">CNPJ</span><p>{{ $empresa->cnpj ?: '—' }}</p></div>
        <div><span class="text-xs text-gray-500">Telefone</span><p>{{ $empresa->telefone ?: '—' }}</p></div><div><span class="text-xs text-gray-500">E-mail</span><p>{{ $empresa->email ?: '—' }}</p></div>
    </div></section>

    <section class="rounded-lg border p-4 sm:p-5">
        <h3 class="font-semibold text-gray-900">Cliente</h3>
        <input id="cliente_id" type="hidden" name="cliente_id" :value="clienteId">
        <div class="relative mt-4">
            <x-input-label for="busca_cliente" value="Buscar cliente por nome ou CNPJ" />
            <x-text-input id="busca_cliente" type="search" x-model="buscaCliente" @input="erroCliente = ''" class="mt-1 block w-full" placeholder="Digite o nome ou CNPJ e selecione o cliente" autocomplete="off" />
            <p x-show="erroCliente" x-text="erroCliente" class="mt-2 text-sm text-red-600"></p>
            <div x-show="buscaCliente.trim().length > 0" x-cloak class="absolute left-0 right-0 z-30 mt-1 max-h-72 overflow-y-auto rounded-md border border-gray-200 bg-white shadow-lg">
                <template x-for="cliente in clientesFiltrados" :key="cliente.id">
                    <button type="button" @click="selecionarCliente(cliente)" class="block w-full border-b px-4 py-3 text-left last:border-0 hover:bg-indigo-50">
                        <span class="block text-sm font-medium text-gray-900" x-text="cliente.nome"></span>
                        <span class="text-xs text-gray-500" x-text="[cliente.codigo, cliente.cnpj].filter(Boolean).join(' - ')"></span>
                    </button>
                </template>
                <p x-show="clientesFiltrados.length === 0" class="px-4 py-4 text-sm text-gray-500">Nenhum cliente encontrado.</p>
            </div>
        </div>
        <div x-show="clienteAtual" x-cloak class="mt-4 rounded-md border border-indigo-200 bg-indigo-50 p-4">
            <div class="flex items-start justify-between gap-4"><div><p class="font-semibold text-gray-900" x-text="clienteAtual?.nome"></p><p class="text-sm text-gray-600" x-text="[clienteAtual?.codigo, clienteAtual?.cnpj].filter(Boolean).join(' - ')"></p></div><button type="button" @click="limparCliente" class="text-sm text-indigo-700">Trocar cliente</button></div>
            <div class="mt-3 grid gap-3 text-sm md:grid-cols-2"><div><span class="text-xs text-gray-500">Telefone</span><p x-text="clienteAtual?.telefone || '—'"></p></div><div><span class="text-xs text-gray-500">E-mail</span><p x-text="clienteAtual?.email || '—'"></p></div></div>
        </div>
        <p x-show="!clienteAtual" class="mt-4 rounded-md border border-dashed p-4 text-center text-sm text-gray-500">Nenhum cliente selecionado.</p>
    </section>

    <section class="rounded-lg border p-4 sm:p-5">
        <h3 class="font-semibold text-gray-900">Itens</h3>
        <div class="relative mt-4 rounded-md bg-gray-50 p-4">
            <x-input-label for="busca_item" value="Buscar item por código ou nome" />
            <x-text-input id="busca_item" type="search" x-model="buscaItem" @input="buscarItensRemoto" @keydown.enter.prevent="adicionarPrimeiroResultado" class="mt-1 block w-full" placeholder="Digite o código, número de série ou nome do item" autocomplete="off" />
            <p x-show="erroItem" x-text="erroItem" class="mt-2 text-sm text-red-600"></p>
            <p x-show="buscandoItens" x-cloak class="mt-2 text-sm text-indigo-700">Buscando itens...</p>
            <div x-show="buscaItem.trim().length > 0" x-cloak class="absolute left-4 right-4 z-20 mt-1 max-h-72 overflow-y-auto rounded-md border border-gray-200 bg-white shadow-lg">
                <template x-for="item in itensFiltrados" :key="item.id">
                    <button type="button" @click="adicionarItem(item)" class="flex w-full items-center justify-between gap-4 border-b px-4 py-3 text-left last:border-0 hover:bg-indigo-50">
                        <span class="grid flex-1 gap-3 sm:grid-cols-[140px_1fr]">
                            <span>
                                <span class="block text-[11px] font-semibold uppercase tracking-wide text-gray-400">Código</span>
                                <span class="block text-sm font-semibold text-gray-900" x-text="item.codigo || 'Sem código'"></span>
                            </span>
                            <span>
                                <span class="block text-[11px] font-semibold uppercase tracking-wide text-gray-400">Nome</span>
                                <span class="block text-sm font-medium text-gray-900" x-text="item.descricao"></span>
                                <span class="text-xs text-gray-500" x-text="resumoItemCatalogo(item)"></span>
                            </span>
                        </span>
                        <span class="whitespace-nowrap text-sm font-medium" x-text="moeda(item.preco)"></span>
                    </button>
                </template>
                <p x-show="itensFiltrados.length === 0" class="px-4 py-4 text-sm text-gray-500">Nenhum item encontrado na base cadastrada.</p>
            </div>
        </div>

        <p x-show="linhas.length === 0" class="mt-4 rounded-md border border-dashed p-6 text-center text-sm text-gray-500">Busque um item acima para adicioná-lo ao orçamento.</p>
        <div class="mt-4 space-y-4"><template x-for="(linha,index) in linhas" :key="linha.key"><div class="orcamento-item-card grid grid-cols-2 gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 shadow-sm lg:grid-cols-12">
            <div class="md:col-span-2"><label class="text-sm font-medium">Código</label><input type="hidden" :name="`itens[${index}][item_id]`" :value="linha.item_id"><p class="mt-2 font-semibold text-gray-900" x-text="codigoItem(linha)"></p><p class="text-xs text-gray-500" x-text="tipoItem(linha)"></p></div>
            <div class="md:col-span-2"><label class="text-sm font-medium">Nome</label><p class="mt-2 font-medium" x-text="descricaoItem(linha)"></p></div>
            <div class="md:col-span-1"><label class="text-sm font-medium">Preço-base</label><p class="mt-3" x-text="moeda(preco(linha))"></p></div>
            <div class="md:col-span-1"><label class="text-sm font-medium">Qtd.</label><input type="number" min="1" step="1" :name="`itens[${index}][quantidade]`" x-model.number="linha.quantidade" class="mt-1 block w-full rounded-md border-gray-300" required><p x-show="textoEmbalagem(linha)" x-text="textoEmbalagem(linha)" class="mt-2 text-xs text-gray-500"></p></div>
            <div class="md:col-span-1"><label class="text-sm font-medium">Desc. (%)</label><input type="number" min="0" max="100" step="0.01" :name="`itens[${index}][desconto_percentual]`" x-model.number="linha.desconto" class="mt-1 block w-full rounded-md border-gray-300" required></div>
            <div class="md:col-span-1"><label class="text-sm font-medium">Produtos</label><p class="mt-3" x-text="moeda(valorProdutosLinha(linha))"></p></div>
            <div class="md:col-span-2"><label class="text-sm font-medium">Impostos</label><p class="mt-3" x-text="moeda(tributosLinha(linha))"></p><p class="text-xs text-gray-500" x-text="resumoTributos(linha)"></p></div>
            <div class="md:col-span-2"><label class="text-sm font-medium">Total</label><div class="mt-3 flex justify-between"><span class="font-semibold" x-text="moeda(totalLinha(linha))"></span><button type="button" @click="removerItem(index)" class="text-red-600">Remover</button></div></div>
            <div x-show="avisoEmbalagem(linha)" x-cloak class="col-span-2 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 lg:col-span-12">
                <span x-text="avisoEmbalagem(linha)"></span>
            </div>
        </div></template></div>
    </section>

    <section class="grid gap-6 rounded-lg border p-5 md:grid-cols-2"><div><x-input-label for="forma_pagamento" value="Forma de pagamento"/><select id="forma_pagamento" name="forma_pagamento" x-model="formaPagamento" class="mt-1 block w-full rounded-md border-gray-300" required><option value="pix">PIX</option><option value="boleto">Boleto</option><option value="cartao">Cartão</option></select></div>
        <div x-show="formaPagamento === 'boleto'"><x-input-label for="boleto_prazos" value="Prazos do boleto"/><select id="boleto_prazos" name="boleto_prazos" class="mt-1 block w-full rounded-md border-gray-300" :required="formaPagamento === 'boleto'"><option value="">Selecione</option>@foreach(\App\Enums\BoletoPrazo::cases() as $prazo)<option value="{{ $prazo->value }}" @selected(old('boleto_prazos',$orcamento?->boleto_prazos)===$prazo->value)>{{ $prazo->label() }}</option>@endforeach</select></div>
        <div><x-input-label for="finalidade" value="Finalidade"/><select id="finalidade" name="finalidade" class="mt-1 block w-full rounded-md border-gray-300" required>@foreach(\App\Enums\OrcamentoFinalidade::cases() as $finalidade)<option value="{{ $finalidade->value }}" @selected($finalidadeSelecionada === $finalidade->value)>{{ $finalidade->label() }}</option>@endforeach</select></div>
        <div><x-input-label for="validade_dias" value="Validade da proposta (dias)"/><x-text-input id="validade_dias" name="validade_dias" type="number" min="1" max="365" class="mt-1 block w-full" :value="old('validade_dias',$orcamento?->validade_dias ?? 15)" required/></div>
        <div>
            <x-input-label value="Frete automático" />
            <input type="hidden" name="frete_tipo" :value="freteAutomatico">
            <div class="mt-1 rounded-md border border-gray-200 bg-gray-50 px-3 py-2">
                <strong x-text="freteAutomatico.toUpperCase()"></strong>
                <span class="ml-2 text-xs text-gray-500" x-text="textoRegraFrete"></span>
            </div>
        </div>
        <div class="md:col-span-2"><x-input-label for="observacoes" value="Observações"/><textarea id="observacoes" name="observacoes" rows="4" class="mt-1 block w-full rounded-md border-gray-300">{{ old('observacoes',$orcamento?->observacoes) }}</textarea></div>
            <div class="col-span-2 md:col-span-2">
            @hasanyrole('admin_empresa|supervisor')
                <x-input-label for="responsavel_id" value="Responsável pelo orçamento" />
                <select id="responsavel_id" name="responsavel_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">Selecione o responsável</option>
                    @foreach($responsaveis as $responsavel)<option value="{{ $responsavel->id }}" @selected((string)$responsavelSelecionado === (string)$responsavel->id)>{{ $responsavel->name }} — {{ $responsavel->cargo }}</option>@endforeach
                </select>
                <x-input-error :messages="$errors->get('responsavel_id')" class="mt-2" />
            @else
                <span class="text-xs text-gray-500">Responsável</span><p>{{ $orcamento?->vendedor?->name ?? Auth::user()->name }} — {{ $orcamento?->vendedor?->cargo ?? Auth::user()->cargo ?? 'Vendedor' }}</p>
            @endhasanyrole
        </div>
    </section>

    <div x-show="linhas.length > 0 && total < pedidoMinimo" x-cloak class="rounded-md border border-amber-300 bg-amber-50 p-4 text-sm text-amber-800">
        <strong>Pedido abaixo do valor mínimo.</strong>
        O total atual é <span x-text="moeda(total)"></span> e o mínimo configurado é <span x-text="moeda(pedidoMinimo)"></span>. O orçamento ainda pode ser salvo.
    </div>

    <section class="grid grid-cols-2 gap-3 rounded-xl bg-indigo-50 p-4 sm:p-5 md:grid-cols-3 lg:grid-cols-6"><div><span class="text-xs text-gray-600">Subtotal-base</span><p class="font-semibold" x-text="moeda(subtotal)"></p></div><div><span class="text-xs text-gray-600">Desconto efetivo</span><p class="font-semibold" x-text="percentual(descontoEfetivo)"></p></div><div><span class="text-xs text-gray-600">Produtos com desconto</span><p class="font-semibold" x-text="moeda(totalProdutos)"></p></div><div><span class="text-xs text-gray-600">Impostos</span><p class="font-semibold" x-text="moeda(totalImpostos)"></p></div><div class="rounded-lg bg-indigo-600 p-3 text-white"><span class="text-xs text-indigo-100">Total</span><p class="text-lg font-bold" x-text="moeda(total)"></p></div><div><span class="text-xs text-gray-600">Frete</span><p class="font-semibold" x-text="freteAutomatico.toUpperCase()"></p></div></section>

    <input type="hidden" name="acao_apos_salvar" :value="acaoAposSalvar">
    <div class="rounded-md border border-sky-200 bg-sky-50 p-3 text-sm text-sky-800">
        <span x-text="rascunhoLocalMensagem || 'Rascunho local automático ativo. O formulário é salvo no navegador a cada 30 segundos e ao sair da página.'"></span>
        <button type="button" x-show="rascunhoRestaurado" x-cloak @click="descartarRascunhoLocal" class="ml-3 font-semibold text-sky-900 underline">Descartar rascunho recuperado</button>
    </div>
    <div class="sticky bottom-0 z-20 -mx-4 border-t border-gray-200 bg-white/95 p-4 shadow-[0_-10px_25px_rgba(15,23,42,0.08)] backdrop-blur sm:static sm:mx-0 sm:border-0 sm:bg-transparent sm:p-0 sm:shadow-none">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm text-gray-600 sm:hidden">
                <span class="font-semibold text-gray-900">Total:</span>
                <span x-text="moeda(total)"></span>
            </div>
            <x-primary-button class="w-full justify-center py-3 text-base sm:w-auto sm:py-2 sm:text-sm">Salvar orçamento</x-primary-button>
        </div>
    </div>

    <div x-show="mostrarConfirmacao" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4" @keydown.escape.window="mostrarConfirmacao = false">
        <div class="w-full max-w-lg rounded-lg bg-white p-6 shadow-xl" @click.stop>
            <h3 class="text-lg font-semibold text-gray-900">Como deseja salvar o orçamento?</h3>
            <p class="mt-2 text-sm text-gray-600">O orçamento será gravado no sistema nas duas opções.</p>
            <div x-show="avisosEmbalagem.length > 0" x-cloak class="mt-4 rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800">
                <p class="font-semibold">Atenção às embalagens dos itens:</p>
                <ul class="mt-2 list-disc pl-5">
                    <template x-for="aviso in avisosEmbalagem" :key="aviso">
                        <li x-text="aviso"></li>
                    </template>
                </ul>
                <p class="mt-2">O orçamento pode ser salvo, mas não poderá ser finalizado/virar pedido enquanto os múltiplos estiverem fora.</p>
            </div>
            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                <button type="button" @click="salvarComo('pdf')" class="rounded-md bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-500">Salvar em PDF</button>
                <button type="button" @click="salvarComo('sistema')" class="rounded-md border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50">Somente salvar no sistema</button>
            </div>
            <button type="button" @click="mostrarConfirmacao = false" class="mt-4 w-full text-sm text-gray-500 hover:text-gray-700">Voltar e continuar editando</button>
        </div>
    </div>
</div>

@once
<script>
function orcamentoForm() {
    const clientes = @json($clientesParaJs);
    const catalogo = @json($itensParaJs);
    const iniciais = @json($linhas);
    const pedidoMinimo = @json($pedidoMinimo);
    const limiteFreteFob = @json($limiteFreteFob);
    const draftKey = @json($draftKey);
    const buscarItensUrl = @json($buscarItensUrl);
    return {
        clientes, catalogo, pedidoMinimo, limiteFreteFob, draftKey, buscarItensUrl, buscaCliente: '', erroCliente: '', buscaItem: '', erroItem: '', resultadosItens: [], buscandoItens: false, itemSearchTimer: null, clienteId: @json($clienteSelecionadoJs), formaPagamento: @json($formaSelecionada), mostrarConfirmacao: false, salvamentoConfirmado: false, acaoAposSalvar: 'sistema', rascunhoLocalMensagem: '', rascunhoRestaurado: false, autosaveTimer: null,
        linhas: iniciais.map((l,i) => ({key: Date.now()+i, item_id: String(l.item_id ?? ''), quantidade: Number(l.quantidade ?? 1), desconto: Number(l.desconto_percentual ?? 0)})),
        init() { this.restaurarRascunhoLocal(); this.autosaveTimer=setInterval(()=>this.salvarRascunhoLocal(),30000); window.addEventListener('beforeunload',()=>this.salvarRascunhoLocal(true)); this.$watch('clienteId',()=>this.salvarRascunhoLocal()); this.$watch('linhas',()=>this.salvarRascunhoLocal(),{deep:true}); },
        get clientesFiltrados() { const q=this.buscaCliente.toLowerCase().replace(/\D/g,''); const texto=this.normalizar(this.buscaCliente); if (!texto) return []; return this.clientes.filter(c => this.normalizar(c.nome).includes(texto) || this.normalizar(c.codigo||'').includes(texto) || (q && (c.cnpj||'').replace(/\D/g,'').includes(q))).slice(0,10); },
        get clienteAtual() { return this.clientes.find(c => String(c.id)===String(this.clienteId)); },
        selecionarCliente(cliente) { this.clienteId=String(cliente.id); this.buscaCliente=''; this.erroCliente=''; },
        limparCliente() { this.clienteId=''; this.buscaCliente=''; this.$nextTick(() => document.getElementById('busca_cliente')?.focus()); },
        get itensFiltrados() { const q=this.normalizar(this.buscaItem); if (!q) return []; return this.resultadosItens.slice(0,15); },
        buscarItensRemoto() { this.erroItem=''; const termo=this.buscaItem.trim(); clearTimeout(this.itemSearchTimer); if (termo.length < 2) { this.resultadosItens=[]; this.buscandoItens=false; return; } this.itemSearchTimer=setTimeout(()=>this.executarBuscaItens(termo),250); },
        async executarBuscaItens(termo) { this.buscandoItens=true; try { const resposta=await fetch(`${this.buscarItensUrl}?q=${encodeURIComponent(termo)}`, {headers:{'Accept':'application/json'}}); if (!resposta.ok) throw new Error('Falha na busca'); const itens=await resposta.json(); this.mesclarItensCatalogo(itens); if (this.buscaItem.trim() === termo && itens.length===0) this.erroItem='Nenhum item encontrado na base cadastrada.'; } catch (e) { this.erroItem='Não foi possível buscar os itens agora. Tente novamente.'; } finally { this.buscandoItens=false; } },
        async executarBuscaItens(termo) { this.buscandoItens=true; try { const resposta=await fetch(`${this.buscarItensUrl}?q=${encodeURIComponent(termo)}`, {headers:{'Accept':'application/json'}}); if (!resposta.ok) throw new Error('Falha na busca'); const itens=await resposta.json(); if (this.buscaItem.trim() !== termo) return; this.resultadosItens=itens; this.mesclarItensCatalogo(itens); if (itens.length===0) this.erroItem='Nenhum item encontrado na base cadastrada.'; } catch (e) { this.resultadosItens=[]; this.erroItem='Não foi possível buscar os itens agora. Tente novamente.'; } finally { this.buscandoItens=false; } },
        mesclarItensCatalogo(itens) { itens.forEach(item => { const index=this.catalogo.findIndex(i => String(i.id)===String(item.id)); if (index >= 0) this.catalogo[index]=item; else this.catalogo.push(item); }); },
        item(l) { return this.catalogo.find(i => String(i.id)===String(l.item_id)); },
        descricaoItem(l) { return this.item(l)?.descricao || 'Item não encontrado'; },
        resumoItemCatalogo(i) { return [i.tipo_label || 'Unitário', 'Quantidade mínima = '+this.formatarQuantidade(i.embalagem_quantidade || 1)+' '+(i.unidade || 'un.')].join(' · '); },
        codigoItem(l) { const i=this.item(l)||{}; return i.codigo || 'Sem código'; },
        tipoItem(l) { const i=this.item(l)||{}; return i.tipo_label || 'Unitário'; },
        textoEmbalagem(l) { const i=this.item(l); if (!i) return ''; const embalagem=Number(i.embalagem_quantidade || 1); if (embalagem <= 1) return ''; return `Quantidade mínima = ${this.formatarQuantidade(embalagem)} ${i.unidade || 'un.'}`; },
        avisoEmbalagem(l) { const i=this.item(l); if (!i) return ''; const embalagem=Number(i.embalagem_quantidade || 1); const quantidade=Number(l.quantidade || 0); if (embalagem <= 1 || quantidade <= 0) return ''; const proporcao=quantidade/embalagem; if (Math.abs(proporcao-Math.round(proporcao)) < 0.000001) return ''; return `${i.descricao}: quantidade mínima = ${this.formatarQuantidade(embalagem)} ${i.unidade || 'un.'}`; },
        get avisosEmbalagem() { return this.linhas.map(l => this.avisoEmbalagem(l)).filter(Boolean); },
        preco(l) { return Number(this.item(l)?.preco || 0); },
        arredondar(v) { return Math.round(Number(v||0)*100)/100; },
        valorProdutosLinha(l) { return this.arredondar(this.preco(l)*Number(l.quantidade||0)*(1-Number(l.desconto||0)/100)); },
        percentualValor(base, aliquota) { return this.arredondar(base*Number(aliquota||0)/100); },
        calculoTributos(l) { const i=this.item(l)||{}; const base=this.valorProdutosLinha(l); const icms=this.percentualValor(base,i.icms); const ipi=this.percentualValor(base,i.ipi); const pis=this.percentualValor(base,i.pis); const cofins=this.percentualValor(base,i.cofins); const baseSt=i.possui_st?this.arredondar(base*(1+Number(i.mva||0)/100)):0; const icmsSt=i.possui_st?Math.max(0,this.arredondar(this.percentualValor(baseSt,i.icms)-icms)):0; return {icms,ipi,pis,cofins,icmsSt,total:this.arredondar(icms+ipi+pis+cofins+icmsSt)}; },
        tributosLinha(l) { return this.calculoTributos(l).total; },
        resumoTributos(l) { const t=this.calculoTributos(l); return `ICMS ${this.moeda(t.icms)} · IPI ${this.moeda(t.ipi)} · PIS ${this.moeda(t.pis)} · COFINS ${this.moeda(t.cofins)}`+(t.icmsSt?` · ICMS-ST ${this.moeda(t.icmsSt)}`:''); },
        totalLinha(l) { return this.arredondar(this.valorProdutosLinha(l)+this.tributosLinha(l)); },
        get subtotal() { return Math.round(this.linhas.reduce((s,l)=>s+this.preco(l)*Number(l.quantidade||0),0)*100)/100; },
        get totalProdutos() { return this.arredondar(this.linhas.reduce((s,l)=>s+this.valorProdutosLinha(l),0)); },
        get totalImpostos() { return this.arredondar(this.linhas.reduce((s,l)=>s+this.tributosLinha(l),0)); },
        get total() { return Math.round(this.linhas.reduce((s,l)=>s+this.totalLinha(l),0)*100)/100; },
        get limiteFreteAplicado() { return this.clienteAtual?.frete_personalizado && this.clienteAtual?.limite_frete_fob !== null ? Number(this.clienteAtual.limite_frete_fob || 0) : Number(this.limiteFreteFob || 0); },
        get regraFreteCliente() { return Boolean(this.clienteAtual?.frete_personalizado && this.clienteAtual?.limite_frete_fob !== null); },
        get freteAutomatico() { return this.total <= this.limiteFreteAplicado ? 'fob' : 'cif'; },
        get textoRegraFrete() { const origem=this.regraFreteCliente ? 'regra do cliente' : 'regra padrão da empresa'; return (this.freteAutomatico === 'fob' ? 'Total até ' : 'Total acima de ') + this.moeda(this.limiteFreteAplicado) + ' — ' + origem; },
        get descontoEfetivo() { return this.subtotal ? Math.round((1-this.totalProdutos/this.subtotal)*10000)/100 : 0; },
        adicionarItem(item) { if (this.linhas.some(l => String(l.item_id)===String(item.id))) { this.erroItem='Este item já foi adicionado ao orçamento.'; return; } this.linhas.push({key:Date.now()+Math.random(),item_id:String(item.id),quantidade:1,desconto:0}); this.buscaItem=''; this.erroItem=''; },
        adicionarItem(item) { if (this.linhas.some(l => String(l.item_id)===String(item.id))) { this.erroItem='Este item já foi adicionado ao orçamento.'; return; } this.mesclarItensCatalogo([item]); this.linhas.push({key:Date.now()+Math.random(),item_id:String(item.id),quantidade:1,desconto:0}); this.buscaItem=''; this.resultadosItens=[]; this.erroItem=''; },
        adicionarPrimeiroResultado() { const item=this.itensFiltrados[0]; if (item) this.adicionarItem(item); else this.erroItem='Nenhum item encontrado na base cadastrada.'; },
        removerItem(i) { this.linhas.splice(i,1); },
        dadosRascunhoLocal() { const valor=(id)=>document.getElementById(id)?.value ?? ''; const ids=this.linhas.map(l=>String(l.item_id)); const catalogoSelecionado=this.catalogo.filter(i=>ids.includes(String(i.id))); return {salvo_em:new Date().toISOString(), clienteId:this.clienteId, formaPagamento:this.formaPagamento, linhas:this.linhas, catalogo:catalogoSelecionado, finalidade:valor('finalidade'), validade_dias:valor('validade_dias'), boleto_prazos:valor('boleto_prazos'), observacoes:valor('observacoes'), responsavel_id:valor('responsavel_id')}; },
        salvarRascunhoLocal(force=false) { if (this.salvamentoConfirmado) return; const temConteudo=this.clienteId || this.linhas.length>0 || document.getElementById('observacoes')?.value || force; if (!temConteudo) return; try { localStorage.setItem(this.draftKey, JSON.stringify(this.dadosRascunhoLocal())); this.rascunhoLocalMensagem='Rascunho local salvo às '+new Date().toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'})+'.'; } catch (e) { this.rascunhoLocalMensagem='Não foi possível salvar o rascunho local neste navegador.'; } },
        restaurarRascunhoLocal() { try { const bruto=localStorage.getItem(this.draftKey); if (!bruto) return; const draft=JSON.parse(bruto); if (!draft || (!draft.clienteId && (!draft.linhas || draft.linhas.length===0) && !draft.observacoes)) return; this.clienteId=String(draft.clienteId || this.clienteId || ''); this.formaPagamento=draft.formaPagamento || this.formaPagamento; this.mesclarItensCatalogo(draft.catalogo || []); this.linhas=(draft.linhas || []).map((l,i)=>({key:Date.now()+i,item_id:String(l.item_id ?? ''),quantidade:Number(l.quantidade ?? 1),desconto:Number(l.desconto ?? l.desconto_percentual ?? 0)})); this.$nextTick(()=>{ if (document.getElementById('finalidade') && draft.finalidade) document.getElementById('finalidade').value=draft.finalidade; if (document.getElementById('validade_dias') && draft.validade_dias) document.getElementById('validade_dias').value=draft.validade_dias; if (document.getElementById('boleto_prazos')) document.getElementById('boleto_prazos').value=draft.boleto_prazos || ''; if (document.getElementById('observacoes')) document.getElementById('observacoes').value=draft.observacoes || ''; if (document.getElementById('responsavel_id') && draft.responsavel_id) document.getElementById('responsavel_id').value=draft.responsavel_id; }); this.rascunhoRestaurado=true; this.rascunhoLocalMensagem='Rascunho local recuperado. Revise e salve no sistema quando terminar.'; } catch (e) { localStorage.removeItem(this.draftKey); } },
        descartarRascunhoLocal() { localStorage.removeItem(this.draftKey); this.rascunhoRestaurado=false; this.rascunhoLocalMensagem='Rascunho local descartado.'; },
        limparRascunhoLocal() { localStorage.removeItem(this.draftKey); },
        confirmarSalvamento(event) { if (this.salvamentoConfirmado) return; event.preventDefault(); if (!this.clienteId || !this.clienteAtual) { this.erroCliente='Selecione um cliente da base cadastrada.'; document.getElementById('busca_cliente')?.focus(); return; } if (this.linhas.length===0) { this.erroItem='Adicione pelo menos um item ao orçamento.'; document.getElementById('busca_item')?.focus(); return; } this.mostrarConfirmacao=true; },
        salvarComo(acao) { this.acaoAposSalvar=acao; this.salvamentoConfirmado=true; this.mostrarConfirmacao=false; this.limparRascunhoLocal(); this.$nextTick(() => this.$root.closest('form').requestSubmit()); },
        normalizar(v) { return String(v||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().trim(); },
        formatarQuantidade(v) { return Number(v||0).toLocaleString('pt-BR',{minimumFractionDigits:0,maximumFractionDigits:3}); },
        moeda(v) { return Number(v||0).toLocaleString('pt-BR',{style:'currency',currency:'BRL'}); }, percentual(v) { return Number(v||0).toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2})+'%'; }
    };
}
</script>
@endonce

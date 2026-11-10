@php
    $item ??= null;
    $tributacaoAtual = $item?->tributacao;
    $padrao = $item ? $tributacaoAtual : ($tributacaoPadrao ?? null);
    $possuiSt = (bool) old('tributacao.possui_st', $tributacaoAtual?->possui_st ?? false);
    $tipoVenda = old('tipo_venda', $item?->tipo_venda?->value ?? 'unitario');
@endphp

<div class="space-y-8" x-data="{ possuiSt: @js($possuiSt), tipoVenda: @js($tipoVenda) }">
    <section class="space-y-6">
        <h3 class="text-lg font-semibold text-gray-900">Dados do produto</h3>

        <div>
            <x-input-label for="item_tabela_id" value="Tabela de itens" />
            <select id="item_tabela_id" name="item_tabela_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                <option value="">Selecione a tabela</option>
                @foreach($tabelas as $tabela)
                    <option value="{{ $tabela->id }}" @selected((string) old('item_tabela_id', $item?->item_tabela_id ?? request('tabela_id')) === (string) $tabela->id)>
                        {{ $tabela->nome }}
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-500">Todo item precisa pertencer a uma tabela. Use a tabela importada ou a tabela padrão.</p>
            <x-input-error :messages="$errors->get('item_tabela_id')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="descricao" value="Descrição" />
            <x-text-input id="descricao" name="descricao" class="mt-1 block w-full" :value="old('descricao', $item?->descricao)" required />
            <x-input-error :messages="$errors->get('descricao')" class="mt-2" />
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <x-input-label for="numero_serie" value="Número de série" />
                <x-text-input id="numero_serie" name="numero_serie" class="mt-1 block w-full" :value="old('numero_serie', $item?->numero_serie)" />
                <x-input-error :messages="$errors->get('numero_serie')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="tipo_venda" value="Tipo de venda" />
                <select id="tipo_venda" name="tipo_venda" x-model="tipoVenda" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    <option value="unitario" @selected($tipoVenda === 'unitario')>Unitário</option>
                    <option value="metro" @selected($tipoVenda === 'metro')>Metro</option>
                </select>
                <x-input-error :messages="$errors->get('tipo_venda')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="embalagem_quantidade" value="Embalagem / múltiplo de venda" />
                <x-text-input id="embalagem_quantidade" name="embalagem_quantidade" type="number" min="1" step="0.001" class="mt-1 block w-full" :value="old('embalagem_quantidade', $item?->embalagem_quantidade ?? 1)" required />
                <p class="mt-1 text-xs text-gray-500">Ex.: embalagem 10 exige venda em múltiplos de 10 <span x-text="tipoVenda === 'metro' ? 'm' : 'un.'"></span>.</p>
                <x-input-error :messages="$errors->get('embalagem_quantidade')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="preco_atual" value="Preço-base atual, sem impostos (R$)" />
                <x-text-input id="preco_atual" name="preco_atual" type="number" min="0" step="0.01" class="mt-1 block w-full" :value="old('preco_atual', $item?->preco_atual)" required />
                <p class="mt-1 text-xs text-gray-500">O desconto do orçamento incide somente sobre este valor.</p>
                <x-input-error :messages="$errors->get('preco_atual')" class="mt-2" />
            </div>
        </div>
    </section>

    <section class="rounded-lg border border-gray-200 bg-gray-50 p-5">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">Tributação</h3>
            <p class="mt-1 text-sm text-gray-600">As alíquotas do último item cadastrado são sugeridas automaticamente em novos itens.</p>
        </div>

        <div class="mt-5 grid gap-5 md:grid-cols-3">
            <div>
                <x-input-label for="tributacao_ncm" value="NCM" />
                <x-text-input id="tributacao_ncm" name="tributacao[ncm]" inputmode="numeric" maxlength="8" class="mt-1 block w-full" :value="old('tributacao.ncm', $tributacaoAtual?->ncm)" />
                <x-input-error :messages="$errors->get('tributacao.ncm')" class="mt-2" />
                @if($tributacaoAtual?->ncm_validado_em)
                    <p class="mt-1 text-xs text-emerald-700">NCM conferido em {{ $tributacaoAtual->ncm_validado_em->format('d/m/Y H:i') }}.</p>
                @endif
            </div>

            <div>
                <x-input-label for="tributacao_cest" value="CEST" />
                <x-text-input id="tributacao_cest" name="tributacao[cest]" inputmode="numeric" maxlength="7" class="mt-1 block w-full" :value="old('tributacao.cest', $tributacaoAtual?->cest)" />
                <x-input-error :messages="$errors->get('tributacao.cest')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="tributacao_unidade" value="Unidade comercial NF-e" />
                <x-text-input id="tributacao_unidade" name="tributacao[unidade_comercial]" maxlength="10" class="mt-1 block w-full uppercase" :value="old('tributacao.unidade_comercial', $tributacaoAtual?->unidade_comercial ?? 'UN')" required />
                <x-input-error :messages="$errors->get('tributacao.unidade_comercial')" class="mt-2" />
            </div>
        </div>

        <div class="mt-5 grid gap-5 md:grid-cols-2">
            <div>
                <x-input-label for="tributacao_cfop" value="CFOP padrão do produto" />
                <x-text-input id="tributacao_cfop" name="tributacao[cfop]" inputmode="numeric" maxlength="4" class="mt-1 block w-full" :value="old('tributacao.cfop', $tributacaoAtual?->cfop)" />
                <x-input-error :messages="$errors->get('tributacao.cfop')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="tributacao_cst_csosn" value="CST/CSOSN" />
                <x-text-input id="tributacao_cst_csosn" name="tributacao[cst_csosn]" maxlength="4" class="mt-1 block w-full uppercase" :value="old('tributacao.cst_csosn', $tributacaoAtual?->cst_csosn)" />
                <x-input-error :messages="$errors->get('tributacao.cst_csosn')" class="mt-2" />
            </div>
        </div>

        <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                'aliquota_icms' => 'Alíquota de ICMS (%)',
                'aliquota_ipi' => 'Alíquota de IPI (%)',
                'aliquota_pis' => 'Alíquota de PIS (%)',
                'aliquota_cofins' => 'Alíquota de COFINS (%)',
            ] as $campo => $rotulo)
                <div>
                    <x-input-label :for="'tributacao_'.$campo" :value="$rotulo" />
                    <x-text-input :id="'tributacao_'.$campo" :name="'tributacao['.$campo.']'" type="number" min="0" max="100" step="0.0001" class="mt-1 block w-full" :value="old('tributacao.'.$campo, $padrao?->{$campo} ?? 0)" required />
                    <x-input-error :messages="$errors->get('tributacao.'.$campo)" class="mt-2" />
                </div>
            @endforeach
        </div>

        <div class="mt-5">
            <x-input-label value="Possui Substituição Tributária (ST)?" />
            <div class="mt-2 flex gap-6">
                <label class="inline-flex items-center gap-2"><input type="radio" name="tributacao[possui_st]" value="1" x-model="possuiSt" @checked($possuiSt) required> Sim</label>
                <label class="inline-flex items-center gap-2"><input type="radio" name="tributacao[possui_st]" value="0" x-model="possuiSt" @checked(! $possuiSt) required> Não</label>
            </div>
            <x-input-error :messages="$errors->get('tributacao.possui_st')" class="mt-2" />
        </div>

        <div x-show="String(possuiSt) === 'true' || String(possuiSt) === '1'" x-cloak class="mt-5 grid gap-5 md:grid-cols-2">
            <div>
                <x-input-label for="tributacao_mva" value="MVA (%)" />
                <x-text-input id="tributacao_mva" name="tributacao[mva]" type="number" min="0" max="999.9999" step="0.0001" class="mt-1 block w-full" :value="old('tributacao.mva', $tributacaoAtual?->mva)" x-bind:required="String(possuiSt) === 'true' || String(possuiSt) === '1'" />
                <x-input-error :messages="$errors->get('tributacao.mva')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="tributacao_observacoes" value="Observações fiscais" />
                <textarea id="tributacao_observacoes" name="tributacao[observacoes_fiscais]" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('tributacao.observacoes_fiscais', $tributacaoAtual?->observacoes_fiscais) }}</textarea>
                <x-input-error :messages="$errors->get('tributacao.observacoes_fiscais')" class="mt-2" />
            </div>
        </div>
    </section>

    <div class="flex gap-3">
        <x-primary-button>Salvar item</x-primary-button>
    </div>
</div>

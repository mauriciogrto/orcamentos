<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold">Configuração Fiscal</h2>
                <p class="mt-1 text-sm text-gray-600">{{ $empresa->razao_social }}</p>
            </div>
            <x-back-link :href="route('fiscal.index')" label="Voltar" />
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="rounded-md bg-green-50 p-4 text-green-800">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('fiscal.configuracao.update') }}" enctype="multipart/form-data" class="space-y-6 rounded-lg bg-white p-6 shadow-sm">
                @csrf
                @method('PUT')

                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <x-input-label for="ambiente_nfe" value="Ambiente NF-e" />
                        <select id="ambiente_nfe" name="ambiente_nfe" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            <option value="homologacao" @selected(old('ambiente_nfe', $configuracao->ambiente_nfe) === 'homologacao')>Homologação</option>
                            <option value="producao" @selected(old('ambiente_nfe', $configuracao->ambiente_nfe) === 'producao')>Produção</option>
                        </select>
                        <x-input-error :messages="$errors->get('ambiente_nfe')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="regime_tributario" value="Regime tributário" />
                        <select id="regime_tributario" name="regime_tributario" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">Selecione</option>
                            <option value="simples_nacional" @selected(old('regime_tributario', $configuracao->regime_tributario) === 'simples_nacional')>Simples Nacional</option>
                            <option value="lucro_presumido" @selected(old('regime_tributario', $configuracao->regime_tributario) === 'lucro_presumido')>Lucro Presumido</option>
                            <option value="lucro_real" @selected(old('regime_tributario', $configuracao->regime_tributario) === 'lucro_real')>Lucro Real</option>
                        </select>
                        <x-input-error :messages="$errors->get('regime_tributario')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="inscricao_estadual" value="Inscrição estadual" />
                        <x-text-input id="inscricao_estadual" name="inscricao_estadual" class="mt-1 block w-full" :value="old('inscricao_estadual', $configuracao->inscricao_estadual)" />
                        <x-input-error :messages="$errors->get('inscricao_estadual')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="certificado_a1_validade" value="Validade do certificado A1" />
                        <x-text-input id="certificado_a1_validade" name="certificado_a1_validade" type="date" class="mt-1 block w-full" :value="old('certificado_a1_validade', $configuracao->certificado_a1_validade?->format('Y-m-d'))" />
                        <x-input-error :messages="$errors->get('certificado_a1_validade')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="certificado_a1" value="Certificado A1 (.pfx/.p12)" />
                        <input id="certificado_a1" name="certificado_a1" type="file" accept=".pfx,.p12" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        @if($configuracao->certificado_a1_path)
                            <p class="mt-1 text-xs text-emerald-700">Certificado já configurado. Envie outro arquivo apenas se quiser substituir.</p>
                        @endif
                        <x-input-error :messages="$errors->get('certificado_a1')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="certificado_a1_senha" value="Senha do certificado A1" />
                        <x-text-input id="certificado_a1_senha" name="certificado_a1_senha" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                        <p class="mt-1 text-xs text-gray-500">Deixe em branco para manter a senha atual.</p>
                        <x-input-error :messages="$errors->get('certificado_a1_senha')" class="mt-2" />
                    </div>
                </div>

                <div class="border-t pt-6">
                    <h3 class="font-semibold text-slate-900">Numeração e operação padrão</h3>
                    <div class="mt-5 grid gap-5 md:grid-cols-2">
                        <div>
                            <x-input-label for="serie_nfe" value="Série NF-e" />
                            <x-text-input id="serie_nfe" name="serie_nfe" class="mt-1 block w-full" :value="old('serie_nfe', $configuracao->serie_nfe ?? '1')" required />
                            <x-input-error :messages="$errors->get('serie_nfe')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="proximo_numero_nfe" value="Próximo número NF-e" />
                            <x-text-input id="proximo_numero_nfe" name="proximo_numero_nfe" type="number" min="1" class="mt-1 block w-full" :value="old('proximo_numero_nfe', $configuracao->proximo_numero_nfe ?? 1)" required />
                            <x-input-error :messages="$errors->get('proximo_numero_nfe')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="natureza_operacao_padrao" value="Natureza da operação padrão" />
                            <x-text-input id="natureza_operacao_padrao" name="natureza_operacao_padrao" class="mt-1 block w-full" :value="old('natureza_operacao_padrao', $configuracao->natureza_operacao_padrao)" placeholder="Ex.: Venda de mercadoria" />
                            <x-input-error :messages="$errors->get('natureza_operacao_padrao')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="cfop_padrao" value="CFOP padrão" />
                            <x-text-input id="cfop_padrao" name="cfop_padrao" inputmode="numeric" maxlength="4" class="mt-1 block w-full" :value="old('cfop_padrao', $configuracao->cfop_padrao)" />
                            <x-input-error :messages="$errors->get('cfop_padrao')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-amber-50 p-4 text-sm text-amber-900">
                    Estes dados são usados somente no módulo Fiscal/NF-e. O orçamento continua simples e não exige essas informações.
                </div>

                <x-primary-button>Salvar configuração fiscal</x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>

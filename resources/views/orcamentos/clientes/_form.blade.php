@php
    $cliente ??= null;
@endphp

<div class="space-y-6" x-data="clienteLookup()">
    <div>
        <x-input-label value="Código do cliente" />
        <div class="mt-1 rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-semibold text-gray-700">
            {{ $cliente?->codigo ?: 'Será gerado automaticamente ao salvar' }}
        </div>
        <p class="mt-1 text-xs text-gray-500">O código é definitivo e não pode ser editado.</p>
    </div>

    <div>
        <x-input-label for="cnpj" value="CNPJ" />
        <div class="mt-1 flex gap-2">
            <x-text-input id="cnpj" name="cnpj" class="block w-full" maxlength="18" :value="old('cnpj', $cliente?->cnpj)" required />
            <button type="button" @click="buscarCnpj" :disabled="consultandoCnpj" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">
                <span x-show="!consultandoCnpj">Buscar CNPJ</span>
                <span x-show="consultandoCnpj">Consultando...</span>
            </button>
        </div>
        <x-input-error :messages="$errors->get('cnpj')" class="mt-2" />
        <p x-show="mensagemCnpj" x-text="mensagemCnpj" class="mt-2 text-sm" :class="erroCnpj ? 'text-amber-700' : 'text-green-700'"></p>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <x-input-label for="nome_razao_social" value="Razão social" />
            <x-text-input id="nome_razao_social" name="nome_razao_social" class="mt-1 block w-full" :value="old('nome_razao_social', $cliente?->nome_razao_social)" required />
            <x-input-error :messages="$errors->get('nome_razao_social')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="nome_fantasia" value="Nome fantasia" />
            <x-text-input id="nome_fantasia" name="nome_fantasia" class="mt-1 block w-full" maxlength="12" :value="old('nome_fantasia', $cliente?->nome_fantasia)" required />
            <p class="mt-1 text-xs text-gray-500">Use um nome curto para aparecer nas listas. Máximo de 12 caracteres.</p>
            <x-input-error :messages="$errors->get('nome_fantasia')" class="mt-2" />
        </div>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <x-input-label for="telefone" value="Telefone" />
            <x-text-input id="telefone" name="telefone" class="mt-1 block w-full" :value="old('telefone', $cliente?->telefone)" />
            <x-input-error :messages="$errors->get('telefone')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="email" value="E-mail" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $cliente?->email)" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 p-4">
        <h3 class="text-sm font-semibold text-gray-900">Dados fiscais do cliente</h3>
        <p class="mt-1 text-xs text-gray-500">O orçamento continua simples. Estes dados serão cobrados apenas na pré-validação da NF-e.</p>

        <div class="mt-4 grid gap-6 md:grid-cols-2">
            <div>
                <x-input-label for="indicador_ie" value="Indicador de inscrição estadual" />
                <select id="indicador_ie" name="indicador_ie" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    <option value="nao_contribuinte" @selected(old('indicador_ie', $cliente?->indicador_ie ?? 'nao_contribuinte') === 'nao_contribuinte')>Não contribuinte</option>
                    <option value="contribuinte" @selected(old('indicador_ie', $cliente?->indicador_ie) === 'contribuinte')>Contribuinte de ICMS</option>
                    <option value="isento" @selected(old('indicador_ie', $cliente?->indicador_ie) === 'isento')>Isento</option>
                </select>
                <x-input-error :messages="$errors->get('indicador_ie')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="inscricao_estadual" value="Inscrição estadual" />
                <x-text-input id="inscricao_estadual" name="inscricao_estadual" class="mt-1 block w-full" :value="old('inscricao_estadual', $cliente?->inscricao_estadual)" />
                <x-input-error :messages="$errors->get('inscricao_estadual')" class="mt-2" />
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 p-4">
        <h3 class="text-sm font-semibold text-gray-900">Endereço</h3>
        <p class="mt-1 text-xs text-gray-500">Informe o CEP para buscar automaticamente pela BrasilAPI. Se a consulta falhar, preencha manualmente.</p>

        <div class="mt-4 grid gap-6 md:grid-cols-3">
            <div class="md:col-span-1">
                <x-input-label for="cep" value="CEP" />
                <div class="mt-1 flex gap-2">
                    <x-text-input id="cep" name="cep" class="block w-full" maxlength="9" :value="old('cep', $cliente?->cep)" />
                    <button type="button" @click="buscarCep" :disabled="consultandoCep" class="rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">
                        <span x-show="!consultandoCep">Buscar</span>
                        <span x-show="consultandoCep">...</span>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('cep')" class="mt-2" />
            </div>
            <div class="md:col-span-2">
                <x-input-label for="logradouro" value="Logradouro" />
                <x-text-input id="logradouro" name="logradouro" class="mt-1 block w-full" :value="old('logradouro', $cliente?->logradouro)" />
                <x-input-error :messages="$errors->get('logradouro')" class="mt-2" />
            </div>
        </div>

        <div class="mt-4 grid gap-6 md:grid-cols-4">
            <div>
                <x-input-label for="numero" value="Número" />
                <x-text-input id="numero" name="numero" class="mt-1 block w-full" :value="old('numero', $cliente?->numero)" />
                <x-input-error :messages="$errors->get('numero')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="complemento" value="Complemento" />
                <x-text-input id="complemento" name="complemento" class="mt-1 block w-full" :value="old('complemento', $cliente?->complemento)" />
                <x-input-error :messages="$errors->get('complemento')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="bairro" value="Bairro" />
                <x-text-input id="bairro" name="bairro" class="mt-1 block w-full" :value="old('bairro', $cliente?->bairro)" />
                <x-input-error :messages="$errors->get('bairro')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="uf" value="UF" />
                <x-text-input id="uf" name="uf" class="mt-1 block w-full uppercase" maxlength="2" :value="old('uf', $cliente?->uf)" />
                <x-input-error :messages="$errors->get('uf')" class="mt-2" />
            </div>
        </div>

        <div class="mt-4">
            <x-input-label for="cidade" value="Cidade" />
            <x-text-input id="cidade" name="cidade" class="mt-1 block w-full" :value="old('cidade', $cliente?->cidade)" />
            <x-input-error :messages="$errors->get('cidade')" class="mt-2" />
        </div>

        <p x-show="mensagemCep" x-text="mensagemCep" class="mt-3 text-sm" :class="erroCep ? 'text-amber-700' : 'text-green-700'"></p>
    </div>
@if ($vendedores->isNotEmpty())
        <div>
            <x-input-label for="user_id" value="Vendedor responsável (opcional)" />
            <select id="user_id" name="user_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                <option value="">Sem vendedor atribuído</option>
                @foreach ($vendedores as $vendedor)
                    <option value="{{ $vendedor->id }}" @selected((string) old('user_id', $cliente?->user_id) === (string) $vendedor->id)>{{ $vendedor->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
        </div>
    @elseif (auth()->user()->hasAnyRole(['admin_empresa', 'supervisor']))
        <p class="rounded-md bg-gray-50 p-3 text-sm text-gray-600">Não há vendedores cadastrados. O cliente será salvo sem vendedor atribuído.</p>
    @else
        <p class="rounded-md bg-gray-50 p-3 text-sm text-gray-600">Você será definido automaticamente como vendedor responsável.</p>
    @endif

    <div class="flex gap-3">
        <x-primary-button>Salvar cliente</x-primary-button>
    </div>
</div>

<script>
function clienteLookup() {
    return {
        consultandoCnpj: false,
        consultandoCep: false,
        mensagemCnpj: '',
        mensagemCep: '',
        erroCnpj: false,
        erroCep: false,

        preencherEndereco(data) {
            const campos = {
                cep: data.cep,
                logradouro: data.logradouro,
                bairro: data.bairro,
                cidade: data.cidade,
                uf: data.uf,
                numero: data.numero,
                complemento: data.complemento,
            };

            Object.entries(campos).forEach(([id, value]) => {
                if (value !== undefined && value !== null && document.getElementById(id)) {
                    document.getElementById(id).value = value;
                }
            });
        },

        async buscarCnpj() {
            const cnpj = document.getElementById('cnpj').value.replace(/\D/g, '');
            if (cnpj.length !== 14) {
                this.erroCnpj = true;
                this.mensagemCnpj = 'Informe um CNPJ com 14 dígitos.';
                return;
            }

            this.consultandoCnpj = true;
            try {
                const url = @js(route('orcamentos.cnpj.buscar', ['cnpj' => '__CNPJ__'])).replace('__CNPJ__', cnpj);
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                const payload = await response.json();

                if (!response.ok) {
                    throw new Error(payload.message || 'Consulta indisponível.');
                }

                document.getElementById('nome_razao_social').value = payload.data.razao_social || '';
                document.getElementById('nome_fantasia').value = (payload.data.nome_fantasia || payload.data.razao_social || '').slice(0, 12);
                document.getElementById('telefone').value = payload.data.telefone || '';
                document.getElementById('email').value = payload.data.email || '';
                this.preencherEndereco(payload.data);
                this.erroCnpj = false;
                this.mensagemCnpj = 'Dados encontrados. Revise antes de salvar.';
            } catch (error) {
                this.erroCnpj = true;
                this.mensagemCnpj = error.message + ' Você pode preencher manualmente.';
            } finally {
                this.consultandoCnpj = false;
            }
        },

        async buscarCep() {
            const cep = document.getElementById('cep').value.replace(/\D/g, '');
            if (cep.length !== 8) {
                this.erroCep = true;
                this.mensagemCep = 'Informe um CEP com 8 dígitos.';
                return;
            }

            this.consultandoCep = true;
            try {
                const url = @js(route('orcamentos.cep.buscar', ['cep' => '__CEP__'])).replace('__CEP__', cep);
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                const payload = await response.json();

                if (!response.ok) {
                    throw new Error(payload.message || 'Consulta indisponível.');
                }

                this.preencherEndereco(payload.data);
                this.erroCep = false;
                this.mensagemCep = 'Endereço encontrado. Complete número e complemento, se necessário.';
            } catch (error) {
                this.erroCep = true;
                this.mensagemCep = error.message + ' Você pode preencher manualmente.';
            } finally {
                this.consultandoCep = false;
            }
        },
    };
}
</script>

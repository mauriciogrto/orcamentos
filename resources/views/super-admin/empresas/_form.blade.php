@php
    use App\Enums\EmpresaStatus;

    $empresa ??= null;
@endphp

<div class="space-y-8" x-data="empresaLookup()">
    <section class="space-y-6">
        <h3 class="text-lg font-semibold text-gray-900">Dados da empresa</h3>

        <div>
            <x-input-label for="cnpj" value="CNPJ" />
            <div class="mt-1 flex gap-2">
                <x-text-input id="cnpj" name="cnpj" type="text" class="block w-full" maxlength="18" :value="old('cnpj', $empresa?->cnpj)" required />
                <button type="button" @click="buscarCnpj" :disabled="consultandoCnpj" class="whitespace-nowrap rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">
                    <span x-show="!consultandoCnpj">Buscar CNPJ</span>
                    <span x-show="consultandoCnpj">Consultando...</span>
                </button>
            </div>
            <x-input-error :messages="$errors->get('cnpj')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="razao_social" value="Razão social" />
            <x-text-input id="razao_social" name="razao_social" type="text" class="mt-1 block w-full" :value="old('razao_social', $empresa?->razao_social)" required />
            <x-input-error :messages="$errors->get('razao_social')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="nome_fantasia" value="Nome fantasia (opcional, preenchimento manual)" />
            <x-text-input id="nome_fantasia" name="nome_fantasia" type="text" class="mt-1 block w-full" :value="old('nome_fantasia', $empresa?->nome_fantasia)" />
            <x-input-error :messages="$errors->get('nome_fantasia')" class="mt-2" />
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <x-input-label for="telefone" value="Telefone" />
                <x-text-input id="telefone" name="telefone" type="text" class="mt-1 block w-full" :value="old('telefone', $empresa?->telefone)" />
                <x-input-error :messages="$errors->get('telefone')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="email" value="E-mail" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $empresa?->email)" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
        </div>
    </section>

    <section class="rounded-lg border border-gray-200 bg-gray-50 p-5">
        <h3 class="text-lg font-semibold text-gray-900">Endereço</h3>
        <div class="mt-5 grid gap-5 md:grid-cols-3">
            <div>
                <x-input-label for="cep" value="CEP" />
                <div class="mt-1 flex gap-2">
                    <x-text-input id="cep" name="cep" type="text" maxlength="9" class="block w-full" :value="old('cep', $empresa?->cep)" />
                    <button type="button" @click="buscarCep" :disabled="consultandoCep" class="whitespace-nowrap rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">Buscar</button>
                </div>
                <x-input-error :messages="$errors->get('cep')" class="mt-2" />
            </div>
            <div class="md:col-span-2">
                <x-input-label for="logradouro" value="Logradouro" />
                <x-text-input id="logradouro" name="logradouro" class="mt-1 block w-full" :value="old('logradouro', $empresa?->logradouro)" />
                <x-input-error :messages="$errors->get('logradouro')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="numero" value="Número" />
                <x-text-input id="numero" name="numero" class="mt-1 block w-full" :value="old('numero', $empresa?->numero)" />
                <x-input-error :messages="$errors->get('numero')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="complemento" value="Complemento" />
                <x-text-input id="complemento" name="complemento" class="mt-1 block w-full" :value="old('complemento', $empresa?->complemento)" />
                <x-input-error :messages="$errors->get('complemento')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="bairro" value="Bairro" />
                <x-text-input id="bairro" name="bairro" class="mt-1 block w-full" :value="old('bairro', $empresa?->bairro)" />
                <x-input-error :messages="$errors->get('bairro')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="cidade" value="Cidade" />
                <x-text-input id="cidade" name="cidade" class="mt-1 block w-full" :value="old('cidade', $empresa?->cidade)" />
                <x-input-error :messages="$errors->get('cidade')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="uf" value="UF" />
                <x-text-input id="uf" name="uf" maxlength="2" class="mt-1 block w-full uppercase" :value="old('uf', $empresa?->uf)" />
                <x-input-error :messages="$errors->get('uf')" class="mt-2" />
            </div>
        </div>
    </section>

    <section class="grid gap-6 md:grid-cols-2">
        <div>
            <x-input-label for="status" value="Status" />
            <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach (EmpresaStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $empresa?->status?->value ?? EmpresaStatus::Ativa->value) === $status->value)>
                        {{ $status->label() }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('status')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="logo" value="Logo (JPG, PNG ou WebP, até 2 MB)" />
            <input id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:font-semibold" />
            <x-input-error :messages="$errors->get('logo')" class="mt-2" />
            @if ($empresa?->logo_path)
                <img src="{{ Storage::disk('public')->url($empresa->logo_path) }}" alt="Logo atual" class="mt-3 h-16 max-w-48 object-contain">
                <p class="mt-1 text-xs text-green-700">Este logo será aplicado automaticamente no cabeçalho dos orçamentos em PDF.</p>
            @elseif ($empresa)
                <p class="mt-2 text-xs text-amber-700">Nenhum logo cadastrado. Envie uma imagem para exibi-la no cabeçalho dos orçamentos em PDF.</p>
            @endif
        </div>
    </section>

    <p x-show="mensagem" x-text="mensagem" class="rounded-md p-3 text-sm" :class="erro ? 'bg-amber-50 text-amber-800' : 'bg-green-50 text-green-800'"></p>

    <div class="flex items-center gap-3">
        <x-primary-button>Salvar empresa</x-primary-button>
    </div>
</div>

<script>
function empresaLookup() {
    return {
        consultandoCnpj: false,
        consultandoCep: false,
        mensagem: '',
        erro: false,
        preencherEndereco(data) {
            ['cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'uf'].forEach((campo) => {
                const el = document.getElementById(campo);
                if (el && data[campo]) el.value = data[campo];
            });
        },
        async buscarCnpj() {
            const cnpj = document.getElementById('cnpj').value.replace(/\D/g, '');
            this.mensagem = '';
            if (cnpj.length !== 14) {
                this.erro = true;
                this.mensagem = 'Informe um CNPJ com 14 dígitos.';
                return;
            }
            this.consultandoCnpj = true;
            try {
                const url = @js(route('super-admin.cnpj.buscar', ['cnpj' => '__CNPJ__'])).replace('__CNPJ__', cnpj);
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                const payload = await response.json();
                if (!response.ok) throw new Error(payload.message || 'Não foi possível consultar o CNPJ.');
                document.getElementById('razao_social').value = payload.data.razao_social || '';
                document.getElementById('telefone').value = payload.data.telefone || '';
                document.getElementById('email').value = payload.data.email || '';
                this.preencherEndereco(payload.data || {});
                this.erro = false;
                this.mensagem = 'Dados encontrados. Revise as informações antes de salvar.';
            } catch (error) {
                this.erro = true;
                this.mensagem = error.message + ' Você pode continuar preenchendo manualmente.';
            } finally {
                this.consultandoCnpj = false;
            }
        },
        async buscarCep() {
            const cep = document.getElementById('cep').value.replace(/\D/g, '');
            this.mensagem = '';
            if (cep.length !== 8) {
                this.erro = true;
                this.mensagem = 'Informe um CEP com 8 dígitos.';
                return;
            }
            this.consultandoCep = true;
            try {
                const url = @js(route('super-admin.cep.buscar', ['cep' => '__CEP__'])).replace('__CEP__', cep);
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                const payload = await response.json();
                if (!response.ok) throw new Error(payload.message || 'Não foi possível consultar o CEP.');
                this.preencherEndereco(payload.data || {});
                this.erro = false;
                this.mensagem = 'Endereço encontrado. Complete número e complemento, se necessário.';
            } catch (error) {
                this.erro = true;
                this.mensagem = error.message + ' Você pode preencher manualmente.';
            } finally {
                this.consultandoCep = false;
            }
        },
    };
}
</script>

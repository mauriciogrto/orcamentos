@php
    $faixa ??= null;
@endphp

<div class="space-y-6">
    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <x-input-label for="desconto_inicial_percentual" value="Desconto inicial (%)" />
            <x-text-input id="desconto_inicial_percentual" name="desconto_inicial_percentual" type="number" min="0" max="100" step="0.01" class="mt-1 block w-full" :value="old('desconto_inicial_percentual', $faixa?->desconto_inicial_percentual ?? $faixa?->desconto_percentual)" required />
            <x-input-error :messages="$errors->get('desconto_inicial_percentual')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="desconto_final_percentual" value="Desconto final (%)" />
            <x-text-input id="desconto_final_percentual" name="desconto_final_percentual" type="number" min="0" max="100" step="0.01" class="mt-1 block w-full" :value="old('desconto_final_percentual', $faixa?->desconto_final_percentual ?? $faixa?->desconto_percentual)" required />
            <p class="mt-1 text-xs text-gray-500">Ex.: de 10% até 50% aplica a comissão cadastrada abaixo.</p>
            <x-input-error :messages="$errors->get('desconto_final_percentual')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="comissao_percentual" value="Comissão (%)" />
        <x-text-input id="comissao_percentual" name="comissao_percentual" type="number" min="0" max="100" step="0.01" class="mt-1 block w-full" :value="old('comissao_percentual', $faixa?->comissao_percentual)" required />
        <x-input-error :messages="$errors->get('comissao_percentual')" class="mt-2" />
    </div>

    <div class="flex items-center gap-4">
        <x-primary-button>Salvar faixa</x-primary-button>
    </div>
</div>

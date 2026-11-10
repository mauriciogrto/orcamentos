<?php

namespace App\Http\Requests\Orcamentos;

use App\Enums\RoleName;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('cliente'));
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cnpj' => $this->filled('cnpj') ? preg_replace('/\D/', '', $this->input('cnpj')) : null,
            'cep' => $this->filled('cep') ? preg_replace('/\D/', '', $this->input('cep')) : null,
            'uf' => $this->filled('uf') ? mb_strtoupper((string) $this->input('uf')) : null,
            'indicador_ie' => $this->input('indicador_ie') ?: 'nao_contribuinte',
        ]);
    }

    public function rules(): array
    {
        $podeEscolherVendedor = $this->user()->can('clientes.editar_todos');

        return [
            'nome_razao_social' => ['required', 'string', 'max:255'],
            'nome_fantasia' => ['required', 'string', 'max:12'],
            'cnpj' => [
                'required', 'digits:14',
                Rule::unique('clientes', 'cnpj')
                    ->where('empresa_id', $this->user()->empresa_id)
                    ->ignore($this->route('cliente')),
            ],
            'telefone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'inscricao_estadual' => ['nullable', 'string', 'max:20'],
            'indicador_ie' => ['required', Rule::in(['contribuinte', 'isento', 'nao_contribuinte'])],
            'cep' => ['nullable', 'digits:8'],
            'logradouro' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:30'],
            'complemento' => ['nullable', 'string', 'max:255'],
            'bairro' => ['nullable', 'string', 'max:255'],
            'cidade' => ['nullable', 'string', 'max:255'],
            'uf' => ['nullable', 'string', 'size:2'],
            'user_id' => [
                Rule::prohibitedIf(! $podeEscolherVendedor),
                'nullable',
                'integer',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value && ! User::query()->role(RoleName::Vendedor->value)->whereKey($value)->exists()) {
                        $fail('Selecione um vendedor válido desta empresa.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'cnpj.unique' => 'Este CNPJ já está cadastrado para outro cliente desta empresa.',
        ];
    }
}

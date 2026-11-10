<?php

namespace App\Http\Requests\Orcamentos;

use App\Enums\RoleName;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRepresentacaoConfiguracaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([RoleName::AdminEmpresa->value, RoleName::SuperAdmin->value]) === true;
    }

    public function rules(): array
    {
        return [
            'empresa_id' => [
                $this->user()?->hasRole(RoleName::SuperAdmin->value) ? 'required' : 'nullable',
                'integer',
                Rule::exists('empresas', 'id'),
            ],
            'pedido_minimo' => ['required', 'decimal:0,2', 'min:0'],
            'limite_frete_fob' => ['required', 'decimal:0,2', 'min:0', 'gte:pedido_minimo'],
        ];
    }

    public function messages(): array
    {
        return [
            'limite_frete_fob.gte' => 'O limite do frete FOB deve ser igual ou maior que o valor mínimo do pedido.',
        ];
    }
}

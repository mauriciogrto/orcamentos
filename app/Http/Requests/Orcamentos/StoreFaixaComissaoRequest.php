<?php

namespace App\Http\Requests\Orcamentos;

use App\Models\FaixaComissao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreFaixaComissaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', FaixaComissao::class);
    }

    public function rules(): array
    {
        return [
            'empresa_id' => ['nullable', 'integer', Rule::exists('empresas', 'id')],
            'desconto_inicial_percentual' => ['required', 'decimal:0,2', 'min:0', 'max:100'],
            'desconto_final_percentual' => ['required', 'decimal:0,2', 'min:0', 'max:100', 'gte:desconto_inicial_percentual'],
            'desconto_percentual' => ['required', 'decimal:0,2', 'min:0', 'max:100'],
            'comissao_percentual' => ['required', 'decimal:0,2', 'min:0', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $inicio = $this->input('desconto_inicial_percentual', $this->input('desconto_percentual'));
        $fim = $this->input('desconto_final_percentual', $this->input('desconto_percentual'));

        $this->merge([
            'desconto_inicial_percentual' => $inicio,
            'desconto_final_percentual' => $fim,
            'desconto_percentual' => $inicio,
        ]);
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $inicio = (float) $this->input('desconto_inicial_percentual');
            $fim = (float) $this->input('desconto_final_percentual');
            $empresaId = $this->input('empresa_id') ?: $this->user()->empresa_id;

            $sobreposta = FaixaComissao::query()
                ->where('empresa_id', $empresaId)
                ->where('desconto_inicial_percentual', '<=', $fim)
                ->where('desconto_final_percentual', '>=', $inicio)
                ->exists();

            if ($sobreposta) {
                $validator->errors()->add('desconto_inicial_percentual', 'Já existe uma faixa de comissão sobreposta a este intervalo.');
                $validator->errors()->add('desconto_percentual', 'Já existe uma faixa de comissão sobreposta a este intervalo.');
            }
        }];
    }
}

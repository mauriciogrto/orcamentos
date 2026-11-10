<?php

namespace App\Http\Requests\Orcamentos;

use App\Models\FaixaComissao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class UpdateFaixaComissaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('faixa'));
    }

    public function rules(): array
    {
        return [
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

            $faixa = $this->route('faixa');
            $inicio = (float) $this->input('desconto_inicial_percentual');
            $fim = (float) $this->input('desconto_final_percentual');

            $sobreposta = FaixaComissao::query()
                ->where('empresa_id', $faixa->empresa_id)
                ->whereKeyNot($faixa->id)
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

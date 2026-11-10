<?php

namespace App\Http\Requests\Orcamentos;

use App\Models\FaixaComissao;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class GenerateComissaoRelatorioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('viewAny', FaixaComissao::class);
    }

    public function rules(): array
    {
        return [
            'mes' => ['required', 'date_format:Y-m'],
            'vendedor_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(
                    fn (Builder $query) => $query
                        ->where('empresa_id', $this->user()->empresa_id)
                        ->whereNull('deleted_at')
                ),
            ],
        ];
    }
}

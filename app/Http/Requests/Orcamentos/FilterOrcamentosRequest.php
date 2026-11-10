<?php

namespace App\Http\Requests\Orcamentos;

use App\Enums\OrcamentoStatus;
use App\Models\Orcamento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class FilterOrcamentosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('viewAny', Orcamento::class);
    }

    public function rules(): array
    {
        return [
            'numero' => ['nullable', 'string', 'max:30'],
            'cliente' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(OrcamentoStatus::class)],
            'situacao' => ['nullable', Rule::in(['aberto', 'finalizado'])],
            'finalizados' => ['nullable', 'boolean'],
        ];
    }
}

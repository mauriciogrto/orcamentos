<?php

namespace App\Http\Requests\Orcamentos;

use App\Http\Requests\Orcamentos\Concerns\ValidatesOrcamento;
use App\Models\Orcamento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreOrcamentoRequest extends FormRequest
{
    use ValidatesOrcamento;

    public function authorize(): bool
    {
        return Gate::allows('create', Orcamento::class);
    }
}

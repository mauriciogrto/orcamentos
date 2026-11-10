<?php

namespace App\Http\Requests\Orcamentos;

use App\Http\Requests\Orcamentos\Concerns\ValidatesOrcamento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateOrcamentoRequest extends FormRequest
{
    use ValidatesOrcamento;

    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('orcamento'));
    }
}

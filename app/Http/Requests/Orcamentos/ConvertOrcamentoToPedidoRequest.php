<?php

namespace App\Http\Requests\Orcamentos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ConvertOrcamentoToPedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('convertToPedido', $this->route('orcamento'));
    }

    public function rules(): array
    {
        return ['confirmacao' => ['required', 'accepted']];
    }
}

<?php

namespace App\Http\Requests\Orcamentos;

use App\Models\Pedido;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class FilterPedidosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('viewAny', Pedido::class);
    }

    public function rules(): array
    {
        return [
            'numero' => ['nullable', 'string', 'max:30'],
            'cliente' => ['nullable', 'string', 'max:255'],
        ];
    }
}

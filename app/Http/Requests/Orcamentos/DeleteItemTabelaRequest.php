<?php

namespace App\Http\Requests\Orcamentos;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DeleteItemTabelaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Item::class);
    }

    public function rules(): array
    {
        return ['confirmacao' => ['required', 'accepted']];
    }
}


<?php

namespace App\Http\Requests\Orcamentos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DeleteItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('delete', $this->route('item'));
    }

    public function rules(): array
    {
        return ['confirmacao' => ['required', 'accepted']];
    }
}

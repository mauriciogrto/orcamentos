<?php

namespace App\Http\Requests\Orcamentos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DeleteClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('delete', $this->route('cliente'));
    }

    public function rules(): array
    {
        return ['confirmacao' => ['required', 'accepted']];
    }
}

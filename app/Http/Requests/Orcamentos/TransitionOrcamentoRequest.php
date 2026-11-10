<?php

namespace App\Http\Requests\Orcamentos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class TransitionOrcamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('transition', $this->route('orcamento'));
    }

    public function rules(): array
    {
        return ['confirmacao' => ['required', 'accepted']];
    }
}

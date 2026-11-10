<?php

namespace App\Http\Requests\SuperAdmin;

use App\Enums\CargoUsuario;
use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreEmpresaUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([
            RoleName::SuperAdmin->value,
            RoleName::AdminEmpresa->value,
        ]) === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'cargo' => ['required', Rule::enum(CargoUsuario::class)],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Este e-mail já está vinculado a outro usuário, inclusive uma conta excluída preservada no histórico. Cada usuário precisa de um e-mail exclusivo.',
        ];
    }
}

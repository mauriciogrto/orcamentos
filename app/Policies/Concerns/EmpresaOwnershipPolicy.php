<?php

namespace App\Policies\Concerns;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

abstract class EmpresaOwnershipPolicy
{
    protected string $resource;

    public function before(User $user): ?bool
    {
        return $user->hasRole(RoleName::SuperAdmin->value) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can("{$this->resource}.visualizar_todos")
            || $user->can("{$this->resource}.visualizar_proprios");
    }

    public function view(User $user, Model $record): bool
    {
        if (! $this->sameEmpresa($user, $record)) {
            return false;
        }

        return $user->can("{$this->resource}.visualizar_todos")
            || ($user->can("{$this->resource}.visualizar_proprios") && $this->owns($user, $record));
    }

    public function create(User $user): bool
    {
        return $user->empresa_id !== null
            && ($user->can("{$this->resource}.criar_todos")
                || $user->can("{$this->resource}.criar_proprios"));
    }

    public function update(User $user, Model $record): bool
    {
        return $this->sameEmpresa($user, $record)
            && ($user->can("{$this->resource}.editar_todos")
                || ($this->owns($user, $record) && $user->can("{$this->resource}.editar_proprios")));
    }

    public function delete(User $user, Model $record): bool
    {
        return $this->sameEmpresa($user, $record)
            && ($user->can("{$this->resource}.excluir_todos")
                || ($this->owns($user, $record) && $user->can("{$this->resource}.excluir_proprios")));
    }

    public function restore(User $user, Model $record): bool
    {
        return $this->update($user, $record);
    }

    public function forceDelete(User $user, Model $record): bool
    {
        return false;
    }

    private function sameEmpresa(User $user, Model $record): bool
    {
        return $user->empresa_id !== null
            && $record->getAttribute('empresa_id') !== null
            && (int) $user->empresa_id === (int) $record->getAttribute('empresa_id');
    }

    private function owns(User $user, Model $record): bool
    {
        return (int) $user->getKey() === (int) $record->getAttribute('user_id');
    }
}

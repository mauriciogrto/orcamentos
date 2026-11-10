<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Item;
use App\Models\User;

class ItemPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole(RoleName::SuperAdmin->value) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->empresa_id !== null && $user->can('itens.visualizar');
    }

    public function view(User $user, Item $item): bool
    {
        return $this->sameEmpresa($user, $item) && $user->can('itens.visualizar');
    }

    public function create(User $user): bool
    {
        return $user->empresa_id !== null && $user->can('itens.gerenciar');
    }

    public function update(User $user, Item $item): bool
    {
        return $this->sameEmpresa($user, $item) && $user->can('itens.gerenciar');
    }

    public function delete(User $user, Item $item): bool
    {
        return $this->update($user, $item);
    }

    public function viewHistory(User $user, Item $item): bool
    {
        return $this->sameEmpresa($user, $item) && $user->can('itens.visualizar_historico');
    }

    public function forceDelete(User $user, Item $item): bool
    {
        return false;
    }

    private function sameEmpresa(User $user, Item $item): bool
    {
        return $user->empresa_id !== null && (int) $user->empresa_id === (int) $item->empresa_id;
    }
}

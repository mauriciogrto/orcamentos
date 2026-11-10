<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\FaixaComissao;
use App\Models\User;

class FaixaComissaoPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdminEmpresa($user);
    }

    public function view(User $user, FaixaComissao $faixa): bool
    {
        return $this->isAdminEmpresa($user) && $this->sameEmpresa($user, $faixa);
    }

    public function create(User $user): bool
    {
        return $this->isAdminEmpresa($user);
    }

    public function update(User $user, FaixaComissao $faixa): bool
    {
        return $this->view($user, $faixa);
    }

    public function delete(User $user, FaixaComissao $faixa): bool
    {
        return $this->view($user, $faixa);
    }

    private function isAdminEmpresa(User $user): bool
    {
        return $user->empresa_id !== null
            && $user->hasRole(RoleName::AdminEmpresa->value)
            && $user->can('comissionamento.gerenciar');
    }

    private function sameEmpresa(User $user, FaixaComissao $faixa): bool
    {
        return (int) $user->empresa_id === (int) $faixa->empresa_id;
    }
}

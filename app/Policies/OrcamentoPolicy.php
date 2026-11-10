<?php

namespace App\Policies;

use App\Enums\OrcamentoStatus;
use App\Models\Orcamento;
use App\Models\User;
use App\Policies\Concerns\EmpresaOwnershipPolicy;
use Illuminate\Database\Eloquent\Model;

class OrcamentoPolicy extends EmpresaOwnershipPolicy
{
    protected string $resource = 'orcamentos';

    public function update(User $user, Model $record): bool
    {
        if ($record instanceof Orcamento
            && ! in_array($record->status, [OrcamentoStatus::Rascunho, OrcamentoStatus::Enviado], true)) {
            return false;
        }

        return parent::update($user, $record);
    }

    public function transition(User $user, Orcamento $orcamento): bool
    {
        return parent::update($user, $orcamento);
    }

    public function convertToPedido(User $user, Orcamento $orcamento): bool
    {
        return $orcamento->status !== OrcamentoStatus::Rejeitado
            && parent::update($user, $orcamento);
    }

    public function delete(User $user, Model $record): bool
    {
        if ($record instanceof Orcamento) {
            $temPedido = $record->relationLoaded('pedido')
                ? $record->pedido !== null
                : $record->pedido()->exists();

            if ($temPedido) {
                return false;
            }
        }

        return parent::delete($user, $record);
    }

    public function renew(User $user, Orcamento $orcamento): bool
    {
        return $orcamento->status === OrcamentoStatus::Expirado
            && parent::update($user, $orcamento);
    }
}

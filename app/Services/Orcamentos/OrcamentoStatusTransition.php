<?php

namespace App\Services\Orcamentos;

use App\Enums\OrcamentoStatus;
use App\Models\Orcamento;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrcamentoStatusTransition
{
    public function transition(Orcamento $orcamento, OrcamentoStatus $target): Orcamento
    {
        return DB::transaction(function () use ($orcamento, $target): Orcamento {
            $locked = Orcamento::withoutGlobalScopes()->whereKey($orcamento->id)->lockForUpdate()->firstOrFail();
            $allowed = match ($locked->status) {
                OrcamentoStatus::Rascunho => [OrcamentoStatus::Enviado, OrcamentoStatus::Finalizado],
                OrcamentoStatus::Enviado => [OrcamentoStatus::Aprovado, OrcamentoStatus::Rejeitado, OrcamentoStatus::Finalizado],
                OrcamentoStatus::Expirado => [OrcamentoStatus::Finalizado],
                default => [],
            };

            if (! in_array($target, $allowed, true)) {
                throw ValidationException::withMessages([
                    'status' => "Não é permitido alterar o status de {$locked->status->label()} para {$target->label()}.",
                ]);
            }

            $locked->update(['status' => $target]);

            return $locked;
        });
    }

    public function renew(Orcamento $orcamento): Orcamento
    {
        return DB::transaction(function () use ($orcamento): Orcamento {
            $locked = Orcamento::withoutGlobalScopes()->whereKey($orcamento->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== OrcamentoStatus::Expirado) {
                throw ValidationException::withMessages([
                    'status' => 'Somente orçamentos expirados podem ser renovados.',
                ]);
            }

            $agora = now();
            $locked->update([
                'status' => OrcamentoStatus::Rascunho,
                'data_emissao' => $agora->toDateString(),
                'hora_emissao' => $agora->format('H:i:s'),
            ]);

            return $locked;
        });
    }
}

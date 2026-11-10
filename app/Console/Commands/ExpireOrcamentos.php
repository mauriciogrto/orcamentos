<?php

namespace App\Console\Commands;

use App\Enums\OrcamentoStatus;
use App\Models\Orcamento;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireOrcamentos extends Command
{
    protected $signature = 'orcamentos:expirar';

    protected $description = 'Expira orçamentos enviados cuja validade já terminou';

    public function handle(): int
    {
        $expirados = 0;

        Orcamento::withoutGlobalScopes()
            ->where('status', OrcamentoStatus::Enviado->value)
            ->whereDate('data_emissao', '<', today())
            ->select('id')
            ->chunkById(100, function ($orcamentos) use (&$expirados): void {
                foreach ($orcamentos as $orcamento) {
                    $alterado = DB::transaction(function () use ($orcamento): bool {
                        $locked = Orcamento::withoutGlobalScopes()
                            ->whereKey($orcamento->id)
                            ->lockForUpdate()
                            ->first();

                        if (! $locked
                            || $locked->status !== OrcamentoStatus::Enviado
                            || ! $locked->data_emissao->copy()->addDays($locked->validade_dias)->lt(today())) {
                            return false;
                        }

                        $locked->updateQuietly(['status' => OrcamentoStatus::Expirado]);

                        activity('orcamentos')
                            ->performedOn($locked)
                            ->causedByAnonymous()
                            ->event('status_expirado')
                            ->withProperties([
                                'sistema' => true,
                                'status_anterior' => OrcamentoStatus::Enviado->value,
                                'status_novo' => OrcamentoStatus::Expirado->value,
                            ])
                            ->log('Orçamento expirado automaticamente pelo sistema');

                        return true;
                    });

                    if ($alterado) {
                        $expirados++;
                    }
                }
            });

        $this->info("{$expirados} orçamento(s) expirado(s).");

        return self::SUCCESS;
    }
}

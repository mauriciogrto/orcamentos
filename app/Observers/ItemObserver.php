<?php

namespace App\Observers;

use App\Models\Item;

class ItemObserver
{
    public function created(Item $item): void
    {
        $this->registrarPreco($item);
    }

    public function updated(Item $item): void
    {
        if ($item->wasChanged('preco_atual')) {
            $this->registrarPreco($item);
        }
    }

    private function registrarPreco(Item $item): void
    {
        $item->precosHistorico()->create([
            'empresa_id' => $item->empresa_id,
            'preco' => $item->preco_atual,
            'registrado_em' => now(),
        ]);
    }
}

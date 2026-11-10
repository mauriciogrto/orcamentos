<?php

namespace App\Enums;

enum BoletoPrazo: string
{
    case Trinta = '30';
    case TrintaSessenta = '30/60';
    case TrintaNoventa = '30/60/90';
    case TrintaCentoVinte = '30/60/90/120';
    case TrintaCentoCinquenta = '30/60/90/120/150';
    case TrintaCentoOitenta = '30/60/90/120/150/180';

    public function label(): string
    {
        return $this->value.' dias';
    }
}

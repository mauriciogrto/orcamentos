<?php

namespace App\Enums;

enum OrcamentoFinalidade: string
{
    case Revenda = 'revenda';
    case Industrializacao = 'industrializacao';
    case UsoConsumo = 'uso_consumo';
    case Outro = 'outro';

    public function label(): string
    {
        return match ($this) {
            self::Revenda => 'Revenda',
            self::Industrializacao => 'Industrialização',
            self::UsoConsumo => 'Uso e consumo',
            self::Outro => 'Outro',
        };
    }
}

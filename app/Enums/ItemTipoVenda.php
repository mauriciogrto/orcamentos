<?php

namespace App\Enums;

enum ItemTipoVenda: string
{
    case Unitario = 'unitario';
    case Metro = 'metro';

    public function label(): string
    {
        return match ($this) {
            self::Unitario => 'Unitário',
            self::Metro => 'Metro',
        };
    }

    public function unidade(): string
    {
        return match ($this) {
            self::Unitario => 'un.',
            self::Metro => 'm',
        };
    }
}

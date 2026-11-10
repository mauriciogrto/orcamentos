<?php

namespace App\Enums;

enum EmpresaStatus: string
{
    case Ativa = 'ativa';
    case Inativa = 'inativa';
    case Suspensa = 'suspensa';

    public function label(): string
    {
        return match ($this) {
            self::Ativa => 'Ativa',
            self::Inativa => 'Inativa',
            self::Suspensa => 'Suspensa',
        };
    }
}

<?php

namespace App\Enums;

enum CargoUsuario: string
{
    case Gerente = 'Gerente';
    case Supervisor = 'Supervisor';
    case Vendedor = 'Vendedor';

    public function role(): RoleName
    {
        return match ($this) {
            self::Gerente => RoleName::AdminEmpresa,
            self::Supervisor => RoleName::Supervisor,
            self::Vendedor => RoleName::Vendedor,
        };
    }

    public function nivelHierarquico(): int
    {
        return match ($this) {
            self::Gerente => 3,
            self::Supervisor => 2,
            self::Vendedor => 1,
        };
    }
}

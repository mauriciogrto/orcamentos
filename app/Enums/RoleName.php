<?php

namespace App\Enums;

enum RoleName: string
{
    case SuperAdmin = 'super_admin';
    case AdminEmpresa = 'admin_empresa';
    case Supervisor = 'supervisor';
    case Vendedor = 'vendedor';
}

<?php

namespace App\Policies;

use App\Policies\Concerns\EmpresaOwnershipPolicy;

class PedidoPolicy extends EmpresaOwnershipPolicy
{
    protected string $resource = 'orcamentos';
}

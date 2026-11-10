<?php

namespace App\Policies;

use App\Policies\Concerns\EmpresaOwnershipPolicy;

class ClientePolicy extends EmpresaOwnershipPolicy
{
    protected string $resource = 'clientes';
}

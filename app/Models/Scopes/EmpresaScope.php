<?php

namespace App\Models\Scopes;

use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class EmpresaScope implements Scope
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function apply(Builder $builder, Model $model): void
    {
        $empresaId = $this->tenantContext->empresaId();

        if ($empresaId !== null) {
            $builder->where($model->qualifyColumn('empresa_id'), $empresaId);
        }
    }
}

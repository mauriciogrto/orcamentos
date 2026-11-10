<?php

namespace App\Models\Concerns;

use App\Models\Scopes\EmpresaScope;
use App\Tenancy\TenantContext;

trait BelongsToEmpresa
{
    protected static function bootBelongsToEmpresa(): void
    {
        static::addGlobalScope(app(EmpresaScope::class));

        static::creating(function ($model): void {
            $empresaId = app(TenantContext::class)->empresaId();

            if ($empresaId !== null && empty($model->empresa_id)) {
                $model->empresa_id = $empresaId;
            }
        });
    }
}

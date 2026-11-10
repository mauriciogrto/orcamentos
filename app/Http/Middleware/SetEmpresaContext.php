<?php

namespace App\Http\Middleware;

use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetEmpresaContext
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->tenantContext->setEmpresaId($request->user()?->empresa_id);

        try {
            return $next($request);
        } finally {
            $this->tenantContext->clear();
        }
    }
}

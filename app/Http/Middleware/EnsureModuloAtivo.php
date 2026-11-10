<?php

namespace App\Http\Middleware;

use App\Enums\EmpresaStatus;
use App\Enums\RoleName;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuloAtivo
{
    public function handle(Request $request, Closure $next, string $modulo): Response
    {
        if ($request->user()?->hasRole(RoleName::SuperAdmin->value)) {
            return $next($request);
        }

        $empresa = $request->user()?->empresa;

        abort_unless(
            $empresa
            && $empresa->status === EmpresaStatus::Ativa
            && $empresa->moduloAtivo($modulo),
            403,
            'Este módulo não está ativo para a empresa.',
        );

        return $next($request);
    }
}

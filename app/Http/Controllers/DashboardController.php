<?php

namespace App\Http\Controllers;

use App\Enums\OrcamentoStatus;
use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $podeVerPanorama = $user->empresa_id !== null
            && $user->hasAnyRole([RoleName::AdminEmpresa->value, RoleName::Supervisor->value])
            && $user->can('orcamentos.visualizar_todos')
            && $user->empresa?->moduloAtivo('orcamentos');

        if (! $podeVerPanorama) {
            return view('dashboard', ['panorama' => null]);
        }

        $statusAbertos = [OrcamentoStatus::Rascunho->value, OrcamentoStatus::Enviado->value];
        $panorama = User::query()
            ->role(RoleName::Vendedor->value)
            ->withCount('clientes')
            ->withCount([
                'orcamentos as orcamentos_abertos_count' => fn ($query) => $query->whereIn('status', $statusAbertos),
            ])
            ->withSum([
                'orcamentos as orcamentos_abertos_total' => fn ($query) => $query->whereIn('status', $statusAbertos),
            ], 'valor_total')
            ->orderBy('name')
            ->get();

        $totais = [
            'clientes' => $panorama->sum('clientes_count'),
            'orcamentos_abertos' => $panorama->sum('orcamentos_abertos_count'),
            'valor_aberto' => $panorama->sum('orcamentos_abertos_total'),
        ];

        return view('dashboard', compact('panorama', 'totais'));
    }
}

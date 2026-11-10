<?php

namespace App\Http\Controllers\Fiscal;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\NotaFiscal;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FiscalDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $empresa = $this->empresaAtual($request);

        $notas = NotaFiscal::query()
            ->with(['pedido.cliente', 'pedido.vendedor'])
            ->where('empresa_id', $empresa->id)
            ->latest()
            ->limit(8)
            ->get();

        $pedidosSemNota = Pedido::query()
            ->with(['cliente', 'vendedor'])
            ->where('empresa_id', $empresa->id)
            ->whereDoesntHave('notaFiscal')
            ->latest()
            ->limit(8)
            ->get();

        return view('fiscal.index', [
            'empresa' => $empresa,
            'notas' => $notas,
            'pedidosSemNota' => $pedidosSemNota,
            'configuracao' => $empresa->configuracaoFiscal,
            'empresas' => $request->user()->hasRole('super_admin') ? Empresa::query()->orderBy('razao_social')->get() : collect(),
        ]);
    }

    private function empresaAtual(Request $request): Empresa
    {
        if ($request->user()->hasRole('super_admin')) {
            $empresa = Empresa::query()->find($request->integer('empresa_id') ?: session('empresa_id'))
                ?? Empresa::query()->orderBy('razao_social')->firstOrFail();

            session(['empresa_id' => $empresa->id]);

            return $empresa;
        }

        return $request->user()->empresa;
    }
}

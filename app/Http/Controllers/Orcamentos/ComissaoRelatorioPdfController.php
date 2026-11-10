<?php

namespace App\Http\Controllers\Orcamentos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Orcamentos\GenerateComissaoRelatorioRequest;
use App\Services\Representacao\ComissaoRelatorioService;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class ComissaoRelatorioPdfController extends Controller
{
    public function __invoke(GenerateComissaoRelatorioRequest $request, ComissaoRelatorioService $relatorioService): Response
    {
        $validated = $request->validated();
        $dados = $relatorioService->gerar(
            $request->user(),
            $validated['mes'],
            ! empty($validated['vendedor_id']) ? (int) $validated['vendedor_id'] : null,
        );

        $empresa = $request->user()->empresa;

        $arquivo = 'comissoes-'.$validated['mes'];
        if ($dados['vendedorSelecionado']) {
            $arquivo .= '-'.str($dados['vendedorSelecionado']->name)->slug();
        }

        return Pdf::loadView('orcamentos.representacao.comissoes_pdf', [
            'empresa' => $empresa,
            ...$dados,
        ])
            ->setPaper('a4', 'landscape')
            ->download($arquivo.'.pdf');
    }
}

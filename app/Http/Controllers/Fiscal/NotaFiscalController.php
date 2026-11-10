<?php

namespace App\Http\Controllers\Fiscal;

use App\Http\Controllers\Controller;
use App\Models\NotaFiscal;
use App\Models\OrcamentoItem;
use App\Models\Pedido;
use App\Services\Fiscal\BrasilApiNcmService;
use App\Services\Fiscal\NFePreValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class NotaFiscalController extends Controller
{
    public function index(Request $request): View
    {
        $notas = NotaFiscal::query()
            ->with(['pedido.cliente', 'pedido.vendedor', 'orcamento'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('fiscal.notas.index', compact('notas'));
    }

    public function precheck(Pedido $pedido, NFePreValidator $validator): View
    {
        Gate::authorize('view', $pedido);

        $resultado = $validator->validar($pedido);
        $nota = $pedido->notaFiscal()->firstOrCreate([
            'empresa_id' => $pedido->empresa_id,
            'orcamento_id' => $pedido->orcamento_id,
        ], [
            'ambiente' => $pedido->empresa->configuracaoFiscal?->ambiente_nfe ?? 'homologacao',
            'status' => 'pendente',
        ]);

        return view('fiscal.notas.precheck', compact('pedido', 'nota', 'resultado'));
    }

    public function validarNcms(Pedido $pedido, BrasilApiNcmService $ncmService): RedirectResponse
    {
        Gate::authorize('view', $pedido);

        $pedido->loadMissing('orcamento.itens.item.tributacao');
        $validados = 0;
        $avisos = [];

        foreach ($pedido->orcamento->itens as $linha) {
            /** @var OrcamentoItem $linha */
            $tributacao = $linha->item?->tributacao;
            $ncm = $linha->ncm ?: $tributacao?->ncm;

            if (! $tributacao || ! $ncm) {
                continue;
            }

            $consulta = $ncmService->consultar($ncm);

            $tributacao->update([
                'ncm_status' => $consulta['status'],
                'ncm_descricao_oficial' => $consulta['descricao'],
                'ncm_validado_em' => $consulta['status'] === 'ativo' ? now() : null,
            ]);

            $validados++;

            if ($consulta['mensagem']) {
                $avisos[] = $linha->descricao.': '.$consulta['mensagem'];
            }
        }

        return redirect()
            ->route('fiscal.notas.precheck', $pedido)
            ->with('success', $validados.' NCM(s) consultado(s). '.implode(' ', array_slice($avisos, 0, 3)));
    }

    public function confirmarPrecheck(Pedido $pedido, NFePreValidator $validator): RedirectResponse
    {
        Gate::authorize('view', $pedido);

        $resultado = $validator->validar($pedido);

        if (! $resultado['ok']) {
            return redirect()
                ->route('fiscal.notas.precheck', $pedido)
                ->withErrors(['precheck' => 'Ainda existem pendências fiscais. Corrija os dados antes de confirmar.']);
        }

        $configuracao = $pedido->empresa->configuracaoFiscal;

        $pedido->notaFiscal()->updateOrCreate([
            'pedido_id' => $pedido->id,
        ], [
            'empresa_id' => $pedido->empresa_id,
            'orcamento_id' => $pedido->orcamento_id,
            'ambiente' => $configuracao?->ambiente_nfe ?? 'homologacao',
            'status' => 'pre_validada',
            'serie' => $configuracao?->serie_nfe,
            'numero' => $configuracao?->proximo_numero_nfe,
            'pre_validacao' => $resultado,
            'pre_validada_em' => now(),
            'mensagem_retorno' => 'Pré-validação confirmada pelo usuário. Emissão real será feita após integração com provedor fiscal.',
        ]);

        return redirect()
            ->route('fiscal.notas.index')
            ->with('success', 'Pré-validação fiscal confirmada. A emissão real da NF-e ainda depende da integração com API fiscal.');
    }
}

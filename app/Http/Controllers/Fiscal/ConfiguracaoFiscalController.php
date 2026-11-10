<?php

namespace App\Http\Controllers\Fiscal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fiscal\UpdateConfiguracaoFiscalRequest;
use App\Models\Empresa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ConfiguracaoFiscalController extends Controller
{
    public function edit(Request $request): View
    {
        $empresa = $this->empresaAtual($request);
        $configuracao = $empresa->configuracaoFiscal()->firstOrCreate([
            'empresa_id' => $empresa->id,
        ]);

        return view('fiscal.configuracao.edit', compact('empresa', 'configuracao'));
    }

    public function update(UpdateConfiguracaoFiscalRequest $request): RedirectResponse
    {
        $empresa = $this->empresaAtual($request);
        $configuracao = $empresa->configuracaoFiscal()->firstOrCreate([
            'empresa_id' => $empresa->id,
        ]);

        $dados = $request->validated();

        if ($request->hasFile('certificado_a1')) {
            if ($configuracao->certificado_a1_path) {
                Storage::disk('local')->delete($configuracao->certificado_a1_path);
            }

            $dados['certificado_a1_path'] = $request
                ->file('certificado_a1')
                ->store("certificados/empresas/{$empresa->id}", 'local');
        }

        if (! filled($dados['certificado_a1_senha'] ?? null)) {
            unset($dados['certificado_a1_senha']);
        }

        unset($dados['certificado_a1']);

        $configuracao->update($dados);

        return redirect()
            ->route('fiscal.configuracao.edit')
            ->with('success', 'Configuração fiscal salva com sucesso.');
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

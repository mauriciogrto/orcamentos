<?php

namespace App\Http\Controllers\Orcamentos;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orcamentos\UpdateRepresentacaoConfiguracaoRequest;
use App\Models\Cliente;
use App\Models\Empresa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RepresentacaoConfiguracaoController extends Controller
{
    public function update(UpdateRepresentacaoConfiguracaoRequest $request): RedirectResponse
    {
        $dados = $request->validated();
        $empresa = $request->user()->hasRole(RoleName::SuperAdmin->value)
            ? Empresa::query()->findOrFail($dados['empresa_id'])
            : $request->user()->empresa;

        $empresa->update([
            'pedido_minimo' => $dados['pedido_minimo'],
            'limite_frete_fob' => $dados['limite_frete_fob'],
        ]);

        return to_route(
            'orcamentos.representacao.frete',
            $request->user()->hasRole(RoleName::SuperAdmin->value) ? ['empresa_id' => $empresa->id] : [],
        )
            ->with('success', 'Regras do pedido e do frete atualizadas com sucesso.');
    }

    public function updateClienteFrete(Request $request): RedirectResponse
    {
        $empresa = $this->empresaDoRequest($request);

        $dados = $request->validate([
            'empresa_id' => [
                $request->user()->hasRole(RoleName::SuperAdmin->value) ? 'required' : 'nullable',
                'integer',
                Rule::exists('empresas', 'id'),
            ],
            'cliente_id' => [
                'required',
                'integer',
                Rule::exists('clientes', 'id')->where('empresa_id', $empresa->id),
            ],
            'limite_frete_fob' => ['required', 'decimal:0,2', 'min:0'],
        ], [
            'cliente_id.required' => 'Selecione o cliente que receberá a regra de frete.',
            'limite_frete_fob.required' => 'Informe o limite FOB/CIF do cliente.',
        ]);

        $cliente = Cliente::query()
            ->where('empresa_id', $empresa->id)
            ->findOrFail($dados['cliente_id']);

        $cliente->update([
            'frete_personalizado' => true,
            'limite_frete_fob' => $dados['limite_frete_fob'],
        ]);

        return to_route('orcamentos.representacao.frete', $this->parametrosEmpresa($request, $empresa))
            ->with('success', 'Regra de frete do cliente atualizada com sucesso.');
    }

    public function destroyClienteFrete(Request $request, Cliente $cliente): RedirectResponse
    {
        $empresa = $request->user()->hasRole(RoleName::SuperAdmin->value)
            ? $cliente->empresa
            : $request->user()->empresa;

        abort_if((int) $cliente->empresa_id !== (int) $empresa?->id, 403);

        $cliente->update([
            'frete_personalizado' => false,
            'limite_frete_fob' => null,
        ]);

        return to_route('orcamentos.representacao.frete', $this->parametrosEmpresa($request, $empresa))
            ->with('success', 'Regra individual de frete removida.');
    }

    private function empresaDoRequest(Request $request): Empresa
    {
        return $request->user()->hasRole(RoleName::SuperAdmin->value)
            ? Empresa::query()->findOrFail($request->integer('empresa_id'))
            : $request->user()->empresa;
    }

    private function parametrosEmpresa(Request $request, Empresa $empresa): array
    {
        return $request->user()->hasRole(RoleName::SuperAdmin->value)
            ? ['empresa_id' => $empresa->id]
            : [];
    }
}

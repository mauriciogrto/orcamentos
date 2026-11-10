<?php

namespace App\Http\Controllers\Orcamentos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Orcamentos\FilterPedidosRequest;
use App\Models\Pedido;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PedidoController extends Controller
{
    public function index(FilterPedidosRequest $request): View
    {
        $query = Pedido::query()->with(['empresa', 'cliente', 'vendedor', 'orcamento'])->latest('id');

        if ($request->user()->cannot('orcamentos.visualizar_todos')) {
            $query->where('user_id', $request->user()->id);
        }

        $query
            ->when($request->validated('numero'), fn ($query, $numero) => $query->where('numero', 'like', "%{$numero}%"))
            ->when($request->validated('cliente'), function ($query, string $cliente): void {
                $cnpj = preg_replace('/\D/', '', $cliente);
                $query->whereHas('cliente', function ($query) use ($cliente, $cnpj): void {
                    $query->where('nome_razao_social', 'like', "%{$cliente}%")
                        ->orWhere('nome_fantasia', 'like', "%{$cliente}%")
                        ->when($cnpj !== '', fn ($query) => $query->orWhere('cnpj', 'like', "%{$cnpj}%"));
                });
            });

        return view('orcamentos.pedidos.index', [
            'pedidos' => $query->paginate(15)->withQueryString(),
        ]);
    }

    public function show(Pedido $pedido): View
    {
        Gate::authorize('view', $pedido);
        $pedido->load(['empresa', 'cliente', 'vendedor', 'orcamento.itens.item']);

        return view('orcamentos.pedidos.show', compact('pedido'));
    }
}

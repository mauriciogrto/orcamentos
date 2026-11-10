<?php

namespace App\Http\Controllers\Orcamentos;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orcamentos\DeleteClienteRequest;
use App\Http\Requests\Orcamentos\StoreClienteRequest;
use App\Http\Requests\Orcamentos\UpdateClienteRequest;
use App\Models\Cliente;
use App\Models\User;
use App\Services\Clientes\ClienteCodeGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ClienteController extends Controller
{
    public function __construct(private readonly ClienteCodeGenerator $codeGenerator) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Cliente::class);
        $query = Cliente::query()->with(['empresa', 'vendedor'])->latest();

        if (! request()->user()->can('clientes.visualizar_todos')) {
            $query->where('user_id', request()->user()->id);
        }

        return view('orcamentos.clientes.index', ['clientes' => $query->paginate(15)]);
    }

    public function create(): View
    {
        Gate::authorize('create', Cliente::class);

        return view('orcamentos.clientes.create', ['vendedores' => $this->vendedoresDisponiveis()]);
    }

    public function store(StoreClienteRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->can('clientes.criar_todos')
            ? ($data['user_id'] ?? null)
            : $request->user()->id;

        DB::transaction(function () use ($data, $request): void {
            $empresaId = $request->user()->empresa_id;
            Cliente::create([
                ...$data,
                'empresa_id' => $empresaId,
                'codigo' => $this->codeGenerator->next($empresaId, $data['cnpj']),
            ]);
        }, 3);

        return redirect()->route('orcamentos.clientes.index')->with('success', 'Cliente cadastrado com sucesso.');
    }

    public function show(Cliente $cliente): View
    {
        Gate::authorize('view', $cliente);

        return view('orcamentos.clientes.show', compact('cliente'));
    }

    public function edit(Cliente $cliente): View
    {
        Gate::authorize('update', $cliente);

        return view('orcamentos.clientes.edit', [
            'cliente' => $cliente,
            'vendedores' => $this->vendedoresDisponiveis(),
        ]);
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente): RedirectResponse
    {
        $data = $request->validated();
        if (! $request->user()->can('clientes.editar_todos')) {
            $data = Arr::except($data, 'user_id');
        }
        DB::transaction(function () use ($cliente, $data): void {
            if (! $cliente->codigo) {
                $data['codigo'] = $this->codeGenerator->next($cliente->empresa_id, $data['cnpj']);
            }
            $cliente->update($data);
        }, 3);

        return redirect()->route('orcamentos.clientes.index')->with('success', 'Cliente atualizado com sucesso.');
    }

    public function destroy(DeleteClienteRequest $request, Cliente $cliente): RedirectResponse
    {
        $cliente->delete();

        return redirect()->route('orcamentos.clientes.index')->with('success', 'Cliente excluído com sucesso.');
    }

    private function vendedoresDisponiveis()
    {
        if (! request()->user()->can('clientes.criar_todos') && ! request()->user()->can('clientes.editar_todos')) {
            return collect();
        }

        return User::query()->role(RoleName::Vendedor->value)->orderBy('name')->get();
    }
}

<?php

namespace App\Http\Controllers\Empresa;

use App\Enums\CargoUsuario;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreEmpresaUserRequest;
use App\Http\Requests\SuperAdmin\UpdateEmpresaUserRequest;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function index(Request $request): View
    {
        $empresa = $this->empresa($request);
        $usuarios = $empresa->users()->orderBy('name')->paginate(15);

        return view('empresa.usuarios.index', compact('empresa', 'usuarios'));
    }

    public function create(Request $request): View
    {
        $empresa = $this->empresa($request);
        $cargos = CargoUsuario::cases();

        return view('empresa.usuarios.create', compact('empresa', 'cargos'));
    }

    public function store(StoreEmpresaUserRequest $request): RedirectResponse
    {
        $empresa = $this->empresa($request);
        $data = $request->validated();
        $cargo = CargoUsuario::from($data['cargo']);

        DB::transaction(function () use ($empresa, $data, $cargo): void {
            $usuario = User::withoutGlobalScopes()->create([
                ...$data,
                'empresa_id' => $empresa->id,
            ]);
            $usuario->assignRole($cargo->role()->value);
        });

        return to_route('empresa.usuarios.index')->with('success', 'Usuário criado com sucesso.');
    }

    public function edit(Request $request, User $usuario): View
    {
        $empresa = $this->empresa($request);
        $this->ensureSameCompany($empresa, $usuario);
        $cargos = CargoUsuario::cases();

        return view('empresa.usuarios.edit', compact('empresa', 'usuario', 'cargos'));
    }

    public function update(UpdateEmpresaUserRequest $request, User $usuario): RedirectResponse
    {
        $empresa = $this->empresa($request);
        $this->ensureSameCompany($empresa, $usuario);
        $data = $request->validated();
        $cargo = CargoUsuario::from($data['cargo']);

        if ($usuario->hasRole(RoleName::AdminEmpresa->value)
            && $cargo !== CargoUsuario::Gerente
            && $this->isLastManager($empresa)) {
            throw ValidationException::withMessages(['cargo' => 'A empresa deve manter pelo menos um Gerente ativo.']);
        }

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        DB::transaction(function () use ($usuario, $data, $cargo): void {
            $usuario->update($data);
            $usuario->syncRoles($cargo->role()->value);
        });

        return to_route('empresa.usuarios.index')->with('success', 'Usuário atualizado com sucesso.');
    }

    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        $empresa = $this->empresa($request);
        $this->ensureSameCompany($empresa, $usuario);

        if ($usuario->is($request->user())) {
            throw ValidationException::withMessages(['usuario' => 'Você não pode excluir seu próprio usuário.']);
        }

        if ($usuario->hasRole(RoleName::AdminEmpresa->value) && $this->isLastManager($empresa)) {
            throw ValidationException::withMessages(['usuario' => 'A empresa deve manter pelo menos um Gerente ativo.']);
        }

        $usuario->delete();

        return to_route('empresa.usuarios.index')
            ->with('success', 'Usuário excluído. Seus dados históricos foram preservados.');
    }

    private function empresa(Request $request): Empresa
    {
        return $request->user()->empresa ?? abort(403);
    }

    private function ensureSameCompany(Empresa $empresa, User $usuario): void
    {
        abort_unless((int) $empresa->id === (int) $usuario->empresa_id, 404);
    }

    private function isLastManager(Empresa $empresa): bool
    {
        return $empresa->users()->role(RoleName::AdminEmpresa->value)->count() <= 1;
    }
}

<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\CargoUsuario;
use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreEmpresaUserRequest;
use App\Http\Requests\SuperAdmin\UpdateEmpresaUserRequest;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EmpresaUserController extends Controller
{
    public function index(Empresa $empresa): View
    {
        $usuarios = $empresa->users()
            ->orderBy('name')
            ->paginate(15);

        return view('super-admin.empresas.usuarios.index', compact('empresa', 'usuarios'));
    }

    public function create(Empresa $empresa): View
    {
        $cargos = CargoUsuario::cases();

        return view('super-admin.empresas.usuarios.create', compact('empresa', 'cargos'));
    }

    public function store(StoreEmpresaUserRequest $request, Empresa $empresa): RedirectResponse
    {
        $data = $request->validated();
        $cargo = CargoUsuario::from($data['cargo']);

        DB::transaction(function () use ($data, $cargo, $empresa): void {
            $user = User::withoutGlobalScopes()->create([
                ...$data,
                'empresa_id' => $empresa->id,
            ]);

            $user->assignRole($cargo->role()->value);
        });

        return redirect()
            ->route('super-admin.empresas.usuarios.index', $empresa)
            ->with('success', 'Usuário criado e vinculado à empresa.');
    }

    public function edit(Empresa $empresa, User $usuario): View
    {
        $this->ensureUserBelongsToCompany($empresa, $usuario);
        $cargos = CargoUsuario::cases();

        return view('super-admin.empresas.usuarios.edit', compact('empresa', 'usuario', 'cargos'));
    }

    public function update(UpdateEmpresaUserRequest $request, Empresa $empresa, User $usuario): RedirectResponse
    {
        $this->ensureUserBelongsToCompany($empresa, $usuario);
        $data = $request->validated();
        $cargo = CargoUsuario::from($data['cargo']);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        DB::transaction(function () use ($usuario, $data, $cargo): void {
            $usuario->update($data);
            $usuario->syncRoles($cargo->role()->value);
        });

        return redirect()
            ->route('super-admin.empresas.usuarios.index', $empresa)
            ->with('success', 'Usuário atualizado com sucesso.');
    }

    public function destroy(Empresa $empresa, User $usuario): RedirectResponse
    {
        $this->ensureUserBelongsToCompany($empresa, $usuario);
        $usuario->delete();

        return redirect()
            ->route('super-admin.empresas.usuarios.index', $empresa)
            ->with('success', 'Usuário excluído. O histórico vinculado foi preservado.');
    }

    private function ensureUserBelongsToCompany(Empresa $empresa, User $usuario): void
    {
        abort_unless((int) $usuario->empresa_id === (int) $empresa->id, 404);
    }
}

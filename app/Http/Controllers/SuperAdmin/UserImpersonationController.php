<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserImpersonationController extends Controller
{
    public function start(Request $request, Empresa $empresa, User $usuario): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(RoleName::SuperAdmin->value), 403);
        abort_unless((int) $usuario->empresa_id === (int) $empresa->id, 404);

        $superAdmin = $request->user();

        Auth::login($usuario);
        $request->session()->regenerate();
        $request->session()->put('impersonator_id', $superAdmin->id);
        $request->session()->put('impersonator_name', $superAdmin->name);
        $request->session()->put('impersonator_email', $superAdmin->email);
        $request->session()->put('impersonator_return_url', route('super-admin.empresas.usuarios.index', $empresa));

        return redirect()
            ->route('dashboard')
            ->with('success', 'Você está acessando como '.$usuario->name.'.');
    }

    public function stop(Request $request): RedirectResponse
    {
        $superAdminId = $request->session()->get('impersonator_id');
        abort_unless($superAdminId, 403);

        $returnUrl = $request->session()->get('impersonator_return_url', route('dashboard'));
        $superAdmin = User::withoutGlobalScopes()->findOrFail($superAdminId);

        Auth::login($superAdmin);
        $request->session()->regenerate();
        $request->session()->forget([
            'impersonator_id',
            'impersonator_name',
            'impersonator_email',
            'impersonator_return_url',
        ]);

        return redirect($returnUrl)->with('success', 'Você voltou para o acesso de superadmin.');
    }
}


<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\EmpresaStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreEmpresaRequest;
use App\Http\Requests\SuperAdmin\UpdateEmpresaRequest;
use App\Models\Empresa;
use App\Models\Modulo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class EmpresaController extends Controller
{
    public function index(): View
    {
        $empresas = Empresa::query()
            ->with('modulos')
            ->withCount('users')
            ->latest()
            ->paginate(15);

        return view('super-admin.empresas.index', compact('empresas'));
    }

    public function create(): View
    {
        return view('super-admin.empresas.create');
    }

    public function store(StoreEmpresaRequest $request): RedirectResponse
    {
        $data = Arr::except($request->validated(), 'logo');
        $logoPath = $request->file('logo')?->store('logos', 'public');

        try {
            $empresa = DB::transaction(function () use ($data, $logoPath): Empresa {
                $empresa = Empresa::create([...$data, 'logo_path' => $logoPath]);

                $empresa->modulos()->sync(
                    Modulo::query()->pluck('id')->mapWithKeys(
                        fn (int $id) => [$id => ['ativo' => false]],
                    )->all(),
                );

                return $empresa;
            });
        } catch (Throwable $exception) {
            if ($logoPath) {
                Storage::disk('public')->delete($logoPath);
            }

            throw $exception;
        }

        return redirect()
            ->route('super-admin.empresas.usuarios.create', $empresa)
            ->with('success', 'Empresa cadastrada. Agora você pode criar o primeiro usuário.');
    }

    public function edit(Empresa $empresa): View
    {
        return view('super-admin.empresas.edit', compact('empresa'));
    }

    public function update(UpdateEmpresaRequest $request, Empresa $empresa): RedirectResponse
    {
        $data = Arr::except($request->validated(), 'logo');
        $oldLogoPath = $empresa->logo_path;
        $newLogoPath = $request->file('logo')?->store('logos', 'public');

        try {
            $empresa->update([...$data, 'logo_path' => $newLogoPath ?? $oldLogoPath]);
        } catch (Throwable $exception) {
            if ($newLogoPath) {
                Storage::disk('public')->delete($newLogoPath);
            }

            throw $exception;
        }

        if ($newLogoPath && $oldLogoPath) {
            Storage::disk('public')->delete($oldLogoPath);
        }

        return redirect()
            ->route('super-admin.empresas.index')
            ->with('success', 'Empresa atualizada com sucesso.');
    }

    public function deactivate(Empresa $empresa): RedirectResponse
    {
        $empresa->update(['status' => EmpresaStatus::Inativa]);

        return back()->with('success', 'Empresa desativada com sucesso.');
    }
}

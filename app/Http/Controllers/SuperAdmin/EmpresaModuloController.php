<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\UpdateEmpresaModulosRequest;
use App\Models\Empresa;
use App\Models\Modulo;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmpresaModuloController extends Controller
{
    public function edit(Empresa $empresa): View
    {
        $modulos = Modulo::query()->orderBy('nome')->get();
        $ativos = $empresa->modulos()
            ->wherePivot('ativo', true)
            ->pluck('modulos.id')
            ->all();

        return view('super-admin.empresas.modulos', compact('empresa', 'modulos', 'ativos'));
    }

    public function update(UpdateEmpresaModulosRequest $request, Empresa $empresa): RedirectResponse
    {
        $selecionados = array_keys($request->validated('modulos', []));
        $sync = Modulo::query()->pluck('id')->mapWithKeys(
            fn (int $id) => [$id => ['ativo' => in_array($id, $selecionados)]],
        );

        $empresa->modulos()->sync($sync->all());

        return back()->with('success', 'Módulos da empresa atualizados.');
    }
}

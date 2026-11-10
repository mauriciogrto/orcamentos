<?php

namespace App\Http\Controllers\Orcamentos;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orcamentos\DeleteFaixaComissaoRequest;
use App\Http\Requests\Orcamentos\StoreFaixaComissaoRequest;
use App\Http\Requests\Orcamentos\UpdateFaixaComissaoRequest;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\FaixaComissao;
use App\Models\Pedido;
use App\Models\User;
use App\Services\Representacao\ComissaoRelatorioService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FaixaComissaoController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', FaixaComissao::class);

        $empresa = $this->empresaRepresentacao();

        return view('orcamentos.representacao.index', [
            'empresa' => $empresa,
            'empresas' => $this->empresasDisponiveis(),
            'faixasCount' => FaixaComissao::query()->where('empresa_id', $empresa->id)->count(),
            'isSuperAdmin' => $this->isSuperAdmin(),
        ]);
    }

    public function faixas(): View
    {
        Gate::authorize('viewAny', FaixaComissao::class);

        $empresa = $this->empresaRepresentacao();

        return view('orcamentos.representacao.faixas', [
            'faixas' => FaixaComissao::query()
                ->with('empresa')
                ->where('empresa_id', $empresa->id)
                ->orderBy('desconto_inicial_percentual')
                ->orderBy('desconto_final_percentual')
                ->paginate(15)
                ->withQueryString(),
            'empresa' => $empresa,
            'empresas' => $this->empresasDisponiveis(),
            'isSuperAdmin' => $this->isSuperAdmin(),
        ]);
    }

    public function frete(): View
    {
        Gate::authorize('viewAny', FaixaComissao::class);
        $empresa = $this->empresaRepresentacao();

        return view('orcamentos.representacao.frete', [
            'empresa' => $empresa,
            'empresas' => $this->empresasDisponiveis(),
            'clientesFreteDisponiveis' => Cliente::query()
                ->where('empresa_id', $empresa->id)
                ->orderBy('nome_fantasia')
                ->orderBy('nome_razao_social')
                ->get(),
            'clientesFretePersonalizado' => Cliente::query()
                ->where('empresa_id', $empresa->id)
                ->where('frete_personalizado', true)
                ->orderBy('nome_fantasia')
                ->orderBy('nome_razao_social')
                ->get(),
            'isSuperAdmin' => $this->isSuperAdmin(),
        ]);
    }

    public function relatorios(Request $request, ComissaoRelatorioService $relatorioService): View
    {
        Gate::authorize('viewAny', FaixaComissao::class);

        $empresa = $this->empresaRepresentacao();
        $validated = $request->validate([
            'empresa_id' => ['nullable', 'integer', Rule::exists('empresas', 'id')],
            'mes' => ['nullable', 'date_format:Y-m'],
            'vendedor_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(
                    fn (Builder $query) => $query
                        ->where('empresa_id', $empresa->id)
                        ->whereNull('deleted_at')
                ),
            ],
        ]);

        $mes = $validated['mes'] ?? now()->format('Y-m');
        $vendedorId = ! empty($validated['vendedor_id']) ? (int) $validated['vendedor_id'] : null;
        $inicio = CarbonImmutable::createFromFormat('!Y-m', $mes)->startOfMonth();
        $fim = $inicio->endOfMonth();
        $pedidosPorResponsavel = Pedido::query()
            ->where('empresa_id', $empresa->id)
            ->whereBetween('data_pedido', [$inicio->toDateString(), $fim->toDateString()])
            ->selectRaw('user_id, count(*) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        $vendedores = User::query()
            ->where('empresa_id', $empresa->id)
            ->orderBy('name')
            ->get()
            ->each(function (User $user) use ($pedidosPorResponsavel): void {
                $user->pedidos_no_periodo = (int) ($pedidosPorResponsavel[$user->id] ?? 0);
            });

        return view('orcamentos.representacao.relatorios', [
            'empresa' => $empresa,
            'empresas' => $this->empresasDisponiveis(),
            'isSuperAdmin' => $this->isSuperAdmin(),
            'vendedores' => $vendedores,
            ...$relatorioService->gerar($request->user(), $mes, $vendedorId),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', FaixaComissao::class);

        return view('orcamentos.representacao.create', [
            'empresa' => $this->empresaRepresentacao(),
            'empresas' => $this->empresasDisponiveis(),
            'isSuperAdmin' => $this->isSuperAdmin(),
        ]);
    }

    public function store(StoreFaixaComissaoRequest $request): RedirectResponse
    {
        $empresa = $this->empresaRepresentacao();

        FaixaComissao::create([
            ...$request->validated(),
            'empresa_id' => $empresa->id,
        ]);

        return redirect()
            ->route('orcamentos.representacao.faixas', $this->queryEmpresa($empresa))
            ->with('success', 'Faixa cadastrada com sucesso.');
    }

    public function edit(FaixaComissao $faixa): View
    {
        Gate::authorize('update', $faixa);

        return view('orcamentos.representacao.edit', [
            'faixa' => $faixa,
            'empresa' => $faixa->empresa,
            'empresas' => $this->empresasDisponiveis(),
            'isSuperAdmin' => $this->isSuperAdmin(),
        ]);
    }

    public function update(UpdateFaixaComissaoRequest $request, FaixaComissao $faixa): RedirectResponse
    {
        $faixa->update($request->validated());

        return redirect()
            ->route('orcamentos.representacao.faixas', $this->queryEmpresa($faixa->empresa))
            ->with('success', 'Faixa atualizada com sucesso.');
    }

    public function destroy(DeleteFaixaComissaoRequest $request, FaixaComissao $faixa): RedirectResponse
    {
        $empresa = $faixa->empresa;
        $faixa->delete();

        return redirect()
            ->route('orcamentos.representacao.faixas', $this->queryEmpresa($empresa))
            ->with('success', 'Faixa excluída com sucesso.');
    }

    private function isSuperAdmin(): bool
    {
        return request()->user()->hasRole(RoleName::SuperAdmin->value);
    }

    private function empresaRepresentacao(): Empresa
    {
        if (! $this->isSuperAdmin()) {
            return request()->user()->empresa;
        }

        return Empresa::query()
            ->when(request('empresa_id'), fn ($query, $empresaId) => $query->whereKey($empresaId))
            ->orderBy('nome_fantasia')
            ->orderBy('razao_social')
            ->firstOrFail();
    }

    /** @return EloquentCollection<int, Empresa>|\Illuminate\Support\Collection<int, Empresa> */
    private function empresasDisponiveis(): EloquentCollection|\Illuminate\Support\Collection
    {
        if (! $this->isSuperAdmin()) {
            return collect();
        }

        return Empresa::query()
            ->orderBy('nome_fantasia')
            ->orderBy('razao_social')
            ->get();
    }

    /** @return array<string, int> */
    private function queryEmpresa(Empresa $empresa): array
    {
        return $this->isSuperAdmin() ? ['empresa_id' => $empresa->id] : [];
    }
}

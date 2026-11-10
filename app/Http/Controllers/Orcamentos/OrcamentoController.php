<?php

namespace App\Http\Controllers\Orcamentos;

use App\Enums\OrcamentoStatus;
use App\Enums\FreteTipo;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orcamentos\ConvertOrcamentoToPedidoRequest;
use App\Http\Requests\Orcamentos\DeleteOrcamentoRequest;
use App\Http\Requests\Orcamentos\FilterOrcamentosRequest;
use App\Http\Requests\Orcamentos\FinishOrcamentoRequest;
use App\Http\Requests\Orcamentos\RenewOrcamentoRequest;
use App\Http\Requests\Orcamentos\SendOrcamentoRequest;
use App\Http\Requests\Orcamentos\StoreOrcamentoRequest;
use App\Http\Requests\Orcamentos\TransitionOrcamentoRequest;
use App\Http\Requests\Orcamentos\UpdateOrcamentoRequest;
use App\Models\Cliente;
use App\Models\Item;
use App\Models\Orcamento;
use App\Models\Pedido;
use App\Models\User;
use App\Services\Orcamentos\OrcamentoCalculator;
use App\Services\Orcamentos\OrcamentoItemSnapshotService;
use App\Services\Orcamentos\OrcamentoNumberGenerator;
use App\Services\Orcamentos\OrcamentoStatusTransition;
use App\Services\Pedidos\PedidoNumberGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrcamentoController extends Controller
{
    public function __construct(
        private readonly OrcamentoCalculator $calculator,
        private readonly OrcamentoItemSnapshotService $snapshotService,
        private readonly OrcamentoNumberGenerator $numberGenerator,
        private readonly OrcamentoStatusTransition $statusTransition,
        private readonly PedidoNumberGenerator $pedidoNumberGenerator,
    ) {}

    public function index(FilterOrcamentosRequest $request): View
    {
        $query = Orcamento::query()->with(['empresa', 'cliente', 'vendedor', 'pedido', 'itens.item'])->latest('id');

        if ($request->user()->cannot('orcamentos.visualizar_todos')) {
            $query->where('user_id', $request->user()->id);
        }

        $statusFiltro = $request->boolean('finalizados') || $request->validated('situacao') === 'finalizado'
            ? OrcamentoStatus::Finalizado->value
            : $request->validated('status');

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

        if ($request->validated('situacao') === 'aberto') {
            $query->where('status', '!=', OrcamentoStatus::Finalizado->value)
                ->doesntHave('pedido');
        } elseif ($statusFiltro === OrcamentoStatus::Finalizado->value) {
            $query->where(function ($query): void {
                $query->where('status', OrcamentoStatus::Finalizado->value)
                    ->orWhereHas('pedido');
            });
        } elseif ($statusFiltro) {
            $query->where('status', $statusFiltro);
        }

        return view('orcamentos.orcamentos.index', [
            'orcamentos' => $query->paginate(15)->withQueryString(),
            'statuses' => OrcamentoStatus::cases(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Orcamento::class);

        return view('orcamentos.orcamentos.create', $this->formData());
    }

    public function buscarItens(Request $request): JsonResponse
    {
        Gate::authorize('create', Orcamento::class);

        abort_if(! $request->user()->empresa_id, 403);

        $termo = trim((string) $request->query('q', ''));
        $termoNumerico = preg_replace('/\D/', '', $termo);

        if (mb_strlen($termo) < 2 && mb_strlen($termoNumerico) < 2) {
            return response()->json([]);
        }

        $itens = Item::query()
            ->with('tributacao')
            ->whereHas('tabela', fn ($query) => $query->where('ativo', true))
            ->where(function ($query) use ($termo, $termoNumerico): void {
                $query->where('descricao', 'like', "%{$termo}%")
                    ->orWhere('numero_serie', 'like', "%{$termo}%")
                    ->when($termoNumerico !== '', fn ($query) => $query->orWhere('numero_serie', 'like', "%{$termoNumerico}%"));
            })
            ->orderBy('descricao')
            ->limit(15)
            ->get()
            ->map(fn (Item $item): array => $this->itemParaFormulario($item))
            ->values();

        return response()->json($itens);
    }

    public function store(StoreOrcamentoRequest $request): RedirectResponse
    {
        $orcamento = DB::transaction(function () use ($request): Orcamento {
            $calculo = $this->calculateFromRequest($request->validated('itens'), $request->user()->empresa_id);
            $numero = $this->numberGenerator->next($request->user()->empresa_id);
            $agora = now();
            $cliente = Cliente::query()->findOrFail($request->validated('cliente_id'));
            $frete = $this->freteAutomatico((float) $calculo['valor_total'], $request->user()->empresa, $cliente);

            $orcamento = Orcamento::create([
                ...$request->safe()->except(['itens', 'responsavel_id', 'acao_apos_salvar']),
                ...$numero,
                ...collect($calculo)->except('itens')->all(),
                'frete_tipo' => $frete,
                'empresa_id' => $request->user()->empresa_id,
                'user_id' => $request->user()->hasAnyRole([RoleName::AdminEmpresa->value, RoleName::Supervisor->value])
                    ? $request->validated('responsavel_id')
                    : $request->user()->id,
                'status' => OrcamentoStatus::Rascunho,
                'data_emissao' => $agora->toDateString(),
                'hora_emissao' => $agora->format('H:i:s'),
            ]);

            $this->replaceItems($orcamento, $calculo['itens']);

            return $orcamento;
        }, 3);

        if ($request->validated('acao_apos_salvar') === 'pdf') {
            return redirect()->route('orcamentos.orcamentos.pdf', $orcamento);
        }

        return redirect()->route('orcamentos.orcamentos.edit', $orcamento)
            ->with('success', "Orçamento {$orcamento->numero} salvo como rascunho.");
    }

    public function show(Orcamento $orcamento): View
    {
        Gate::authorize('view', $orcamento);
        $orcamento->load(['empresa', 'cliente', 'vendedor', 'itens', 'pedido']);
        $alteracoesItens = $this->snapshotService->priceChanges($orcamento);

        return view('orcamentos.orcamentos.show', compact('orcamento', 'alteracoesItens'));
    }

    public function edit(Orcamento $orcamento): View
    {
        Gate::authorize('update', $orcamento);
        $orcamento->load('itens');
        $alteracoesItens = $this->snapshotService->priceChanges($orcamento);

        return view('orcamentos.orcamentos.edit', [
            ...$this->formData($orcamento),
            'orcamento' => $orcamento,
            'alteracoesItens' => $alteracoesItens,
        ]);
    }

    public function update(UpdateOrcamentoRequest $request, Orcamento $orcamento): RedirectResponse
    {
        DB::transaction(function () use ($request, $orcamento): void {
            $calculo = $this->calculateFromRequest($request->validated('itens'), $request->user()->empresa_id);
            $dados = $request->safe()->except(['itens', 'responsavel_id', 'acao_apos_salvar']);
            $cliente = Cliente::query()->findOrFail($request->validated('cliente_id'));
            $dados['frete_tipo'] = $this->freteAutomatico((float) $calculo['valor_total'], $request->user()->empresa, $cliente);
            if ($request->user()->hasAnyRole([RoleName::AdminEmpresa->value, RoleName::Supervisor->value])) {
                $dados['user_id'] = $request->validated('responsavel_id');
            }
            $orcamento->update([
                ...$dados,
                ...collect($calculo)->except('itens')->all(),
            ]);
            $this->replaceItems($orcamento, $calculo['itens']);
        });

        if ($request->validated('acao_apos_salvar') === 'pdf') {
            return redirect()->route('orcamentos.orcamentos.pdf', $orcamento);
        }

        return redirect()->route('orcamentos.orcamentos.edit', $orcamento)->with('success', 'Orçamento atualizado com sucesso.');
    }

    public function destroy(DeleteOrcamentoRequest $request, Orcamento $orcamento): RedirectResponse
    {
        if ($orcamento->pedido()->exists()) {
            return redirect()
                ->route('orcamentos.orcamentos.index')
                ->with('error', 'Este orçamento já virou pedido e não pode mais ser excluído.');
        }

        $orcamento->delete();

        return redirect()->route('orcamentos.orcamentos.index')->with('success', 'Orçamento excluído com sucesso.');
    }

    public function send(SendOrcamentoRequest $request, Orcamento $orcamento): RedirectResponse
    {
        $this->statusTransition->transition($orcamento, OrcamentoStatus::Enviado);

        return redirect()->route('orcamentos.orcamentos.show', $orcamento)->with('success', 'Orçamento marcado como enviado.');
    }

    public function approve(TransitionOrcamentoRequest $request, Orcamento $orcamento): RedirectResponse
    {
        $this->statusTransition->transition($orcamento, OrcamentoStatus::Aprovado);

        return redirect()->route('orcamentos.orcamentos.show', $orcamento)->with('success', 'Orçamento marcado como aprovado.');
    }

    public function reject(TransitionOrcamentoRequest $request, Orcamento $orcamento): RedirectResponse
    {
        $this->statusTransition->transition($orcamento, OrcamentoStatus::Rejeitado);

        return redirect()->route('orcamentos.orcamentos.show', $orcamento)->with('success', 'Orçamento marcado como rejeitado.');
    }

    public function finish(FinishOrcamentoRequest $request, Orcamento $orcamento): RedirectResponse
    {
        if ($erro = $this->erroMultiplosEmbalagem($orcamento)) {
            return redirect()
                ->route('orcamentos.orcamentos.show', $orcamento)
                ->with('error', $erro);
        }

        $this->statusTransition->transition($orcamento, OrcamentoStatus::Finalizado);

        return redirect()->route('orcamentos.orcamentos.show', $orcamento)->with('success', 'Orçamento finalizado.');
    }

    public function convertToPedido(ConvertOrcamentoToPedidoRequest $request, Orcamento $orcamento): RedirectResponse
    {
        $pedido = DB::transaction(function () use ($orcamento): Pedido {
            $locked = Orcamento::withoutGlobalScopes()
                ->with('pedido')
                ->whereKey($orcamento->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->pedido) {
                return $locked->pedido;
            }

            abort_if($locked->status === OrcamentoStatus::Rejeitado, 422, 'Orçamentos rejeitados não podem virar pedido.');

            $locked = $this->snapshotService->syncWithCurrentCatalog($locked);

            if ($erro = $this->erroMultiplosEmbalagem($locked)) {
                throw ValidationException::withMessages(['itens' => $erro]);
            }

            $agora = now();
            $pedido = Pedido::create([
                ...$this->pedidoNumberGenerator->next($locked->empresa_id),
                'empresa_id' => $locked->empresa_id,
                'orcamento_id' => $locked->id,
                'user_id' => $locked->user_id,
                'cliente_id' => $locked->cliente_id,
                'status' => 'aberto',
                'data_pedido' => $agora->toDateString(),
                'hora_pedido' => $agora->format('H:i:s'),
                'subtotal_sem_desconto' => $locked->subtotal_sem_desconto,
                'desconto_efetivo_percentual' => $locked->desconto_efetivo_percentual,
                'total_produtos' => $locked->total_produtos,
                'total_impostos' => $locked->total_impostos,
                'valor_total' => $locked->valor_total,
            ]);

            if ($locked->status !== OrcamentoStatus::Finalizado) {
                $locked->update(['status' => OrcamentoStatus::Finalizado]);
            }

            return $pedido;
        }, 3);

        return redirect()->route('orcamentos.pedidos.show', $pedido)
            ->with('success', "Orçamento {$orcamento->numero} virou o pedido {$pedido->numero}.");
    }

    public function renew(RenewOrcamentoRequest $request, Orcamento $orcamento): RedirectResponse
    {
        $renovado = $this->statusTransition->renew($orcamento);

        return redirect()->route('orcamentos.orcamentos.edit', $renovado)
            ->with('success', 'Orçamento renovado como rascunho. Revise os dados e envie novamente.');
    }

    public function duplicate(Orcamento $orcamento): RedirectResponse
    {
        Gate::authorize('view', $orcamento);
        Gate::authorize('create', Orcamento::class);
        abort_if($orcamento->cliente()->whereNull('deleted_at')->doesntExist(), 422, 'O cliente deste orçamento não está mais ativo.');

        $novo = DB::transaction(function () use ($orcamento): Orcamento {
            $orcamento->load('itens');
            $numero = $this->numberGenerator->next(request()->user()->empresa_id);
            $agora = now();
            $novo = Orcamento::create([
                ...$orcamento->only([
                    'cliente_id', 'forma_pagamento', 'boleto_prazos', 'validade_dias', 'observacoes',
                    'finalidade', 'frete_tipo', 'subtotal_sem_desconto', 'desconto_efetivo_percentual', 'total_produtos',
                    'total_impostos', 'valor_total',
                    'comissao_percentual', 'valor_comissao',
                ]),
                ...$numero,
                'empresa_id' => request()->user()->empresa_id,
                'user_id' => request()->user()->hasAnyRole([RoleName::AdminEmpresa->value, RoleName::Supervisor->value])
                    ? $orcamento->user_id
                    : request()->user()->id,
                'status' => OrcamentoStatus::Rascunho,
                'data_emissao' => $agora->toDateString(),
                'hora_emissao' => $agora->format('H:i:s'),
            ]);

            $novo->itens()->createMany($orcamento->itens->map(fn ($item): array => [
                ...$item->only([
                    'item_id', 'descricao', 'tipo_venda', 'embalagem_quantidade', 'ncm',
                    'cest', 'quantidade', 'valor_unitario', 'desconto_percentual',
                    'aliquota_icms', 'aliquota_ipi', 'aliquota_pis', 'aliquota_cofins',
                    'possui_st', 'mva', 'observacoes_fiscais', 'valor_produtos', 'valor_icms',
                    'valor_ipi', 'valor_pis', 'valor_cofins', 'base_calculo_st',
                    'valor_icms_st', 'valor_impostos', 'valor_total',
                ]),
                'empresa_id' => $novo->empresa_id,
            ])->all());

            return $novo;
        }, 3);

        return redirect()->route('orcamentos.orcamentos.edit', $novo)->with('success', "Orçamento duplicado como {$novo->numero}.");
    }

    private function formData(?Orcamento $orcamento = null): array
    {
        $user = request()->user();
        $linhasDoOrcamento = $orcamento
            ? $orcamento->itens->map(fn ($linha) => ['item_id' => $linha->item_id])->all()
            : [];
        $itensSelecionados = collect(request()->old('itens', $linhasDoOrcamento))
            ->pluck('item_id')
            ->filter()
            ->unique()
            ->values();

        $clientes = Cliente::query()
            ->when(
                ! $user->hasAnyRole([RoleName::AdminEmpresa->value, RoleName::Supervisor->value]),
                fn ($query) => $query->where(function ($query) use ($user, $orcamento): void {
                    $query->where('user_id', $user->id)
                        ->when($orcamento, fn ($query) => $query->orWhere('id', $orcamento->cliente_id));
                }),
            )
            ->orderBy('nome_fantasia')
            ->orderBy('nome_razao_social')
            ->get();

        return [
            'empresa' => $user->empresa,
            'clientes' => $clientes,
            'catalogoItens' => Item::query()
                ->with('tributacao')
                ->when($itensSelecionados->isNotEmpty(), fn ($query) => $query->whereIn('id', $itensSelecionados))
                ->when($itensSelecionados->isEmpty(), fn ($query) => $query->whereRaw('1 = 0'))
                ->orderBy('descricao')
                ->get(),
            'responsaveis' => User::query()
                ->role([
                    RoleName::AdminEmpresa->value,
                    RoleName::Supervisor->value,
                    RoleName::Vendedor->value,
                ])
                ->orderBy('name')
                ->get(),
        ];
    }

    private function itemParaFormulario(Item $item): array
    {
        return [
            'id' => $item->id,
            'codigo' => $item->numero_serie,
            'descricao' => $item->descricao,
            'tipo_venda' => $item->tipo_venda?->value ?? 'unitario',
            'tipo_label' => $item->tipo_venda?->label() ?? 'Unitário',
            'unidade' => $item->tipo_venda?->unidade() ?? 'un.',
            'embalagem_quantidade' => (float) ($item->embalagem_quantidade ?? 1),
            'preco' => (float) $item->preco_atual,
            'ncm' => $item->tributacao?->ncm,
            'icms' => (float) ($item->tributacao?->aliquota_icms ?? 0),
            'ipi' => (float) ($item->tributacao?->aliquota_ipi ?? 0),
            'pis' => (float) ($item->tributacao?->aliquota_pis ?? 0),
            'cofins' => (float) ($item->tributacao?->aliquota_cofins ?? 0),
            'possui_st' => (bool) ($item->tributacao?->possui_st ?? false),
            'mva' => (float) ($item->tributacao?->mva ?? 0),
        ];
    }

    private function calculateFromRequest(array $linhas, int $empresaId): array
    {
        $catalogo = Item::query()->with('tributacao')->whereIn('id', collect($linhas)->pluck('item_id'))->get()->keyBy('id');
        $itens = collect($linhas)->map(function (array $linha) use ($catalogo): array {
            $item = $catalogo->get((int) $linha['item_id']);
            $tributacao = $item->tributacao;

            return [
                'item_id' => $item->id,
                'descricao' => $item->descricao,
                'tipo_venda' => $item->tipo_venda?->value ?? 'unitario',
                'embalagem_quantidade' => (float) ($item->embalagem_quantidade ?? 1),
                'quantidade' => (int) $linha['quantidade'],
                'valor_unitario' => (float) $item->preco_atual,
                'desconto_percentual' => (float) $linha['desconto_percentual'],
                'ncm' => $tributacao?->ncm,
                'cest' => $tributacao?->cest,
                'aliquota_icms' => (float) ($tributacao?->aliquota_icms ?? 0),
                'aliquota_ipi' => (float) ($tributacao?->aliquota_ipi ?? 0),
                'aliquota_pis' => (float) ($tributacao?->aliquota_pis ?? 0),
                'aliquota_cofins' => (float) ($tributacao?->aliquota_cofins ?? 0),
                'possui_st' => (bool) ($tributacao?->possui_st ?? false),
                'mva' => (float) ($tributacao?->mva ?? 0),
                'observacoes_fiscais' => $tributacao?->observacoes_fiscais,
            ];
        });

        return $this->calculator->calculate($itens, $empresaId);
    }

    private function freteAutomatico(float $valorTotal, \App\Models\Empresa $empresa, ?Cliente $cliente = null): FreteTipo
    {
        $limite = $cliente?->frete_personalizado && $cliente->limite_frete_fob !== null
            ? (float) $cliente->limite_frete_fob
            : (float) $empresa->limite_frete_fob;

        return $valorTotal <= $limite
            ? FreteTipo::Fob
            : FreteTipo::Cif;
    }

    /** @param Collection<int, array<string, mixed>> $itens */
    private function replaceItems(Orcamento $orcamento, Collection $itens): void
    {
        $orcamento->itens()->delete();
        $orcamento->itens()->createMany($itens->map(fn (array $item): array => [
            ...$item,
            'empresa_id' => $orcamento->empresa_id,
        ])->all());
    }

    private function erroMultiplosEmbalagem(Orcamento $orcamento): ?string
    {
        $orcamento->loadMissing('itens.item');

        $pendencias = $orcamento->itens
            ->filter(function ($linha): bool {
                $embalagem = (float) ($linha->embalagem_quantidade ?: $linha->item?->embalagem_quantidade ?: 1);
                $quantidade = (float) $linha->quantidade;

                if ($embalagem <= 1 || $quantidade <= 0) {
                    return false;
                }

                $proporcao = $quantidade / $embalagem;

                return abs($proporcao - round($proporcao)) > 0.000001;
            })
            ->map(function ($linha): string {
                $embalagem = (float) ($linha->embalagem_quantidade ?: $linha->item?->embalagem_quantidade ?: 1);
                $unidade = $linha->tipo_venda === 'metro' ? 'm' : 'un.';

                return "{$linha->descricao}: quantidade {$linha->quantidade} não é múltiplo da embalagem de ".number_format($embalagem, 3, ',', '.')." {$unidade}";
            })
            ->values();

        if ($pendencias->isEmpty()) {
            return null;
        }

        return 'Não é possível finalizar/virar pedido. Ajuste os múltiplos de embalagem: '.$pendencias->implode('; ').'.';
    }
}

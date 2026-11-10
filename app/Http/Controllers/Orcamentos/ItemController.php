<?php

namespace App\Http\Controllers\Orcamentos;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orcamentos\DeleteItemTabelaRequest;
use App\Http\Requests\Orcamentos\DeleteItemRequest;
use App\Http\Requests\Orcamentos\StoreItemRequest;
use App\Http\Requests\Orcamentos\UpdateItemRequest;
use App\Models\Empresa;
use App\Models\Item;
use App\Models\ItemTabela;
use App\Models\ItemTributacao;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ItemController extends Controller
{
    private const TABELA_PADRAO_NOME = 'Tabela padrão';

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Item::class);

        $tabelas = $this->tabelasDisponiveis($request);
        $tabelaSelecionada = $this->tabelaSelecionada($tabelas, $request->integer('tabela_id'));

        $itens = Item::query()
            ->with(['empresa', 'tabela'])
            ->when($tabelaSelecionada, fn ($query) => $query->where('item_tabela_id', $tabelaSelecionada->id))
            ->when($request->filled('busca'), function ($query) use ($request): void {
                $busca = trim((string) $request->query('busca'));
                $buscaNumerica = preg_replace('/\D/', '', $busca);

                $query->where(function ($query) use ($busca, $buscaNumerica): void {
                    $query->where('descricao', 'like', "%{$busca}%")
                        ->orWhere('numero_serie', 'like', "%{$busca}%")
                        ->when($buscaNumerica !== '', fn ($query) => $query->orWhere('numero_serie', 'like', "%{$buscaNumerica}%"));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('orcamentos.itens.index', [
            'itens' => $itens,
            'tabelas' => $tabelas,
            'tabelaSelecionada' => $tabelaSelecionada,
            'empresas' => $this->empresasDisponiveis($request),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Item::class);

        return view('orcamentos.itens.create', [
            'tabelas' => $this->tabelasDisponiveis($request),
            'tributacaoPadrao' => ItemTributacao::query()->latest('id')->first(),
        ]);
    }

    public function store(StoreItemRequest $request): RedirectResponse
    {
        $tabelaId = null;

        DB::transaction(function () use ($request, &$tabelaId): void {
            $dados = $request->validated();
            $tabelaId = (int) $dados['item_tabela_id'];
            $item = Item::create([
                ...Arr::except($dados, 'tributacao'),
                'empresa_id' => $request->user()->empresa_id,
            ]);
            $item->tributacao()->create([
                ...$dados['tributacao'],
                'empresa_id' => $request->user()->empresa_id,
            ]);

            $this->atualizarLogTabela($tabelaId, $request);
        });

        return redirect()
            ->route('orcamentos.itens.index', ['tabela_id' => $tabelaId])
            ->with('success', 'Item cadastrado com sucesso.');
    }

    public function show(Item $item): View
    {
        Gate::authorize('view', $item);
        $item->load(['tributacao', 'tabela']);
        $historico = Gate::allows('viewHistory', $item) ? $item->precosHistorico()->get() : collect();

        return view('orcamentos.itens.show', compact('item', 'historico'));
    }

    public function edit(Request $request, Item $item): View
    {
        Gate::authorize('update', $item);
        $item->load(['tributacao', 'tabela']);
        $historico = $item->precosHistorico()->get();

        return view('orcamentos.itens.edit', [
            'item' => $item,
            'historico' => $historico,
            'tabelas' => $this->tabelasDisponiveis($request),
        ]);
    }

    public function update(UpdateItemRequest $request, Item $item): RedirectResponse
    {
        $tabelaAnterior = $item->item_tabela_id;

        DB::transaction(function () use ($request, $item, $tabelaAnterior): void {
            $dados = $request->validated();
            $item->update(Arr::except($dados, 'tributacao'));
            $item->tributacao()->updateOrCreate(
                ['item_id' => $item->id],
                [...$dados['tributacao'], 'empresa_id' => $item->empresa_id],
            );

            $this->atualizarLogTabela($item->item_tabela_id, $request);

            if ($tabelaAnterior && (int) $tabelaAnterior !== (int) $item->item_tabela_id) {
                $this->atualizarLogTabela((int) $tabelaAnterior, $request);
            }
        });

        return redirect()
            ->route('orcamentos.itens.edit', $item)
            ->with('success', 'Item atualizado e histórico registrado.');
    }

    public function destroy(DeleteItemRequest $request, Item $item): RedirectResponse
    {
        $tabelaId = $item->item_tabela_id;
        $item->delete();

        if ($tabelaId) {
            $this->atualizarLogTabela((int) $tabelaId, $request);
        }

        return redirect()
            ->route('orcamentos.itens.index', ['tabela_id' => $tabelaId])
            ->with('success', 'Item excluído com sucesso.');
    }

    public function downloadModelo(): StreamedResponse
    {
        Gate::authorize('create', Item::class);

        return $this->baixarCsv('modelo-importacao-itens.csv', function ($saida): void {
            $this->escreverLinhaCsv($saida, $this->cabecalhoCsv());
        });
    }

    public function importarTabela(Request $request): RedirectResponse
    {
        Gate::authorize('create', Item::class);
        $empresaId = $this->empresaIdParaCadastro($request);
        abort_if(! $empresaId, 403);

        $validated = $request->validate([
            'empresa_id' => [
                $this->isSuperAdmin($request) ? 'required' : 'nullable',
                'integer',
                'exists:empresas,id',
            ],
            'nome_tabela' => [
                'required',
                'string',
                'max:120',
            ],
            'arquivo' => ['required', 'file', 'max:10240', 'mimes:csv,txt,xlsx'],
        ], [
            'empresa_id.required' => 'Selecione a empresa que receberá esta tabela.',
            'arquivo.required' => 'Selecione a planilha preenchida.',
            'arquivo.mimes' => 'Envie uma planilha CSV ou Excel no formato .xlsx.',
        ]);

        $nomeTabela = $this->texto($validated['nome_tabela']);

        if ($this->existeTabelaAtivaComNome($empresaId, $nomeTabela)) {
            return back()->withErrors(['nome_tabela' => 'Já existe uma tabela com este nome nesta empresa.'])->withInput();
        }

        [$linhas, $erros] = $this->lerItensPlanilha(
            $request->file('arquivo')->getRealPath(),
            $request->file('arquivo')->getClientOriginalName(),
        );

        if ($erros !== []) {
            return back()->withErrors(['arquivo' => implode(' ', array_slice($erros, 0, 6))])->withInput();
        }

        $tabela = null;

        $resultado = ['criados' => 0, 'atualizados' => 0];

        DB::transaction(function () use ($request, $linhas, $empresaId, $nomeTabela, &$tabela, &$resultado): void {
            $this->liberarNomeDeTabelasExcluidas($empresaId, $nomeTabela);

            $tabela = ItemTabela::create([
                'empresa_id' => $empresaId,
                'nome' => $nomeTabela,
                'arquivo_original' => $this->texto($request->file('arquivo')->getClientOriginalName()),
                'itens_importados' => 0,
                'ultima_atualizacao_em' => now(),
                'atualizado_por_id' => $request->user()->id,
            ]);

            if ($linhas !== []) {
                $resultado = $this->importarLinhasNaTabela($request, $tabela, $linhas);
            }
        });

        if ($linhas === []) {
            return redirect()
                ->route('orcamentos.itens.index', ['tabela_id' => $tabela?->id])
                ->with('success', 'Tabela criada sem itens. Use "Novo item" ou importe outra planilha para preencher esta tabela.');
        }

        return redirect()
            ->route('orcamentos.itens.index', ['tabela_id' => $tabela?->id])
            ->with('success', 'Tabela criada e importada: '.$resultado['criados'].' item(ns) criado(s), '.$resultado['atualizados'].' atualizado(s).');
    }

    public function storeTabela(Request $request): RedirectResponse
    {
        Gate::authorize('create', Item::class);

        $empresaId = $this->empresaIdParaCadastro($request);
        abort_if(! $empresaId, 403);

        $validated = $request->validate([
            'empresa_id' => [
                $this->isSuperAdmin($request) ? 'required' : 'nullable',
                'integer',
                'exists:empresas,id',
            ],
            'nome_tabela' => ['required', 'string', 'max:120'],
        ], [
            'empresa_id.required' => 'Selecione a empresa que receberá esta tabela.',
            'nome_tabela.required' => 'Informe o nome da tabela.',
        ]);

        $nome = $this->texto($validated['nome_tabela']);

        if ($nome === self::TABELA_PADRAO_NOME) {
            return back()->withErrors(['nome_tabela' => 'Este nome é reservado para a tabela base do sistema.'])->withInput();
        }

        if ($this->existeTabelaAtivaComNome($empresaId, $nome)) {
            return back()->withErrors(['nome_tabela' => 'Já existe uma tabela com este nome nesta empresa.'])->withInput();
        }

        $tabela = DB::transaction(function () use ($request, $empresaId, $nome): ItemTabela {
            $this->liberarNomeDeTabelasExcluidas($empresaId, $nome);

            return ItemTabela::create([
                'empresa_id' => $empresaId,
                'nome' => $nome,
                'itens_importados' => 0,
                'ultima_atualizacao_em' => now(),
                'atualizado_por_id' => $request->user()->id,
            ]);
        });

        return redirect()
            ->route('orcamentos.itens.index', ['tabela_id' => $tabela->id])
            ->with('success', 'Tabela criada com sucesso.');
    }

    public function exportarTabela(ItemTabela $tabela): StreamedResponse
    {
        Gate::authorize('viewAny', Item::class);

        $itens = Item::query()
            ->with('tributacao')
            ->where('item_tabela_id', $tabela->id)
            ->orderBy('descricao')
            ->get();

        $arquivo = Str::slug($tabela->nome) ?: 'tabela-itens';

        return $this->baixarCsv($arquivo.'.csv', function ($saida) use ($itens): void {
            $this->escreverLinhaCsv($saida, $this->cabecalhoCsv());

            foreach ($itens as $item) {
                $tributacao = $item->tributacao;
                $this->escreverLinhaCsv($saida, [
                    $item->numero_serie,
                    $item->descricao,
                    $item->tipo_venda?->value ?? 'unitario',
                    $this->decimalCsv($item->embalagem_quantidade),
                    $this->decimalCsv($item->preco_atual),
                    $tributacao?->ncm,
                    $tributacao?->cest,
                    $tributacao?->cfop,
                    $tributacao?->cst_csosn,
                    $tributacao?->unidade_comercial ?? 'UN',
                    $this->decimalCsv($tributacao?->aliquota_icms ?? 0),
                    $this->decimalCsv($tributacao?->aliquota_ipi ?? 0),
                    $this->decimalCsv($tributacao?->aliquota_pis ?? 0),
                    $this->decimalCsv($tributacao?->aliquota_cofins ?? 0),
                    $tributacao?->possui_st ? 'sim' : 'não',
                    $this->decimalCsv($tributacao?->mva),
                    $tributacao?->observacoes_fiscais,
                ]);
            }
        });
    }

    public function destroyTabela(DeleteItemTabelaRequest $request, ItemTabela $tabela): RedirectResponse
    {
        if ($this->isTabelaPadrao($tabela)) {
            return back()->withErrors(['tabela' => 'A Tabela padrão é obrigatória e não pode ser excluída.']);
        }

        DB::transaction(function () use ($tabela): void {
            Item::query()
                ->where('item_tabela_id', $tabela->id)
                ->get()
                ->each(fn (Item $item) => $item->delete());

            $tabela->forceFill([
                'nome' => $this->nomeTabelaExcluida($tabela),
            ])->save();

            $tabela->delete();
        });

        return redirect()
            ->route('orcamentos.itens.index')
            ->with('success', 'Tabela excluída com sucesso.');
    }

    public function toggleTabelaAtiva(Request $request, ItemTabela $tabela): RedirectResponse
    {
        Gate::authorize('create', Item::class);

        $validated = $request->validate([
            'ativo' => ['required', 'boolean'],
        ]);

        $tabela->update([
            'ativo' => (bool) $validated['ativo'],
            'ultima_atualizacao_em' => now(),
            'atualizado_por_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('orcamentos.itens.index', ['tabela_id' => $tabela->id])
            ->with('success', (bool) $validated['ativo'] ? 'Tabela ativada para orçamentos.' : 'Tabela desativada para orçamentos.');
    }

    private function tabelasDisponiveis(Request $request): Collection
    {
        if ($request->user()->empresa_id) {
            $this->garantirTabelaPadrao($request);
        }

        return ItemTabela::query()
            ->with('empresa')
            ->withCount('itens')
            ->orderBy('nome')
            ->get();
    }

    private function empresasDisponiveis(Request $request): Collection
    {
        if (! $this->isSuperAdmin($request)) {
            return new Collection();
        }

        return Empresa::query()
            ->orderBy('nome_fantasia')
            ->orderBy('razao_social')
            ->get();
    }

    private function tabelaSelecionada(Collection $tabelas, ?int $tabelaId): ?ItemTabela
    {
        if ($tabelaId) {
            return $tabelas->firstWhere('id', $tabelaId);
        }

        return $tabelas->first();
    }

    private function existeTabelaAtivaComNome(int $empresaId, string $nome): bool
    {
        return ItemTabela::query()
            ->where('empresa_id', $empresaId)
            ->where('nome', $nome)
            ->exists();
    }

    private function liberarNomeDeTabelasExcluidas(int $empresaId, string $nome): void
    {
        ItemTabela::withTrashed()
            ->where('empresa_id', $empresaId)
            ->where('nome', $nome)
            ->whereNotNull('deleted_at')
            ->get()
            ->each(function (ItemTabela $tabela): void {
                $tabela->forceFill([
                    'nome' => $this->nomeTabelaExcluida($tabela),
                ])->save();
            });
    }

    private function nomeTabelaExcluida(ItemTabela $tabela): string
    {
        $sufixo = ' [excluída #'.$tabela->id.']';

        return Str::limit($tabela->nome, 120 - mb_strlen($sufixo), '').$sufixo;
    }

    private function garantirTabelaPadrao(Request $request): void
    {
        $tabela = ItemTabela::withTrashed()->firstOrNew([
            'empresa_id' => $request->user()->empresa_id,
            'nome' => self::TABELA_PADRAO_NOME,
        ]);

        if (! $tabela->exists) {
            $tabela->fill([
                'itens_importados' => 0,
                'ultima_atualizacao_em' => now(),
                'atualizado_por_id' => $request->user()->id,
            ])->save();

            return;
        }

        if ($tabela->trashed()) {
            $tabela->restore();

            Item::withTrashed()
                ->where('item_tabela_id', $tabela->id)
                ->restore();
        }
    }

    private function isTabelaPadrao(ItemTabela $tabela): bool
    {
        return $tabela->nome === self::TABELA_PADRAO_NOME;
    }

    private function atualizarLogTabela(?int $tabelaId, Request $request): void
    {
        if (! $tabelaId) {
            return;
        }

        ItemTabela::query()
            ->whereKey($tabelaId)
            ->update([
                'itens_importados' => Item::query()->where('item_tabela_id', $tabelaId)->count(),
                'ultima_atualizacao_em' => now(),
                'atualizado_por_id' => $request->user()->id,
            ]);
    }

    private function empresaIdParaCadastro(Request $request): ?int
    {
        if ($this->isSuperAdmin($request)) {
            return $request->integer('empresa_id') ?: null;
        }

        return $request->user()?->empresa_id;
    }

    private function isSuperAdmin(Request $request): bool
    {
        return (bool) $request->user()?->hasRole(RoleName::SuperAdmin->value);
    }

    /**
     * @param  array<int, array<string, mixed>>  $linhas
     * @return array{criados:int, atualizados:int}
     */
    private function importarLinhasNaTabela(Request $request, ItemTabela $tabela, array $linhas): array
    {
        $resultado = ['criados' => 0, 'atualizados' => 0];

        foreach ($linhas as $linha) {
            $item = null;

            if (! empty($linha['numero_serie'])) {
                $item = Item::query()
                    ->where('item_tabela_id', $tabela->id)
                    ->where('numero_serie', $linha['numero_serie'])
                    ->first();
            }

            $dadosItem = [
                'empresa_id' => $tabela->empresa_id,
                'item_tabela_id' => $tabela->id,
                'numero_serie' => $linha['numero_serie'],
                'descricao' => $linha['descricao'],
                'tipo_venda' => $linha['tipo_venda'],
                'embalagem_quantidade' => $linha['embalagem_quantidade'],
                'preco_atual' => $linha['preco_atual'],
            ];

            if ($item) {
                $item->update($dadosItem);
                $resultado['atualizados']++;
            } else {
                $item = Item::create($dadosItem);
                $resultado['criados']++;
            }

            $item->tributacao()->updateOrCreate(
                ['item_id' => $item->id],
                [
                    'empresa_id' => $tabela->empresa_id,
                    'ncm' => $linha['tributacao']['ncm'],
                    'cest' => $linha['tributacao']['cest'],
                    'cfop' => $linha['tributacao']['cfop'],
                    'cst_csosn' => $linha['tributacao']['cst_csosn'],
                    'unidade_comercial' => $linha['tributacao']['unidade_comercial'],
                    'aliquota_icms' => $linha['tributacao']['aliquota_icms'],
                    'aliquota_ipi' => $linha['tributacao']['aliquota_ipi'],
                    'aliquota_pis' => $linha['tributacao']['aliquota_pis'],
                    'aliquota_cofins' => $linha['tributacao']['aliquota_cofins'],
                    'possui_st' => $linha['tributacao']['possui_st'],
                    'mva' => $linha['tributacao']['mva'],
                    'observacoes_fiscais' => $linha['tributacao']['observacoes_fiscais'],
                ],
            );
        }

        $this->atualizarLogTabela($tabela->id, $request);

        return $resultado;
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, string>}
     */
    private function lerItensPlanilha(string $caminho, string $nomeOriginal): array
    {
        $extensao = Str::lower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));

        if ($extensao === 'xlsx') {
            return $this->lerItensXlsx($caminho);
        }

        if (in_array($extensao, ['csv', 'txt'], true)) {
            return $this->lerItensCsv($caminho);
        }

        return [[], ['Formato não suportado. Envie CSV ou Excel .xlsx.']];
    }

    private function lerItensCsv(string $caminho): array
    {
        $handle = fopen($caminho, 'rb');

        if (! $handle) {
            return [[], ['Não foi possível abrir a planilha enviada.']];
        }

        $primeiraLinha = fgets($handle) ?: '';
        $delimitador = substr_count($primeiraLinha, ';') >= substr_count($primeiraLinha, ',') ? ';' : ',';
        rewind($handle);

        $cabecalho = fgetcsv($handle, 0, $delimitador);

        if (! is_array($cabecalho)) {
            fclose($handle);

            return [[], ['Não foi possível ler o cabeçalho da planilha.']];
        }

        $linhasDados = [];

        while (($dados = fgetcsv($handle, 0, $delimitador)) !== false) {
            $linhasDados[] = $dados;
        }

        fclose($handle);

        return $this->normalizarLinhasPlanilha($cabecalho, $linhasDados);
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, string>}
     */
    private function lerItensXlsx(string $caminho): array
    {
        $zip = new \ZipArchive();

        if ($zip->open($caminho) !== true) {
            return [[], ['Não foi possível abrir o arquivo Excel enviado.']];
        }

        try {
            $sharedStrings = $this->xlsxSharedStrings($zip);
            $sheetPath = $this->xlsxPrimeiraPlanilhaPath($zip);

            if (! $sheetPath) {
                return [[], ['Não foi possível encontrar a primeira aba da planilha Excel.']];
            }

            $sheetXml = $zip->getFromName($sheetPath);

            if (! is_string($sheetXml)) {
                return [[], ['Não foi possível ler a primeira aba da planilha Excel.']];
            }

            $xml = simplexml_load_string($sheetXml);

            if (! $xml) {
                return [[], ['A primeira aba da planilha Excel está inválida.']];
            }

            $matriz = [];

            foreach ($xml->sheetData->row as $row) {
                $linha = [];

                foreach ($row->c as $cell) {
                    $referencia = (string) $cell['r'];
                    $indice = $this->xlsxIndiceColuna($referencia);
                    $linha[$indice] = $this->xlsxValorCelula($cell, $sharedStrings);
                }

                if ($linha !== []) {
                    ksort($linha);
                    $matriz[] = $this->preencherColunasAusentes($linha);
                }
            }

            if ($matriz === []) {
                return [[], ['A planilha Excel está vazia.']];
            }

            $cabecalho = array_shift($matriz);

            return $this->normalizarLinhasPlanilha($cabecalho, $matriz);
        } finally {
            $zip->close();
        }
    }

    /**
     * @return array<int, string>
     */
    private function xlsxSharedStrings(\ZipArchive $zip): array
    {
        $xmlString = $zip->getFromName('xl/sharedStrings.xml');

        if (! is_string($xmlString)) {
            return [];
        }

        $xml = simplexml_load_string($xmlString);

        if (! $xml) {
            return [];
        }

        $strings = [];

        foreach ($xml->si as $si) {
            if (isset($si->t)) {
                $strings[] = (string) $si->t;
                continue;
            }

            $texto = '';

            foreach ($si->r as $richText) {
                $texto .= (string) $richText->t;
            }

            $strings[] = $texto;
        }

        return $strings;
    }

    private function xlsxPrimeiraPlanilhaPath(\ZipArchive $zip): ?string
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if (! is_string($workbookXml) || ! is_string($relsXml)) {
            return $zip->locateName('xl/worksheets/sheet1.xml') !== false ? 'xl/worksheets/sheet1.xml' : null;
        }

        $workbook = simplexml_load_string($workbookXml);
        $rels = simplexml_load_string($relsXml);

        if (! $workbook || ! $rels) {
            return null;
        }

        $workbook->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $workbook->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $sheets = $workbook->xpath('//main:sheet');
        $primeiroSheet = $sheets[0] ?? null;
        $relationId = $primeiroSheet ? (string) $primeiroSheet->attributes('r', true)['id'] : null;

        if (! $relationId) {
            return null;
        }

        foreach ($rels->Relationship as $relationship) {
            if ((string) $relationship['Id'] === $relationId) {
                $target = ltrim((string) $relationship['Target'], '/');

                return str_starts_with($target, 'xl/') ? $target : 'xl/'.$target;
            }
        }

        return null;
    }

    private function xlsxIndiceColuna(string $referencia): int
    {
        preg_match('/^[A-Z]+/i', $referencia, $matches);
        $letras = strtoupper($matches[0] ?? 'A');
        $indice = 0;

        foreach (str_split($letras) as $letra) {
            $indice = ($indice * 26) + (ord($letra) - 64);
        }

        return $indice - 1;
    }

    private function xlsxValorCelula(\SimpleXMLElement $cell, array $sharedStrings): string
    {
        $tipo = (string) $cell['t'];

        if ($tipo === 's') {
            $indice = (int) $cell->v;

            return $sharedStrings[$indice] ?? '';
        }

        if ($tipo === 'inlineStr') {
            return (string) ($cell->is->t ?? '');
        }

        return (string) ($cell->v ?? '');
    }

    /**
     * @param  array<int, mixed>  $linha
     * @return array<int, mixed>
     */
    private function preencherColunasAusentes(array $linha): array
    {
        $max = max(array_keys($linha));
        $preenchida = [];

        for ($i = 0; $i <= $max; $i++) {
            $preenchida[] = $linha[$i] ?? null;
        }

        return $preenchida;
    }

    /**
     * @param  array<int, mixed>  $cabecalhoOriginal
     * @param  array<int, array<int, mixed>>  $linhasDados
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, string>}
     */
    private function normalizarLinhasPlanilha(array $cabecalhoOriginal, array $linhasDados): array
    {
        $cabecalho = array_map(fn ($valor) => $this->normalizarCabecalho((string) $valor), $cabecalhoOriginal);
        $linhas = [];
        $erros = [];
        $codigos = [];
        $numeroLinha = 1;

        foreach ($linhasDados as $dados) {
            $numeroLinha++;

            if ($this->linhaVazia($dados)) {
                continue;
            }

            $dados = array_pad($dados, count($cabecalho), null);
            $linha = array_combine($cabecalho, array_slice($dados, 0, count($cabecalho)));

            if (! is_array($linha)) {
                $erros[] = "Linha {$numeroLinha}: quantidade de colunas inválida.";
                continue;
            }

            $codigo = $this->texto($this->valor($linha, 'codigo', 'numero_serie'));
            $descricao = $this->texto($this->valor($linha, 'nome', 'descricao', 'produto'));
            $tipoVenda = strtolower($this->texto($this->valor($linha, 'tipo_venda', 'tipo')) ?: 'unitario');
            $embalagem = $this->decimal($this->valor($linha, 'embalagem_quantidade', 'embalagem', 'quantidade_minima')) ?: 1.0;
            $preco = $this->decimal($this->valor($linha, 'preco_base_sem_impostos', 'preco_atual', 'preco'));

            if ($descricao === '') {
                $erros[] = "Linha {$numeroLinha}: nome/descrição é obrigatório.";
            }

            if (! in_array($tipoVenda, ['unitario', 'metro'], true)) {
                $erros[] = "Linha {$numeroLinha}: tipo_venda deve ser unitario ou metro.";
            }

            if ($embalagem < 1) {
                $erros[] = "Linha {$numeroLinha}: embalagem_quantidade deve ser maior ou igual a 1.";
            }

            if ($preco < 0) {
                $erros[] = "Linha {$numeroLinha}: preço não pode ser negativo.";
            }

            if ($codigo !== '') {
                if (isset($codigos[$codigo])) {
                    $erros[] = "Linha {$numeroLinha}: código {$codigo} está duplicado na planilha.";
                }

                $codigos[$codigo] = true;
            }

            $linhas[] = [
                'numero_serie' => $codigo === '' ? null : mb_strtoupper($codigo),
                'descricao' => $descricao,
                'tipo_venda' => $tipoVenda,
                'embalagem_quantidade' => $embalagem,
                'preco_atual' => $preco,
                'tributacao' => [
                    'ncm' => $this->somenteDigitos($this->valor($linha, 'ncm'), 8),
                    'cest' => $this->somenteDigitos($this->valor($linha, 'cest'), 7),
                    'cfop' => $this->somenteDigitos($this->valor($linha, 'cfop'), 4),
                    'cst_csosn' => mb_strtoupper($this->texto($this->valor($linha, 'cst_csosn', 'cst', 'csosn'))),
                    'unidade_comercial' => mb_strtoupper($this->texto($this->valor($linha, 'unidade_comercial', 'unidade')) ?: 'UN'),
                    'aliquota_icms' => $this->decimal($this->valor($linha, 'icms_percentual', 'aliquota_icms')),
                    'aliquota_ipi' => $this->decimal($this->valor($linha, 'ipi_percentual', 'aliquota_ipi')),
                    'aliquota_pis' => $this->decimal($this->valor($linha, 'pis_percentual', 'aliquota_pis')),
                    'aliquota_cofins' => $this->decimal($this->valor($linha, 'cofins_percentual', 'aliquota_cofins')),
                    'possui_st' => $this->booleano($this->valor($linha, 'possui_st', 'st')),
                    'mva' => $this->decimal($this->valor($linha, 'mva_percentual', 'mva')),
                    'observacoes_fiscais' => $this->texto($this->valor($linha, 'observacoes_fiscais', 'observacoes')),
                ],
            ];
        }

        return [$linhas, $erros];
    }

    private function cabecalhoCsv(): array
    {
        return [
            'codigo',
            'nome',
            'tipo_venda',
            'embalagem_quantidade',
            'preco_base_sem_impostos',
            'ncm',
            'cest',
            'cfop',
            'cst_csosn',
            'unidade_comercial',
            'icms_percentual',
            'ipi_percentual',
            'pis_percentual',
            'cofins_percentual',
            'possui_st',
            'mva_percentual',
            'observacoes_fiscais',
        ];
    }

    private function baixarCsv(string $nomeArquivo, callable $callback): StreamedResponse
    {
        return response()->streamDownload(function () use ($callback): void {
            $saida = fopen('php://output', 'wb');
            fwrite($saida, "\xEF\xBB\xBF");
            $callback($saida);
            fclose($saida);
        }, $nomeArquivo, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function escreverLinhaCsv($saida, array $linha): void
    {
        fputcsv($saida, $linha, ';');
    }

    private function normalizarCabecalho(string $valor): string
    {
        $valor = $this->utf8($valor);
        $valor = preg_replace('/^\xEF\xBB\xBF/', '', $valor) ?? $valor;
        $valor = Str::ascii(Str::lower(trim($valor)));

        return trim((string) preg_replace('/[^a-z0-9]+/', '_', $valor), '_');
    }

    private function valor(array $linha, string ...$chaves): mixed
    {
        foreach ($chaves as $chave) {
            if (array_key_exists($chave, $linha)) {
                return $linha[$chave];
            }
        }

        return null;
    }

    private function texto(mixed $valor): string
    {
        return trim($this->utf8((string) $valor));
    }

    private function utf8(string $valor): string
    {
        if ($valor === '') {
            return '';
        }

        if (mb_check_encoding($valor, 'UTF-8')) {
            return $valor;
        }

        $convertido = @mb_convert_encoding($valor, 'UTF-8', 'Windows-1252');

        if (is_string($convertido) && mb_check_encoding($convertido, 'UTF-8')) {
            return $convertido;
        }

        return iconv('Windows-1252', 'UTF-8//IGNORE', $valor) ?: '';
    }

    private function decimal(mixed $valor): float
    {
        $valor = trim((string) $valor);

        if ($valor === '') {
            return 0.0;
        }

        if (str_contains($valor, ',')) {
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
        }

        return (float) preg_replace('/[^0-9\.\-]/', '', $valor);
    }

    private function decimalCsv(mixed $valor): string
    {
        return number_format((float) $valor, 2, ',', '');
    }

    private function booleano(mixed $valor): bool
    {
        return in_array(Str::lower(trim((string) $valor)), ['1', 's', 'sim', 'true', 'yes'], true);
    }

    private function somenteDigitos(mixed $valor, int $tamanho): ?string
    {
        $digitos = preg_replace('/\D/', '', (string) $valor);

        return strlen((string) $digitos) === $tamanho ? $digitos : null;
    }

    private function linhaVazia(array $linha): bool
    {
        return collect($linha)->every(fn ($valor) => trim((string) $valor) === '');
    }
}

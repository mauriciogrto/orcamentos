<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Empresa\UsuarioController as EmpresaUsuarioController;
use App\Http\Controllers\Orcamentos\ClienteController;
use App\Http\Controllers\Orcamentos\ComissaoRelatorioPdfController;
use App\Http\Controllers\Orcamentos\FaixaComissaoController;
use App\Http\Controllers\Orcamentos\ItemController;
use App\Http\Controllers\Orcamentos\OrcamentoController;
use App\Http\Controllers\Orcamentos\OrcamentoPdfController;
use App\Http\Controllers\Orcamentos\PedidoController;
use App\Http\Controllers\Orcamentos\PedidoPdfController;
use App\Http\Controllers\Orcamentos\RepresentacaoConfiguracaoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SuperAdmin\BrasilApiCepController;
use App\Http\Controllers\SuperAdmin\BrasilApiCnpjController;
use App\Http\Controllers\SuperAdmin\EmpresaController;
use App\Http\Controllers\SuperAdmin\EmpresaModuloController;
use App\Http\Controllers\SuperAdmin\EmpresaUserController;
use App\Http\Controllers\SuperAdmin\UserImpersonationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::get('/orcamentos', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/impersonation/stop', [UserImpersonationController::class, 'stop'])->name('impersonation.stop');
});

Route::middleware(['auth', 'role:admin_empresa'])
    ->prefix('empresa')
    ->name('empresa.')
    ->group(function () {
        Route::resource('usuarios', EmpresaUsuarioController::class)->except(['show']);
    });

Route::middleware(['auth', 'role:super_admin'])
    ->prefix('super-admin')
    ->name('super-admin.')
    ->group(function () {
        Route::get('cnpj/{cnpj}', BrasilApiCnpjController::class)->name('cnpj.buscar');
        Route::get('cep/{cep}', BrasilApiCepController::class)->name('cep.buscar');

        Route::resource('empresas', EmpresaController::class)
            ->except(['show', 'destroy']);
        Route::patch('empresas/{empresa}/desativar', [EmpresaController::class, 'deactivate'])
            ->name('empresas.desativar');

        Route::get('empresas/{empresa}/modulos', [EmpresaModuloController::class, 'edit'])
            ->name('empresas.modulos.edit');
        Route::put('empresas/{empresa}/modulos', [EmpresaModuloController::class, 'update'])
            ->name('empresas.modulos.update');

        Route::get('empresas/{empresa}/usuarios', [EmpresaUserController::class, 'index'])
            ->name('empresas.usuarios.index');
        Route::get('empresas/{empresa}/usuarios/create', [EmpresaUserController::class, 'create'])
            ->name('empresas.usuarios.create');
        Route::post('empresas/{empresa}/usuarios', [EmpresaUserController::class, 'store'])
            ->name('empresas.usuarios.store');
        Route::get('empresas/{empresa}/usuarios/{usuario}/edit', [EmpresaUserController::class, 'edit'])
            ->name('empresas.usuarios.edit');
        Route::put('empresas/{empresa}/usuarios/{usuario}', [EmpresaUserController::class, 'update'])
            ->name('empresas.usuarios.update');
        Route::delete('empresas/{empresa}/usuarios/{usuario}', [EmpresaUserController::class, 'destroy'])
            ->name('empresas.usuarios.destroy');
        Route::post('empresas/{empresa}/usuarios/{usuario}/acessar-como', [UserImpersonationController::class, 'start'])
            ->name('empresas.usuarios.impersonate');
    });

Route::middleware(['auth', 'modulo:orcamentos'])
    ->prefix('orcamentos')
    ->name('orcamentos.')
    ->group(function () {
        Route::get('cnpj/{cnpj}', BrasilApiCnpjController::class)->name('cnpj.buscar');
        Route::get('cep/{cep}', BrasilApiCepController::class)->name('cep.buscar');
        Route::resource('clientes', ClienteController::class);
        Route::get('itens/modelo-importacao', [ItemController::class, 'downloadModelo'])->name('itens.modelo');
        Route::post('itens/tabelas', [ItemController::class, 'storeTabela'])->name('itens.store-tabela');
        Route::post('itens/importar-tabela', [ItemController::class, 'importarTabela'])->name('itens.importar-tabela');
        Route::get('itens/tabelas/{tabela}/exportar', [ItemController::class, 'exportarTabela'])->name('itens.exportar-tabela');
        Route::patch('itens/tabelas/{tabela}/status', [ItemController::class, 'toggleTabelaAtiva'])->name('itens.toggle-tabela-ativa');
        Route::delete('itens/tabelas/{tabela}', [ItemController::class, 'destroyTabela'])->name('itens.destroy-tabela');
        Route::resource('itens', ItemController::class)->parameters(['itens' => 'item']);
        Route::get('pedidos/{pedido}/pdf', PedidoPdfController::class)->name('pedidos.pdf');
        Route::resource('pedidos', PedidoController::class)->only(['index', 'show']);
        Route::post('orcamentos/{orcamento}/enviar', [OrcamentoController::class, 'send'])->name('orcamentos.send');
        Route::post('orcamentos/{orcamento}/aprovar', [OrcamentoController::class, 'approve'])->name('orcamentos.approve');
        Route::post('orcamentos/{orcamento}/rejeitar', [OrcamentoController::class, 'reject'])->name('orcamentos.reject');
        Route::post('orcamentos/{orcamento}/finalizar', [OrcamentoController::class, 'finish'])->name('orcamentos.finish');
        Route::post('orcamentos/{orcamento}/virar-pedido', [OrcamentoController::class, 'convertToPedido'])->name('orcamentos.convert-to-pedido');
        Route::post('orcamentos/{orcamento}/renovar', [OrcamentoController::class, 'renew'])->name('orcamentos.renew');
        Route::post('orcamentos/{orcamento}/duplicar', [OrcamentoController::class, 'duplicate'])->name('orcamentos.duplicate');
        Route::get('orcamentos/buscar-itens', [OrcamentoController::class, 'buscarItens'])->name('orcamentos.buscar-itens');
        Route::get('orcamentos/{orcamento}/pdf', OrcamentoPdfController::class)->name('orcamentos.pdf');
        Route::resource('orcamentos', OrcamentoController::class);

        Route::middleware('role:admin_empresa|super_admin')->group(function () {
            Route::get('representacao', [FaixaComissaoController::class, 'index'])
                ->name('representacao.index');
            Route::get('representacao/faixas', [FaixaComissaoController::class, 'faixas'])
                ->name('representacao.faixas');
            Route::get('representacao/frete', [FaixaComissaoController::class, 'frete'])
                ->name('representacao.frete');
            Route::get('representacao/relatorios-comissao', [FaixaComissaoController::class, 'relatorios'])
                ->name('representacao.relatorios');
            Route::patch('representacao/configuracao', [RepresentacaoConfiguracaoController::class, 'update'])
                ->name('representacao.configuracao.update');
            Route::patch('representacao/configuracao/clientes-frete', [RepresentacaoConfiguracaoController::class, 'updateClienteFrete'])
                ->name('representacao.configuracao.cliente-frete.update');
            Route::delete('representacao/configuracao/clientes-frete/{cliente}', [RepresentacaoConfiguracaoController::class, 'destroyClienteFrete'])
                ->name('representacao.configuracao.cliente-frete.destroy');
            Route::get('representacao/comissoes/pdf', ComissaoRelatorioPdfController::class)
                ->name('representacao.comissoes.pdf');
            Route::resource('representacao', FaixaComissaoController::class)
                ->except(['index', 'show'])
                ->parameters(['representacao' => 'faixa']);
        });
    });

require __DIR__.'/auth.php';

if (app()->environment(['local', 'testing'])) {
    Route::middleware(['auth', 'role:admin_empresa'])
        ->get('/_teste/papeis/admin-empresa', fn () => response()->json(['autorizado' => true]));

    Route::middleware(['auth', 'permission:empresas.gerenciar'])
        ->get('/_teste/permissoes/gerenciar-empresas', fn () => response()->json(['autorizado' => true]));

    Route::middleware(['auth', 'permission:clientes.visualizar_todos'])
        ->get('/_teste/permissoes/visualizar-todos-clientes', fn () => response()->json(['autorizado' => true]));
}

<?php

namespace Tests\Feature\SuperAdmin;

use App\Enums\FormaPagamento;
use App\Enums\FreteTipo;
use App\Enums\OrcamentoStatus;
use App\Enums\RoleName;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\FaixaComissao;
use App\Models\Item;
use App\Models\Modulo;
use App\Models\Orcamento;
use App\Models\Pedido;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_operational_records_from_all_companies(): void
    {
        $this->seed(DatabaseSeeder::class);

        [$empresaA, $orcamentoA, $pedidoA] = $this->empresaComDados('Empresa A', 'Cliente A', 'Produto A', 1);
        [$empresaB, $orcamentoB, $pedidoB] = $this->empresaComDados('Empresa B', 'Cliente B', 'Produto B', 2);

        $superAdmin = User::factory()->create(['empresa_id' => null]);
        $superAdmin->assignRole(RoleName::SuperAdmin->value);

        $this->actingAs($superAdmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Empresas')
            ->assertSee('Clientes')
            ->assertSee('Itens')
            ->assertSee('Orçamentos')
            ->assertSee('Pedidos')
            ->assertSee('Representação');

        $this->actingAs($superAdmin)
            ->get(route('orcamentos.clientes.index'))
            ->assertOk()
            ->assertSee('Empresa A')
            ->assertSee('Empresa B')
            ->assertSee('Cliente A')
            ->assertSee('Cliente B')
            ->assertDontSee('Novo cliente');

        $this->actingAs($superAdmin)
            ->get(route('orcamentos.itens.index'))
            ->assertOk()
            ->assertSee('Produto A')
            ->assertSee('Produto B')
            ->assertDontSee('Novo item');

        $this->actingAs($superAdmin)
            ->get(route('orcamentos.orcamentos.index'))
            ->assertOk()
            ->assertSee('2026-001')
            ->assertSee('2026-002')
            ->assertDontSee('Novo orçamento')
            ->assertDontSee('Virar pedido');

        $this->actingAs($superAdmin)
            ->get(route('orcamentos.pedidos.index'))
            ->assertOk()
            ->assertSee('2026-001')
            ->assertSee('2026-002');

        $this->actingAs($superAdmin)
            ->get(route('orcamentos.representacao.index'))
            ->assertOk()
            ->assertSee('Configurações de Frete')
            ->assertSee('Relatórios de Comissão')
            ->assertSee('Faixas de Comissão')
            ->assertDontSee('Nova faixa');

        $this->actingAs($superAdmin)
            ->get(route('orcamentos.representacao.faixas'))
            ->assertOk()
            ->assertSee('Empresa A')
            ->assertSee('Empresa B')
            ->assertSee('Visualização global')
            ->assertDontSee('Nova faixa');
    }

    public function test_super_admin_cannot_change_company_representation_from_global_view(): void
    {
        $this->seed(DatabaseSeeder::class);
        [$empresa] = $this->empresaComDados('Empresa A', 'Cliente A', 'Produto A', 1);
        $faixa = FaixaComissao::withoutGlobalScopes()->where('empresa_id', $empresa->id)->firstOrFail();

        $superAdmin = User::factory()->create(['empresa_id' => null]);
        $superAdmin->assignRole(RoleName::SuperAdmin->value);

        $this->actingAs($superAdmin)
            ->get(route('orcamentos.representacao.edit', $faixa))
            ->assertForbidden();
    }

    /**
     * @return array{0: Empresa, 1: Orcamento, 2: Pedido}
     */
    private function empresaComDados(string $empresaNome, string $clienteNome, string $itemNome, int $sequencial): array
    {
        $empresa = Empresa::factory()->create([
            'razao_social' => $empresaNome,
            'nome_fantasia' => $empresaNome,
        ]);
        $empresa->modulos()->attach(
            Modulo::query()->where('slug', 'orcamentos')->firstOrFail(),
            ['ativo' => true]
        );

        $vendedor = User::factory()->create(['empresa_id' => $empresa->id, 'name' => 'Vendedor '.$sequencial]);
        $vendedor->assignRole(RoleName::Vendedor->value);

        $cliente = Cliente::create([
            'empresa_id' => $empresa->id,
            'user_id' => $vendedor->id,
            'nome_razao_social' => $clienteNome,
            'nome_fantasia' => $clienteNome,
            'cnpj' => str_pad((string) $sequencial, 14, '0', STR_PAD_LEFT),
        ]);

        Item::create([
            'empresa_id' => $empresa->id,
            'descricao' => $itemNome,
            'preco_atual' => 100,
        ]);

        FaixaComissao::create([
            'empresa_id' => $empresa->id,
            'desconto_inicial_percentual' => 0,
            'desconto_final_percentual' => 100,
            'comissao_percentual' => 5,
        ]);

        $orcamento = Orcamento::create([
            'empresa_id' => $empresa->id,
            'user_id' => $vendedor->id,
            'cliente_id' => $cliente->id,
            'ano' => 2026,
            'sequencial' => $sequencial,
            'numero' => sprintf('ORC-2026-%03d', $sequencial),
            'status' => OrcamentoStatus::Finalizado,
            'data_emissao' => '2026-07-01',
            'hora_emissao' => '10:00:00',
            'forma_pagamento' => FormaPagamento::Pix,
            'validade_dias' => 15,
            'frete_tipo' => FreteTipo::Cif,
            'subtotal_sem_desconto' => 100,
            'desconto_efetivo_percentual' => 0,
            'total_produtos' => 100,
            'total_impostos' => 0,
            'valor_total' => 100,
            'comissao_percentual' => 5,
            'valor_comissao' => 5,
        ]);

        $pedido = Pedido::create([
            'empresa_id' => $empresa->id,
            'orcamento_id' => $orcamento->id,
            'user_id' => $vendedor->id,
            'cliente_id' => $cliente->id,
            'ano' => 2026,
            'sequencial' => $sequencial,
            'numero' => sprintf('PED-2026-%03d', $sequencial),
            'status' => 'aberto',
            'data_pedido' => '2026-07-01',
            'hora_pedido' => '10:30:00',
            'subtotal_sem_desconto' => 100,
            'desconto_efetivo_percentual' => 0,
            'total_produtos' => 100,
            'total_impostos' => 0,
            'valor_total' => 100,
        ]);

        return [$empresa, $orcamento, $pedido];
    }
}

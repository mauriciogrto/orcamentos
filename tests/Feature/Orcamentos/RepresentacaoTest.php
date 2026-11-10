<?php

namespace Tests\Feature\Orcamentos;

use App\Enums\FormaPagamento;
use App\Enums\FreteTipo;
use App\Enums\OrcamentoStatus;
use App\Enums\RoleName;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\FaixaComissao;
use App\Models\Modulo;
use App\Models\Orcamento;
use App\Models\Pedido;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepresentacaoTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $admin;

    private User $supervisor;

    private User $vendedor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->empresa = Empresa::factory()->create();
        $modulo = Modulo::query()->where('slug', 'orcamentos')->firstOrFail();
        $this->empresa->modulos()->attach($modulo, ['ativo' => true]);

        $this->admin = $this->usuario(RoleName::AdminEmpresa);
        $this->supervisor = $this->usuario(RoleName::Supervisor);
        $this->vendedor = $this->usuario(RoleName::Vendedor);
    }

    public function test_only_admin_empresa_can_access_representacao_routes(): void
    {
        $this->actingAs($this->admin)
            ->get(route('orcamentos.representacao.index'))
            ->assertOk()
            ->assertSee('Configurações de Frete')
            ->assertSee('Relatórios de Comissão')
            ->assertSee('Faixas de Comissão');

        $this->actingAs($this->admin)
            ->get(route('orcamentos.representacao.frete'))
            ->assertOk()
            ->assertSee('Valor mínimo do pedido');

        $this->actingAs($this->admin)
            ->get(route('orcamentos.representacao.relatorios'))
            ->assertOk()
            ->assertSee('Aplicar filtro')
            ->assertSee('Gerar PDF');

        $this->actingAs($this->admin)
            ->get(route('orcamentos.representacao.faixas'))
            ->assertOk()
            ->assertSee('Faixas de Comissão');

        $this->actingAs($this->supervisor)
            ->get(route('orcamentos.representacao.index'))
            ->assertForbidden();

        $this->actingAs($this->vendedor)
            ->get(route('orcamentos.representacao.index'))
            ->assertForbidden();

        $this->actingAs($this->vendedor)
            ->get(route('orcamentos.representacao.create'))
            ->assertForbidden();
    }

    public function test_link_is_only_visible_to_admin_empresa(): void
    {
        $this->actingAs($this->admin)->get(route('dashboard'))->assertSee('Representação');
        $this->actingAs($this->supervisor)->get(route('dashboard'))->assertDontSee('Representação');
        $this->actingAs($this->vendedor)->get(route('dashboard'))->assertDontSee('Representação');
    }

    public function test_admin_can_create_update_and_delete_a_commission_rate(): void
    {
        $this->actingAs($this->admin)
            ->post(route('orcamentos.representacao.store'), [
                'desconto_inicial_percentual' => '10.00',
                'desconto_final_percentual' => '50.00',
                'comissao_percentual' => '7.50',
            ])
            ->assertRedirect(route('orcamentos.representacao.faixas'));

        $faixa = FaixaComissao::query()->firstOrFail();
        $this->assertSame($this->empresa->id, $faixa->empresa_id);
        $this->assertSame('10.00', $faixa->desconto_inicial_percentual);
        $this->assertSame('50.00', $faixa->desconto_final_percentual);
        $this->assertSame('10.00', $faixa->desconto_percentual);
        $this->assertSame('7.50', $faixa->comissao_percentual);

        $this->actingAs($this->admin)
            ->put(route('orcamentos.representacao.update', $faixa), [
                'desconto_inicial_percentual' => '10.00',
                'desconto_final_percentual' => '40.00',
                'comissao_percentual' => '8.00',
            ])
            ->assertRedirect(route('orcamentos.representacao.faixas'));

        $this->assertSame('40.00', $faixa->fresh()->desconto_final_percentual);
        $this->assertSame('8.00', $faixa->fresh()->comissao_percentual);

        $this->actingAs($this->admin)
            ->delete(route('orcamentos.representacao.destroy', $faixa))
            ->assertSessionHasErrors('confirmacao');

        $this->actingAs($this->admin)
            ->delete(route('orcamentos.representacao.destroy', $faixa), ['confirmacao' => '1'])
            ->assertRedirect(route('orcamentos.representacao.faixas'));

        $this->assertDatabaseMissing('faixas_comissao', ['id' => $faixa->id]);
    }

    public function test_admin_can_configure_minimum_order_and_automatic_freight_limit(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('orcamentos.representacao.configuracao.update'), [
                'pedido_minimo' => '1000.00',
                'limite_frete_fob' => '2000.00',
            ])
            ->assertRedirect(route('orcamentos.representacao.frete'));

        $empresa = $this->empresa->fresh();
        $this->assertSame('1000.00', $empresa->pedido_minimo);
        $this->assertSame('2000.00', $empresa->limite_frete_fob);

        $this->actingAs($this->supervisor)
            ->patch(route('orcamentos.representacao.configuracao.update'), [
                'pedido_minimo' => '500.00',
                'limite_frete_fob' => '1500.00',
            ])
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->patch(route('orcamentos.representacao.configuracao.update'), [
                'pedido_minimo' => '3000.00',
                'limite_frete_fob' => '2000.00',
            ])
            ->assertSessionHasErrors('limite_frete_fob');
    }

    public function test_discount_ranges_cannot_overlap_within_each_company(): void
    {
        $this->actingAs($this->admin)->post(route('orcamentos.representacao.store'), [
            'desconto_inicial_percentual' => '10.00',
            'desconto_final_percentual' => '50.00',
            'comissao_percentual' => '5.00',
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->admin)->post(route('orcamentos.representacao.store'), [
            'desconto_inicial_percentual' => '40.00',
            'desconto_final_percentual' => '60.00',
            'comissao_percentual' => '3.00',
        ])->assertSessionHasErrors('desconto_inicial_percentual');

        $outraEmpresa = Empresa::factory()->create();
        $outraEmpresa->modulos()->attach(
            Modulo::query()->where('slug', 'orcamentos')->firstOrFail(),
            ['ativo' => true],
        );
        $outroAdmin = User::factory()->create(['empresa_id' => $outraEmpresa->id]);
        $outroAdmin->assignRole(RoleName::AdminEmpresa->value);

        $this->actingAs($outroAdmin)->post(route('orcamentos.representacao.store'), [
            'desconto_inicial_percentual' => '40.00',
            'desconto_final_percentual' => '60.00',
            'comissao_percentual' => '9.00',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('faixas_comissao', 2);
    }

    public function test_values_must_be_percentages_between_zero_and_one_hundred(): void
    {
        $this->actingAs($this->admin)->post(route('orcamentos.representacao.store'), [
            'desconto_percentual' => '100.01',
            'comissao_percentual' => '-1',
        ])->assertSessionHasErrors(['desconto_percentual', 'comissao_percentual']);
    }

    public function test_admin_can_generate_monthly_commission_pdf_by_seller(): void
    {
        FaixaComissao::create([
            'empresa_id' => $this->empresa->id,
            'desconto_inicial_percentual' => '0.00',
            'desconto_final_percentual' => '30.00',
            'comissao_percentual' => '5.00',
        ]);

        $this->pedido($this->vendedor, 'PED-2026-001', 'ORC-2026-001', '2026-06-15', 1000, 10, 5, 50);

        $outroVendedor = $this->usuario(RoleName::Vendedor);
        $this->pedido($outroVendedor, 'PED-2026-002', 'ORC-2026-002', '2026-06-20', 2000, 20, 4, 80);
        $this->pedido($this->vendedor, 'PED-2026-003', 'ORC-2026-003', '2026-07-01', 3000, 30, 3, 90);

        $this->actingAs($this->admin)
            ->get(route('orcamentos.representacao.relatorios', [
                'mes' => '2026-06',
                'vendedor_id' => $this->vendedor->id,
            ]))
            ->assertOk()
            ->assertSee('PED-2026-001')
            ->assertDontSee('PED-2026-002')
            ->assertSee('R$ 1.000,00')
            ->assertSee('10,00%')
            ->assertSee('5,00%')
            ->assertSee('R$ 50,00');

        $response = $this->actingAs($this->admin)
            ->get(route('orcamentos.representacao.comissoes.pdf', [
                'mes' => '2026-06',
                'vendedor_id' => $this->vendedor->id,
            ]));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload();

        $this->assertStringStartsWith('%PDF', $response->getContent());

        $this->actingAs($this->supervisor)
            ->get(route('orcamentos.representacao.comissoes.pdf', [
                'mes' => '2026-06',
                'vendedor_id' => $this->vendedor->id,
            ]))
            ->assertForbidden();
    }

    private function usuario(RoleName $role): User
    {
        $user = User::factory()->create(['empresa_id' => $this->empresa->id]);
        $user->assignRole($role->value);

        return $user;
    }

    private function pedido(
        User $vendedor,
        string $numeroPedido,
        string $numeroOrcamento,
        string $dataPedido,
        float $valorTotal,
        float $desconto,
        float $comissaoPercentual,
        float $valorComissao,
    ): Pedido {
        $cliente = Cliente::create([
            'empresa_id' => $this->empresa->id,
            'user_id' => $vendedor->id,
            'nome_razao_social' => 'Cliente '.$numeroPedido,
            'nome_fantasia' => 'Cliente',
        ]);

        $sequencial = (int) substr($numeroPedido, -3);
        $ano = (int) substr($numeroPedido, 4, 4);
        $subtotalSemDesconto = $desconto > 0 && $desconto < 100
            ? round($valorTotal / (1 - ($desconto / 100)), 2)
            : $valorTotal;

        $orcamento = Orcamento::create([
            'empresa_id' => $this->empresa->id,
            'user_id' => $vendedor->id,
            'cliente_id' => $cliente->id,
            'ano' => $ano,
            'sequencial' => $sequencial,
            'numero' => $numeroOrcamento,
            'status' => OrcamentoStatus::Finalizado,
            'data_emissao' => $dataPedido,
            'hora_emissao' => '10:00:00',
            'forma_pagamento' => FormaPagamento::Pix,
            'validade_dias' => 15,
            'frete_tipo' => FreteTipo::Cif,
            'subtotal_sem_desconto' => $subtotalSemDesconto,
            'desconto_efetivo_percentual' => $desconto,
            'total_produtos' => $valorTotal,
            'total_impostos' => 0,
            'valor_total' => $valorTotal,
            'comissao_percentual' => $comissaoPercentual,
            'valor_comissao' => $valorComissao,
        ]);

        return Pedido::create([
            'empresa_id' => $this->empresa->id,
            'orcamento_id' => $orcamento->id,
            'user_id' => $vendedor->id,
            'cliente_id' => $cliente->id,
            'ano' => $ano,
            'sequencial' => $sequencial,
            'numero' => $numeroPedido,
            'status' => 'aberto',
            'data_pedido' => $dataPedido,
            'hora_pedido' => '10:30:00',
            'subtotal_sem_desconto' => $subtotalSemDesconto,
            'desconto_efetivo_percentual' => $desconto,
            'total_produtos' => $valorTotal,
            'total_impostos' => 0,
            'valor_total' => $valorTotal,
        ]);
    }
}

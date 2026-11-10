<?php

namespace Tests\Feature\Orcamentos;

use App\Enums\FormaPagamento;
use App\Enums\FreteTipo;
use App\Enums\OrcamentoStatus;
use App\Enums\RoleName;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Item;
use App\Models\Modulo;
use App\Models\Orcamento;
use App\Models\OrcamentoItem;
use App\Models\Pedido;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PedidoTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $vendedorA;

    private User $vendedorB;

    private User $admin;

    private Cliente $clienteA;

    private Cliente $clienteB;

    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->empresa = Empresa::factory()->create();
        $this->empresa->modulos()->attach(
            Modulo::query()->where('slug', 'orcamentos')->firstOrFail(),
            ['ativo' => true],
        );

        $this->vendedorA = $this->usuario(RoleName::Vendedor);
        $this->vendedorB = $this->usuario(RoleName::Vendedor);
        $this->admin = $this->usuario(RoleName::AdminEmpresa);
        $this->clienteA = $this->cliente($this->vendedorA, 'Cliente A');
        $this->clienteB = $this->cliente($this->vendedorB, 'Cliente B');
        $this->item = Item::create([
            'empresa_id' => $this->empresa->id,
            'descricao' => 'Produto pedido',
            'preco_atual' => 100,
        ]);
    }

    public function test_budget_can_be_finished_and_locked_for_normal_editing(): void
    {
        $orcamento = $this->orcamento($this->vendedorA, $this->clienteA);

        $this->actingAs($this->vendedorA)
            ->post(route('orcamentos.orcamentos.finish', $orcamento), ['confirmacao' => '1'])
            ->assertRedirect(route('orcamentos.orcamentos.show', $orcamento));

        $this->assertSame(OrcamentoStatus::Finalizado, $orcamento->fresh()->status);

        $this->actingAs($this->vendedorA)
            ->get(route('orcamentos.orcamentos.edit', $orcamento))
            ->assertForbidden();
    }

    public function test_budget_can_be_converted_to_order_only_once(): void
    {
        $orcamento = $this->orcamento($this->vendedorA, $this->clienteA);

        $this->actingAs($this->vendedorA)
            ->post(route('orcamentos.orcamentos.convert-to-pedido', $orcamento), ['confirmacao' => '1'])
            ->assertRedirect();

        $pedido = Pedido::withoutGlobalScopes()->firstOrFail();
        $this->assertSame('PED-'.now()->year.'-001', $pedido->numero);
        $this->assertSame($orcamento->id, $pedido->orcamento_id);
        $this->assertSame($this->vendedorA->id, $pedido->user_id);
        $this->assertSame(OrcamentoStatus::Finalizado, $orcamento->fresh()->status);

        $this->actingAs($this->vendedorA)
            ->post(route('orcamentos.orcamentos.convert-to-pedido', $orcamento), ['confirmacao' => '1'])
            ->assertRedirect(route('orcamentos.pedidos.show', $pedido));

        $this->assertDatabaseCount('pedidos', 1);
    }

    public function test_budget_warns_about_updated_item_price_and_order_uses_current_price(): void
    {
        $orcamento = $this->orcamento($this->vendedorA, $this->clienteA);
        $this->item->update(['preco_atual' => 150]);

        $this->actingAs($this->vendedorA)
            ->get(route('orcamentos.orcamentos.show', $orcamento))
            ->assertOk()
            ->assertSee('Itens com preço atualizado no cadastro')
            ->assertSee('R$ 100,00')
            ->assertSee('R$ 150,00');

        $this->actingAs($this->vendedorA)
            ->post(route('orcamentos.orcamentos.convert-to-pedido', $orcamento), ['confirmacao' => '1'])
            ->assertRedirect();

        $pedido = Pedido::withoutGlobalScopes()->firstOrFail();
        $this->assertSame('150.00', $pedido->subtotal_sem_desconto);
        $this->assertSame('150.00', $pedido->total_produtos);
        $this->assertSame('150.00', $pedido->valor_total);
        $this->assertSame('150.00', $orcamento->fresh()->valor_total);
        $this->assertSame('150.00', $orcamento->itens()->firstOrFail()->valor_unitario);
    }

    public function test_budget_list_shows_convert_to_order_action_and_then_order_link(): void
    {
        $orcamento = $this->orcamento($this->vendedorA, $this->clienteA);

        $this->actingAs($this->vendedorA)
            ->get(route('orcamentos.orcamentos.index'))
            ->assertOk()
            ->assertSee($this->numeroCurto($orcamento))
            ->assertSee('Virar pedido')
            ->assertSee('Excluir')
            ->assertDontSee('Ver pedido');

        $this->actingAs($this->vendedorA)
            ->post(route('orcamentos.orcamentos.convert-to-pedido', $orcamento), ['confirmacao' => '1'])
            ->assertRedirect();

        $this->flushSession();

        $this->actingAs($this->vendedorA)
            ->get(route('orcamentos.orcamentos.index'))
            ->assertOk()
            ->assertSee($this->numeroCurto($orcamento))
            ->assertSee('Ver pedido')
            ->assertDontSee('Excluir')
            ->assertDontSee('Virar pedido');
    }

    public function test_order_pdf_is_generated_with_order_route_and_filename(): void
    {
        $orcamento = $this->orcamento($this->vendedorA, $this->clienteA);

        $this->actingAs($this->vendedorA)
            ->post(route('orcamentos.orcamentos.convert-to-pedido', $orcamento), ['confirmacao' => '1'])
            ->assertRedirect();

        $pedido = Pedido::withoutGlobalScopes()->firstOrFail();

        $this->actingAs($this->vendedorA)
            ->get(route('orcamentos.pedidos.show', $pedido))
            ->assertOk()
            ->assertSee(route('orcamentos.pedidos.pdf', $pedido));

        $pdf = $this->actingAs($this->vendedorA)
            ->get(route('orcamentos.pedidos.pdf', $pedido));

        $pdf->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload("pedido-{$pedido->numero}.pdf");

        $this->assertStringStartsWith('%PDF', $pdf->getContent());

        $this->actingAs($this->vendedorB)
            ->get(route('orcamentos.pedidos.pdf', $pedido))
            ->assertForbidden();
    }

    public function test_budget_converted_to_order_cannot_be_deleted(): void
    {
        $orcamento = $this->orcamento($this->vendedorA, $this->clienteA);

        $this->actingAs($this->vendedorA)
            ->post(route('orcamentos.orcamentos.convert-to-pedido', $orcamento), ['confirmacao' => '1'])
            ->assertRedirect();

        $this->actingAs($this->vendedorA)
            ->delete(route('orcamentos.orcamentos.destroy', $orcamento), ['confirmacao' => '1'])
            ->assertForbidden();

        $this->assertNotSoftDeleted('orcamentos', ['id' => $orcamento->id]);
    }

    public function test_order_tab_uses_same_visibility_scope_as_budget(): void
    {
        $orcamentoA = $this->orcamento($this->vendedorA, $this->clienteA);
        $orcamentoB = $this->orcamento($this->vendedorB, $this->clienteB, 2);

        $this->actingAs($this->vendedorA)
            ->post(route('orcamentos.orcamentos.convert-to-pedido', $orcamentoA), ['confirmacao' => '1'])
            ->assertSessionHasNoErrors();
        $this->actingAs($this->vendedorB)
            ->post(route('orcamentos.orcamentos.convert-to-pedido', $orcamentoB), ['confirmacao' => '1'])
            ->assertSessionHasNoErrors();

        $pedidoA = Pedido::withoutGlobalScopes()->where('orcamento_id', $orcamentoA->id)->firstOrFail();
        $pedidoB = Pedido::withoutGlobalScopes()->where('orcamento_id', $orcamentoB->id)->firstOrFail();

        $this->flushSession();

        $this->actingAs($this->vendedorA)
            ->get(route('orcamentos.pedidos.index'))
            ->assertOk()
            ->assertSee($this->numeroCurto($pedidoA))
            ->assertDontSee($this->numeroCurto($pedidoB));

        $this->actingAs($this->vendedorA)
            ->get(route('orcamentos.pedidos.show', $pedidoB))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->get(route('orcamentos.pedidos.index'))
            ->assertOk()
            ->assertSee($this->numeroCurto($pedidoA))
            ->assertSee($this->numeroCurto($pedidoB));
    }

    public function test_rejected_budget_cannot_be_converted_to_order(): void
    {
        $orcamento = $this->orcamento($this->vendedorA, $this->clienteA);
        $orcamento->updateQuietly(['status' => OrcamentoStatus::Rejeitado]);

        $this->actingAs($this->vendedorA)
            ->post(route('orcamentos.orcamentos.convert-to-pedido', $orcamento), ['confirmacao' => '1'])
            ->assertForbidden();

        $this->assertDatabaseCount('pedidos', 0);
    }

    private function usuario(RoleName $role): User
    {
        $user = User::factory()->create(['empresa_id' => $this->empresa->id]);
        $user->assignRole($role->value);

        return $user;
    }

    private function cliente(User $user, string $nome): Cliente
    {
        return Cliente::create([
            'empresa_id' => $user->empresa_id,
            'user_id' => $user->id,
            'nome_razao_social' => $nome,
        ]);
    }

    private function orcamento(User $user, Cliente $cliente, int $sequencial = 1): Orcamento
    {
        $orcamento = Orcamento::create([
            'empresa_id' => $this->empresa->id,
            'user_id' => $user->id,
            'cliente_id' => $cliente->id,
            'ano' => now()->year,
            'sequencial' => $sequencial,
            'numero' => sprintf('ORC-%d-%03d', now()->year, $sequencial),
            'status' => OrcamentoStatus::Rascunho,
            'data_emissao' => today(),
            'hora_emissao' => now()->format('H:i:s'),
            'forma_pagamento' => FormaPagamento::Pix,
            'validade_dias' => 15,
            'frete_tipo' => FreteTipo::Cif,
            'subtotal_sem_desconto' => 100,
            'desconto_efetivo_percentual' => 0,
            'total_produtos' => 100,
            'total_impostos' => 0,
            'valor_total' => 100,
            'comissao_percentual' => 0,
            'valor_comissao' => 0,
        ]);

        OrcamentoItem::create([
            'empresa_id' => $this->empresa->id,
            'orcamento_id' => $orcamento->id,
            'item_id' => $this->item->id,
            'descricao' => $this->item->descricao,
            'tipo_venda' => 'unitario',
            'embalagem_quantidade' => 1,
            'quantidade' => 1,
            'valor_unitario' => 100,
            'desconto_percentual' => 0,
            'aliquota_icms' => 0,
            'aliquota_ipi' => 0,
            'aliquota_pis' => 0,
            'aliquota_cofins' => 0,
            'possui_st' => false,
            'valor_produtos' => 100,
            'valor_icms' => 0,
            'valor_ipi' => 0,
            'valor_pis' => 0,
            'valor_cofins' => 0,
            'base_calculo_st' => 0,
            'valor_icms_st' => 0,
            'valor_impostos' => 0,
            'valor_total' => 100,
        ]);

        return $orcamento;
    }

    private function numeroCurto(Orcamento|Pedido $record): string
    {
        return preg_replace('/^(ORC|PED)-/', '', $record->numero);
    }
}

<?php

namespace Tests\Feature\Orcamentos;

use App\Enums\OrcamentoStatus;
use App\Enums\RoleName;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\FaixaComissao;
use App\Models\Item;
use App\Models\Modulo;
use App\Models\Orcamento;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class OrcamentoTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $vendedorA;

    private User $vendedorB;

    private User $admin;

    private User $supervisor;

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
        $this->supervisor = $this->usuario(RoleName::Supervisor);
        $this->clienteA = $this->cliente($this->vendedorA, 'Cliente A');
        $this->clienteB = $this->cliente($this->vendedorB, 'Cliente B');
        $this->item = Item::create([
            'empresa_id' => $this->empresa->id,
            'descricao' => 'Equipamento',
            'numero_serie' => 'EQ-1',
            'preco_atual' => 100,
        ]);
        FaixaComissao::create([
            'empresa_id' => $this->empresa->id,
            'desconto_percentual' => 60,
            'comissao_percentual' => 3,
        ]);
    }

    public function test_numbers_are_sequential_per_company_and_generated_on_save(): void
    {
        $this->actingAs($this->vendedorA)
            ->get(route('orcamentos.orcamentos.create'))
            ->assertOk()
            ->assertDontSee('ORC-'.now()->year.'-001');

        $this->createBudget($this->vendedorA, $this->clienteA);
        $this->createBudget($this->vendedorA, $this->clienteA);

        $this->assertSame([
            'ORC-'.now()->year.'-001',
            'ORC-'.now()->year.'-002',
        ], Orcamento::withoutGlobalScopes()->where('empresa_id', $this->empresa->id)->orderBy('id')->pluck('numero')->all());

        [$outraEmpresa, $outroVendedor, $outroCliente, $outroItem] = $this->otherTenant();
        $this->createBudget($outroVendedor, $outroCliente, $outroItem);

        $this->assertDatabaseHas('orcamentos', [
            'empresa_id' => $outraEmpresa->id,
            'numero' => 'ORC-'.now()->year.'-001',
        ]);
    }

    public function test_commission_uses_effective_budget_discount_and_final_total(): void
    {
        $this->createBudget($this->vendedorA, $this->clienteA, discount: '60.00');
        $orcamento = Orcamento::withoutGlobalScopes()->firstOrFail();

        $this->assertSame('100.00', $orcamento->subtotal_sem_desconto);
        $this->assertSame('60.00', $orcamento->desconto_efetivo_percentual);
        $this->assertSame('40.00', $orcamento->valor_total);
        $this->assertSame('3.00', $orcamento->comissao_percentual);
        $this->assertSame('1.20', $orcamento->valor_comissao);

        $this->actingAs($this->vendedorA)
            ->get(route('orcamentos.orcamentos.show', $orcamento))
            ->assertOk()
            ->assertSee('60,00%')
            ->assertDontSee('Comissão')
            ->assertDontSee('3,00%')
            ->assertDontSee('R$ 1,20');

        $this->actingAs($this->vendedorA)
            ->get(route('orcamentos.orcamentos.edit', $orcamento))
            ->assertOk()
            ->assertDontSee('Comissão')
            ->assertDontSee('comissao_percentual');

        $this->actingAs($this->supervisor)
            ->get(route('orcamentos.orcamentos.show', $orcamento))
            ->assertOk()
            ->assertDontSee('Comissão')
            ->assertDontSee('3,00%')
            ->assertDontSee('R$ 1,20');

        $this->actingAs($this->admin)
            ->get(route('orcamentos.orcamentos.show', $orcamento))
            ->assertOk()
            ->assertSee('Comissão')
            ->assertSee('3,00%')
            ->assertSee('R$ 1,20');
    }

    public function test_different_item_discounts_produce_one_weighted_budget_commission(): void
    {
        $segundoItem = Item::create([
            'empresa_id' => $this->empresa->id,
            'descricao' => 'Segundo equipamento',
            'preco_atual' => 100,
        ]);
        FaixaComissao::create([
            'empresa_id' => $this->empresa->id,
            'desconto_inicial_percentual' => 10,
            'desconto_final_percentual' => 50,
            'comissao_percentual' => 5,
        ]);
        $payload = $this->payload($this->clienteA, $this->item, '20.00');
        $payload['itens'][] = [
            'item_id' => $segundoItem->id,
            'quantidade' => 1,
            'desconto_percentual' => '60.00',
        ];

        $this->actingAs($this->vendedorA)
            ->post(route('orcamentos.orcamentos.store'), $payload)
            ->assertSessionHasNoErrors();

        $orcamento = Orcamento::withoutGlobalScopes()->firstOrFail();
        $this->assertSame('200.00', $orcamento->subtotal_sem_desconto);
        $this->assertSame('120.00', $orcamento->valor_total);
        $this->assertSame('40.00', $orcamento->desconto_efetivo_percentual);
        $this->assertSame('5.00', $orcamento->comissao_percentual);
        $this->assertSame('6.00', $orcamento->valor_comissao);
    }

    public function test_minimum_order_only_warns_and_freight_is_calculated_automatically(): void
    {
        $this->empresa->update([
            'pedido_minimo' => '1000.00',
            'limite_frete_fob' => '2000.00',
        ]);

        $this->actingAs($this->vendedorA)
            ->get(route('orcamentos.orcamentos.create'))
            ->assertOk()
            ->assertSee('Pedido abaixo do valor mínimo')
            ->assertSee('Frete automático');

        $abaixoDoLimite = $this->payload($this->clienteA, $this->item);
        $abaixoDoLimite['frete_tipo'] = 'cif';
        $this->actingAs($this->vendedorA)
            ->post(route('orcamentos.orcamentos.store'), $abaixoDoLimite)
            ->assertSessionHasNoErrors();

        $this->assertSame('fob', Orcamento::withoutGlobalScopes()->firstOrFail()->frete_tipo->value);

        $acimaDoLimite = $this->payload($this->clienteA, $this->item);
        $acimaDoLimite['itens'][0]['quantidade'] = 21;
        $acimaDoLimite['frete_tipo'] = 'fob';
        $this->actingAs($this->vendedorA)
            ->post(route('orcamentos.orcamentos.store'), $acimaDoLimite)
            ->assertSessionHasNoErrors();

        $this->assertSame('cif', Orcamento::withoutGlobalScopes()->latest('id')->firstOrFail()->frete_tipo->value);
    }

    public function test_discount_applies_only_to_product_and_taxes_are_snapshotted_in_budget(): void
    {
        $this->item->update(['preco_atual' => '10.00']);
        $this->item->tributacao()->create([
            'empresa_id' => $this->empresa->id,
            'ncm' => '85362000',
            'cest' => '1200100',
            'aliquota_icms' => '18.0000',
            'aliquota_ipi' => '5.0000',
            'aliquota_pis' => '1.6500',
            'aliquota_cofins' => '7.6000',
            'possui_st' => true,
            'mva' => '40.0000',
            'observacoes_fiscais' => 'Regra fiscal de teste',
        ]);
        FaixaComissao::create([
            'empresa_id' => $this->empresa->id,
            'desconto_percentual' => '10.00',
            'comissao_percentual' => '5.00',
        ]);

        $this->createBudget($this->vendedorA, $this->clienteA, discount: '10.00');

        $orcamento = Orcamento::withoutGlobalScopes()->with('itens')->firstOrFail();
        $linha = $orcamento->itens->first();
        $this->assertSame('9.00', $linha->valor_produtos);
        $this->assertSame('1.62', $linha->valor_icms);
        $this->assertSame('0.45', $linha->valor_ipi);
        $this->assertSame('0.15', $linha->valor_pis);
        $this->assertSame('0.68', $linha->valor_cofins);
        $this->assertSame('12.60', $linha->base_calculo_st);
        $this->assertSame('0.65', $linha->valor_icms_st);
        $this->assertSame('3.55', $linha->valor_impostos);
        $this->assertSame('12.55', $linha->valor_total);
        $this->assertSame('9.00', $orcamento->total_produtos);
        $this->assertSame('3.55', $orcamento->total_impostos);
        $this->assertSame('12.55', $orcamento->valor_total);
        $this->assertSame('0.45', $orcamento->valor_comissao);

        $this->item->tributacao->update(['aliquota_icms' => '20.0000']);
        $this->assertSame('18.0000', $linha->fresh()->aliquota_icms);
        $this->assertSame('12.55', $linha->fresh()->valor_total);
    }

    public function test_backend_rejects_another_sellers_client(): void
    {
        $this->actingAs($this->vendedorA)
            ->post(route('orcamentos.orcamentos.store'), $this->payload($this->clienteB, $this->item))
            ->assertSessionHasErrors('cliente_id');

        $this->assertDatabaseCount('orcamentos', 0);
    }

    public function test_manager_can_create_budget_for_a_company_client(): void
    {
        $this->actingAs($this->admin)
            ->get(route('orcamentos.orcamentos.create'))
            ->assertOk()
            ->assertSee('Buscar item por código ou nome')
            ->assertSee('EQ-1')
            ->assertSee('Como deseja salvar o orçamento?')
            ->assertSee('Salvar em PDF')
            ->assertSee('Somente salvar no sistema');

        $this->createBudget($this->admin, $this->clienteA);

        $orcamento = Orcamento::withoutGlobalScopes()->firstOrFail();
        $this->assertSame($this->vendedorA->id, $orcamento->user_id);
        $this->assertSame($this->clienteA->id, $orcamento->cliente_id);
    }

    public function test_manager_can_create_budget_as_responsible_when_company_has_no_seller(): void
    {
        $empresa = Empresa::factory()->create();
        $empresa->modulos()->attach(
            Modulo::query()->where('slug', 'orcamentos')->firstOrFail(),
            ['ativo' => true],
        );
        $admin = User::factory()->create(['empresa_id' => $empresa->id]);
        $admin->assignRole(RoleName::AdminEmpresa->value);
        $cliente = Cliente::create([
            'empresa_id' => $empresa->id,
            'user_id' => null,
            'nome_razao_social' => 'Cliente sem vendedor',
        ]);
        $item = Item::create([
            'empresa_id' => $empresa->id,
            'descricao' => 'Item sem vendedor',
            'preco_atual' => 100,
        ]);
        $payload = $this->payload($cliente, $item);
        $payload['responsavel_id'] = $admin->id;

        $this->actingAs($admin)
            ->get(route('orcamentos.orcamentos.create'))
            ->assertOk()
            ->assertSee('Rascunho local automático ativo')
            ->assertSee($admin->name);

        $this->actingAs($admin)
            ->post(route('orcamentos.orcamentos.store'), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $orcamento = Orcamento::withoutGlobalScopes()->latest('id')->firstOrFail();
        $this->assertSame($admin->id, $orcamento->user_id);
        $this->assertSame($cliente->id, $orcamento->cliente_id);
    }

    public function test_supervisor_can_create_budget_and_assign_a_seller(): void
    {
        $payload = $this->payload($this->clienteB, $this->item);
        $payload['responsavel_id'] = $this->vendedorB->id;

        $this->actingAs($this->supervisor)
            ->get(route('orcamentos.orcamentos.create'))
            ->assertOk()
            ->assertSee('Responsável pelo orçamento');

        $this->actingAs($this->supervisor)
            ->post(route('orcamentos.orcamentos.store'), $payload)
            ->assertSessionHasNoErrors();

        $orcamento = Orcamento::withoutGlobalScopes()->firstOrFail();
        $this->assertSame($this->vendedorB->id, $orcamento->user_id);
        $this->assertSame($this->clienteB->id, $orcamento->cliente_id);
    }

    public function test_manager_assigns_budget_responsible_without_changing_client_assignment(): void
    {
        $clienteSemVendedor = Cliente::create([
            'empresa_id' => $this->empresa->id,
            'user_id' => null,
            'codigo' => '77777-001',
            'nome_razao_social' => 'Cliente sem vendedor',
            'cnpj' => '77777000000001',
        ]);
        $payload = $this->payload($clienteSemVendedor, $this->item);
        $payload['responsavel_id'] = $this->vendedorB->id;

        $this->actingAs($this->admin)
            ->post(route('orcamentos.orcamentos.store'), $payload)
            ->assertSessionHasNoErrors();

        $orcamento = Orcamento::withoutGlobalScopes()->firstOrFail();
        $this->assertSame($this->vendedorB->id, $orcamento->user_id);
        $this->assertNull($clienteSemVendedor->fresh()->user_id);

        $this->actingAs($this->vendedorB)
            ->get(route('orcamentos.orcamentos.edit', $orcamento))
            ->assertOk()
            ->assertSee('Cliente sem vendedor');
        $this->actingAs($this->vendedorB)
            ->put(route('orcamentos.orcamentos.update', $orcamento), $this->payload($clienteSemVendedor, $this->item))
            ->assertSessionHasNoErrors();
    }

    public function test_save_as_pdf_persists_budget_and_opens_protected_pdf(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('logos/empresa.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
        ));
        $this->empresa->update(['logo_path' => 'logos/empresa.png']);

        $payload = $this->payload($this->clienteA, $this->item);
        $payload['acao_apos_salvar'] = 'pdf';

        $response = $this->actingAs($this->vendedorA)
            ->post(route('orcamentos.orcamentos.store'), $payload)
            ->assertSessionHasNoErrors();

        $orcamento = Orcamento::withoutGlobalScopes()->firstOrFail();
        $response->assertRedirect(route('orcamentos.orcamentos.pdf', $orcamento));

        $pdf = $this->actingAs($this->vendedorA)->get(route('orcamentos.orcamentos.pdf', $orcamento));
        $pdf->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload("orcamento-{$orcamento->numero}.pdf");
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
        $this->assertStringContainsString('/Subtype /Image', $pdf->getContent());

        $this->actingAs($this->vendedorA)
            ->get(route('orcamentos.orcamentos.show', $orcamento))
            ->assertOk()
            ->assertSee('Exportar PDF');

        $this->actingAs($this->vendedorA)
            ->get(route('orcamentos.orcamentos.index'))
            ->assertOk()
            ->assertSee('PDF');

        $this->actingAs($this->vendedorB)
            ->get(route('orcamentos.orcamentos.pdf', $orcamento))
            ->assertForbidden();
    }

    public function test_selected_client_remains_saved_after_budget_update(): void
    {
        $this->createBudget($this->admin, $this->clienteA);
        $orcamento = Orcamento::withoutGlobalScopes()->firstOrFail();
        $payload = $this->payload($this->clienteB, $this->item);
        $payload['acao_apos_salvar'] = 'sistema';
        $payload['responsavel_id'] = $this->vendedorB->id;

        $this->actingAs($this->admin)
            ->put(route('orcamentos.orcamentos.update', $orcamento), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('orcamentos.orcamentos.edit', $orcamento));

        $this->assertSame($this->clienteB->id, $orcamento->fresh()->cliente_id);
        $this->actingAs($this->admin)
            ->get(route('orcamentos.orcamentos.edit', $orcamento))
            ->assertOk()
            ->assertSee($this->clienteB->nome_razao_social);
    }

    public function test_vendedor_only_sees_and_manages_own_budgets(): void
    {
        $this->createBudget($this->vendedorA, $this->clienteA);
        $orcamento = Orcamento::withoutGlobalScopes()->firstOrFail();
        $this->flushSession();

        $this->actingAs($this->vendedorB)
            ->get(route('orcamentos.orcamentos.index'))
            ->assertOk()
            ->assertDontSee($orcamento->numero);
        $this->actingAs($this->vendedorB)->get(route('orcamentos.orcamentos.show', $orcamento))->assertForbidden();
        $this->actingAs($this->vendedorB)->get(route('orcamentos.orcamentos.edit', $orcamento))->assertForbidden();

        $this->actingAs($this->admin)->get(route('orcamentos.orcamentos.show', $orcamento))->assertOk();
        $this->actingAs($this->supervisor)->get(route('orcamentos.orcamentos.show', $orcamento))->assertOk();
        $this->actingAs($this->supervisor)->get(route('orcamentos.orcamentos.edit', $orcamento))->assertOk();
    }

    public function test_budget_can_be_duplicated_as_a_new_draft(): void
    {
        $this->createBudget($this->vendedorA, $this->clienteA);
        $original = Orcamento::withoutGlobalScopes()->with('itens')->firstOrFail();

        $this->actingAs($this->vendedorA)
            ->post(route('orcamentos.orcamentos.duplicate', $original))
            ->assertRedirect();

        $duplicado = Orcamento::withoutGlobalScopes()->latest('id')->with('itens')->firstOrFail();
        $this->assertSame('ORC-'.now()->year.'-002', $duplicado->numero);
        $this->assertSame(OrcamentoStatus::Rascunho, $duplicado->status);
        $this->assertSame($original->cliente_id, $duplicado->cliente_id);
        $this->assertSame($original->valor_total, $duplicado->valor_total);
        $this->assertSame($original->itens->first()->descricao, $duplicado->itens->first()->descricao);
    }

    public function test_manual_send_and_soft_delete_require_confirmation(): void
    {
        $this->createBudget($this->vendedorA, $this->clienteA);
        $orcamento = Orcamento::withoutGlobalScopes()->firstOrFail();

        $this->actingAs($this->vendedorA)
            ->post(route('orcamentos.orcamentos.send', $orcamento))
            ->assertSessionHasErrors('confirmacao');
        $this->assertSame(OrcamentoStatus::Rascunho, $orcamento->fresh()->status);

        $this->actingAs($this->vendedorA)
            ->post(route('orcamentos.orcamentos.send', $orcamento), ['confirmacao' => '1'])
            ->assertRedirect(route('orcamentos.orcamentos.show', $orcamento));
        $this->assertSame(OrcamentoStatus::Enviado, $orcamento->fresh()->status);

        $this->actingAs($this->vendedorA)
            ->delete(route('orcamentos.orcamentos.destroy', $orcamento))
            ->assertSessionHasErrors('confirmacao');
        $this->actingAs($this->vendedorA)
            ->delete(route('orcamentos.orcamentos.destroy', $orcamento), ['confirmacao' => '1'])
            ->assertRedirect(route('orcamentos.orcamentos.index'));

        $this->assertSoftDeleted('orcamentos', ['id' => $orcamento->id]);
    }

    public function test_boleto_accepts_only_configured_terms_and_card_has_no_installments(): void
    {
        $invalid = $this->payload($this->clienteA, $this->item);
        $invalid['forma_pagamento'] = 'boleto';
        $invalid['boleto_prazos'] = '15/30';
        $this->actingAs($this->vendedorA)->post(route('orcamentos.orcamentos.store'), $invalid)->assertSessionHasErrors('boleto_prazos');

        $valid = $this->payload($this->clienteA, $this->item);
        $valid['forma_pagamento'] = 'boleto';
        $valid['boleto_prazos'] = '30/60/90/120/150/180';
        $this->actingAs($this->vendedorA)->post(route('orcamentos.orcamentos.store'), $valid)->assertSessionHasNoErrors();

        $card = $this->payload($this->clienteA, $this->item);
        $card['forma_pagamento'] = 'cartao';
        $card['boleto_prazos'] = '30';
        $this->actingAs($this->vendedorA)->post(route('orcamentos.orcamentos.store'), $card)->assertSessionHasErrors('boleto_prazos');
    }

    public function test_owner_can_approve_or_reject_sent_budget_and_terminal_status_is_locked(): void
    {
        $this->createBudget($this->vendedorA, $this->clienteA);
        $aprovado = Orcamento::withoutGlobalScopes()->firstOrFail();
        $this->sendBudget($aprovado);

        $this->actingAs($this->vendedorA)
            ->post(route('orcamentos.orcamentos.approve', $aprovado), ['confirmacao' => '1'])
            ->assertRedirect(route('orcamentos.orcamentos.show', $aprovado));
        $this->assertSame(OrcamentoStatus::Aprovado, $aprovado->fresh()->status);
        $this->actingAs($this->vendedorA)->get(route('orcamentos.orcamentos.edit', $aprovado))->assertForbidden();
        $this->actingAs($this->vendedorA)
            ->post(route('orcamentos.orcamentos.send', $aprovado), ['confirmacao' => '1'])
            ->assertSessionHasErrors('status');

        $this->createBudget($this->vendedorA, $this->clienteA);
        $rejeitado = Orcamento::withoutGlobalScopes()->latest('id')->firstOrFail();
        $this->sendBudget($rejeitado);
        $this->actingAs($this->vendedorA)
            ->post(route('orcamentos.orcamentos.reject', $rejeitado), ['confirmacao' => '1'])
            ->assertRedirect(route('orcamentos.orcamentos.show', $rejeitado));
        $this->assertSame(OrcamentoStatus::Rejeitado, $rejeitado->fresh()->status);
        $this->actingAs($this->vendedorA)->get(route('orcamentos.orcamentos.edit', $rejeitado))->assertForbidden();
    }

    public function test_supervisor_and_manager_can_operate_sellers_budget_and_action_is_logged(): void
    {
        $this->createBudget($this->vendedorA, $this->clienteA);
        $orcamento = Orcamento::withoutGlobalScopes()->firstOrFail();
        $this->sendBudget($orcamento);

        $this->actingAs($this->vendedorB)
            ->post(route('orcamentos.orcamentos.approve', $orcamento), ['confirmacao' => '1'])
            ->assertForbidden();
        $this->actingAs($this->supervisor)
            ->post(route('orcamentos.orcamentos.reject', $orcamento), ['confirmacao' => '1'])
            ->assertRedirect(route('orcamentos.orcamentos.show', $orcamento));

        $this->assertSame(OrcamentoStatus::Rejeitado, $orcamento->fresh()->status);
        $atividadeSupervisor = Activity::query()
            ->where('subject_type', Orcamento::class)
            ->where('subject_id', $orcamento->id)
            ->where('event', 'updated')
            ->latest('id')
            ->firstOrFail();
        $this->assertSame($this->supervisor->id, $atividadeSupervisor->causer_id);

        $this->actingAs($this->supervisor)
            ->delete(route('orcamentos.orcamentos.destroy', $orcamento), ['confirmacao' => '1'])
            ->assertRedirect(route('orcamentos.orcamentos.index'));
        $atividadeExclusao = Activity::query()
            ->where('subject_type', Orcamento::class)
            ->where('subject_id', $orcamento->id)
            ->where('event', 'deleted')
            ->latest('id')
            ->firstOrFail();
        $this->assertSame($this->supervisor->id, $atividadeExclusao->causer_id);

        $this->createBudget($this->vendedorA, $this->clienteA);
        $orcamentoGerente = Orcamento::withoutGlobalScopes()->latest('id')->firstOrFail();
        $this->sendBudget($orcamentoGerente);
        $this->actingAs($this->admin)
            ->post(route('orcamentos.orcamentos.approve', $orcamentoGerente), ['confirmacao' => '1'])
            ->assertRedirect(route('orcamentos.orcamentos.show', $orcamentoGerente));
        $this->assertSame(OrcamentoStatus::Aprovado, $orcamentoGerente->fresh()->status);
    }

    public function test_expiration_command_only_expires_overdue_sent_budgets_and_logs_system_actor(): void
    {
        $orcamentos = collect();
        foreach (range(1, 4) as $unused) {
            $this->createBudget($this->vendedorA, $this->clienteA);
            $orcamentos->push(Orcamento::withoutGlobalScopes()->latest('id')->firstOrFail());
        }

        $orcamentos[0]->updateQuietly(['status' => OrcamentoStatus::Enviado, 'data_emissao' => today()->subDays(20), 'validade_dias' => 15]);
        $orcamentos[1]->updateQuietly(['status' => OrcamentoStatus::Rascunho, 'data_emissao' => today()->subDays(20), 'validade_dias' => 15]);
        $orcamentos[2]->updateQuietly(['status' => OrcamentoStatus::Enviado, 'data_emissao' => today()->subDays(10), 'validade_dias' => 15]);
        $orcamentos[3]->updateQuietly(['status' => OrcamentoStatus::Aprovado, 'data_emissao' => today()->subDays(20), 'validade_dias' => 15]);

        $this->artisan('orcamentos:expirar')->expectsOutput('1 orçamento(s) expirado(s).')->assertSuccessful();

        $this->assertSame(OrcamentoStatus::Expirado, $orcamentos[0]->fresh()->status);
        $this->assertSame(OrcamentoStatus::Rascunho, $orcamentos[1]->fresh()->status);
        $this->assertSame(OrcamentoStatus::Enviado, $orcamentos[2]->fresh()->status);
        $this->assertSame(OrcamentoStatus::Aprovado, $orcamentos[3]->fresh()->status);

        $activity = Activity::query()->where('event', 'status_expirado')->latest('id')->firstOrFail();
        $this->assertNull($activity->causer_id);
        $this->assertSame('Orçamento expirado automaticamente pelo sistema', $activity->description);
        $this->assertTrue($activity->properties->get('sistema'));
    }

    public function test_expired_budget_can_be_renewed_with_same_number_and_new_emission_date(): void
    {
        $this->createBudget($this->vendedorA, $this->clienteA);
        $orcamento = Orcamento::withoutGlobalScopes()->firstOrFail();
        $numero = $orcamento->numero;
        $orcamento->updateQuietly([
            'status' => OrcamentoStatus::Enviado,
            'data_emissao' => today()->subDays(20),
            'validade_dias' => 15,
        ]);
        $this->artisan('orcamentos:expirar')->assertSuccessful();

        $this->actingAs($this->vendedorA)
            ->post(route('orcamentos.orcamentos.renew', $orcamento))
            ->assertSessionHasErrors('confirmacao');
        $this->actingAs($this->vendedorB)
            ->post(route('orcamentos.orcamentos.renew', $orcamento), ['confirmacao' => '1'])
            ->assertForbidden();
        $this->actingAs($this->vendedorA)
            ->post(route('orcamentos.orcamentos.renew', $orcamento), ['confirmacao' => '1'])
            ->assertRedirect(route('orcamentos.orcamentos.edit', $orcamento));

        $renovado = $orcamento->fresh();
        $this->assertSame(OrcamentoStatus::Rascunho, $renovado->status);
        $this->assertSame(today()->toDateString(), $renovado->data_emissao->toDateString());
        $this->assertSame($numero, $renovado->numero);
        $this->actingAs($this->vendedorA)->get(route('orcamentos.orcamentos.edit', $renovado))->assertOk();
    }

    public function test_expiration_command_is_registered_in_daily_schedule(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('orcamentos:expirar')->assertSuccessful();
    }

    private function createBudget(User $user, Cliente $cliente, ?Item $item = null, string $discount = '0.00'): void
    {
        $payload = $this->payload($cliente, $item ?? $this->item, $discount);
        if ($user->hasAnyRole([RoleName::AdminEmpresa->value, RoleName::Supervisor->value])) {
            $payload['responsavel_id'] = $this->vendedorA->id;
        }

        $this->actingAs($user)
            ->post(route('orcamentos.orcamentos.store'), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect();
    }

    private function sendBudget(Orcamento $orcamento): void
    {
        $this->actingAs($this->vendedorA)
            ->post(route('orcamentos.orcamentos.send', $orcamento), ['confirmacao' => '1'])
            ->assertSessionHasNoErrors();
    }

    private function payload(Cliente $cliente, Item $item, string $discount = '0.00'): array
    {
        return [
            'cliente_id' => $cliente->id,
            'forma_pagamento' => 'pix',
            'validade_dias' => 15,
            'observacoes' => 'Teste',
            'frete_tipo' => 'cif',
            'itens' => [[
                'item_id' => $item->id,
                'quantidade' => 1,
                'desconto_percentual' => $discount,
            ]],
        ];
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

    private function otherTenant(): array
    {
        $empresa = Empresa::factory()->create();
        $empresa->modulos()->attach(Modulo::query()->where('slug', 'orcamentos')->firstOrFail(), ['ativo' => true]);
        $vendedor = User::factory()->create(['empresa_id' => $empresa->id]);
        $vendedor->assignRole(RoleName::Vendedor->value);
        $cliente = Cliente::create(['empresa_id' => $empresa->id, 'user_id' => $vendedor->id, 'nome_razao_social' => 'Outro cliente']);
        $item = Item::create(['empresa_id' => $empresa->id, 'descricao' => 'Outro item', 'preco_atual' => 50]);

        return [$empresa, $vendedor, $cliente, $item];
    }
}

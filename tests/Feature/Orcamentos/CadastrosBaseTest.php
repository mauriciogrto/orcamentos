<?php

namespace Tests\Feature\Orcamentos;

use App\Enums\RoleName;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Item;
use App\Models\Modulo;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CadastrosBaseTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $admin;

    private User $supervisor;

    private User $vendedorA;

    private User $vendedorB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->empresa = Empresa::factory()->create();
        $modulo = Modulo::query()->where('slug', 'orcamentos')->firstOrFail();
        $this->empresa->modulos()->attach($modulo, ['ativo' => true]);

        $this->admin = $this->usuario(RoleName::AdminEmpresa);
        $this->supervisor = $this->usuario(RoleName::Supervisor);
        $this->vendedorA = $this->usuario(RoleName::Vendedor);
        $this->vendedorB = $this->usuario(RoleName::Vendedor);
    }

    public function test_vendedor_only_lists_and_edits_own_clients(): void
    {
        $this->actingAs($this->vendedorA)
            ->post(route('orcamentos.clientes.store'), [
                'nome_razao_social' => 'Cliente do vendedor A',
                'nome_fantasia' => 'Cliente A',
                'cnpj' => '19131243000197',
                'telefone' => '1133334444',
                'email' => 'cliente-a@example.com',
            ])
            ->assertRedirect(route('orcamentos.clientes.index'));

        $cliente = Cliente::withoutGlobalScopes()->firstOrFail();
        $this->assertSame($this->vendedorA->id, $cliente->user_id);
        $this->assertSame('19131-001', $cliente->codigo);

        $this->actingAs($this->vendedorA)
            ->get(route('orcamentos.clientes.index'))
            ->assertOk()
            ->assertSee('Cliente do vendedor A');

        $this->actingAs($this->vendedorB)
            ->get(route('orcamentos.clientes.index'))
            ->assertOk()
            ->assertDontSee('Cliente do vendedor A');

        $this->actingAs($this->vendedorB)
            ->get(route('orcamentos.clientes.edit', $cliente))
            ->assertForbidden();
    }

    public function test_admin_and_supervisor_manage_all_company_clients(): void
    {
        $cliente = $this->cliente($this->vendedorA, 'Cliente compartilhado');

        $this->actingAs($this->admin)
            ->put(route('orcamentos.clientes.update', $cliente), [
                'nome_razao_social' => 'Cliente transferido',
                'nome_fantasia' => 'Transferido',
                'cnpj' => '19131243000197',
                'telefone' => '',
                'email' => '',
                'user_id' => $this->vendedorB->id,
            ])
            ->assertRedirect(route('orcamentos.clientes.index'));

        $this->assertSame($this->vendedorB->id, $cliente->fresh()->user_id);

        $this->actingAs($this->supervisor)
            ->get(route('orcamentos.clientes.index'))
            ->assertOk()
            ->assertSee('Cliente transferido');

        $this->actingAs($this->supervisor)
            ->get(route('orcamentos.clientes.edit', $cliente))
            ->assertOk();

        $this->actingAs($this->supervisor)
            ->put(route('orcamentos.clientes.update', $cliente), [
                'nome_razao_social' => 'Cliente editado pelo supervisor',
                'nome_fantasia' => 'Supervisor',
                'cnpj' => '19131243000197',
                'telefone' => '',
                'email' => '',
                'user_id' => $this->vendedorA->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame($this->vendedorA->id, $cliente->fresh()->user_id);
    }

    public function test_manager_and_supervisor_can_create_unassigned_client_while_seller_is_assigned_automatically(): void
    {
        $this->actingAs($this->admin)
            ->post(route('orcamentos.clientes.store'), [
                'nome_razao_social' => 'Cliente sem vendedor',
                'nome_fantasia' => 'Sem vendedor',
                'cnpj' => '44444000000001',
                'telefone' => '',
                'email' => '',
                'cep' => '01001000',
                'logradouro' => 'Praça da Sé',
                'numero' => '100',
                'complemento' => 'Sala 1',
                'bairro' => 'Sé',
                'cidade' => 'São Paulo',
                'uf' => 'sp',
                'user_id' => '',
            ])
            ->assertSessionHasNoErrors();

        $semVendedor = Cliente::withoutGlobalScopes()->where('cnpj', '44444000000001')->firstOrFail();
        $this->assertNull($semVendedor->user_id);
        $this->assertSame('01001000', $semVendedor->cep);
        $this->assertSame('SP', $semVendedor->uf);

        $this->actingAs($this->supervisor)
            ->post(route('orcamentos.clientes.store'), [
                'nome_razao_social' => 'Cliente criado pelo supervisor',
                'nome_fantasia' => 'Super cli',
                'cnpj' => '44444000000002',
                'telefone' => '',
                'email' => '',
                'user_id' => '',
            ])
            ->assertSessionHasNoErrors();
        $this->assertNull(Cliente::withoutGlobalScopes()->where('cnpj', '44444000000002')->firstOrFail()->user_id);

        $this->actingAs($this->vendedorA)
            ->post(route('orcamentos.clientes.store'), [
                'nome_razao_social' => 'Cliente do vendedor',
                'nome_fantasia' => 'Vend cli',
                'cnpj' => '55555000000001',
                'telefone' => '',
                'email' => '',
            ])
            ->assertSessionHasNoErrors();

        $doVendedor = Cliente::withoutGlobalScopes()->where('cnpj', '55555000000001')->firstOrFail();
        $this->assertSame($this->vendedorA->id, $doVendedor->user_id);

        $this->actingAs($this->supervisor)->get(route('orcamentos.clientes.index'))->assertOk()->assertSee('Cliente sem vendedor');
        $this->actingAs($this->vendedorA)->get(route('orcamentos.clientes.index'))->assertOk()->assertDontSee('Cliente sem vendedor')->assertSee('Cliente do vendedor');
    }

    public function test_client_cep_lookup_returns_available_address_fields(): void
    {
        Http::fake([
            'brasilapi.com.br/api/cep/v2/*' => Http::response([
                'cep' => '01001000',
                'street' => 'Praça da Sé',
                'neighborhood' => 'Sé',
                'city' => 'São Paulo',
                'state' => 'SP',
            ]),
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('orcamentos.cep.buscar', '01001-000'))
            ->assertOk()
            ->assertJsonPath('data.cep', '01001000')
            ->assertJsonPath('data.logradouro', 'Praça da Sé')
            ->assertJsonPath('data.bairro', 'Sé')
            ->assertJsonPath('data.cidade', 'São Paulo')
            ->assertJsonPath('data.uf', 'SP');
    }

    public function test_client_code_uses_cnpj_prefix_sequence_is_immutable_and_not_reused(): void
    {
        foreach ([
            ['Cliente um', '12345000000001', '12345-001'],
            ['Cliente dois', '12345000000002', '12345-002'],
            ['Outro prefixo', '98765000000001', '98765-001'],
        ] as [$nome, $cnpj, $codigo]) {
            $this->actingAs($this->vendedorA)
                ->post(route('orcamentos.clientes.store'), [
                    'nome_razao_social' => $nome,
                    'nome_fantasia' => mb_substr($nome, 0, 12),
                    'cnpj' => $cnpj,
                    'telefone' => '',
                    'email' => '',
                ])
                ->assertSessionHasNoErrors();

            $this->assertDatabaseHas('clientes', ['cnpj' => $cnpj, 'codigo' => $codigo]);
        }

        $primeiro = Cliente::query()->where('codigo', '12345-001')->firstOrFail();
        $this->actingAs($this->vendedorA)
            ->put(route('orcamentos.clientes.update', $primeiro), [
                'nome_razao_social' => 'Cliente atualizado',
                'nome_fantasia' => 'Atualizado',
                'cnpj' => '55555000000001',
                'telefone' => '',
                'email' => '',
            ])
            ->assertSessionHasNoErrors();
        $this->assertSame('12345-001', $primeiro->fresh()->codigo);

        $segundo = Cliente::query()->where('codigo', '12345-002')->firstOrFail();
        $this->actingAs($this->vendedorA)
            ->delete(route('orcamentos.clientes.destroy', $segundo), ['confirmacao' => '1'])
            ->assertRedirect();

        $this->actingAs($this->vendedorA)
            ->post(route('orcamentos.clientes.store'), [
                'nome_razao_social' => 'Cliente três',
                'nome_fantasia' => 'Cliente três',
                'cnpj' => '12345000000003',
                'telefone' => '',
                'email' => '',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('clientes', ['cnpj' => '12345000000003', 'codigo' => '12345-003']);
        $this->actingAs($this->vendedorA)
            ->get(route('orcamentos.clientes.index'))
            ->assertOk()
            ->assertDontSee('12345-002')
            ->assertSee('12345-003');
    }

    public function test_client_cannot_be_created_without_cnpj(): void
    {
        $this->actingAs($this->vendedorA)
            ->post(route('orcamentos.clientes.store'), [
                'nome_razao_social' => 'Sem CNPJ',
                'nome_fantasia' => 'Sem CNPJ',
                'cnpj' => '',
                'telefone' => '',
                'email' => '',
            ])
            ->assertSessionHasErrors('cnpj');
    }

    public function test_client_cannot_be_created_without_trade_name(): void
    {
        $this->actingAs($this->vendedorA)
            ->post(route('orcamentos.clientes.store'), [
                'nome_razao_social' => 'Sem Nome Fantasia',
                'nome_fantasia' => '',
                'cnpj' => '66666000000001',
                'telefone' => '',
                'email' => '',
            ])
            ->assertSessionHasErrors('nome_fantasia');
    }

    public function test_client_trade_name_cannot_exceed_twelve_characters(): void
    {
        $this->actingAs($this->vendedorA)
            ->post(route('orcamentos.clientes.store'), [
                'nome_razao_social' => 'Nome Fantasia Longo',
                'nome_fantasia' => '1234567890123',
                'cnpj' => '77777000000001',
                'telefone' => '',
                'email' => '',
            ])
            ->assertSessionHasErrors('nome_fantasia');
    }

    public function test_client_deletion_requires_confirmation_and_uses_soft_delete(): void
    {
        $cliente = $this->cliente($this->vendedorA, 'Cliente a excluir');

        $this->actingAs($this->vendedorA)
            ->delete(route('orcamentos.clientes.destroy', $cliente))
            ->assertSessionHasErrors('confirmacao');
        $this->assertNotSoftDeleted($cliente);

        $this->actingAs($this->vendedorA)
            ->delete(route('orcamentos.clientes.destroy', $cliente), ['confirmacao' => '1'])
            ->assertRedirect(route('orcamentos.clientes.index'));
        $this->assertSoftDeleted($cliente);
    }

    public function test_item_records_initial_price_and_every_change(): void
    {
        $this->actingAs($this->admin)
            ->post(route('orcamentos.itens.store'), [
                'descricao' => 'Produto histórico',
                'numero_serie' => 'SERIE-01',
                'preco_atual' => '2.00',
                'tributacao' => $this->tributacao(),
            ])
            ->assertRedirect(route('orcamentos.itens.index'));

        $item = Item::query()->firstOrFail();

        foreach (['4.00', '7.50'] as $preco) {
            $this->actingAs($this->admin)
                ->put(route('orcamentos.itens.update', $item), [
                    'descricao' => $item->descricao,
                    'numero_serie' => $item->numero_serie,
                    'preco_atual' => $preco,
                    'tributacao' => $this->tributacao(),
                ])
                ->assertRedirect(route('orcamentos.itens.edit', $item));
            $item->refresh();
        }

        $this->assertSame(
            ['7.50', '4.00', '2.00'],
            $item->precosHistorico()->pluck('preco')->all(),
        );
        $this->assertDatabaseCount('item_precos_historico', 3);
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Item::class,
            'subject_id' => $item->id,
            'event' => 'updated',
        ]);

        $this->actingAs($this->supervisor)
            ->get(route('orcamentos.itens.show', $item))
            ->assertOk()
            ->assertSee('Hist');
    }

    public function test_item_serial_is_normalized_and_cannot_repeat_within_the_same_company(): void
    {
        $this->actingAs($this->admin)
            ->post(route('orcamentos.itens.store'), [
                'descricao' => 'Primeiro item',
                'numero_serie' => '  serie-duplicada  ',
                'preco_atual' => '10.00',
                'tributacao' => $this->tributacao(),
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('itens', [
            'empresa_id' => $this->empresa->id,
            'numero_serie' => 'SERIE-DUPLICADA',
        ]);

        $this->actingAs($this->admin)
            ->post(route('orcamentos.itens.store'), [
                'descricao' => 'Segundo item',
                'numero_serie' => 'Serie-Duplicada',
                'preco_atual' => '20.00',
                'tributacao' => $this->tributacao(),
            ])
            ->assertSessionHasErrors([
                'numero_serie' => 'Este número de série já está cadastrado em outro item desta empresa.',
            ]);

        $this->assertSame(1, Item::withoutGlobalScopes()
            ->where('empresa_id', $this->empresa->id)
            ->where('numero_serie', 'SERIE-DUPLICADA')
            ->count());
    }

    public function test_new_item_suggests_the_last_registered_tax_rates(): void
    {
        $this->actingAs($this->admin)
            ->post(route('orcamentos.itens.store'), [
                'descricao' => 'Item com tributação',
                'numero_serie' => 'TRIB-01',
                'preco_atual' => '10.00',
                'tributacao' => $this->tributacao([
                    'aliquota_icms' => '17.5000',
                    'aliquota_ipi' => '4.2500',
                    'aliquota_pis' => '1.6500',
                    'aliquota_cofins' => '7.6000',
                ]),
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('item_tributacoes', [
            'empresa_id' => $this->empresa->id,
            'aliquota_icms' => '17.5',
        ]);

        $this->actingAs($this->admin)
            ->get(route('orcamentos.itens.create'))
            ->assertOk()
            ->assertSee('value="17.5000"', false)
            ->assertSee('value="4.2500"', false)
            ->assertSee('value="1.6500"', false)
            ->assertSee('value="7.6000"', false);
    }

    public function test_only_admin_can_manage_items_and_vendedor_cannot_see_history(): void
    {
        $item = Item::create([
            'empresa_id' => $this->empresa->id,
            'descricao' => 'Item compartilhado',
            'preco_atual' => '10.00',
        ]);

        $this->actingAs($this->supervisor)
            ->get(route('orcamentos.itens.create'))
            ->assertForbidden();

        $this->actingAs($this->vendedorA)
            ->get(route('orcamentos.itens.show', $item))
            ->assertForbidden();

        $this->actingAs($this->supervisor)
            ->get(route('orcamentos.itens.show', $item))
            ->assertOk();

        $this->actingAs($this->supervisor)
            ->get(route('orcamentos.itens.edit', $item))
            ->assertForbidden();
    }

    public function test_inactive_module_blocks_budget_registrations(): void
    {
        $this->empresa->modulos()->updateExistingPivot(
            Modulo::query()->where('slug', 'orcamentos')->value('id'),
            ['ativo' => false],
        );

        $this->actingAs($this->admin)
            ->get(route('orcamentos.clientes.index'))
            ->assertForbidden();
    }

    public function test_item_deletion_requires_confirmation_and_uses_soft_delete(): void
    {
        $item = Item::create([
            'empresa_id' => $this->empresa->id,
            'descricao' => 'Item a excluir',
            'preco_atual' => '15.00',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('orcamentos.itens.destroy', $item))
            ->assertSessionHasErrors('confirmacao');
        $this->assertNotSoftDeleted($item);

        $this->actingAs($this->admin)
            ->delete(route('orcamentos.itens.destroy', $item), ['confirmacao' => '1'])
            ->assertRedirect(route('orcamentos.itens.index'));
        $this->assertSoftDeleted($item);
    }

    public function test_global_scope_hides_records_from_another_company(): void
    {
        $outraEmpresa = Empresa::factory()->create();
        $outroItem = Item::create([
            'empresa_id' => $outraEmpresa->id,
            'descricao' => 'Item de outra empresa',
            'preco_atual' => '99.00',
        ]);

        $this->actingAs($this->admin)
            ->get(route('orcamentos.itens.show', $outroItem))
            ->assertNotFound();
    }

    private function usuario(RoleName $role): User
    {
        $user = User::factory()->for($this->empresa)->create();
        $user->assignRole($role->value);

        return $user;
    }

    private function cliente(User $vendedor, string $nome): Cliente
    {
        return Cliente::create([
            'empresa_id' => $this->empresa->id,
            'user_id' => $vendedor->id,
            'nome_razao_social' => $nome,
        ]);
    }

    private function tributacao(array $sobrescrever = []): array
    {
        return [
            'ncm' => '85362000',
            'cest' => '1200100',
            'aliquota_icms' => '18.0000',
            'aliquota_ipi' => '5.0000',
            'aliquota_pis' => '1.6500',
            'aliquota_cofins' => '7.6000',
            'possui_st' => '0',
            'mva' => null,
            'observacoes_fiscais' => null,
            ...$sobrescrever,
        ];
    }
}

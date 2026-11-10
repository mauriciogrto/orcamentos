<?php

namespace Tests\Feature\Orcamentos;

use App\Enums\OrcamentoStatus;
use App\Enums\RoleName;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Modulo;
use App\Models\Orcamento;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListagemPanoramaTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $admin;

    private User $supervisor;

    private User $vendedorA;

    private User $vendedorB;

    private Cliente $clienteA;

    private Cliente $clienteB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->empresa = Empresa::factory()->create();
        $this->empresa->modulos()->attach(
            Modulo::query()->where('slug', 'orcamentos')->firstOrFail(),
            ['ativo' => true],
        );
        $this->admin = $this->usuario(RoleName::AdminEmpresa, 'Admin Empresa');
        $this->supervisor = $this->usuario(RoleName::Supervisor, 'Supervisor');
        $this->vendedorA = $this->usuario(RoleName::Vendedor, 'Vendedor A');
        $this->vendedorB = $this->usuario(RoleName::Vendedor, 'Vendedor B');
        $this->clienteA = $this->cliente($this->vendedorA, 'Alfa Comércio', '19131243000197');
        $this->clienteB = $this->cliente($this->vendedorB, 'Beta Indústria', '11222333000181');
    }

    public function test_number_client_and_status_filters_work_together(): void
    {
        $esperado = $this->orcamento($this->vendedorA, $this->clienteA, 1, OrcamentoStatus::Aprovado, 100);
        $mesmoClienteOutroStatus = $this->orcamento($this->vendedorA, $this->clienteA, 2, OrcamentoStatus::Rascunho, 200);
        $outroClienteMesmoStatus = $this->orcamento($this->vendedorB, $this->clienteB, 3, OrcamentoStatus::Aprovado, 300);

        $this->actingAs($this->admin)
            ->get(route('orcamentos.orcamentos.index', [
                'numero' => '001',
                'cliente' => 'Alfa',
                'status' => OrcamentoStatus::Aprovado->value,
            ]))
            ->assertOk()
            ->assertSee($this->numeroCurto($esperado))
            ->assertDontSee($this->numeroCurto($mesmoClienteOutroStatus))
            ->assertDontSee($this->numeroCurto($outroClienteMesmoStatus));

        $this->actingAs($this->supervisor)
            ->get(route('orcamentos.orcamentos.index', ['cliente' => '19131243']))
            ->assertOk()
            ->assertSee($this->numeroCurto($esperado))
            ->assertSee($this->numeroCurto($mesmoClienteOutroStatus))
            ->assertDontSee($this->numeroCurto($outroClienteMesmoStatus));
    }

    public function test_seller_scope_cannot_be_bypassed_with_filters(): void
    {
        $orcamentoA = $this->orcamento($this->vendedorA, $this->clienteA, 1, OrcamentoStatus::Aprovado, 100);
        $orcamentoB = $this->orcamento($this->vendedorB, $this->clienteB, 2, OrcamentoStatus::Aprovado, 200);

        $this->actingAs($this->vendedorA)
            ->get(route('orcamentos.orcamentos.index', [
                'cliente' => 'Beta',
                'status' => OrcamentoStatus::Aprovado->value,
            ]))
            ->assertOk()
            ->assertDontSee($this->numeroCurto($orcamentoB))
            ->assertSee('Nenhum orçamento encontrado.');

        $this->actingAs($this->vendedorA)
            ->get(route('orcamentos.orcamentos.index'))
            ->assertOk()
            ->assertSee($this->numeroCurto($orcamentoA))
            ->assertDontSee($this->numeroCurto($orcamentoB));
    }

    public function test_finished_budget_checkbox_filters_only_finalized_records(): void
    {
        $finalizadoA = $this->orcamento($this->vendedorA, $this->clienteA, 1, OrcamentoStatus::Finalizado, 100);
        $rascunhoA = $this->orcamento($this->vendedorA, $this->clienteA, 2, OrcamentoStatus::Rascunho, 200);
        $finalizadoB = $this->orcamento($this->vendedorB, $this->clienteB, 3, OrcamentoStatus::Finalizado, 300);

        $this->actingAs($this->admin)
            ->get(route('orcamentos.orcamentos.index', ['finalizados' => '1']))
            ->assertOk()
            ->assertSee('Situação')
            ->assertSee('Finalizado')
            ->assertSee($this->numeroCurto($finalizadoA))
            ->assertSee($this->numeroCurto($finalizadoB))
            ->assertDontSee($this->numeroCurto($rascunhoA));

        $this->actingAs($this->vendedorA)
            ->get(route('orcamentos.orcamentos.index', ['finalizados' => '1']))
            ->assertOk()
            ->assertSee($this->numeroCurto($finalizadoA))
            ->assertDontSee($this->numeroCurto($finalizadoB))
            ->assertDontSee($this->numeroCurto($rascunhoA));
    }

    public function test_admin_and_supervisor_see_every_seller_only_from_their_company(): void
    {
        $orcamentoA = $this->orcamento($this->vendedorA, $this->clienteA, 1, OrcamentoStatus::Rascunho, 100);
        $orcamentoB = $this->orcamento($this->vendedorB, $this->clienteB, 2, OrcamentoStatus::Enviado, 200);
        $orcamentoExterno = $this->orcamentoExterno();

        foreach ([$this->admin, $this->supervisor] as $usuario) {
            $this->actingAs($usuario)
                ->get(route('orcamentos.orcamentos.index'))
                ->assertOk()
                ->assertSee($this->numeroCurto($orcamentoA))
                ->assertSee($this->numeroCurto($orcamentoB))
                ->assertDontSee($this->numeroCurto($orcamentoExterno));
        }
    }

    public function test_dashboard_totals_open_budgets_by_seller_for_admin_and_supervisor(): void
    {
        $this->cliente($this->vendedorA, 'Segundo cliente A', null);
        $this->orcamento($this->vendedorA, $this->clienteA, 1, OrcamentoStatus::Rascunho, 100);
        $this->orcamento($this->vendedorA, $this->clienteA, 2, OrcamentoStatus::Enviado, 200);
        $this->orcamento($this->vendedorA, $this->clienteA, 3, OrcamentoStatus::Aprovado, 300);
        $this->orcamento($this->vendedorB, $this->clienteB, 4, OrcamentoStatus::Enviado, 50);
        $this->orcamento($this->vendedorB, $this->clienteB, 5, OrcamentoStatus::Rejeitado, 80);
        $this->orcamentoExterno();

        foreach ([$this->admin, $this->supervisor] as $usuario) {
            $this->actingAs($usuario)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertSee('Panorama geral da empresa')
                ->assertViewHas('panorama', function ($panorama): bool {
                    $vendedorA = $panorama->firstWhere('id', $this->vendedorA->id);
                    $vendedorB = $panorama->firstWhere('id', $this->vendedorB->id);

                    return $panorama->count() === 2
                        && $vendedorA->clientes_count === 2
                        && $vendedorA->orcamentos_abertos_count === 2
                        && (float) $vendedorA->orcamentos_abertos_total === 300.0
                        && $vendedorB->clientes_count === 1
                        && $vendedorB->orcamentos_abertos_count === 1
                        && (float) $vendedorB->orcamentos_abertos_total === 50.0;
                })
                ->assertViewHas('totais', fn (array $totais): bool => $totais['clientes'] === 3
                    && $totais['orcamentos_abertos'] === 3
                    && (float) $totais['valor_aberto'] === 350.0);
        }
    }

    public function test_seller_dashboard_does_not_receive_company_panorama(): void
    {
        $this->orcamento($this->vendedorB, $this->clienteB, 1, OrcamentoStatus::Enviado, 900);

        $this->actingAs($this->vendedorA)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('panorama', null)
            ->assertDontSee('Panorama geral da empresa')
            ->assertDontSee('Vendedor B');
    }

    private function usuario(RoleName $role, string $name): User
    {
        $user = User::factory()->create(['empresa_id' => $this->empresa->id, 'name' => $name]);
        $user->assignRole($role->value);

        return $user;
    }

    private function cliente(User $vendedor, string $nome, ?string $cnpj): Cliente
    {
        return Cliente::create([
            'empresa_id' => $vendedor->empresa_id,
            'user_id' => $vendedor->id,
            'nome_razao_social' => $nome,
            'cnpj' => $cnpj,
        ]);
    }

    private function orcamento(User $vendedor, Cliente $cliente, int $sequencial, OrcamentoStatus $status, float $total): Orcamento
    {
        return Orcamento::create([
            'empresa_id' => $vendedor->empresa_id,
            'user_id' => $vendedor->id,
            'cliente_id' => $cliente->id,
            'ano' => 2026,
            'sequencial' => $sequencial,
            'numero' => sprintf('ORC-2026-%03d', $sequencial),
            'status' => $status,
            'data_emissao' => '2026-06-19',
            'hora_emissao' => '10:00:00',
            'forma_pagamento' => 'pix',
            'validade_dias' => 15,
            'frete_tipo' => 'cif',
            'subtotal_sem_desconto' => $total,
            'desconto_efetivo_percentual' => 0,
            'valor_total' => $total,
            'comissao_percentual' => 0,
            'valor_comissao' => 0,
        ]);
    }

    private function numeroCurto(Orcamento $orcamento): string
    {
        return preg_replace('/^ORC-/', '', $orcamento->numero);
    }

    private function orcamentoExterno(): Orcamento
    {
        $empresa = Empresa::factory()->create();
        $empresa->modulos()->attach(Modulo::query()->where('slug', 'orcamentos')->firstOrFail(), ['ativo' => true]);
        $vendedor = User::factory()->create(['empresa_id' => $empresa->id]);
        $vendedor->assignRole(RoleName::Vendedor->value);
        $cliente = $this->clienteExterno($empresa, $vendedor);

        return $this->orcamento($vendedor, $cliente, 99, OrcamentoStatus::Enviado, 9999);
    }

    private function clienteExterno(Empresa $empresa, User $vendedor): Cliente
    {
        return Cliente::create([
            'empresa_id' => $empresa->id,
            'user_id' => $vendedor->id,
            'nome_razao_social' => 'Cliente externo',
        ]);
    }
}

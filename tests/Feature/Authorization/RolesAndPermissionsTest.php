<?php

namespace Tests\Feature\Authorization;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\Empresa;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolesAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_expected_roles_and_permissions_are_seeded(): void
    {
        $this->assertEqualsCanonicalizing(
            array_column(RoleName::cases(), 'value'),
            Role::query()->pluck('name')->all(),
        );

        $vendedor = User::factory()->for(Empresa::factory())->create();
        $vendedor->assignRole(RoleName::Vendedor->value);

        $this->assertTrue($vendedor->can(PermissionName::CriarPropriosClientes->value));
        $this->assertFalse($vendedor->can(PermissionName::VisualizarTodosClientes->value));
        $this->assertFalse($vendedor->can(PermissionName::GerenciarEmpresas->value));
    }

    public function test_role_and_permission_middlewares_protect_local_test_routes(): void
    {
        $empresa = Empresa::factory()->create();
        $adminEmpresa = User::factory()->for($empresa)->create();
        $vendedor = User::factory()->for($empresa)->create();

        $adminEmpresa->assignRole(RoleName::AdminEmpresa->value);
        $vendedor->assignRole(RoleName::Vendedor->value);

        $this->actingAs($adminEmpresa)
            ->get('/_teste/papeis/admin-empresa')
            ->assertOk();

        $this->actingAs($vendedor)
            ->get('/_teste/papeis/admin-empresa')
            ->assertForbidden();

        $this->actingAs($vendedor)
            ->get('/_teste/permissoes/gerenciar-empresas')
            ->assertForbidden();
    }

    public function test_super_admin_passes_any_registered_permission_check(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(RoleName::SuperAdmin->value);

        $this->actingAs($superAdmin)
            ->get('/_teste/permissoes/gerenciar-empresas')
            ->assertOk();
    }

    public function test_supervisor_can_operate_clients_and_budgets_but_cannot_manage_items_or_team(): void
    {
        $supervisor = User::factory()->for(Empresa::factory())->create();
        $supervisor->assignRole(RoleName::Supervisor->value);

        $this->assertTrue($supervisor->can(PermissionName::VisualizarTodosOrcamentos->value));
        $this->assertTrue($supervisor->can(PermissionName::EditarTodosOrcamentos->value));
        $this->assertTrue($supervisor->can(PermissionName::ExcluirTodosOrcamentos->value));
        $this->assertTrue($supervisor->can(PermissionName::VisualizarTodosClientes->value));
        $this->assertTrue($supervisor->can(PermissionName::CriarTodosClientes->value));
        $this->assertTrue($supervisor->can(PermissionName::EditarTodosClientes->value));
        $this->assertTrue($supervisor->can(PermissionName::ExcluirTodosClientes->value));
        $this->assertTrue($supervisor->can(PermissionName::CriarTodosOrcamentos->value));
        $this->assertTrue($supervisor->can(PermissionName::VisualizarItens->value));
        $this->assertTrue($supervisor->can(PermissionName::VisualizarHistoricoItens->value));
        $this->assertFalse($supervisor->can(PermissionName::GerenciarItens->value));
        $this->assertFalse($supervisor->can(PermissionName::GerenciarUsuariosEmpresa->value));
    }

    public function test_manager_and_supervisor_can_create_budgets(): void
    {
        $empresa = Empresa::factory()->create();
        $gerente = User::factory()->for($empresa)->create();
        $supervisor = User::factory()->for($empresa)->create();
        $gerente->assignRole(RoleName::AdminEmpresa->value);
        $supervisor->assignRole(RoleName::Supervisor->value);

        $this->assertTrue($gerente->can(PermissionName::CriarTodosOrcamentos->value));
        $this->assertTrue($supervisor->can(PermissionName::CriarTodosOrcamentos->value));
    }
}

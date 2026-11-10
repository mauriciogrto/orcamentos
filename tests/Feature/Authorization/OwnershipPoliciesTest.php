<?php

namespace Tests\Feature\Authorization;

use App\Enums\RoleName;
use App\Models\Empresa;
use App\Models\User;
use App\Policies\ClientePolicy;
use App\Policies\OrcamentoPolicy;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnershipPoliciesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_vendedor_can_only_access_own_records_from_same_empresa(): void
    {
        $empresa = Empresa::factory()->create();
        $outraEmpresa = Empresa::factory()->create();
        $vendedor = User::factory()->for($empresa)->create();
        $outroVendedor = User::factory()->for($empresa)->create();
        $vendedor->assignRole(RoleName::Vendedor->value);

        $proprio = $this->record($empresa->id, $vendedor->id);
        $deOutroVendedor = $this->record($empresa->id, $outroVendedor->id);
        $deOutraEmpresa = $this->record($outraEmpresa->id, $vendedor->id);

        foreach ([new ClientePolicy, new OrcamentoPolicy] as $policy) {
            $this->assertTrue($policy->view($vendedor, $proprio));
            $this->assertTrue($policy->update($vendedor, $proprio));
            $this->assertFalse($policy->view($vendedor, $deOutroVendedor));
            $this->assertFalse($policy->update($vendedor, $deOutroVendedor));
            $this->assertFalse($policy->view($vendedor, $deOutraEmpresa));
        }
    }

    public function test_supervisor_can_view_and_edit_company_clients(): void
    {
        $empresa = Empresa::factory()->create();
        $outraEmpresa = Empresa::factory()->create();
        $supervisor = User::factory()->for($empresa)->create();
        $vendedor = User::factory()->for($empresa)->create();
        $supervisor->assignRole(RoleName::Supervisor->value);
        $policy = new ClientePolicy;

        $this->assertTrue($policy->view($supervisor, $this->record($empresa->id, $vendedor->id)));
        $this->assertFalse($policy->view($supervisor, $this->record($outraEmpresa->id, $vendedor->id)));
        $this->assertTrue($policy->update($supervisor, $this->record($empresa->id, $vendedor->id)));
    }

    public function test_super_admin_bypasses_policy_ownership(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(RoleName::SuperAdmin->value);

        $this->assertTrue((new ClientePolicy)->before($superAdmin));
        $this->assertTrue((new OrcamentoPolicy)->before($superAdmin));
    }

    private function record(int $empresaId, int $userId): Model
    {
        $record = new class extends Model
        {
            protected $guarded = [];
        };

        return $record->forceFill([
            'empresa_id' => $empresaId,
            'user_id' => $userId,
        ]);
    }
}

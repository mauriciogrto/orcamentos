<?php

namespace Tests\Feature\Tenancy;

use App\Models\Empresa;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmpresaScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_scope_filters_models_by_current_empresa(): void
    {
        $empresa = Empresa::factory()->create();
        $outraEmpresa = Empresa::factory()->create();
        $usuarioDaEmpresa = User::factory()->for($empresa)->create();
        User::factory()->for($outraEmpresa)->create();

        app(TenantContext::class)->setEmpresaId($empresa->id);

        $this->assertSame([$usuarioDaEmpresa->id], User::query()->pluck('id')->all());
        $this->assertCount(2, User::withoutGlobalScopes()->get());
    }

    public function test_new_tenant_model_receives_empresa_id_from_context(): void
    {
        $empresa = Empresa::factory()->create();
        app(TenantContext::class)->setEmpresaId($empresa->id);

        $user = User::factory()->make(['empresa_id' => null]);
        $user->save();

        $this->assertSame($empresa->id, $user->empresa_id);
    }
}

<?php

namespace Tests\Feature\SuperAdmin;

use App\Enums\EmpresaStatus;
use App\Enums\RoleName;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Modulo;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmpresaPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole(RoleName::SuperAdmin->value);
    }

    public function test_panel_is_restricted_to_super_admin(): void
    {
        $vendedor = User::factory()->for(Empresa::factory())->create();
        $vendedor->assignRole(RoleName::Vendedor->value);

        $this->actingAs($vendedor)
            ->get(route('super-admin.empresas.index'))
            ->assertForbidden();

        $this->actingAs($this->superAdmin)
            ->get(route('super-admin.empresas.index'))
            ->assertOk();
    }

    public function test_super_admin_can_create_company_with_logo_and_inactive_module(): void
    {
        Storage::fake('public');
        $logo = UploadedFile::fake()->createWithContent(
            'logo.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='),
        );

        $response = $this->actingAs($this->superAdmin)
            ->post(route('super-admin.empresas.store'), [
                'nome_fantasia' => 'Empresa Manual',
                'razao_social' => 'Empresa de Teste Ltda.',
                'cnpj' => '11.222.333/0001-81',
                'telefone' => '(11) 3333-4444',
                'email' => 'empresa@example.com',
                'cep' => '01001-000',
                'logradouro' => 'Praça da Sé',
                'numero' => '100',
                'complemento' => 'Conjunto 1',
                'bairro' => 'Sé',
                'cidade' => 'São Paulo',
                'uf' => 'sp',
                'status' => EmpresaStatus::Ativa->value,
                'logo' => $logo,
            ]);

        $empresa = Empresa::query()->firstOrFail();

        $response->assertRedirect(route('super-admin.empresas.usuarios.create', $empresa));
        $this->assertSame('19131243000197', $empresa->cnpj);
        $this->assertSame('01001000', $empresa->cep);
        $this->assertSame('SP', $empresa->uf);
        Storage::disk('public')->assertExists($empresa->logo_path);
        $this->assertFalse($empresa->moduloAtivo('orcamentos'));
        $this->assertDatabaseHas('empresa_modulo', [
            'empresa_id' => $empresa->id,
            'modulo_id' => Modulo::query()->where('slug', 'orcamentos')->value('id'),
            'ativo' => false,
        ]);
    }

    public function test_super_admin_can_update_and_deactivate_company(): void
    {
        $empresa = Empresa::factory()->create();

        $this->actingAs($this->superAdmin)
            ->put(route('super-admin.empresas.update', $empresa), [
                'nome_fantasia' => '',
                'razao_social' => 'Razão Social Atualizada Ltda.',
                'cnpj' => $empresa->cnpj,
                'telefone' => '',
                'email' => '',
                'cep' => '30140071',
                'logradouro' => 'Avenida Afonso Pena',
                'numero' => '1500',
                'complemento' => '',
                'bairro' => 'Centro',
                'cidade' => 'Belo Horizonte',
                'uf' => 'mg',
                'status' => EmpresaStatus::Suspensa->value,
            ])
            ->assertRedirect(route('super-admin.empresas.index'));

        $this->assertDatabaseHas('empresas', [
            'id' => $empresa->id,
            'nome_fantasia' => null,
            'razao_social' => 'Razão Social Atualizada Ltda.',
            'cep' => '30140071',
            'uf' => 'MG',
            'status' => EmpresaStatus::Suspensa->value,
        ]);

        $this->actingAs($this->superAdmin)
            ->patch(route('super-admin.empresas.desativar', $empresa))
            ->assertRedirect();

        $this->assertDatabaseHas('empresas', [
            'id' => $empresa->id,
            'status' => EmpresaStatus::Inativa->value,
        ]);
    }

    public function test_brasil_api_lookup_returns_available_fields(): void
    {
        Http::fake([
            'brasilapi.com.br/api/cnpj/v1/*' => Http::response([
                'razao_social' => 'EMPRESA CONSULTADA S.A.',
                'nome_fantasia' => 'EMPRESA CONSULTADA',
                'ddd_telefone_1' => '1133334444',
                'email' => 'contato@example.com',
                'cep' => '01001000',
                'logradouro' => 'Praça da Sé',
                'numero' => '100',
                'complemento' => 'lado ímpar',
                'bairro' => 'Sé',
                'municipio' => 'São Paulo',
                'uf' => 'SP',
            ]),
        ]);

        $this->actingAs($this->superAdmin)
            ->getJson(route('super-admin.cnpj.buscar', '19131243000197'))
            ->assertOk()
            ->assertJsonPath('data.razao_social', 'EMPRESA CONSULTADA S.A.')
            ->assertJsonPath('data.nome_fantasia', 'EMPRESA CONSULTADA')
            ->assertJsonPath('data.telefone', '1133334444')
            ->assertJsonPath('data.cep', '01001000')
            ->assertJsonPath('data.logradouro', 'Praça da Sé')
            ->assertJsonPath('data.cidade', 'São Paulo');
    }

    public function test_cep_lookup_returns_available_address_fields(): void
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

        $this->actingAs($this->superAdmin)
            ->getJson(route('super-admin.cep.buscar', '01001-000'))
            ->assertOk()
            ->assertJsonPath('data.cep', '01001000')
            ->assertJsonPath('data.logradouro', 'Praça da Sé')
            ->assertJsonPath('data.bairro', 'Sé')
            ->assertJsonPath('data.cidade', 'São Paulo')
            ->assertJsonPath('data.uf', 'SP');
    }

    public function test_brasil_api_failure_returns_friendly_message(): void
    {
        Http::fake([
            'brasilapi.com.br/api/cnpj/v1/*' => Http::response([], 404),
        ]);

        $this->actingAs($this->superAdmin)
            ->getJson(route('super-admin.cnpj.buscar', '00000000000000'))
            ->assertUnprocessable()
            ->assertJsonFragment(['message' => 'CNPJ não encontrado na BrasilAPI. Confira o número ou preencha os dados manualmente.']);
    }

    public function test_super_admin_can_activate_budget_module(): void
    {
        $empresa = Empresa::factory()->create();
        $modulo = Modulo::query()->where('slug', 'orcamentos')->firstOrFail();
        $empresa->modulos()->attach($modulo, ['ativo' => false]);

        $this->actingAs($this->superAdmin)
            ->put(route('super-admin.empresas.modulos.update', $empresa), [
                'modulos' => [$modulo->id => true],
            ])
            ->assertRedirect();

        $this->assertTrue($empresa->fresh()->moduloAtivo('orcamentos'));
    }

    public function test_super_admin_can_create_company_user_with_allowed_role(): void
    {
        $empresa = Empresa::factory()->create();

        $this->actingAs($this->superAdmin)
            ->post(route('super-admin.empresas.usuarios.store', $empresa), [
                'name' => 'Administrador Empresa',
                'cargo' => 'Gerente',
                'email' => 'admin.empresa@example.com',
                'password' => 'senha-segura-123',
                'password_confirmation' => 'senha-segura-123',
            ])
            ->assertRedirect(route('super-admin.empresas.usuarios.index', $empresa));

        $user = User::withoutGlobalScopes()->where('email', 'admin.empresa@example.com')->firstOrFail();

        $this->assertSame($empresa->id, $user->empresa_id);
        $this->assertSame('Gerente', $user->cargo);
        $this->assertTrue($user->hasRole(RoleName::AdminEmpresa->value));
    }

    public function test_company_user_must_have_a_predefined_cargo(): void
    {
        $empresa = Empresa::factory()->create();

        $this->actingAs($this->superAdmin)
            ->post(route('super-admin.empresas.usuarios.store', $empresa), [
                'name' => 'Outro Super Admin',
                'cargo' => 'Diretor',
                'email' => 'outro.super@example.com',
                'password' => 'senha-segura-123',
                'password_confirmation' => 'senha-segura-123',
            ])
            ->assertSessionHasErrors('cargo');

        $this->assertDatabaseMissing('users', ['email' => 'outro.super@example.com']);
    }

    public function test_super_admin_can_edit_user_and_cargo_updates_the_role(): void
    {
        $empresa = Empresa::factory()->create();
        $user = User::factory()->for($empresa)->create(['cargo' => 'Vendedor']);
        $user->assignRole(RoleName::Vendedor->value);

        $this->actingAs($this->superAdmin)
            ->put(route('super-admin.empresas.usuarios.update', [$empresa, $user]), [
                'name' => 'Usuário Promovido',
                'cargo' => 'Supervisor',
                'email' => 'promovido@example.com',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect(route('super-admin.empresas.usuarios.index', $empresa));

        $user->refresh();
        $this->assertSame('Usuário Promovido', $user->name);
        $this->assertSame('Supervisor', $user->cargo);
        $this->assertTrue($user->hasRole(RoleName::Supervisor->value));
        $this->assertFalse($user->hasRole(RoleName::Vendedor->value));
    }

    public function test_deleting_user_is_soft_and_preserves_important_relationships(): void
    {
        $empresa = Empresa::factory()->create();
        $user = User::factory()->for($empresa)->create(['cargo' => 'Vendedor']);
        $user->assignRole(RoleName::Vendedor->value);
        $cliente = Cliente::create([
            'empresa_id' => $empresa->id,
            'user_id' => $user->id,
            'nome_razao_social' => 'Cliente preservado',
        ]);

        $this->actingAs($this->superAdmin)
            ->delete(route('super-admin.empresas.usuarios.destroy', [$empresa, $user]))
            ->assertRedirect(route('super-admin.empresas.usuarios.index', $empresa));

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertDatabaseHas('clientes', ['id' => $cliente->id, 'user_id' => $user->id]);
        $this->assertSame($user->name, $cliente->fresh()->vendedor->name);
    }

    public function test_user_from_another_company_cannot_be_changed_through_nested_route(): void
    {
        $empresa = Empresa::factory()->create();
        $outraEmpresa = Empresa::factory()->create();
        $user = User::factory()->for($outraEmpresa)->create(['cargo' => 'Vendedor']);
        $user->assignRole(RoleName::Vendedor->value);

        $this->actingAs($this->superAdmin)
            ->delete(route('super-admin.empresas.usuarios.destroy', [$empresa, $user]))
            ->assertNotFound();

        $this->assertNotSoftDeleted('users', ['id' => $user->id]);
    }
}

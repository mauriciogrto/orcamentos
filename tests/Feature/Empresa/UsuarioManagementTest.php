<?php

namespace Tests\Feature\Empresa;

use App\Enums\RoleName;
use App\Models\Empresa;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsuarioManagementTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $gerente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->empresa = Empresa::factory()->create();
        $this->gerente = $this->usuario(RoleName::AdminEmpresa, 'Gerente');
    }

    public function test_manager_can_create_every_company_cargo_with_matching_role(): void
    {
        foreach ([
            'Gerente' => RoleName::AdminEmpresa,
            'Supervisor' => RoleName::Supervisor,
            'Vendedor' => RoleName::Vendedor,
        ] as $cargo => $role) {
            $email = strtolower($cargo).'@example.test';
            $this->actingAs($this->gerente)
                ->post(route('empresa.usuarios.store'), [
                    'name' => $cargo.' Novo',
                    'cargo' => $cargo,
                    'email' => $email,
                    'password' => 'TesteSeguro123!',
                    'password_confirmation' => 'TesteSeguro123!',
                ])
                ->assertRedirect(route('empresa.usuarios.index'));

            $usuario = User::query()->where('email', $email)->firstOrFail();
            $this->assertSame($this->empresa->id, $usuario->empresa_id);
            $this->assertTrue($usuario->hasRole($role->value));
        }
    }

    public function test_supervisor_and_seller_cannot_manage_team(): void
    {
        foreach ([RoleName::Supervisor, RoleName::Vendedor] as $role) {
            $this->actingAs($this->usuario($role, $role === RoleName::Supervisor ? 'Supervisor' : 'Vendedor'))
                ->get(route('empresa.usuarios.index'))
                ->assertForbidden();
        }
    }

    public function test_duplicate_login_email_is_rejected_with_a_clear_message(): void
    {
        User::factory()->for($this->empresa)->create(['email' => 'usuario@example.test']);

        $this->actingAs($this->gerente)
            ->post(route('empresa.usuarios.store'), [
                'name' => 'Usuário duplicado',
                'cargo' => 'Vendedor',
                'email' => '  USUARIO@EXAMPLE.TEST ',
                'password' => 'TesteSeguro123!',
                'password_confirmation' => 'TesteSeguro123!',
            ])
            ->assertSessionHasErrors([
                'email' => 'Este e-mail já está vinculado a outro usuário, inclusive uma conta excluída preservada no histórico. Cada usuário precisa de um e-mail exclusivo.',
            ]);

        $this->assertSame(1, User::withoutGlobalScopes()->where('email', 'usuario@example.test')->count());
    }

    public function test_manager_cannot_delete_self_or_the_last_active_manager(): void
    {
        $this->actingAs($this->gerente)
            ->delete(route('empresa.usuarios.destroy', $this->gerente))
            ->assertSessionHasErrors('usuario');

        $this->assertNotSoftDeleted('users', ['id' => $this->gerente->id]);
    }

    public function test_manager_cannot_edit_user_from_another_company(): void
    {
        $outraEmpresa = Empresa::factory()->create();
        $outroUsuario = User::factory()->for($outraEmpresa)->create(['cargo' => 'Vendedor']);
        $outroUsuario->assignRole(RoleName::Vendedor->value);

        $this->actingAs($this->gerente)
            ->get(route('empresa.usuarios.edit', $outroUsuario))
            ->assertNotFound();
    }

    private function usuario(RoleName $role, string $cargo): User
    {
        $usuario = User::factory()->for($this->empresa)->create(['cargo' => $cargo]);
        $usuario->assignRole($role->value);

        return $usuario;
    }
}

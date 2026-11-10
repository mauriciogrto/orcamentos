<?php

namespace Tests\Feature\SuperAdmin;

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_idempotent_multi_company_administrator(): void
    {
        $this->seed(DatabaseSeeder::class);
        config()->set('super_admin.name', 'Mauricio');
        config()->set('super_admin.email', 'mauricio.admin@example.test');
        config()->set('super_admin.password', 'TesteSeguro123!');

        $this->seed(SuperAdminSeeder::class);
        $this->seed(SuperAdminSeeder::class);

        $superAdmin = User::query()->where('email', 'mauricio.admin@example.test')->firstOrFail();

        $this->assertNull($superAdmin->empresa_id);
        $this->assertSame('Mauricio', $superAdmin->name);
        $this->assertNotNull($superAdmin->email_verified_at);
        $this->assertTrue(Hash::check('TesteSeguro123!', $superAdmin->password));
        $this->assertTrue($superAdmin->hasRole(RoleName::SuperAdmin->value));
        $this->assertSame(1, User::query()->where('email', 'mauricio.admin@example.test')->count());

        $this->actingAs($superAdmin)
            ->get(route('super-admin.empresas.index'))
            ->assertOk();
    }
}

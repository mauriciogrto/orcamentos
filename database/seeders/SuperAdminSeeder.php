<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('super_admin.email');
        $password = config('super_admin.password');

        if (! $email || ! $password) {
            $this->command?->warn('Super admin não criado: configure SUPER_ADMIN_EMAIL e SUPER_ADMIN_PASSWORD.');

            return;
        }

        $superAdmin = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'empresa_id' => null,
                'name' => config('super_admin.name') ?: 'Administrador Multiempresa',
                'cargo' => 'Administrador Multiempresa',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ],
        );

        $superAdmin->syncRoles(RoleName::SuperAdmin->value);
    }
}

<?php

namespace Database\Seeders;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(PermissionName::cases())
            ->mapWithKeys(fn (PermissionName $permission) => [
                $permission->value => Permission::findOrCreate($permission->value, 'web'),
            ]);

        $superAdmin = Role::findOrCreate(RoleName::SuperAdmin->value, 'web');
        $adminEmpresa = Role::findOrCreate(RoleName::AdminEmpresa->value, 'web');
        $supervisor = Role::findOrCreate(RoleName::Supervisor->value, 'web');
        $vendedor = Role::findOrCreate(RoleName::Vendedor->value, 'web');

        $superAdmin->syncPermissions($permissions->values());

        $adminEmpresa->syncPermissions($permissions->only([
            PermissionName::GerenciarUsuariosEmpresa->value,
            PermissionName::GerenciarComissionamento->value,
            PermissionName::VisualizarTodosClientes->value,
            PermissionName::CriarTodosClientes->value,
            PermissionName::EditarTodosClientes->value,
            PermissionName::ExcluirTodosClientes->value,
            PermissionName::VisualizarItens->value,
            PermissionName::GerenciarItens->value,
            PermissionName::VisualizarHistoricoItens->value,
            PermissionName::VisualizarTodosOrcamentos->value,
            PermissionName::CriarTodosOrcamentos->value,
            PermissionName::EditarTodosOrcamentos->value,
            PermissionName::ExcluirTodosOrcamentos->value,
        ])->values());

        $supervisor->syncPermissions($permissions->only([
            PermissionName::VisualizarTodosClientes->value,
            PermissionName::CriarTodosClientes->value,
            PermissionName::EditarTodosClientes->value,
            PermissionName::ExcluirTodosClientes->value,
            PermissionName::VisualizarItens->value,
            PermissionName::VisualizarHistoricoItens->value,
            PermissionName::VisualizarTodosOrcamentos->value,
            PermissionName::CriarTodosOrcamentos->value,
            PermissionName::EditarTodosOrcamentos->value,
            PermissionName::ExcluirTodosOrcamentos->value,
        ])->values());

        $vendedor->syncPermissions($permissions->only([
            PermissionName::VisualizarPropriosClientes->value,
            PermissionName::CriarPropriosClientes->value,
            PermissionName::EditarPropriosClientes->value,
            PermissionName::ExcluirPropriosClientes->value,
            PermissionName::VisualizarPropriosOrcamentos->value,
            PermissionName::CriarPropriosOrcamentos->value,
            PermissionName::EditarPropriosOrcamentos->value,
            PermissionName::ExcluirPropriosOrcamentos->value,
        ])->values());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

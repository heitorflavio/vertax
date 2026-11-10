<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

/**
 * Sincroniza papéis e permissões com os enums App\Enums\Role e Permission.
 * Idempotente: rodar de novo só ajusta o que mudou nos enums.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        foreach (Permission::cases() as $permission) {
            PermissionModel::findOrCreate($permission);
        }

        foreach (Role::cases() as $role) {
            RoleModel::findOrCreate($role)->syncPermissions($role->permissions());
        }

        $registrar->forgetCachedPermissions();
    }
}

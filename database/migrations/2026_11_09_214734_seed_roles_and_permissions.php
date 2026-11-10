<?php

use App\Enums\Permission;
use App\Enums\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

/*
 * Os papéis precisam existir em qualquer ambiente (o cadastro atribui
 * "cliente" a todo usuário novo), então eles vêm na migration, não só no
 * seeder. Para ressincronizar depois de mudar os enums:
 * php artisan db:seed --class=RolesAndPermissionsSeeder
 */
return new class extends Migration
{
    public function up(): void
    {
        (new RolesAndPermissionsSeeder)->run();
    }

    public function down(): void
    {
        RoleModel::query()
            ->whereIn('name', array_map(fn (Role $role): string => $role->value, Role::cases()))
            ->delete();

        PermissionModel::query()
            ->whereIn('name', array_map(fn (Permission $permission): string => $permission->value, Permission::cases()))
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

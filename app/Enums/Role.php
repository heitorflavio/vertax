<?php

namespace App\Enums;

/**
 * Papéis do sistema e as permissões de cada um. Esta é a fonte da verdade:
 * o RolesAndPermissionsSeeder sincroniza o banco a partir daqui.
 */
enum Role: string
{
    case Admin = 'admin';
    case Operator = 'operador';
    case Customer = 'cliente';

    /**
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Admin => Permission::cases(),
            self::Operator => [Permission::RunConsultations, Permission::ViewPanel],
            self::Customer => [Permission::RunConsultations],
        };
    }
}

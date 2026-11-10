<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;

class UserRoleCommand extends Command
{
    protected $signature = 'user:role
        {email : E-mail do usuário}
        {role? : Papel a atribuir (admin, operador ou cliente). Sem ele, só mostra o papel atual}';

    protected $description = 'Mostra ou troca o papel de um usuário (cada usuário tem um papel).';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error('Usuário não encontrado.');

            return self::FAILURE;
        }

        $roleName = $this->argument('role');

        if ($roleName === null) {
            $this->line(sprintf(
                '%s: %s',
                $user->email,
                $user->getRoleNames()->implode(', ') ?: 'sem papel',
            ));

            return self::SUCCESS;
        }

        $role = Role::tryFrom((string) $roleName);

        if ($role === null) {
            $this->error(sprintf(
                'Papel inválido: %s. Use um destes: %s.',
                $roleName,
                implode(', ', array_column(Role::cases(), 'value')),
            ));

            return self::FAILURE;
        }

        $user->syncRoles($role);

        $this->info("{$user->email} agora é {$role->value}.");

        return self::SUCCESS;
    }
}

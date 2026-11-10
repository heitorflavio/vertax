<?php

use App\Enums\Role;
use App\Models\User;

test('troca o papel do usuário, substituindo o anterior', function () {
    $user = userWithRole(Role::Customer);

    $this->artisan('user:role', ['email' => $user->email, 'role' => 'admin'])
        ->expectsOutput("{$user->email} agora é admin.")
        ->assertSuccessful();

    expect($user->fresh()->getRoleNames()->all())->toBe(['admin']);
});

test('sem papel informado, mostra o papel atual', function () {
    $user = userWithRole(Role::Operator);

    $this->artisan('user:role', ['email' => $user->email])
        ->expectsOutput("{$user->email}: operador")
        ->assertSuccessful();
});

test('recusa papel que não existe sem criar papel novo', function () {
    $user = userWithRole(Role::Customer);

    $this->artisan('user:role', ['email' => $user->email, 'role' => 'superadmin'])
        ->assertFailed();

    expect($user->fresh()->getRoleNames()->all())->toBe(['cliente']);
    $this->assertDatabaseMissing('roles', ['name' => 'superadmin']);
});

test('falha quando o e-mail não existe', function () {
    $this->artisan('user:role', ['email' => 'ninguem@mail.test', 'role' => 'admin'])
        ->expectsOutput('Usuário não encontrado.')
        ->assertFailed();

    expect(User::query()->count())->toBe(0);
});

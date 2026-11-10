<?php

use App\Enums\Role;

test('returns a successful response', function () {
    $response = $this->actingAs(userWithRole(Role::Customer))->get(route('home'));

    $response->assertOk();
});

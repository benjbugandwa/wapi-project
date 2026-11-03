<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;

test('seeded demo admin account is verified and can access dashboard', function () {
    $this->seed();

    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

    expect($admin->hasVerifiedEmail())->toBeTrue();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk();
});

test('seeded demo system admin account is verified and can access dashboard', function () {
    $this->seed();

    $systemAdmin = User::query()->where('email', 'sysadmin@example.com')->firstOrFail();

    expect($systemAdmin->hasVerifiedEmail())->toBeTrue();

    $this->actingAs($systemAdmin)
        ->get(route('dashboard'))
        ->assertOk();
});

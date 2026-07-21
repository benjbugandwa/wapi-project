<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\CabinetStatus;
use App\Enums\RoleEnum;
use App\Models\Cabinet;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Volt\Volt;

test('users from suspended cabinet cannot access authenticated application', function () {
    $cabinet = Cabinet::factory()->suspended()->create();
    $user = User::factory()->for($cabinet)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertForbidden();
});

test('system admin can access application without cabinet', function () {
    $this->seed(RolePermissionSeeder::class);

    $systemAdmin = User::factory()->systemAdmin()->create();
    $systemAdmin->assignRole(RoleEnum::SystemAdmin->value);

    $this->actingAs($systemAdmin)
        ->get(route('dashboard'))
        ->assertOk();
});

test('suspended cabinet users cannot authenticate from login screen', function () {
    $cabinet = Cabinet::factory()->create(['statut_cabinet' => CabinetStatus::Suspended]);
    $user = User::factory()->for($cabinet)->create();

    $component = Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'password');

    $component->call('login');

    $component->assertHasErrors('form.email');

    $this->assertGuest();
});

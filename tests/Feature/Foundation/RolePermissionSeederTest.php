<?php

declare(strict_types=1);

namespace Tests\Feature\Foundation;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('role permission seeder creates documented spatie roles and permissions', function () {
    $this->seed(RolePermissionSeeder::class);

    foreach (RoleEnum::values() as $role) {
        expect(Role::query()->where('name', $role)->where('guard_name', 'web')->exists())->toBeTrue();
    }

    foreach (PermissionEnum::values() as $permission) {
        expect(Permission::query()->where('name', $permission)->where('guard_name', 'web')->exists())->toBeTrue();
    }
});

test('system admin receives every documented permission', function () {
    $this->seed(RolePermissionSeeder::class);

    $systemAdmin = Role::findByName(RoleEnum::SystemAdmin->value);

    expect($systemAdmin->permissions()->pluck('name')->all())
        ->toEqualCanonicalizing(PermissionEnum::values());
});

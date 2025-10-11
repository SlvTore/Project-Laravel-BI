<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;

class PermissionsCheckTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_without_role_has_no_permissions(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($user->hasPermission('metrics.view'));
    }

    /** @test */
    public function role_attached_grants_permission(): void
    {
        $perm = Permission::create([
            'resource' => 'metrics',
            'action' => 'view',
            'scope' => null,
            'code' => 'metrics.view',
            'description' => 'View metrics dashboard',
        ]);
        $role = Role::create(['name' => 'analyst']);
        $role->permissionItems()->attach($perm->id);
        $user = User::factory()->create();
        $user->role_id = $role->id; // assumes users table has role_id
        $user->save();

        $this->assertTrue($user->hasPermission('metrics.view'));
    }
}

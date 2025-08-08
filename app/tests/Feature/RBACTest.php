<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Business;

class RBACTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create([
            'name' => 'business-owner',
            'display_name' => 'Business Owner',
            'description' => 'Business Owner with full access',
            'permissions' => ['all'],
            'is_active' => true,
        ]);

        Role::create([
            'name' => 'administrator',
            'display_name' => 'Administrator',
            'description' => 'Administrator with management access',
            'permissions' => ['manage_data', 'view_reports', 'manage_team'],
            'is_active' => true,
        ]);

        Role::create([
            'name' => 'staff',
            'display_name' => 'Staff',
            'description' => 'Staff with limited access',
            'permissions' => ['create_data', 'view_data'],
            'is_active' => true,
        ]);

        Role::create([
            'name' => 'business-investigator',
            'display_name' => 'Business Investigator',
            'description' => 'Investigator with view-only access',
            'permissions' => ['view_statistics'],
            'is_active' => true,
        ]);
    }

    /** @test */
    public function business_owner_can_access_all_routes()
    {
        $role = Role::where('name', 'business-owner')->first();
        $user = User::factory()->create([
            'role_id' => $role->id,
            'setup_completed' => true,
        ]);

        // Create a business for the owner
        $business = Business::create([
            'user_id' => $user->id,
            'business_name' => 'Test Business',
            'industry' => 'Technology',
        ]);

        $this->actingAs($user);

        // Test accessing user management
        $response = $this->get('/dashboard/users');
        $response->assertStatus(200);

        // Test accessing metrics
        $response = $this->get('/dashboard/metrics');
        $response->assertStatus(200);
    }

    /** @test */
    public function staff_cannot_access_user_management()
    {
        $role = Role::where('name', 'staff')->first();
        $user = User::factory()->create([
            'role_id' => $role->id,
            'setup_completed' => true,
        ]);

        $this->actingAs($user);

        // Staff should not be able to access user management
        $response = $this->get('/dashboard/users');
        $response->assertStatus(403);
    }

    /** @test */
    public function business_investigator_can_only_view_summary()
    {
        $role = Role::where('name', 'business-investigator')->first();
        $user = User::factory()->create([
            'role_id' => $role->id,
            'setup_completed' => true,
        ]);

        $this->actingAs($user);

        // Investigator should see investigator dashboard
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $response->assertViewIs('dashboard-main.investigator');

        // Should not access user management
        $response = $this->get('/dashboard/users');
        $response->assertStatus(403);

        // Should not access feeds
        $response = $this->get('/dashboard/feeds');
        $response->assertStatus(403);
    }

    /** @test */
    public function user_helper_methods_work_correctly()
    {
        $businessOwnerRole = Role::where('name', 'business-owner')->first();
        $staffRole = Role::where('name', 'staff')->first();

        $owner = User::factory()->create(['role_id' => $businessOwnerRole->id]);
        $staff = User::factory()->create(['role_id' => $staffRole->id]);

        // Test role checking methods
        $this->assertTrue($owner->isBusinessOwner());
        $this->assertFalse($owner->isStaff());
        $this->assertTrue($owner->canManageUsers());

        $this->assertTrue($staff->isStaff());
        $this->assertFalse($staff->isBusinessOwner());
        $this->assertFalse($staff->canManageUsers());

        // Test promotion
        $this->assertTrue($staff->promoteTo('administrator'));
        $this->assertTrue($staff->fresh()->isAdministrator());
    }

    /** @test */
    public function business_can_generate_codes()
    {
        $business = Business::create([
            'user_id' => 1,
            'business_name' => 'Test Business',
            'industry' => 'Technology',
        ]);

        // Test public ID generation
        $publicId = $business->generatePublicId();
        $this->assertNotEmpty($publicId);
        $this->assertStringStartsWith('BIZ-', $publicId);

        // Test invitation code generation
        $invitationCode = $business->generateInvitationCode();
        $this->assertNotEmpty($invitationCode);
        $this->assertEquals(12, strlen($invitationCode));
    }
}
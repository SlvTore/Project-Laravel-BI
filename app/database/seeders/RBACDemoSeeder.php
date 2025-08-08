<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Business;
use Illuminate\Support\Facades\Hash;

class RBACDemoSeeder extends Seeder
{
    /**
     * Run the database seeds for RBAC demo.
     */
    public function run(): void
    {
        // Ensure roles exist
        $this->call(UpdateRolesSeeder::class);

        // Get roles
        $businessOwnerRole = Role::where('name', 'business-owner')->first();
        $administratorRole = Role::where('name', 'administrator')->first();
        $staffRole = Role::where('name', 'staff')->first();
        $investigatorRole = Role::where('name', 'business-investigator')->first();

        // Create Business Owner
        $owner = User::create([
            'name' => 'John Owner',
            'email' => 'owner@example.com',
            'password' => Hash::make('password'),
            'role_id' => $businessOwnerRole->id,
            'setup_completed' => true,
            'is_active' => true,
        ]);

        // Create Business
        $business = Business::create([
            'user_id' => $owner->id,
            'business_name' => 'Demo Tech Company',
            'industry' => 'Technology',
            'description' => 'A demo technology company for testing RBAC',
            'founded_date' => now()->subYears(2),
            'website' => 'https://demo-tech.example.com',
            'initial_revenue' => 5000000,
            'initial_customers' => 100,
            'goals' => [
                'revenue_target' => 20000000,
                'customer_target' => 500,
                'growth_rate_target' => 25,
                'key_metrics' => ['Monthly Revenue', 'Customer Acquisition', 'User Engagement'],
                'target_date' => now()->addYear(),
            ],
        ]);

        // Generate business codes
        $business->generatePublicId();
        $business->generateInvitationCode();

        // Add owner to business
        $business->addUserWithRole($owner, 'business-owner');

        // Create Administrator
        $admin = User::create([
            'name' => 'Jane Administrator',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role_id' => $administratorRole->id,
            'setup_completed' => true,
            'is_active' => true,
        ]);
        $business->addUserWithRole($admin, 'administrator');

        // Create Staff members
        $staff1 = User::create([
            'name' => 'Mike Staff',
            'email' => 'staff1@example.com',
            'password' => Hash::make('password'),
            'role_id' => $staffRole->id,
            'setup_completed' => true,
            'is_active' => true,
        ]);
        $business->addUserWithRole($staff1, 'staff');

        $staff2 = User::create([
            'name' => 'Sarah Staff',
            'email' => 'staff2@example.com',
            'password' => Hash::make('password'),
            'role_id' => $staffRole->id,
            'setup_completed' => true,
            'is_active' => true,
        ]);
        $business->addUserWithRole($staff2, 'staff');

        // Create Business Investigator
        $investigator = User::create([
            'name' => 'Alex Investigator',
            'email' => 'investigator@example.com',
            'password' => Hash::make('password'),
            'role_id' => $investigatorRole->id,
            'setup_completed' => true,
            'is_active' => true,
        ]);
        $business->addUserWithRole($investigator, 'business-investigator');

        $this->command->info('RBAC Demo data created successfully!');
        $this->command->info('Business Owner: owner@example.com (password: password)');
        $this->command->info('Administrator: admin@example.com (password: password)');
        $this->command->info('Staff: staff1@example.com, staff2@example.com (password: password)');
        $this->command->info('Investigator: investigator@example.com (password: password)');
        $this->command->info("Business Public ID: {$business->public_id}");
        $this->command->info("Business Invitation Code: {$business->invitation_code}");
    }
}
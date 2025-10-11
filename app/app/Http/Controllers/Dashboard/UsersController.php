<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Business;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UsersController extends Controller
{
    /**
     * Display a listing of users in the business
     */
    public function index()
    {
        $user = Auth::user();

        // Get user's business
        $business = $this->getUserBusiness($user);

        if (!$business) {
            return redirect()->route('dashboard')->with('error', 'No business found');
        }

        // Get all users in this business
        $users = User::whereHas('businesses', function($query) use ($business) {
            $query->where('business_id', $business->id);
        })->with(['userRole', 'businesses'])->get();

        // Get available roles for promotion
        $roles = Role::all();

        return view('dashboard-users.index', compact('users', 'roles', 'business'));
    }

    /**
     * Store a newly created user in storage
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $business = $this->getUserBusiness($user);

        if (!$business) {
            return response()->json(['error' => 'No business found'], 404);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role_id' => ['required', 'exists:roles,id'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        // Create the user
        $newUser = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
            'phone' => $request->phone,
            'is_active' => true,
            'setup_completed' => true,
            'setup_completed_at' => now(),
        ]);

        // Attach user to business
        $newUser->businesses()->attach($business->id, [
            'joined_at' => now()
        ]);

        // Log the activity
        ActivityLog::create([
            'business_id' => $business->id,
            'user_id' => Auth::id(),
            'type' => 'user_management',
            'title' => 'New User Added',
            'description' => "{$user->name} added {$newUser->name} to the business",
            'metadata' => [
                'new_user_id' => $newUser->id,
                'new_user_role' => $newUser->userRole->name ?? 'Unknown',
                'added_by' => $user->name
            ],
            'icon' => 'bi-person-plus',
            'color' => 'success'
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'User added successfully',
                'user' => [
                    'id' => $newUser->id,
                    'name' => $newUser->name,
                    'email' => $newUser->email,
                    'role' => $newUser->userRole->display_name ?? 'Unknown',
                    'phone' => $newUser->phone,
                    'is_active' => $newUser->is_active
                ]
            ]);
        }

        return redirect()->route('dashboard.users')->with('success', 'User added successfully');
    }

    /**
     * Promote a user to a different role
     */
    public function promote(Request $request, User $user)
    {
        $currentUser = Auth::user();
        $business = $this->getUserBusiness($currentUser);

        if (!$business) {
            return response()->json(['error' => 'No business found'], 404);
        }

        // Check if the user belongs to this business
        if (!$user->businesses()->where('business_id', $business->id)->exists()) {
            return response()->json(['error' => 'User not found in this business'], 404);
        }

        $request->validate([
            'role_id' => ['required', 'exists:roles,id']
        ]);

        $oldRole = $user->userRole;
        $newRole = Role::find($request->role_id);

        // Update user role
        $user->update(['role_id' => $request->role_id]);

        // Log the promotion activity
        ActivityLog::create([
            'business_id' => $business->id,
            'user_id' => Auth::id(),
            'type' => 'user_management',
            'title' => 'User Role Updated',
            'description' => "{$currentUser->name} changed {$user->name}'s role from {$oldRole->display_name} to {$newRole->display_name}",
            'metadata' => [
                'target_user_id' => $user->id,
                'old_role' => $oldRole->name,
                'new_role' => $newRole->name,
                'promoted_by' => $currentUser->name
            ],
            'icon' => 'bi-arrow-up-circle',
            'color' => 'info'
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "User role updated to {$newRole->display_name}",
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $newRole->display_name
                ]
            ]);
        }

        return redirect()->route('dashboard.users')->with('success', 'User role updated successfully');
    }

    /**
     * Toggle user active status
     */
    public function toggleStatus(Request $request, User $user)
    {
        $currentUser = Auth::user();
        $business = $this->getUserBusiness($currentUser);

        if (!$business) {
            return response()->json(['error' => 'No business found'], 404);
        }

        // Check if the user belongs to this business
        if (!$user->businesses()->where('business_id', $business->id)->exists()) {
            return response()->json(['error' => 'User not found in this business'], 404);
        }

        // Cannot deactivate yourself
        if ($user->id === Auth::id()) {
            return response()->json(['error' => 'Cannot deactivate your own account'], 422);
        }

        $newStatus = !$user->is_active;
        $user->update(['is_active' => $newStatus]);

        $action = $newStatus ? 'activated' : 'deactivated';

        // Log the activity
        ActivityLog::create([
            'business_id' => $business->id,
            'user_id' => Auth::id(),
            'type' => 'user_management',
            'title' => 'User Status Changed',
            'description' => "{$currentUser->name} {$action} {$user->name}'s account",
            'metadata' => [
                'target_user_id' => $user->id,
                'new_status' => $newStatus,
                'changed_by' => $currentUser->name
            ],
            'icon' => $newStatus ? 'bi-check-circle' : 'bi-x-circle',
            'color' => $newStatus ? 'success' : 'warning'
        ]);

        return response()->json([
            'success' => true,
            'message' => "User {$action} successfully",
            'is_active' => $newStatus
        ]);
    }

    /**
     * Remove user from business
     */
    public function remove(Request $request, User $user)
    {
        $currentUser = Auth::user();
        $business = $this->getUserBusiness($currentUser);

        if (!$business) {
            return response()->json(['error' => 'No business found'], 404);
        }

        // Check if the user belongs to this business
        if (!$user->businesses()->where('business_id', $business->id)->exists()) {
            return response()->json(['error' => 'User not found in this business'], 404);
        }

        // Cannot remove yourself
        if ($user->id === Auth::id()) {
            return response()->json(['error' => 'Cannot remove your own account'], 422);
        }

        // Detach user from business
        $user->businesses()->detach($business->id);

        // Log the activity
        ActivityLog::create([
            'business_id' => $business->id,
            'user_id' => Auth::id(),
            'type' => 'user_management',
            'title' => 'User Removed',
            'description' => "{$currentUser->name} removed {$user->name} from the business",
            'metadata' => [
                'removed_user_id' => $user->id,
                'removed_user_role' => $user->userRole->name ?? 'Unknown',
                'removed_by' => $currentUser->name
            ],
            'icon' => 'bi-person-x',
            'color' => 'danger'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User removed from business successfully'
        ]);
    }

    /**
     * Get user's business - helper method
     */
    private function getUserBusiness($user)
    {
        // Try to get business through Business model directly
        $business = Business::whereHas('users', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->first();

        // Fallback to owned business
        if (!$business) {
            $business = Business::where('user_id', $user->id)->first();
        }

        return $business;
    }

    /**
     * Get user statistics for the business
     */
    public function getUserStats()
    {
        $user = Auth::user();
        $business = $this->getUserBusiness($user);

        if (!$business) {
            return response()->json(['error' => 'No business found'], 404);
        }

        $totalUsers = User::whereHas('businesses', function($query) use ($business) {
            $query->where('business_id', $business->id);
        })->count();

        $activeUsers = User::whereHas('businesses', function($query) use ($business) {
            $query->where('business_id', $business->id);
        })->where('is_active', true)->count();

        $usersByRole = User::whereHas('businesses', function($query) use ($business) {
            $query->where('business_id', $business->id);
        })->with('userRole')
        ->get()
        ->groupBy(function($user) {
            return $user->userRole->display_name ?? 'No Role';
        })
        ->map(function($group) {
            return $group->count();
        });

        return response()->json([
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'inactive_users' => $totalUsers - $activeUsers,
            'users_by_role' => $usersByRole
        ]);
    }
}

<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Role;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingsController extends Controller
{
    use LogsActivity;

    /**
     * Display the dashboard settings page with business context and BI highlights.
     */
    public function index(): View|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $business = $user ? $this->getUserBusiness($user) : null;

        if (!$user || !$business) {
            return redirect()->route('dashboard')->with('error', 'We could not locate an active business for your account.');
        }

        $teamMembers = collect($business->users()->with('userRole')->get())
            ->when($business->owner, fn ($collection) => $collection->push($business->owner))
            ->unique('id')
            ->sortBy('name')
            ->values();

        $latestActivity = $business->activityLogs()->latest()->first();

        $stats = [
            'team_count' => $teamMembers->count(),
            'active_invitations' => $business->invitations()->active()->count(),
            'metrics_count' => $business->metrics()->count(),
            'last_activity' => $latestActivity?->created_at,
        ];

        $biHighlights = [
            [
                'icon' => 'bi bi-lightning-charge-fill text-warning',
                'title' => 'Actionable BI in Minutes',
                'description' => 'Traction Tracker unifies sales, cost, and product feeds so owners can act on live performance trends without waiting on manual reports.',
            ],
            [
                'icon' => 'bi bi-people-fill text-primary',
                'title' => 'Role-Aware Access',
                'description' => 'Each role sees only the tools they need. Owners orchestrate growth, staff run the operations, and investigators focus on insights.',
            ],
            [
                'icon' => 'bi bi-ui-checks-grid text-success',
                'title' => 'Frictionless UX',
                'description' => 'Pinned quick actions, color-coded alerts, and guided setup help teams adopt BI workflows faster and keep their data healthy.',
            ],
        ];

        return view('dashboard-settings.index', [
            'business' => $business,
            'user' => $user,
            'teamMembers' => $teamMembers,
            'stats' => $stats,
            'biHighlights' => $biHighlights,
        ]);
    }

    /**
     * Update core business profile information (name, description, industry, website).
     */
    public function update(Request $request): JsonResponse|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $business = $user ? $this->getUserBusiness($user) : null;

        if (!$user || !$business) {
            return $this->respondNotFound($request, 'Business not found for this account.');
        }

        $this->assertCanManageBusiness($user);

        $validated = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'dashboard_display_name' => ['nullable', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'website' => ['nullable', 'url', 'max:255'],
        ]);

        $business->business_name = $validated['business_name'];
        $business->dashboard_display_name = $validated['dashboard_display_name'] ? trim($validated['dashboard_display_name']) : null;
        $business->industry = $validated['industry'] ?? null;
        $business->description = $validated['description'] ?? null;
        $business->website = $validated['website'] ?? null;
        $business->save();

        $this->logActivity('settings_updated', 'Business Profile Updated', "Updated business profile for {$business->business_name}", [
            'metadata' => [
                'business_id' => $business->id,
                'fields' => array_keys($validated),
            ],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Business profile updated successfully.',
                'data' => [
                    'business_name' => $business->business_name,
                    'dashboard_display_name' => $business->dashboard_display_name,
                    'industry' => $business->industry,
                    'description' => $business->description,
                    'website' => $business->website,
                ],
            ]);
        }

        return redirect()->route('dashboard.settings')->with('success', 'Business profile updated successfully.');
    }

    /**
     * Update branding assets, including logo and dashboard display name.
     */
    public function updateBranding(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $business = $user ? $this->getUserBusiness($user) : null;

        if (!$user || !$business) {
            return response()->json(['success' => false, 'error' => 'Business not found'], 404);
        }

        $this->assertCanManageBusiness($user);

        $validated = $request->validate([
            'dashboard_display_name' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        if (!empty($validated['remove_logo'])) {
            if ($business->logo_path && Storage::disk('public')->exists($business->logo_path)) {
                Storage::disk('public')->delete($business->logo_path);
            }

            $business->logo_path = null;
        }

        if ($request->hasFile('logo')) {
            if ($business->logo_path && Storage::disk('public')->exists($business->logo_path)) {
                Storage::disk('public')->delete($business->logo_path);
            }

            $business->logo_path = $request->file('logo')->store('business-logos', 'public');
        }

        if (array_key_exists('dashboard_display_name', $validated)) {
            $business->dashboard_display_name = $validated['dashboard_display_name'] ? trim($validated['dashboard_display_name']) : null;
        }

        $business->save();

        $this->logActivity('settings_updated', 'Branding Updated', "Updated branding assets for {$business->business_name}", [
            'metadata' => [
                'logo_updated' => $request->hasFile('logo'),
                'logo_removed' => !empty($validated['remove_logo']),
                'display_name' => $business->dashboard_display_name,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Branding updated successfully.',
            'logo_url' => $business->logo_path ? Storage::url($business->logo_path) : null,
            'dashboard_display_name' => $business->dashboard_display_name,
        ]);
    }

    /**
     * Persist user-level preferences like theme and accent color.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'error' => 'User not authenticated'], 401);
        }

        $validated = $request->validate([
            'theme' => ['required', 'in:light,dark'],
            'accent_color' => ['nullable', 'regex:/^#?[0-9a-fA-F]{6}$/'],
        ]);

        $accentColor = $validated['accent_color'] ?? '#0d6efd';
        if ($accentColor && !str_starts_with($accentColor, '#')) {
            $accentColor = '#' . $accentColor;
        }

        $user->theme = $validated['theme'];
        $user->accent_color = strtolower($accentColor);
        $user->save();

        $this->logActivity('settings_updated', 'Preferences Updated', 'Updated personal dashboard preferences.', [
            'metadata' => [
                'theme' => $user->theme,
                'accent_color' => $user->accent_color,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Preferences saved successfully.',
            'theme' => $user->theme,
            'accent_color' => $user->accent_color,
        ]);
    }

    /**
     * Regenerate the business invitation code (owner only).
     */
    public function regenerateInvitationCode(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $business = $user ? $this->getUserBusiness($user, mustOwn: true) : null;

        if (!$user || !$business) {
            return response()->json(['success' => false, 'error' => 'Only business owners can regenerate invitation codes.'], 403);
        }

        $newCode = $business->refreshInvitationCode();

        $this->logActivity('settings_updated', 'Invitation Code Regenerated', 'Generated a fresh invitation code for the team.', [
            'metadata' => [
                'business_id' => $business->id,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Invitation code regenerated successfully.',
            'new_code' => $newCode,
        ]);
    }

    /**
     * Transfer business ownership to another team member.
     */
    public function transferOwnership(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $business = $user ? $this->getUserBusiness($user, mustOwn: true) : null;

        if (!$user || !$business) {
            return response()->json(['success' => false, 'error' => 'Only business owners can transfer ownership.'], 403);
        }

        $validated = $request->validate([
            'new_owner_email' => ['required', 'email'],
        ]);

        $newOwner = User::where('email', strtolower($validated['new_owner_email']))->first();

        if (!$newOwner) {
            return response()->json(['success' => false, 'error' => 'We could not find a user with that email address.'], 404);
        }

        if ($newOwner->id === $user->id) {
            return response()->json(['success' => false, 'error' => 'You are already the business owner.'], 422);
        }

        if (!$business->users()->where('user_id', $newOwner->id)->exists()) {
            return response()->json(['success' => false, 'error' => 'The new owner must already be part of this business.'], 422);
        }

        DB::transaction(function () use ($business, $user, $newOwner) {
            $ownerRole = Role::where('name', 'business-owner')->first();
            $adminRole = Role::where('name', 'administrator')->first();

            $business->users()->syncWithoutDetaching([$user->id, $newOwner->id]);
            $business->user_id = $newOwner->id;
            $business->save();

            if ($ownerRole) {
                $newOwner->role_id = $ownerRole->id;
                $newOwner->save();
            }

            if ($adminRole) {
                $user->role_id = $adminRole->id;
                $user->save();
            }
        });

        $this->logActivity('settings_updated', 'Ownership Transferred', "Transferred ownership of {$business->business_name} to {$newOwner->name}.", [
            'metadata' => [
                'business_id' => $business->id,
                'new_owner_id' => $newOwner->id,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => "Ownership transferred to {$newOwner->name}. You will be redirected shortly.",
            'redirect' => route('dashboard'),
        ]);
    }

    /**
     * Permanently delete the current business (owner only).
     */
    public function destroyBusiness(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $business = $user ? $this->getUserBusiness($user, mustOwn: true) : null;

        if (!$user || !$business) {
            return response()->json(['success' => false, 'error' => 'Only business owners can delete the business.'], 403);
        }

        $validated = $request->validate([
            'confirmation' => ['required', 'in:DELETE'],
        ]);

        $this->logActivity('settings_updated', 'Business Deletion Initiated', "Scheduled deletion for {$business->business_name}.", [
            'metadata' => [
                'business_id' => $business->id,
            ],
        ]);

        DB::transaction(function () use ($business, $user) {
            if ($business->logo_path && Storage::disk('public')->exists($business->logo_path)) {
                Storage::disk('public')->delete($business->logo_path);
            }

            $business->users()->detach();
            $business->delete();

            $user->update([
                'setup_completed' => false,
                'setup_completed_at' => null,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Business deleted successfully. We will take you back to the dashboard to start fresh.',
            'redirect' => route('dashboard'),
        ]);
    }

    /**
     * Resolve the active business for a user.
     */
    private function getUserBusiness(User $user, bool $mustOwn = false): ?Business
    {
        if ($mustOwn) {
            return $user->ownedBusinesses()->first();
        }

        if ($user->isBusinessOwner()) {
            return $user->ownedBusinesses()->first();
        }

        return $user->businesses()->first();
    }

    /**
     * Ensure the current user can manage business-level settings.
     */
    private function assertCanManageBusiness(User $user): void
    {
        if (!($user->isBusinessOwner() || $user->isAdministrator())) {
            abort(403, 'You are not allowed to update business settings.');
        }
    }

    /**
     * Return a JSON or redirect response depending on the request type.
     */
    private function respondNotFound(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'error' => $message], 404);
        }

        return redirect()->route('dashboard')->with('error', $message);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Traits\LogsActivity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    use LogsActivity;

    /**
     * Display the user's profile page with edit functionality.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // Get business information for the user
        $business = null;
        if ($user->isBusinessOwner()) {
            $business = $user->primaryBusiness()->first();
        } else {
            // For staff and admin, get the business they're associated with
            $business = $user->businesses()->first();
        }

        return view('profile.index', [
            'user' => $user,
            'business' => $business,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Handle avatar removal first
        if ($request->input('remove_avatar') === '1' && $user->avatar_path) {
            // Delete the old avatar file
            if (Storage::disk('public')->exists($user->avatar_path)) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $user->avatar_path = null;
        }

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete the old avatar if it exists
            if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            // Store the new avatar
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $user->avatar_path = $avatarPath;
        }

        // Update other profile fields
        $validated = $request->safe()->except(['avatar', 'remove_avatar']);
        if (!empty($validated)) {
            $user->fill($validated);
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->logActivity('profile_updated', 'Profile Updated', 'Updated profile information and preferences.');

        return Redirect::route('profile.index')->with('status', 'profile-updated');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        /** @var User $user */
        $user = Auth::user();
        $user->password = Hash::make($validated['password']);
        $user->save();

        $this->logActivity('profile_updated', 'Password Updated', 'Refreshed account security credentials.', [
            'metadata' => [
                'event' => 'password_update',
            ],
        ]);

        return Redirect::route('profile.index')->with('status', 'password-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}

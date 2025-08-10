<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Business;

class SettingsController extends Controller
{
    /**
     * Display the settings page
     */
    public function index()
    {
        $user = Auth::user();
        $business = $user->primaryBusiness()->first();

        if (!$business) {
            return redirect()->route('dashboard')->with('error', 'No business found');
        }

        return view('dashboard-settings.index', compact('user', 'business'));
    }

    /**
     * Update business branding settings
     */
    public function updateBranding(Request $request)
    {
        $request->validate([
            'dashboard_display_name' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user = Auth::user();
        $business = $user->primaryBusiness()->first();

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        DB::beginTransaction();
        try {
            // Handle logo upload
            if ($request->hasFile('logo')) {
                // Delete old logo if exists
                if ($business->logo_path) {
                    Storage::delete($business->logo_path);
                }

                $logoPath = $request->file('logo')->store('business-logos', 'public');
                $business->logo_path = $logoPath;
            }

            // Update display name
            $business->dashboard_display_name = $request->dashboard_display_name;
            $business->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Branding updated successfully',
                'logo_url' => $business->logo_path ? Storage::url($business->logo_path) : null
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update branding'], 500);
        }
    }

    /**
     * Update user preferences
     */
    public function updatePreferences(Request $request)
    {
        $request->validate([
            'theme' => 'required|in:light,dark',
            'accent_color' => 'required|string|max:7', // For hex color codes
        ]);

        $user = Auth::user();

        try {
            $user->update([
                'theme' => $request->theme,
                'accent_color' => $request->accent_color,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Preferences updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update preferences'], 500);
        }
    }

    /**
     * Regenerate business invitation code
     */
    public function regenerateInvitationCode()
    {
        $user = Auth::user();
        $business = $user->primaryBusiness()->first();

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        try {
            $newCode = $business->refreshInvitationCode();

            return response()->json([
                'success' => true,
                'message' => 'Invitation code regenerated successfully',
                'new_code' => $newCode
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to regenerate invitation code'], 500);
        }
    }

    /**
     * Transfer business ownership
     */
    public function transferOwnership(Request $request)
    {
        $request->validate([
            'new_owner_email' => 'required|email|exists:users,email',
        ]);

        $user = Auth::user();
        $business = $user->primaryBusiness()->first();

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        $newOwner = User::where('email', $request->new_owner_email)->first();

        if (!$newOwner) {
            return response()->json(['error' => 'New owner not found'], 404);
        }

        if ($newOwner->id === $user->id) {
            return response()->json(['error' => 'Cannot transfer ownership to yourself'], 400);
        }

        DB::beginTransaction();
        try {
            // Transfer ownership
            $business->user_id = $newOwner->id;
            $business->save();

            // Add new owner to business if not already a member
            $business->addUser($newOwner);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Ownership transferred successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to transfer ownership'], 500);
        }
    }

    /**
     * Delete business (danger zone)
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'confirmation' => 'required|string|in:DELETE',
        ]);

        $user = Auth::user();
        $business = $user->primaryBusiness()->first();

        if (!$business) {
            return response()->json(['error' => 'Business not found'], 404);
        }

        DB::beginTransaction();
        try {
            // Delete business logo if exists
            if ($business->logo_path) {
                Storage::delete($business->logo_path);
            }

            // Delete business and related data
            $business->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Business deleted successfully',
                'redirect' => route('dashboard')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to delete business'], 500);
        }
    }
}

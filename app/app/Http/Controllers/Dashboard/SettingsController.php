<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    /**
     * Display business settings
     */
    public function index()
    {
        $user = Auth::user();
        $business = $this->getUserBusiness($user);

        if (!$business) {
            return redirect()->route('dashboard')->with('error', 'No business found');
        }

        return view('dashboard-settings.index', compact('business'));
    }

    /**
     * Update business settings
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $business = $this->getUserBusiness($user);

        if (!$business) {
            return redirect()->back()->with('error', 'No business found');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'industry' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'website_url' => 'nullable|url|max:255',
            'logo_path' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'primary_color' => 'nullable|string|max:7',
            'secondary_color' => 'nullable|string|max:7',
        ]);

        $updateData = [
            'name' => $request->name,
            'industry' => $request->industry,
            'description' => $request->description,
            'website_url' => $request->website_url,
            'primary_color' => $request->primary_color,
            'secondary_color' => $request->secondary_color,
        ];

        // Handle logo upload
        if ($request->hasFile('logo_path')) {
            // Delete old logo if exists
            if ($business->logo_path) {
                Storage::delete('public/' . $business->logo_path);
            }

            $logoPath = $request->file('logo_path')->store('business-logos', 'public');
            $updateData['logo_path'] = $logoPath;
        }

        $business->update($updateData);

        return redirect()->route('dashboard.settings')->with('success', 'Business settings updated successfully');
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
}

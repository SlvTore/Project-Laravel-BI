<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
}

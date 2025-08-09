<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class AuthActivityListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle login events.
     */
    public function handleLogin(Login $event): void
    {
        $user = $event->user;

        // Get user's business (first business if they have multiple)
        $business = $user->isBusinessOwner()
            ? $user->primaryBusiness()->first()
            : $user->businesses()->first();

        if ($business) {
            ActivityLog::create([
                'business_id' => $business->id,
                'user_id' => $user->id,
                'type' => 'auth',
                'title' => 'User Login',
                'description' => "{$user->name} logged into the system",
                'icon' => 'bi-box-arrow-in-right',
                'color' => 'success',
                'metadata' => json_encode([
                    'user_role' => $user->userRole->name ?? 'Unknown',
                    'login_time' => now(),
                    'ip_address' => request()->ip()
                ])
            ]);
        }
    }

    /**
     * Handle registration events.
     */
    public function handleRegistered(Registered $event): void
    {
        $user = $event->user;

        // For new registrations, we'll try to get business later or create a general log
        ActivityLog::create([
            'business_id' => null, // Will be updated when user joins a business
            'user_id' => $user->id,
            'type' => 'auth',
            'title' => 'New User Registered',
            'description' => "{$user->name} registered a new account",
            'icon' => 'bi-person-plus',
            'color' => 'info',
            'metadata' => json_encode([
                'registration_time' => now(),
                'ip_address' => request()->ip()
            ])
        ]);
    }

    /**
     * Handle logout events.
     */
    public function handleLogout(Logout $event): void
    {
        $user = $event->user;

        if ($user) {
            $business = $user->isBusinessOwner()
                ? $user->primaryBusiness()->first()
                : $user->businesses()->first();

            if ($business) {
                ActivityLog::create([
                    'business_id' => $business->id,
                    'user_id' => $user->id,
                    'type' => 'auth',
                    'title' => 'User Logout',
                    'description' => "{$user->name} logged out of the system",
                    'icon' => 'bi-box-arrow-right',
                    'color' => 'warning',
                    'metadata' => json_encode([
                        'logout_time' => now(),
                        'ip_address' => request()->ip()
                    ])
                ]);
            }
        }
    }
}

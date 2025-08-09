<?php

namespace App\Providers;

use App\Listeners\AuthActivityListener;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register authentication event listeners
        Event::listen(Login::class, [AuthActivityListener::class, 'handleLogin']);
        Event::listen(Registered::class, [AuthActivityListener::class, 'handleRegistered']);
        Event::listen(Logout::class, [AuthActivityListener::class, 'handleLogout']);
    }
}

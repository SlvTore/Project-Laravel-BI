<?php

use App\Http\Controllers\SetupWizardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing.welcome');
})->name('home');

Route::get('/features', function () {
    return view('landing.features');
})->name('features');

Route::get('/about', function () {
    return view('landing.about');
})->name('about');

Route::get('/pricing', function () {
    return view('landing.pricing');
})->name('pricing');

Route::get('/news', function () {
    return view('landing.news');
})->name('news');


// Setup wizard routes - hanya untuk authenticated users
Route::middleware(['auth'])->group(function () {
    Route::get('/setup', [SetupWizardController::class, 'index'])->name('setup.wizard');
    Route::post('/setup', [SetupWizardController::class, 'store'])->name('setup.store');
});

// Dashboard routes - require authentication and setup completion
Route::middleware(['auth', 'setup.completed'])->group(function () {
    // Main Dashboard
    Route::get('/dashboard', function () {
        return view('dashboard-main.index');
    })->name('dashboard');

    // Dashboard - Main/Overview
    Route::get('/dashboard/main', function () {
        return view('dashboard-main.index');
    })->name('dashboard.main');

    // Dashboard - Metrics
    Route::get('/dashboard/metrics', [App\Http\Controllers\MetricsController::class, 'index'])->name('dashboard.metrics');
    Route::get('/dashboard/metrics/create', [App\Http\Controllers\MetricsController::class, 'create'])->name('dashboard.metrics.create');
    Route::post('/dashboard/metrics', [App\Http\Controllers\MetricsController::class, 'store'])->name('dashboard.metrics.store');
    Route::get('/dashboard/metrics/{id}/edit', [App\Http\Controllers\MetricsController::class, 'edit'])->name('dashboard.metrics.edit');
    Route::put('/dashboard/metrics/{id}', [App\Http\Controllers\MetricsController::class, 'update'])->name('dashboard.metrics.update');
    Route::delete('/dashboard/metrics/{id}', [App\Http\Controllers\MetricsController::class, 'destroy'])->name('dashboard.metrics.destroy');

    // Dashboard - Users
    Route::get('/dashboard/users', function () {
        return view('dashboard-users.index');
    })->name('dashboard.users');

    // Dashboard - Settings
    Route::get('/dashboard/settings', function () {
        return view('dashboard-settings.index');
    })->name('dashboard.settings');

    // Dashboard - Feeds (placeholder)
    Route::get('/dashboard/feeds', function () {
        return view('dashboard-main.index')->with('page_title', 'Data Feeds');
    })->name('dashboard.feeds');

    // Dashboard - Notifications (placeholder)
    Route::get('/dashboard/notifications', function () {
        return view('dashboard-main.index')->with('page_title', 'Notifications');
    })->name('dashboard.notifications');

    // Dashboard - Help (placeholder)
    Route::get('/dashboard/help', function () {
        return view('dashboard-main.index')->with('page_title', 'Help & Support');
    })->name('dashboard.help');

    // Profile routes
    Route::get('/profile', function () {
        return view('profile.show');
    })->name('profile.show');

    Route::get('/profile/edit', function () {
        return view('profile.edit');
    })->name('profile.edit');

    Route::patch('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/password', [App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('password.update');
    Route::delete('/profile', [App\Http\Controllers\ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Redirect authenticated users to appropriate page
Route::middleware(['auth'])->group(function () {
    Route::get('/app', function () {
        $user = auth()->user();

        // Jika setup belum completed, redirect ke wizard
        if (!$user->setup_completed) {
            return redirect()->route('setup.wizard');
        }

        // Jika sudah completed, redirect ke dashboard
        return redirect()->route('dashboard');
    })->name('app');
});

require __DIR__.'/auth.php';

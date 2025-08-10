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
    Route::get('/dashboard', [App\Http\Controllers\Dashboard\MainDashboardController::class, 'index'])->name('dashboard');

    // Dashboard - Main/Overview
    Route::get('/dashboard/main', [App\Http\Controllers\Dashboard\MainDashboardController::class, 'index'])->name('dashboard.main');

    // Dashboard - Metrics (accessible to Business Owner, Administrator, Staff)
    Route::middleware(['role:business-owner,administrator,staff'])->group(function () {
        Route::get('/dashboard/metrics', [App\Http\Controllers\MetricsController::class, 'index'])->name('dashboard.metrics');
        Route::get('/dashboard/metrics/create', [App\Http\Controllers\MetricsController::class, 'create'])->name('dashboard.metrics.create');
        Route::post('/dashboard/metrics', [App\Http\Controllers\MetricsController::class, 'store'])->name('dashboard.metrics.store');
        Route::get('/dashboard/metrics/{id}/edit', [App\Http\Controllers\MetricsController::class, 'edit'])->name('dashboard.metrics.edit');
        Route::put('/dashboard/metrics/{id}', [App\Http\Controllers\MetricsController::class, 'update'])->name('dashboard.metrics.update');

        // Only Business Owner and Administrator can delete metrics
        Route::middleware(['role:business-owner,administrator'])->group(function () {
            Route::delete('/dashboard/metrics/{id}', [App\Http\Controllers\MetricsController::class, 'destroy'])->name('dashboard.metrics.destroy');
        });
    });

    // Dashboard - Metric Records
    Route::middleware(['role:business-owner,administrator,staff'])->group(function () {
        Route::get('/dashboard/metrics/{businessMetric}/overview', [App\Http\Controllers\Dashboard\MetricRecordsController::class, 'overview'])->name('dashboard.metrics.overview');
        Route::get('/dashboard/metrics/{businessMetric}/records', [App\Http\Controllers\Dashboard\MetricRecordsController::class, 'show'])->name('dashboard.metrics.records.show');
        Route::get('/dashboard/metrics/{businessMetric}/records/edit', [App\Http\Controllers\Dashboard\MetricRecordsController::class, 'editPage'])->name('dashboard.metrics.records.edit');
        Route::get('/dashboard/metrics/{businessMetric}/records/stats', [App\Http\Controllers\Dashboard\MetricRecordsController::class, 'getTableStats'])->name('dashboard.metrics.records.stats');
        Route::post('/dashboard/metrics/{businessMetric}/records', [App\Http\Controllers\Dashboard\MetricRecordsController::class, 'store'])->name('dashboard.metrics.records.store');
        Route::get('/dashboard/metrics/records/{record}', [App\Http\Controllers\Dashboard\MetricRecordsController::class, 'getRecord'])->name('dashboard.metrics.records.get');
        Route::put('/dashboard/metrics/records/{record}', [App\Http\Controllers\Dashboard\MetricRecordsController::class, 'update'])->name('dashboard.metrics.records.update');
        Route::delete('/dashboard/metrics/records/{record}', [App\Http\Controllers\Dashboard\MetricRecordsController::class, 'destroy'])->name('dashboard.metrics.records.destroy');
        Route::post('/dashboard/metrics/records/bulk-delete', [App\Http\Controllers\Dashboard\MetricRecordsController::class, 'bulkDelete'])->name('dashboard.metrics.records.bulk-delete');
        Route::get('/dashboard/metrics/{businessMetric}/records/export', [App\Http\Controllers\Dashboard\MetricRecordsController::class, 'export'])->name('dashboard.metrics.records.export');
    });

    // New routes for metric calculations
    Route::middleware(['role:business-owner,administrator,staff'])->group(function () {
        Route::get('/dashboard/metrics/{businessMetric}/calculation-data', [App\Http\Controllers\Dashboard\MetricRecordsController::class, 'getCalculationData'])->name('dashboard.metrics.calculation.data');
        Route::get('/dashboard/business/{business}/daily-data', [App\Http\Controllers\Dashboard\MetricRecordsController::class, 'getDailyData'])->name('dashboard.metrics.daily.data');
    });

    // AI Chat routes
    Route::middleware(['role:business-owner,administrator,staff'])->group(function () {
        Route::post('/dashboard/metrics/{businessMetric}/ai-chat', [App\Http\Controllers\Dashboard\MetricRecordsController::class, 'askAI'])->name('dashboard.metrics.ai-chat');
    });

    // Dashboard - Users (only accessible to Business Owner and Administrator)
    Route::middleware(['role:business-owner,administrator'])->group(function () {
        Route::get('/dashboard/users', [App\Http\Controllers\UserManagementController::class, 'index'])->name('dashboard.users');
        Route::get('/users/data', [App\Http\Controllers\UserManagementController::class, 'getUsersData'])->name('users.data');
        Route::post('/users/{user}/promote', [App\Http\Controllers\UserManagementController::class, 'promote'])->name('users.promote');
        Route::delete('/users/{user}/remove', [App\Http\Controllers\UserManagementController::class, 'remove'])->name('users.remove');

        // Business Owner only routes
        Route::middleware(['role:business-owner'])->group(function () {
            Route::get('/users/business-codes', [App\Http\Controllers\UserManagementController::class, 'getBusinessCodes'])->name('users.business-codes');
            Route::post('/users/regenerate-invite-code', [App\Http\Controllers\UserManagementController::class, 'regenerateInvitationCode'])->name('users.regenerate-invite-code');
        });
    });

    // Dashboard - Settings
    Route::middleware(['role:business-owner'])->group(function () {
        Route::get('/dashboard/settings', [\App\Http\Controllers\SettingsController::class, 'index'])->name('dashboard.settings');
        Route::post('/settings/branding', [\App\Http\Controllers\SettingsController::class, 'updateBranding'])->name('settings.branding.update');
        Route::post('/settings/preferences', [\App\Http\Controllers\SettingsController::class, 'updatePreferences'])->name('settings.preferences.update');
        Route::post('/settings/invitation/regenerate', [\App\Http\Controllers\SettingsController::class, 'regenerateInvitationCode'])->name('settings.invitation.regenerate');
        Route::post('/settings/ownership/transfer', [\App\Http\Controllers\SettingsController::class, 'transferOwnership'])->name('settings.ownership.transfer');
        Route::delete('/settings/business', [\App\Http\Controllers\SettingsController::class, 'destroy'])->name('settings.business.destroy');
    });

    // Dashboard - Feeds (accessible to Business Owner, Administrator, Staff)
    Route::middleware(['role:business-owner,administrator,staff'])->group(function () {
        Route::get('/dashboard/feeds', [App\Http\Controllers\FeedsController::class, 'index'])->name('dashboard.feeds');
        Route::get('/dashboard/feeds/activities', [App\Http\Controllers\FeedsController::class, 'getActivitiesData'])->name('dashboard.feeds.activities');
    });

    // Dashboard - Help (accessible to all authenticated users)
    Route::get('/dashboard/help', function () {
        return view('dashboard-main.help')->with('page_title', 'Help & Support');
    })->name('dashboard.help');

    // Help Center routes (accessible to all authenticated users)
    Route::get('/help-center', [App\Http\Controllers\HelpCenterController::class, 'index'])->name('help-center.index');

    // Profile routes (accessible to all authenticated users)
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'index'])->name('profile.index');

    Route::patch('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/password', [App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('password.update');
    Route::delete('/profile', [App\Http\Controllers\ProfileController::class, 'destroy'])->name('profile.destroy');
});

// JSON API for Dashboard Goals and AI Insight (authenticated)
Route::middleware(['auth', 'setup.completed'])->group(function () {
    Route::prefix('dashboard')->group(function () {
        Route::get('/goals', [App\Http\Controllers\Dashboard\GoalController::class, 'index'])->name('dashboard.goals.index');
        Route::post('/goals', [App\Http\Controllers\Dashboard\GoalController::class, 'store'])->name('dashboard.goals.store');
        Route::put('/goals/{goal}', [App\Http\Controllers\Dashboard\GoalController::class, 'update'])->name('dashboard.goals.update');
        Route::delete('/goals/{goal}', [App\Http\Controllers\Dashboard\GoalController::class, 'destroy'])->name('dashboard.goals.destroy');
        Route::post('/goals/{goal}/toggle', [App\Http\Controllers\Dashboard\GoalController::class, 'toggle'])->name('dashboard.goals.toggle');

        Route::post('/ai/insight', [App\Http\Controllers\Dashboard\MainDashboardAIController::class, 'insight'])->name('dashboard.ai.insight');
    });
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

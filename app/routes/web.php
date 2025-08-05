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

    // Dashboard - Metric Records
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

    // New routes for metric calculations
    Route::get('/dashboard/metrics/{businessMetric}/calculation-data', [App\Http\Controllers\Dashboard\MetricRecordsController::class, 'getCalculationData'])->name('dashboard.metrics.calculation.data');
    Route::get('/dashboard/business/{business}/daily-data', [App\Http\Controllers\Dashboard\MetricRecordsController::class, 'getDailyData'])->name('dashboard.metrics.daily.data');

    // AI Chat routes
    Route::post('/dashboard/metrics/{businessMetric}/ai-chat', [App\Http\Controllers\Dashboard\MetricRecordsController::class, 'askAI'])->name('dashboard.metrics.ai-chat');

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

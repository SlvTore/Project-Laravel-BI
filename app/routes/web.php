<?php

use App\Http\Controllers\SetupWizardController;
use App\Http\Controllers\InvitationController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

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

// Invitation routes - public access
Route::get('/invite/{token}', [InvitationController::class, 'handleInvite'])->name('invitation.handle');
Route::get('/invitation/{token}', [InvitationController::class, 'show'])->name('invitation.show');

// Setup wizard routes - hanya untuk authenticated users
Route::middleware(['auth'])->group(function () {
    Route::get('/setup', [SetupWizardController::class, 'index'])->name('setup.wizard');
    Route::post('/setup', [SetupWizardController::class, 'store'])->name('setup.store');

    // Invitation acceptance route (after user is authenticated)
    Route::post('/invitation/accept', [InvitationController::class, 'accept'])->name('invitation.accept');
});

// Dashboard routes - require authentication and setup completion
Route::middleware(['auth', 'setup.completed'])->group(function () {
    // Main Dashboard - accessible to ALL authenticated users
    Route::get('/dashboard', [App\Http\Controllers\Dashboard\MainDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/main', [App\Http\Controllers\Dashboard\MainDashboardController::class, 'index'])->name('dashboard.main');

    // Metrics - accessible to ALL roles (business-owner, administrator, staff, business-investigator)
    Route::get('/dashboard/metrics', [App\Http\Controllers\MetricsController::class, 'index'])->name('dashboard.metrics');

    // Metrics editing - accessible to Business Owner, Administrator, Staff
    Route::middleware(['role:business-owner,administrator,staff'])->group(function () {
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
            Route::get('/users/invitations', [App\Http\Controllers\BusinessInvitationController::class, 'index'])->name('users.invitations.index');
            Route::post('/users/invitations', [App\Http\Controllers\BusinessInvitationController::class, 'store'])->name('users.invitations.store');
            Route::patch('/users/invitations/{invitation}/revoke', [App\Http\Controllers\BusinessInvitationController::class, 'revoke'])->name('users.invitations.revoke');
        });
    });

    // Dashboard - Settings (accessible to ALL roles)
    Route::get('/dashboard/settings', [App\Http\Controllers\Dashboard\SettingsController::class, 'index'])->name('dashboard.settings');
    Route::put('/dashboard/settings', [App\Http\Controllers\Dashboard\SettingsController::class, 'update'])->name('dashboard.settings.update');

    // Dashboard - Users Management (only Business Owner)
    Route::middleware(['role:business-owner'])->group(function () {
        Route::get('/dashboard/users', [App\Http\Controllers\Dashboard\UsersController::class, 'index'])->name('dashboard.users');
        Route::post('/dashboard/users/{user}/promote', [App\Http\Controllers\Dashboard\UsersController::class, 'promote'])->name('dashboard.users.promote');
    });

    // Dashboard - Data Integrity (accessible to Business Owner and Administrator)
    Route::middleware(['role:business-owner,administrator'])->group(function () {
        Route::get('/dashboard/data-integrity', [App\Http\Controllers\Dashboard\DataIntegrityController::class, 'index'])->name('data-integrity.index');
        Route::get('/dashboard/data-integrity/anomalies', [App\Http\Controllers\Dashboard\DataIntegrityController::class, 'detectAnomalies'])->name('data-integrity.detect-anomalies');
        Route::post('/dashboard/data-integrity/recover', [App\Http\Controllers\Dashboard\DataIntegrityController::class, 'recoverData'])->name('data-integrity.recover-data');
        Route::get('/dashboard/data-integrity/download-report', [App\Http\Controllers\Dashboard\DataIntegrityController::class, 'downloadReport'])->name('data-integrity.download-report');
        Route::get('/dashboard/data-integrity/backup-history', [App\Http\Controllers\Dashboard\DataIntegrityController::class, 'getBackupHistory'])->name('data-integrity.backup-history');
    });

    // Dashboard - Activity Log (legacy feeds path kept for backward compatibility redirect)
    Route::redirect('/dashboard/feeds', '/dashboard/activity-log')->name('dashboard.feeds');

    // Help Center
    Route::get('/help', [App\Http\Controllers\HelpCenterController::class, 'index'])->name('help.center');

    // Profile Routes
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'index'])->name('profile.index');
    Route::patch('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/password', [App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('password.update');
    Route::delete('/profile', [App\Http\Controllers\ProfileController::class, 'destroy'])->name('profile.destroy');

    // Dashboard - Activity Log (accessible to Business Owner, Administrator, Staff)
    Route::middleware(['role:business-owner,administrator,staff'])->group(function () {
        Route::get('/dashboard/activity-log', [App\Http\Controllers\Dashboard\LogController::class, 'index'])->name('dashboard.activity-log.index');
        Route::get('/dashboard/activity-log/activities', [App\Http\Controllers\Dashboard\LogController::class, 'getActivitiesData'])->name('dashboard.activity-log.activities');

        // Data Feeds main page
        Route::get('/dashboard/data-feeds', [App\Http\Controllers\Dashboard\DataFeedController::class, 'index'])->name('dashboard.data-feeds.index');

        // API routes for Product Management Modal
        Route::prefix('api/products')->group(function () {
            Route::get('/all', [App\Http\Controllers\Dashboard\ProductController::class, 'getAllProducts'])->name('api.products.all');
            Route::post('/create-draft', [App\Http\Controllers\Dashboard\ProductController::class, 'createDraft'])->name('api.products.create-draft');
            Route::post('/update-title', [App\Http\Controllers\Dashboard\ProductController::class, 'updateTitle'])->name('api.products.update-title');
            Route::delete('/delete', [App\Http\Controllers\Dashboard\ProductController::class, 'deleteByCardId'])->name('api.products.delete');
            Route::get('/get/{cardId}', [App\Http\Controllers\Dashboard\ProductController::class, 'getByCardId'])->name('api.products.get');
            Route::post('/save', [App\Http\Controllers\Dashboard\ProductController::class, 'saveFromModal'])->name('api.products.save');
            Route::post('/save-bom', [App\Http\Controllers\Dashboard\ProductController::class, 'saveBom'])->name('api.products.save-bom');
            Route::get('/search', [App\Http\Controllers\Dashboard\ProductController::class, 'search'])->name('api.products.search');
        });

        // API routes for Sales Transaction Management
        Route::prefix('api/sales-transactions')->group(function () {
            Route::get('/template', [App\Http\Controllers\Dashboard\SalesTransactionController::class, 'downloadTemplate'])->name('api.sales-transactions.template');
            Route::post('/preview', [App\Http\Controllers\Dashboard\SalesTransactionController::class, 'previewImport'])->name('api.sales-transactions.preview');
            Route::post('/import', [App\Http\Controllers\Dashboard\SalesTransactionController::class, 'processImport'])->name('api.sales-transactions.import');

            Route::get('/', [App\Http\Controllers\Dashboard\SalesTransactionController::class, 'index'])->name('api.sales-transactions.index');
            Route::post('/', [App\Http\Controllers\Dashboard\SalesTransactionController::class, 'store'])->name('api.sales-transactions.store');

            Route::get('/{id}', [App\Http\Controllers\Dashboard\SalesTransactionController::class, 'show'])
                ->name('api.sales-transactions.show')
                ->whereNumber('id');
            Route::put('/{id}', [App\Http\Controllers\Dashboard\SalesTransactionController::class, 'update'])
                ->name('api.sales-transactions.update')
                ->whereNumber('id');
            Route::patch('/{id}/status', [App\Http\Controllers\Dashboard\SalesTransactionController::class, 'updateStatus'])
                ->name('api.sales-transactions.update-status')
                ->whereNumber('id');
            Route::delete('/{id}', [App\Http\Controllers\Dashboard\SalesTransactionController::class, 'destroy'])
                ->name('api.sales-transactions.destroy')
                ->whereNumber('id');
        });

        // API routes for Customer Management
        Route::prefix('api/customers')->group(function () {
            Route::get('/search', [App\Http\Controllers\Dashboard\CustomerController::class, 'search'])->name('api.customers.search');
            Route::post('/', [App\Http\Controllers\Dashboard\CustomerController::class, 'store'])->name('api.customers.store');
        });

        // Manual sales input
        Route::post('/dashboard/data-feeds/manual-sales', [App\Http\Controllers\Dashboard\DataFeedController::class, 'storeManualSales'])->name('dashboard.data-feeds.manual-sales.store');

        // File upload for import
        Route::post('/dashboard/data-feeds/upload', [App\Http\Controllers\Dashboard\DataFeedController::class, 'upload'])->name('dashboard.data-feeds.upload');
    Route::post('/dashboard/data-feeds/preview', [App\Http\Controllers\Dashboard\DataFeedController::class, 'preview'])->name('dashboard.data-feeds.preview');
    Route::post('/dashboard/data-feeds/commit', [App\Http\Controllers\Dashboard\DataFeedController::class, 'commit'])->name('dashboard.data-feeds.commit');
    Route::post('/dashboard/data-feeds/auto-create-products', [App\Http\Controllers\Dashboard\DataFeedController::class, 'autoCreateProducts'])->name('dashboard.data-feeds.auto-create-products');
    Route::get('/api/data-feeds/universal-template', [App\Http\Controllers\Dashboard\DataFeedController::class, 'downloadUniversalTemplate'])->name('api.data-feeds.universal-template');

        // Product Catalog CRUD
        Route::get('/dashboard/products', [App\Http\Controllers\Dashboard\ProductController::class, 'index'])->name('dashboard.products.index');
        Route::get('/dashboard/products/datatable', [App\Http\Controllers\Dashboard\ProductController::class, 'datatable'])->name('dashboard.products.datatable');
        Route::post('/dashboard/products', [App\Http\Controllers\Dashboard\ProductController::class, 'store'])->name('dashboard.products.store');
        Route::get('/dashboard/products/{product}', [App\Http\Controllers\Dashboard\ProductController::class, 'show'])->name('dashboard.products.show');
        Route::put('/dashboard/products/{product}', [App\Http\Controllers\Dashboard\ProductController::class, 'update'])->name('dashboard.products.update');
        Route::delete('/dashboard/products/{product}', [App\Http\Controllers\Dashboard\ProductController::class, 'destroy'])->name('dashboard.products.destroy');

        // Data Feeds History datatable (server-side)
        Route::get('/dashboard/data-feeds/history', [App\Http\Controllers\Dashboard\DataFeedController::class, 'history'])->name('dashboard.data-feeds.history');

    // Data Feeds -> OLAP Transform endpoints
    Route::post('/dashboard/data-feeds/{dataFeedId}/transform', [App\Http\Controllers\Dashboard\DataFeedController::class, 'transform'])->name('dashboard.data-feeds.transform');
    Route::get('/dashboard/data-feeds/{dataFeedId}/transform-status', [App\Http\Controllers\Dashboard\DataFeedController::class, 'transformStatus'])->name('dashboard.data-feeds.transform-status');
    Route::post('/dashboard/data-feeds/backfill-facts', [App\Http\Controllers\Dashboard\DataFeedController::class, 'backfillFacts'])->name('dashboard.data-feeds.backfill-facts');
    // Data Feeds uploads list & delete
    Route::get('/dashboard/data-feeds/uploads', [App\Http\Controllers\Dashboard\DataFeedController::class, 'listUploads'])->name('dashboard.data-feeds.uploads');
    Route::delete('/dashboard/data-feeds/{id}', [App\Http\Controllers\Dashboard\DataFeedController::class, 'deleteFeed'])->name('dashboard.data-feeds.delete');
    Route::post('/dashboard/data-feeds/clean-warehouse', [App\Http\Controllers\Dashboard\DataFeedController::class, 'cleanAllWarehouseData'])->name('dashboard.data-feeds.clean-warehouse');

        // Data Feeds Template Downloads
        Route::get('/dashboard/data-feeds/template/{type}', [App\Http\Controllers\Dashboard\DataFeedController::class, 'downloadTemplate'])->name('dashboard.data-feeds.template.download');

    // OLAP Metrics data endpoints
    Route::get('/dashboard/olap/daily-sales', [App\Http\Controllers\Dashboard\OlapMetricsController::class, 'dailySales'])->name('dashboard.olap.daily-sales');
    Route::get('/dashboard/metrics/kpi', [App\Http\Controllers\Dashboard\OlapMetricsController::class, 'kpi'])->name('dashboard.metrics.kpi');
    Route::get('/dashboard/metrics/top-products', [App\Http\Controllers\Dashboard\OlapMetricsController::class, 'topProducts'])->name('dashboard.metrics.top-products');
    Route::get('/dashboard/metrics/trend', [App\Http\Controllers\Dashboard\OlapMetricsController::class, 'trend'])->name('dashboard.metrics.trend');

        // Production Costs Management
        Route::post('/dashboard/products/{product}/production-costs', [App\Http\Controllers\Dashboard\DataFeedController::class, 'storeProductionCost'])->name('dashboard.products.production-costs.store');
        Route::get('/dashboard/products/{product}/production-costs', [App\Http\Controllers\Dashboard\DataFeedController::class, 'getProductionCosts'])->name('dashboard.products.production-costs.index');
        Route::delete('/dashboard/production-costs/{productionCost}', [App\Http\Controllers\Dashboard\DataFeedController::class, 'deleteProductionCost'])->name('dashboard.production-costs.destroy');
    });

    // Dashboard - Help (accessible to all authenticated users)
    Route::get('/dashboard/help', function () {
        return view('home.help')->with('page_title', 'Help & Support');
    })->name('dashboard.help');

    // Help Center routes (accessible to all authenticated users)
    Route::get('/help-center', [App\Http\Controllers\HelpCenterController::class, 'index'])->name('help-center.index');
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
        $user = Auth::user();

        // Jika setup belum completed, redirect ke wizard
        if (!$user->setup_completed) {
            return redirect()->route('setup.wizard');
        }

        // Jika sudah completed, redirect ke dashboard
        return redirect()->route('dashboard');
    })->name('app');
});

require __DIR__.'/auth.php';

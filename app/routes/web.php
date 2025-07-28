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
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

require __DIR__.'/auth.php';

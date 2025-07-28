<?php

use App\Http\Controllers\SetupWizardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/about', function () {
    return view('about');
})->name('about');

Route::get('/Pricing', function () {
    return view('pricing');
})->name('pricing');

Route::get('/News', function () {
    return view('news');
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

<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\PasswordController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('login.store');
});

Route::middleware(['auth', 'account.active'])->group(function () {
    Route::get('/password/change', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/password/change', [PasswordController::class, 'update'])->name('password.update');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::middleware('password.changed')->group(function () {
        Route::get('/', HomeController::class)->name('home');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/export', [DashboardController::class, 'export'])->name('dashboard.export');
        Route::get('/master-data', [MasterDataController::class, 'index'])->name('master-data.index');
        Route::get('/log-perubahan', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::post('/master-data/users', [MasterDataController::class, 'storeUser'])->name('master-data.users.store');
        Route::patch('/master-data/users/{user}/status', [MasterDataController::class, 'toggleUser'])->name('master-data.users.status');
        Route::post('/master-data/sources', [MasterDataController::class, 'storeSource'])->name('master-data.sources.store');
        Route::patch('/master-data/sources/{source}/status', [MasterDataController::class, 'toggleSource'])->name('master-data.sources.status');
        Route::post('/master-data/categories', [MasterDataController::class, 'storeCategory'])->name('master-data.categories.store');
        Route::patch('/master-data/categories/{category}/status', [MasterDataController::class, 'toggleCategory'])->name('master-data.categories.status');
        Route::post('/password-resets/{user}', [PasswordController::class, 'reset'])->middleware('throttle:10,1')->name('password.resets.store');

        // /leads/{category}  ->  halaman Input Data Leads per kategori layanan.
        // scopeBindings: {lead} hanya cocok kalau memang milik {category}, selain itu 404.
        Route::prefix('leads/{category}')->name('leads.')->scopeBindings()->group(function () {
            Route::get('/', [LeadController::class, 'index'])->name('index');
            Route::post('/', [LeadController::class, 'store'])->name('store');
            Route::put('/{lead}', [LeadController::class, 'update'])->name('update');
            Route::delete('/{lead}', [LeadController::class, 'destroy'])->name('destroy');
        });
    });
});

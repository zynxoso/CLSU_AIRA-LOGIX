<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\SuperAdminDashboardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IctServiceRequestController;
use App\Http\Controllers\AiConsumptionController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\AnalyticsController;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::get('/terms', function () {
    return Inertia::render('legal/terms');
})->name('terms');

Route::get('/privacy', function () {
    return Inertia::render('legal/privacy');
})->name('privacy');

Route::middleware(['auth'])->group(function () {
    Route::middleware('can:access-dashboard')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('dashboard/reports', [AnalyticsController::class, 'index'])->name('ict.reports');
        Route::get('requests/create', [IctServiceRequestController::class, 'intake'])->name('ict.create');
        Route::put('requests/{id}', [IctServiceRequestController::class, 'update'])->name('ict.update');
        Route::delete('requests/{id}', [IctServiceRequestController::class, 'destroy'])->name('ict.destroy');
        Route::post('requests/{id}/restore', [IctServiceRequestController::class, 'restore'])->name('ict.restore');
        Route::get('requests/{id}/download', [IctServiceRequestController::class, 'download'])->name('ict.download');
        Route::get('requests/{id}/edit', [IctServiceRequestController::class, 'edit'])->name('ict.edit');
        Route::get('dashboard/export/csv', [IctServiceRequestController::class, 'export'])->name('ict.export-csv');
        Route::get('dashboard/export/xlsx', [IctServiceRequestController::class, 'exportXlsx'])->name('ict.export-xlsx');
        Route::get('dashboard/export/bulk-docx', [IctServiceRequestController::class, 'exportBulkDocx'])->name('ict.export-bulk-docx');
    });

    Route::middleware('can:access-smart-scan')->group(function () {
        Route::get('dashboard/smart-scan', [IctServiceRequestController::class, 'smartScan'])->name('ict.smart-scan');
        Route::get('dashboard/intake', [IctServiceRequestController::class, 'intake'])->name('ict.intake');
        Route::post('dashboard/intake', [IctServiceRequestController::class, 'store'])->name('ict.store');
        Route::post('api/extract', [IctServiceRequestController::class, 'extract'])->name('api.ict.extract');
        Route::get('api/extract/{jobId}/status', [IctServiceRequestController::class, 'checkStatus'])->name('api.ict.extract.status');
        Route::post('api/requests', [IctServiceRequestController::class, 'storeManual'])->name('api.ict.store-manual');
        Route::post('api/requests/batch', [IctServiceRequestController::class, 'storeBatch'])->name('api.ict.store-batch');
    });

    Route::middleware('can:access-documentation')->group(function () {
        Route::get('dashboard/documentation', [DocumentationController::class, 'index'])->name('documentation.index');
    });

    Route::middleware('can:access-ai-consumption')->group(function () {
        Route::get('dashboard/ai-consumption', [AiConsumptionController::class, 'index'])->name('ai.consumption');
    });

    Route::middleware('role:super_admin')->prefix('superadmin')->name('superadmin.')->group(function () {
        Route::get('dashboard', [SuperAdminDashboardController::class, 'index'])->name('dashboard');

        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserManagementController::class, 'index'])->name('index');
            Route::post('/', [UserManagementController::class, 'store'])->name('store');
            Route::put('/{user}', [UserManagementController::class, 'update'])->name('update');
            Route::delete('/{user}', [UserManagementController::class, 'destroy'])->name('destroy');
        });
    });

    // API Routes for SMART Scan
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

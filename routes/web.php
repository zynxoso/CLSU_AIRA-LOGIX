<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\SuperAdminDashboardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IctServiceRequestController;
use App\Http\Controllers\MisoAccomplishmentController;
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
        Route::get('dashboard/miso/export/csv', [MisoAccomplishmentController::class, 'export'])->name('miso.export-csv');
        Route::get('dashboard/miso/export/xlsx', [MisoAccomplishmentController::class, 'exportXlsx'])->name('miso.export-xlsx');

        Route::get('miso-accomplishments/create', [MisoAccomplishmentController::class, 'intake'])->name('miso.create');
        Route::get('miso-accomplishments/{id}/edit', [MisoAccomplishmentController::class, 'edit'])->name('miso.edit');
        Route::post('miso-accomplishments', [MisoAccomplishmentController::class, 'store'])->name('miso.store');
        Route::put('miso-accomplishments/{id}', [MisoAccomplishmentController::class, 'update'])->name('miso.update');
        Route::delete('miso-accomplishments/{id}', [MisoAccomplishmentController::class, 'destroy'])->name('miso.destroy');
        Route::post('miso-accomplishments/{id}/restore', [MisoAccomplishmentController::class, 'restore'])->name('miso.restore');
        Route::get('miso-accomplishments/{id}/download', [MisoAccomplishmentController::class, 'download'])->name('miso.download');
    });

    Route::middleware('can:access-smart-scan')->group(function () {
        Route::get('dashboard/smart-scan', [IctServiceRequestController::class, 'smartScan'])->name('ict.smart-scan');
        Route::get('dashboard/miso-smart-scan', [MisoAccomplishmentController::class, 'smartScan'])->name('miso.smart-scan');
        Route::get('dashboard/intake', [IctServiceRequestController::class, 'intake'])->name('ict.intake');
        Route::post('dashboard/intake', [IctServiceRequestController::class, 'store'])->name('ict.store');
        Route::post('api/extract', [IctServiceRequestController::class, 'extract'])->name('api.ict.extract'); //->middleware('throttle:15,1')
        Route::get('api/extract/{jobId}/status', [IctServiceRequestController::class, 'checkStatus'])->name('api.ict.extract.status'); //->middleware('throttle:60,1')
        Route::post('api/requests', [IctServiceRequestController::class, 'storeManual'])->name('api.ict.store-manual'); //->middleware('throttle:20,1')
        Route::post('api/requests/batch', [IctServiceRequestController::class, 'storeBatch'])->name('api.ict.store-batch'); //->middleware('throttle:20,1')
        Route::post('api/miso/extract', [MisoAccomplishmentController::class, 'extract'])->name('api.miso.extract'); //->middleware('throttle:15,1')
        Route::get('api/miso/extract/{jobId}/status', [MisoAccomplishmentController::class, 'checkStatus'])->name('api.miso.extract.status'); //->middleware('throttle:60,1')
        Route::post('api/miso/requests', [MisoAccomplishmentController::class, 'storeManual'])->name('api.miso.store-manual'); //->middleware('throttle:20,1')
        Route::post('api/miso/requests/batch', [MisoAccomplishmentController::class, 'storeBatch'])->name('api.miso.store-batch'); //->middleware('throttle:20,1')
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

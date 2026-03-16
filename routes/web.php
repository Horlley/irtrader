<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TradeController;
use App\Http\Controllers\ImportController;

Route::get('/', [UploadController::class, 'index']);

Route::post('/upload', [UploadController::class, 'upload'])->name('upload.pdf');

Route::get('/report/monthly', [ReportController::class, 'monthly']);

/*
|--------------------------------------------------------------------------
| Páginas do sistema
|--------------------------------------------------------------------------
*/

Route::view('/dashboard', 'pages.dashboard');
Route::view('/imports', 'pages.imports');
Route::get('/trades', [TradeController::class, 'index'])->name('trades.index');
Route::view('/taxes', 'pages.taxes');
Route::view('/settings', 'pages.settings');

Route::get('/api/dashboard', [DashboardController::class, 'stats']);
Route::get('/api/dashboard/chart', [DashboardController::class, 'chart']);

Route::resource('trades', TradeController::class)
    ->only([
        'index',
        'edit',
        'update',
        'destroy'
    ]);

Route::get('/imports', [ImportController::class,'index'])->name('imports.index');
Route::post('/imports/upload', [UploadController::class,'upload'])->name('imports.upload');
Route::get('/imports/{id}', [ImportController::class,'show']);
Route::delete('/imports/{id}', [ImportController::class,'destroy']);
Route::get('/imports/{id}/trades', [ImportController::class,'trades']);
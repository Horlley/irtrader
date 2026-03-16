<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


Route::get('/', [App\Http\Controllers\UploadController::class, 'index']);
Route::post('/upload', [App\Http\Controllers\UploadController::class, 'upload'])->name('upload.pdf');
Route::get('/report/monthly',[ReportController::class,'monthly']);
Route::get('/dashboard', function () {
    return view('pages.dashboard');
});
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceRecordController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;

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
Route::redirect('/', '/login');


Route::get('/admin/login', [AdminController::class, 'login'])->name('admin.login');
Route::post('/admin/login',[Laravel\Fortify\Http\Controllers\AuthenticatedSessionController::class, 'store'])->name('admin.login.post');
Route::get('/admin/logout', [Laravel\Fortify\Http\Controllers\AuthenticatedSessionController::class, 'destroy'])->name('admin.logout');

Route::middleware('auth')->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/punch-in', [AttendanceController::class, 'punchIn'])->name('attendance.punch-in');
    Route::post('/attendance/punch-out', [AttendanceController::class, 'punchOut'])->name('attendance.punch-out');
    Route::post('/attendance/rest-in', [AttendanceController::class, 'restIn'])->name('attendance.rest-in');
    Route::post('/attendance/rest-out', [AttendanceController::class, 'restOut'])->name('attendance.rest-out');

    Route::get('/attendance-list', [AttendanceRecordController::class, 'index'])->name('attendance-records.index');
    Route::get('/attendance/detail', [AttendanceRecordController::class, 'detail'])->name('attendance-records.detail');
});

Route::middleware('admin')->group(function () {
    Route::get('/admin/attendance/list', [AdminController::class, 'index'])->name('admin.index');
});
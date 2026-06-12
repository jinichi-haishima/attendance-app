<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceRecordController;
use App\Http\Controllers\AttendanceRequestController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\AttendanceApprovalController;

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

// 【ログインなしでOK】
// 一般ユーザー用ログイン（Fortifyが自動生成していない場合はここに書く）
// 管理者用ログイン画面とログイン処理
Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminController::class, 'login'])->name('admin.login');
    Route::post('/login', [Laravel\Fortify\Http\Controllers\AuthenticatedSessionController::class, 'store'])->name('admin.login.post');
});

// 【ログイン必須（authミドルウェア）】
Route::middleware(['auth', 'can:admin-only'])->prefix('admin')->group(function () {

// --- 👨‍💼 管理者用の画面ルート ---
    Route::post('/admin/logout', [Laravel\Fortify\Http\Controllers\AuthenticatedSessionController::class, 'destroy'])->name('admin.logout');
    Route::get('attendance/list', [AdminController::class, 'index'])->name('admin.index');
    Route::get('attendance', [AdminController::class, 'detail'])->name('admin.detail');
    Route::post('attendance/update', [AdminController::class, 'update'])->name('admin.attendance.update');
    Route::get('staff/list', [StaffController::class, 'index'])->name('admin.staff.list');
    Route::get('attendance/staff/{id}', [StaffController::class, 'show'])->name('admin.staff.show');
    Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AttendanceApprovalController::class, 'show'])->name('admin.attendance.approval');
    Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [AttendanceApprovalController::class, 'approve'])->name('admin.attendance.approval.update');
});

Route::middleware(['auth'])->group(function () {
    // --- 👤 一般ユーザー用の画面ルート ---
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/punch-in', [AttendanceController::class, 'punchIn'])->name('attendance.punch-in');
    Route::post('/attendance/punch-out', [AttendanceController::class, 'punchOut'])->name('attendance.punch-out');
    Route::post('/attendance/rest-in', [AttendanceController::class, 'restIn'])->name('attendance.rest-in');
    Route::post('/attendance/rest-out', [AttendanceController::class, 'restOut'])->name('attendance.rest-out');
    Route::get('/attendance-list', [AttendanceRecordController::class, 'index'])->name('attendance-records.index');
    Route::get('/attendance/detail', [AttendanceRecordController::class, 'detail'])->name('attendance-records.detail');
    Route::post('/attendance/store', [AttendanceRecordController::class, 'store'])->name('attendance-records.store');
    Route::get('/stamp_correction_request/list', [AttendanceRequestController::class, 'index'])->name('attendance-requests.index');
});
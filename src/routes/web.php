<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceRecordController;
use App\Http\Controllers\AttendanceRequestController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\AttendanceApprovalController;
use App\Http\Controllers\AttendanceReportController;


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

//APIルートは routes/api.php に定義するため、ここにはWeb画面用のルートを定義する


// 【ログインなしでOK】
// 一般ユーザー用ログイン（Fortifyが自動生成していない場合はここに書く）
Route::redirect('/', '/login');

// 管理者用ログイン画面とログイン処理
Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminController::class, 'login'])->name('admin.login');
    Route::post('/login', [Laravel\Fortify\Http\Controllers\AuthenticatedSessionController::class, 'store'])->name('admin.login.post');

});

// 【ログイン必須（authミドルウェア）】
Route::middleware(['auth', 'verified','can:admin-only'])->prefix('admin')->group(function () {

// --- 👨‍💼 管理者用の画面ルート ---
    Route::post('/logout', [Laravel\Fortify\Http\Controllers\AuthenticatedSessionController::class, 'destroy'])->name('admin.logout');
    Route::get('attendance/list', [AdminController::class, 'index'])->name('admin.index');
    Route::get('attendance/{id}', [AdminController::class, 'detail'])->name('admin.detail');
    Route::post('attendance/update', [AdminController::class, 'update'])->name('admin.attendance.update');
    Route::get('staff/list', [StaffController::class, 'index'])->name('admin.staff.list');
    Route::get('attendance/staff/{id}', [StaffController::class, 'show'])->name('admin.staff.show');
    Route::get('staff/{id}/csv', [StaffController::class, 'downloadCsv'])->name('admin.staff.csv');
    Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AttendanceApprovalController::class, 'show'])->name('admin.attendance.approval');
    Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [AttendanceApprovalController::class, 'approve'])->name('admin.attendance.approval.update');
});

Route::middleware(['auth','verified'])->group(function () {
    // --- 👤 一般ユーザー用の画面ルート ---
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/punch-in', [AttendanceController::class, 'punchIn'])->name('attendance.punch-in');
    Route::post('/attendance/punch-out', [AttendanceController::class, 'punchOut'])->name('attendance.punch-out');
    Route::post('/attendance/rest-in', [AttendanceController::class, 'restIn'])->name('attendance.rest-in');
    Route::post('/attendance/rest-out', [AttendanceController::class, 'restOut'])->name('attendance.rest-out');
    Route::get('/attendance-list', [AttendanceRecordController::class, 'index'])->name('attendance-records.index');
    Route::get('/attendance/detail/{id}', [AttendanceRecordController::class, 'detail'])->name('attendance-records.detail');
    Route::post('/attendance/store', [AttendanceRecordController::class, 'store'])->name('attendance-records.store');
    Route::get('/stamp_correction_request/list', [AttendanceRequestController::class, 'index'])->name('attendance-requests.index');
    Route::get('/attendance/report', [AttendanceReportController::class, 'index'])->name('attendance.report');
});

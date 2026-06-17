<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// 🔓 誰でもアクセスできるルート（一覧取得・詳細取得）
Route::get('v1/attendance-records', ['App\Http\Controllers\Api\V1\AttendanceRecordController', 'index']);
Route::get('v1/attendance-records/{attendanceRecord}', ['App\Http\Controllers\Api\V1\AttendanceRecordController', 'show']);

// 🔒 認証ユーザーのみアクセスできるルート（作成・更新・削除）を復活させる
Route::middleware('auth:sanctum')->prefix('v1')->group(function(){
    Route::post('attendance-records', ['App\Http\Controllers\Api\V1\AttendanceRecordController', 'store']);
    Route::put('attendance-records/{attendanceRecord}', ['App\Http\Controllers\Api\V1\AttendanceRecordController', 'update']);
    Route::delete('attendance-records/{attendanceRecord}', ['App\Http\Controllers\Api\V1\AttendanceRecordController', 'destroy']);
});
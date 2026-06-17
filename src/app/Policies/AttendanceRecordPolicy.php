<?php

namespace App\Policies;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttendanceRecordPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AttendanceRecord  $attendanceRecord
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, AttendanceRecord $attendanceRecord)
    {
        //
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AttendanceRecord  $attendanceRecord
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, AttendanceRecord $attendanceRecord)
    {
        // ⭕ 自分の勤怠レコードか、管理者権限を持つユーザーのみ更新可能
        return $user->id === $attendanceRecord->user_id || $user->is_admin;
    }

    public function delete(User $user, AttendanceRecord $attendanceRecord)
    {
        // ⭕ 同上
        return $user->id === $attendanceRecord->user_id || $user->is_admin;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AttendanceRecord  $attendanceRecord
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, AttendanceRecord $attendanceRecord)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AttendanceRecord  $attendanceRecord
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, AttendanceRecord $attendanceRecord)
    {
        //
    }
}

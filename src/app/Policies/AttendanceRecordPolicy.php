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
    public function before(User $user, string $ability)
    {
        if ($user->is_admin) {
            return true; // 👑 管理者なら何でも一発許可！
        }
    }

    public function update(User $user, AttendanceRecord $attendanceRecord)
    {
        return $user->id === $attendanceRecord->user_id; 
    }

    public function delete(User $user, AttendanceRecord $attendanceRecord)
    {
        return $user->id === $attendanceRecord->user_id; 
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

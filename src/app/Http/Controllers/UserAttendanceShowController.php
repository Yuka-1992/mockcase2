<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UserAttendanceShowController extends Controller
{
    public function show(Request $request)
    {
        $userId = Auth::id();
        $date = $request->query('date', Carbon::today()->toDateString());
        
        $attendance = DB::table('attendances')
            ->where('user_id', $userId)
            ->where('work_date', $date)
            ->first();
        
        // TODO: Parse break times from database
        $breaks = [];
        
        return view('user.show', [
            'attendance' => $attendance,
            'breaks' => $breaks,
        ]);
    }

    /**
     * Display attendance detail by ID
     */
    public function detail(Request $request, $id)
    {
        $userId = Auth::id();
        
        // If id is 0, use date parameter to find/create attendance view
        $date = null;
        if ($id == 0) {
            $date = $request->query('date', Carbon::today()->toDateString());
            
            $attendance = DB::table('attendances')
                ->where('user_id', $userId)
                ->where('work_date', $date)
                ->first();
            
            // If no attendance record, create a dummy object with the date
            if (!$attendance) {
                $attendance = (object) [
                    'id' => null,
                    'user_id' => $userId,
                    'work_date' => $date,
                    'clock_in' => null,
                    'clock_out' => null,
                    'break_time' => null,
                    'work_time' => null,
                    'note' => null,
                ];
            }
        } else {
            $attendance = DB::table('attendances')
                ->where('id', $id)
                ->where('user_id', $userId)
                ->first();
            
            if (!$attendance) {
                abort(404, '勤怠データが見つかりませんでした');
            }
            $date = $attendance->work_date;
        }
        
        // Pending correction request for this user/date
        $pendingRequest = DB::table('attendance_correction_requests')
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->where(function($q) use ($attendance, $date) {
                if ($attendance && $attendance->id) {
                    $q->orWhere('attendance_id', $attendance->id);
                }
                if ($date) {
                    $q->orWhere('target_date', $date);
                }
            })
            ->orderBy('created_at', 'desc')
            ->first();
        
        // Get latest correction request (approved or rejected) if no pending request
        $latestRequest = null;
        $approvedRequest = null;
        if (!$pendingRequest) {
            $latestRequest = DB::table('attendance_correction_requests')
                ->where('user_id', $userId)
                ->where(function($q) use ($attendance, $date) {
                    if ($attendance && $attendance->id) {
                        $q->orWhere('attendance_id', $attendance->id);
                    }
                    if ($date) {
                        $q->orWhere('target_date', $date);
                    }
                })
                ->whereIn('status', ['approved', 'rejected'])
                ->orderBy('created_at', 'desc')
                ->first();
            
            // 承認済みのリクエストを取得
            if ($latestRequest && $latestRequest->status === 'approved') {
                $approvedRequest = $latestRequest;
            }
        }
        
        // Get any correction request (pending or latest)
        $correctionRequest = $pendingRequest ?? $latestRequest;
        
        // 承認済みの場合は、attendancesテーブルの最新データを取得
        if ($approvedRequest && $attendance) {
            $attendance = DB::table('attendances')
                ->where('user_id', $userId)
                ->where('work_date', $date)
                ->first();
        }
        
        // Get user name
        $user = Auth::user();
        $userName = $user ? $user->name : '';
        
        // Parse break times from database
        $breaks = [];
        if ($attendance && !empty($attendance->break_times)) {
            $breaks = json_decode($attendance->break_times, true);
        }
        
        return view('user.show', [
            'attendance' => $attendance,
            'breaks' => $breaks,
            'pendingRequest' => $pendingRequest,
            'correctionRequest' => $correctionRequest,
            'approvedRequest' => $approvedRequest,
            'userName' => $userName,
            'workDate' => $date ?? ($attendance->work_date ?? Carbon::today()->toDateString()),
        ]);
    }
}
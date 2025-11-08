<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UserAttendanceRequestController extends Controller
{
    /**
     * Display user's correction request list (pending and approved)
     */
    public function index()
    {
        $userId = Auth::id();

        $pendingRequests = DB::table('attendance_correction_requests')
            ->join('users', 'users.id', '=', 'attendance_correction_requests.user_id')
            ->where('attendance_correction_requests.user_id', $userId)
            ->where('attendance_correction_requests.status', 'pending')
            ->select(
                'attendance_correction_requests.*',
                'users.name as user_name'
            )
            ->orderBy('attendance_correction_requests.created_at', 'desc')
            ->get();

        $approvedRequests = DB::table('attendance_correction_requests')
            ->join('users', 'users.id', '=', 'attendance_correction_requests.user_id')
            ->where('attendance_correction_requests.user_id', $userId)
            ->where('attendance_correction_requests.status', 'approved')
            ->select(
                'attendance_correction_requests.*',
                'users.name as user_name'
            )
            ->orderBy('attendance_correction_requests.approved_at', 'desc')
            ->get();

        return view('user.request', [
            'pendingRequests' => $pendingRequests,
            'approvedRequests' => $approvedRequests,
        ]);
    }
}
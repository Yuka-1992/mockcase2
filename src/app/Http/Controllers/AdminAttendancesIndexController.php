<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminAttendancesIndexController extends Controller
{
    public function index(Request $request)
    {
        $day = $request->query('date') ? Carbon::parse($request->query('date')) : Carbon::today();
        $dateStr = $day->toDateString();

        $list = DB::table('attendances')
            ->join('users', 'users.id', '=', 'attendances.user_id')
            ->where('attendances.work_date', $dateStr)
            ->select(
                'users.id as user_id',
                'users.name',
                'attendances.clock_in',
                'attendances.clock_out',
                'attendances.break_time',
                'attendances.work_time'
            )
            ->orderBy('users.id')
            ->get();

        return view('admin.attendance.index', [
            'day' => $day,
            'prevDate' => $day->copy()->subDay()->toDateString(),
            'nextDate' => $day->copy()->addDay()->toDateString(),
            'list' => $list,
        ]);
    }
}

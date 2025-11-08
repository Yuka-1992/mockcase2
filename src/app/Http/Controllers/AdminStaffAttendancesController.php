<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminStaffAttendancesController extends Controller
{
    public function index(Request $request)
    {
        $userId = (int)$request->route('id');
        if (!$userId) {
            abort(404);
        }

        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user) {
            abort(404);
        }

        // month=YYYY-MM（未指定なら今月）
        $monthStr = $request->query('month');
        try {
            $month = $monthStr ? Carbon::parse($monthStr . '-01')->startOfMonth() : Carbon::now()->startOfMonth();
        } catch (\Throwable $e) {
            $month = Carbon::now()->startOfMonth();
        }
        $start = $month->copy();
        $end   = $month->copy()->endOfMonth();

        // 当月の勤怠をまとめて取得し、日付キー連想配列に
        $attendances = DB::table('attendances')
            ->where('user_id', $userId)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy('work_date');

        // 月内の全日 rows 構築
        $rows = [];
        for ($d = 0; $d < $start->daysInMonth; $d++) {
            $date = $start->copy()->addDays($d);
            $key = $date->toDateString();
            $rows[] = [
                'date' => $date,
                'attendance' => $attendances[$key] ?? null,
            ];
        }

        // 前月/翌月（YYYY-MM）
        $prevMonth = $month->copy()->subMonth()->format('Y-m');
        $nextMonth = $month->copy()->addMonth()->format('Y-m');

        return view('admin.staff.attendances', [
            'user' => $user,
            'month' => $month,
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
            'rows' => $rows,
        ]);
    }
}
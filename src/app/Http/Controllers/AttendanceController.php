<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $today = Carbon::today()->toDateString();
        $att = DB::table('attendances')->where('user_id', $userId)->where('work_date', $today)->first();

        $status = 'off';
        if ($att) {
            if (!is_null($att->clock_out)) {
                $status = 'done';
            } elseif (!is_null($att->break_started_at)) {
                $status = 'break';
            } else {
                $status = 'working';
            }
        }

        return view('user.clock', compact('status'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'action' => 'required|in:clock_in,clock_out,break_in,break_out',
        ]);

        $userId = Auth::id();
        $now = Carbon::now();
        $today = $now->toDateString();

        $att = DB::table('attendances')->where('user_id', $userId)->where('work_date', $today)->lockForUpdate()->first();

        switch ($request->input('action')) {
            case 'clock_in':
                if ($att) {
                    return back()->withErrors(['action' => '本日は既に出勤記録があります。']);
                }
                DB::table('attendances')->insert([
                    'user_id' => $userId,
                    'work_date' => $today,
                    'clock_in' => $now,
                    'break_time' => 0,
                    'work_time' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                return back()->with('status', '出勤を記録しました。');

            case 'break_in':
                if (!$att || $att->clock_out) {
                    return back()->withErrors(['action' => '出勤中ではありません。']);
                }
                if ($att->break_started_at) {
                    return back()->withErrors(['action' => '既に休憩中です。']);
                }
                DB::table('attendances')->where('id', $att->id)->update([
                    'break_started_at' => $now,
                    'updated_at' => $now,
                ]);
                return back()->with('status', '休憩を開始しました。');

            case 'break_out':
                if (!$att || $att->clock_out) {
                    return back()->withErrors(['action' => '出勤中ではありません。']);
                }
                if (!$att->break_started_at) {
                    return back()->withErrors(['action' => '休憩中ではありません。']);
                }
                $breakStart = Carbon::parse($att->break_started_at);
                $addMinutes = intdiv($breakStart->diffInSeconds($now), 60);
                DB::table('attendances')->where('id', $att->id)->update([
                    'break_time' => ($att->break_time ?? 0) + $addMinutes,
                    'break_started_at' => null,
                    'updated_at' => $now,
                ]);
                return back()->with('status', '休憩を終了しました。');

            case 'clock_out':
                if (!$att) {
                    return back()->withErrors(['action' => '出勤記録がありません。']);
                }
                if ($att->clock_out) {
                    return back()->withErrors(['action' => '既に退勤済みです。']);
                }
                // 休憩中のまま退勤する場合、直前の休憩を加算
                $breakMinutes = $att->break_time ?? 0;
                if ($att->break_started_at) {
                    $breakStart = Carbon::parse($att->break_started_at);
                    $breakMinutes += intdiv($breakStart->diffInSeconds($now), 60);
                }
                $clockIn = Carbon::parse($att->clock_in);
                $elapsed = intdiv($clockIn->diffInSeconds($now), 60); // 総経過分（秒切り捨て）
                $workMinutes = max(0, $elapsed - $breakMinutes);
                DB::table('attendances')->where('id', $att->id)->update([
                    'clock_out' => $now,
                    'break_time' => $breakMinutes,
                    'work_time' => $workMinutes,
                    'break_started_at' => null,
                    'updated_at' => $now,
                ]);
                return back()->with('status', '退勤を記録しました。');
        }

        return back();
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminStaffAttendanceController extends Controller
{
    // 管理者用：勤怠詳細表示
    public function show(Request $request)
    {
        $userId = (int)$request->route('id');
        $date   = $request->query('date'); // YYYY-MM-DD

        if (!$userId || !$date) {
            abort(404);
        }

        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user) abort(404);

        $attendance = DB::table('attendances')
            ->where('user_id', $userId)
            ->where('work_date', $date)
            ->first();

        // 休憩時間を取得
        $breakTimes = null;
        if ($attendance && !empty($attendance->break_times)) {
            $breakTimes = json_decode($attendance->break_times, true);
        }

        return view('admin.staff.show', [
            'user' => $user,
            'attendance' => $attendance,
            'breakTimes' => $breakTimes,
        ]);
    }

    // 管理者用：勤怠更新（複数休憩の合計分と実働分の再計算）
    public function update(Request $request)
    {
        $data = $request->validate([
            'date_year'   => ['nullable','integer','min:1970','max:2100'],
            'date_month'  => ['nullable','integer','min:1','max:12'],
            'date_day'    => ['nullable','integer','min:1','max:31'],
            'work_date'   => ['nullable','date'],
            'clock_in'    => ['nullable','date_format:H:i'],
            'clock_out'   => ['nullable','date_format:H:i'],
            'break_start' => ['array'],
            'break_start.*' => ['nullable','date_format:H:i'],
            'break_end'   => ['array'],
            'break_end.*'   => ['nullable','date_format:H:i'],
            'note'        => ['required','string','max:1000'],
            'name'        => ['nullable','string','max:255'],
        ], [
            'date_year.integer' => '年が不適切な値です',
            'date_year.min' => '年が不適切な値です',
            'date_year.max' => '年が不適切な値です',
            'date_month.integer' => '月が不適切な値です',
            'date_month.min' => '月が不適切な値です',
            'date_month.max' => '月が不適切な値です',
            'date_day.integer' => '日が不適切な値です',
            'date_day.min' => '日が不適切な値です',
            'date_day.max' => '日が不適切な値です',
            'work_date.date' => '対象日が不適切な値です',
            'clock_in.date_format' => '出勤時間が不適切な値です',
            'clock_out.date_format' => '退勤時間が不適切な値です',
            'break_start.*.date_format' => '休憩開始時間が不適切な値です',
            'break_end.*.date_format' => '休憩終了時間が不適切な値です',
            'note.required' => '備考を入力してください',
            'note.max' => '備考は1000文字以内で入力してください',
            'name.max' => '名前は255文字以内で入力してください',
        ]);

        $userId = (int)$request->route('id');

        // 日付の決定
        if (isset($data['date_year'], $data['date_month'], $data['date_day'])) {
            $day = Carbon::create(
                (int)$data['date_year'],
                (int)$data['date_month'],
                (int)$data['date_day']
            )->startOfDay();
        } else {
            $day = Carbon::parse($data['work_date'] ?? now()->toDateString())->startOfDay();
        }
        $workDate = $day->toDateString();

        // 休憩時間の計算（break_start[]とbreak_end[]から）
        $breakTotal = 0;
        $breakStarts = $data['break_start'] ?? [];
        $breakEnds = $data['break_end'] ?? [];
        $breakTimes = [];
        
        for ($i = 0; $i < max(count($breakStarts), count($breakEnds)); $i++) {
            if (!empty($breakStarts[$i]) && !empty($breakEnds[$i])) {
                $start = Carbon::parse($breakStarts[$i]);
                $end = Carbon::parse($breakEnds[$i]);
                $breakTotal += max(0, $end->diffInMinutes($start));
                
                // 個別の休憩時間を保存
                $breakTimes[] = [
                    'start' => $breakStarts[$i],
                    'end' => $breakEnds[$i],
                ];
            }
        }

        // 出退勤
        $clockIn = null;
        $clockOut = null;
        if (!empty($data['clock_in'])) {
            [$h,$i] = explode(':', $data['clock_in']);
            $clockIn = (clone $day)->setTime((int)$h, (int)$i, 0);
        }
        if (!empty($data['clock_out'])) {
            [$h,$i] = explode(':', $data['clock_out']);
            $clockOut = (clone $day)->setTime((int)$h, (int)$i, 0);
        }

        // 実働（分）再計算
        $workMinutes = 0;
        if ($clockIn && $clockOut) {
            $elapsed = intdiv($clockIn->diffInSeconds($clockOut), 60);
            $workMinutes = max(0, $elapsed - $breakTotal);
        }

        $now = now();
        $existing = DB::table('attendances')
            ->where('user_id', $userId)
            ->where('work_date', $workDate)
            ->first();

        $payload = [
            'user_id'    => $userId,
            'work_date'  => $workDate,
            'break_time' => $breakTotal,
            'break_times' => !empty($breakTimes) ? json_encode($breakTimes) : null,
            'work_time'  => $workMinutes,
            'updated_at' => $now,
        ];
        if ($clockIn)  $payload['clock_in'] = $clockIn;
        if ($clockOut) $payload['clock_out'] = $clockOut;
        if (array_key_exists('note', $data)) $payload['note'] = $data['note'] ?? null;

        if ($existing) {
            DB::table('attendances')->where('id', $existing->id)->update($payload);
        } else {
            $payload['created_at'] = $now;
            DB::table('attendances')->insert($payload);
        }

        if (!empty($data['name'])) {
            DB::table('users')->where('id', $userId)->update([
                'name' => $data['name'],
                'updated_at' => $now,
            ]);
        }

        return back()->with('status', '勤怠を更新しました。');
    }
}

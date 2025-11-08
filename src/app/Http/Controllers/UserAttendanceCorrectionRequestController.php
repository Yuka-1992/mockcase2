<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UserAttendanceCorrectionRequestController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'attendance_id' => 'nullable|string',
                'work_date' => 'required|date',
                'clock_in' => 'nullable|date_format:H:i',
                'clock_out' => 'nullable|date_format:H:i',
                'break_start' => 'array',
                'break_start.*' => 'nullable|date_format:H:i',
                'break_end' => 'array',
                'break_end.*' => 'nullable|date_format:H:i',
                'note' => 'required|string|max:1000',
            ], [
                'work_date.required' => '対象日を入力してください',
                'work_date.date' => '対象日が不適切な値です',
                'clock_in.date_format' => '出勤時間が不適切な値です',
                'clock_out.date_format' => '退勤時間が不適切な値です',
                'break_start.*.date_format' => '休憩開始時間が不適切な値です',
                'break_end.*.date_format' => '休憩終了時間が不適切な値です',
                'note.required' => '備考を入力してください',
                'note.max' => '備考は1000文字以内で入力してください',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // バリデーションエラーは自動的にJSON形式で返される
            return response()->json([
                'error' => 'バリデーションエラー',
                'errors' => $e->errors(),
                'message' => '入力内容に誤りがあります。'
            ], 422);
        }
        
        $userId = Auth::id();
        $attendanceId = null;
        
        // attendance_idが空文字列の場合はnullとして扱う
        if (!empty($validated['attendance_id']) && $validated['attendance_id'] !== '') {
            $attendanceIdValue = (int)$validated['attendance_id'];
            
            // attendance_idが提供されている場合、そのIDがattendancesテーブルに存在し、
            // かつ現在のユーザーのものであることを確認
            // 外部キー制約を保持するため、存在するIDのみを設定
            $attendance = DB::table('attendances')
                ->where('id', $attendanceIdValue)
                ->where('user_id', $userId)
                ->first();
            
            if ($attendance) {
                // 有効なattendance_idの場合のみ設定
                // 外部キー制約により、存在しないIDは設定できない
                $attendanceId = $attendance->id;
            }
            // attendanceが見つからない場合はnullのまま（実績がない場合）
        } else {
            // attendance_idが提供されていない場合、日付で検索（オプション）
            // 実績がない場合でも修正申請できるようにするため、見つからなくてもエラーにしない
            $attendance = DB::table('attendances')
                ->where('user_id', $userId)
                ->where('work_date', $validated['work_date'])
                ->first();
            
            if ($attendance) {
                $attendanceId = $attendance->id;
            }
        }
        
        // attendance_idはnullでも可（外部キー制約はnullableなので問題なし）
        // 遅刻などで実績がない場合でも修正申請が可能
        
        // 出勤・退勤時間を構築
        $requestedClockIn = null;
        $requestedClockOut = null;
        if (!empty($validated['clock_in'])) {
            $workDate = Carbon::parse($validated['work_date']);
            [$h, $m] = explode(':', $validated['clock_in']);
            $requestedClockIn = $workDate->copy()->setTime((int)$h, (int)$m, 0);
        }
        if (!empty($validated['clock_out'])) {
            $workDate = Carbon::parse($validated['work_date']);
            [$h, $m] = explode(':', $validated['clock_out']);
            $requestedClockOut = $workDate->copy()->setTime((int)$h, (int)$m, 0);
        }
        
        // 休憩時間の合計を計算（分単位）と個別の休憩時間を保存
        $breakTotal = 0;
        $breakStarts = $validated['break_start'] ?? [];
        $breakEnds = $validated['break_end'] ?? [];
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
        
        // 実働時間を計算
        $workMinutes = 0;
        if ($requestedClockIn && $requestedClockOut) {
            $elapsed = $requestedClockIn->diffInMinutes($requestedClockOut);
            $workMinutes = max(0, $elapsed - $breakTotal);
        }
        
        // Create correction request
        // 実勤怠実績がなくても（attendance_idがnullでも）修正申請を作成可能
        try {
            DB::table('attendance_correction_requests')->insert([
                'user_id' => $userId,
                'attendance_id' => $attendanceId,
                'target_date' => $validated['work_date'],
                'reason' => $validated['note'] ?? '修正申請',
                'status' => 'pending',
                'requested_clock_in' => $requestedClockIn,
                'requested_clock_out' => $requestedClockOut,
                'requested_break_time' => $breakTotal,
                'requested_break_times' => !empty($breakTimes) ? json_encode($breakTimes) : null,
                'requested_work_time' => $workMinutes,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
            
            return response()->json(['success' => true, 'message' => '修正申請を送信しました']);
        } catch (\Exception $e) {
            \Log::error('修正申請の作成に失敗しました: ' . $e->getMessage());
            return response()->json([
                'error' => '修正申請の作成に失敗しました',
                'message' => 'データベースエラーが発生しました。もう一度お試しください。'
            ], 500);
        }
    }
}
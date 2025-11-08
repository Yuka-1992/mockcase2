<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceCorrectionRequestSeeder extends Seeder
{
    public function run(): void
    {
        // ユーザーIDを取得（一般ユーザーのみ）
        $users = DB::table('users')
            ->where('role', 'user')
            ->orderBy('id')
            ->get();

        if ($users->isEmpty()) {
            $this->command->warn('一般ユーザーが見つかりません。先にUserSeederを実行してください。');
            return;
        }

        // 管理者IDを取得
        $admin = DB::table('users')
            ->where('role', 'admin')
            ->first();

        if (!$admin) {
            $this->command->warn('管理者が見つかりません。先にUserSeederを実行してください。');
            return;
        }

        $now = Carbon::now();
        
        // 各ユーザーに対して修正申請を作成
        foreach ($users as $user) {
            // 過去7日分のデータから、いくつかを修正申請対象にする
            $attendances = DB::table('attendances')
                ->where('user_id', $user->id)
                ->where('work_date', '>=', $now->copy()->subDays(7)->toDateString())
                ->orderBy('work_date', 'desc')
                ->limit(3)
                ->get();
            
            foreach ($attendances as $attendance) {
                $targetDate = Carbon::parse($attendance->work_date);
                
                // 修正申請の種類をランダムに決定
                $requestType = rand(1, 3);
                
                $requestedClockIn = null;
                $requestedClockOut = null;
                $breakTimes = null;
                $breakTotal = 0;
                $workMinutes = 0;
                $status = ['pending', 'approved', 'rejected'][rand(0, 2)];
                
                if ($requestType === 1) {
                    // 出退勤時間の修正
                    $requestedClockIn = $targetDate->copy()->setTime(rand(7, 9), rand(0, 59), 0);
                    $requestedClockOut = $targetDate->copy()->setTime(rand(18, 21), rand(0, 59), 0);
                } elseif ($requestType === 2) {
                    // 休憩時間の修正
                    $requestedClockIn = Carbon::parse($attendance->clock_in);
                    $requestedClockOut = Carbon::parse($attendance->clock_out);
                    
                    $breakCount = rand(1, 2);
                    $breakTimes = [];
                    
                    for ($i = 0; $i < $breakCount; $i++) {
                        $breakStartHour = rand(12, 13);
                        $breakStartMinute = rand(0, 30);
                        $breakEndHour = $breakStartHour + 1;
                        $breakEndMinute = rand(0, 30);
                        
                        $breakStart = sprintf('%02d:%02d', $breakStartHour, $breakStartMinute);
                        $breakEnd = sprintf('%02d:%02d', $breakEndHour, $breakEndMinute);
                        
                        $start = Carbon::parse($breakStart);
                        $end = Carbon::parse($breakEnd);
                        $breakMinutes = $end->diffInMinutes($start);
                        $breakTotal += $breakMinutes;
                        
                        $breakTimes[] = [
                            'start' => $breakStart,
                            'end' => $breakEnd,
                        ];
                    }
                } else {
                    // 全体的な修正
                    $requestedClockIn = $targetDate->copy()->setTime(rand(8, 10), rand(0, 59), 0);
                    $requestedClockOut = $targetDate->copy()->setTime(rand(17, 20), rand(0, 59), 0);
                    
                    $breakCount = rand(1, 2);
                    $breakTimes = [];
                    
                    for ($i = 0; $i < $breakCount; $i++) {
                        $breakStartHour = rand(12, 13);
                        $breakStartMinute = rand(0, 30);
                        $breakEndHour = $breakStartHour + 1;
                        $breakEndMinute = rand(0, 30);
                        
                        $breakStart = sprintf('%02d:%02d', $breakStartHour, $breakStartMinute);
                        $breakEnd = sprintf('%02d:%02d', $breakEndHour, $breakEndMinute);
                        
                        $start = Carbon::parse($breakStart);
                        $end = Carbon::parse($breakEnd);
                        $breakMinutes = $end->diffInMinutes($start);
                        $breakTotal += $breakMinutes;
                        
                        $breakTimes[] = [
                            'start' => $breakStart,
                            'end' => $breakEnd,
                        ];
                    }
                }
                
                // 実働時間を計算
                if ($requestedClockIn && $requestedClockOut) {
                    $elapsed = $requestedClockIn->diffInMinutes($requestedClockOut);
                    $workMinutes = max(0, $elapsed - $breakTotal);
                }
                
                // 申請理由
                $reasons = [
                    '出勤時間の記録漏れ',
                    '退勤時間の修正',
                    '休憩時間の修正',
                    '打刻ミスの修正',
                    '遅刻の申請',
                ];
                $reason = $reasons[array_rand($reasons)];
                
                // 承認済みの場合は承認日時と承認者を設定
                $approvedAt = null;
                $approvedBy = null;
                if ($status === 'approved') {
                    $approvedAt = $now->copy()->subDays(rand(1, 3));
                    $approvedBy = $admin->id;
                }
                
                DB::table('attendance_correction_requests')->insert([
                    'user_id' => $user->id,
                    'attendance_id' => $attendance->id,
                    'target_date' => $targetDate->toDateString(),
                    'reason' => $reason,
                    'status' => $status,
                    'requested_clock_in' => $requestedClockIn,
                    'requested_clock_out' => $requestedClockOut,
                    'requested_break_time' => $breakTotal,
                    'requested_break_times' => !empty($breakTimes) ? json_encode($breakTimes) : null,
                    'requested_work_time' => $workMinutes,
                    'approved_at' => $approvedAt,
                    'approved_by' => $approvedBy,
                    'created_at' => $now->copy()->subDays(rand(1, 5)),
                    'updated_at' => $now->copy()->subDays(rand(1, 5)),
                ]);
            }
            
            // 実績がない日付に対する修正申請も作成（attendance_idがnull）
            $futureDate = $now->copy()->addDays(rand(1, 3));
            $requestedClockIn = $futureDate->copy()->setTime(rand(8, 10), rand(0, 59), 0);
            $requestedClockOut = $futureDate->copy()->setTime(rand(17, 20), rand(0, 59), 0);
            
            $breakTimes = [[
                'start' => sprintf('%02d:%02d', rand(12, 13), rand(0, 30)),
                'end' => sprintf('%02d:%02d', rand(13, 14), rand(0, 30)),
            ]];
            $breakTotal = 60;
            $workMinutes = $requestedClockIn->diffInMinutes($requestedClockOut) - $breakTotal;
            
            DB::table('attendance_correction_requests')->insert([
                'user_id' => $user->id,
                'attendance_id' => null,
                'target_date' => $futureDate->toDateString(),
                'reason' => '遅刻の申請',
                'status' => 'pending',
                'requested_clock_in' => $requestedClockIn,
                'requested_clock_out' => $requestedClockOut,
                'requested_break_time' => $breakTotal,
                'requested_break_times' => json_encode($breakTimes),
                'requested_work_time' => $workMinutes,
                'approved_at' => null,
                'approved_by' => null,
                'created_at' => $now->copy()->subDays(rand(1, 3)),
                'updated_at' => $now->copy()->subDays(rand(1, 3)),
            ]);
        }
        
        $this->command->info('修正申請データのシードが完了しました。');
    }
}


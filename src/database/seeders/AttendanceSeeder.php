<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
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

        $now = Carbon::now();
        
        // 過去30日分の退勤データを作成
        foreach ($users as $user) {
            for ($i = 0; $i < 30; $i++) {
                $workDate = $now->copy()->subDays($i);
                
                // 週末はスキップ（オプション）
                if ($workDate->isWeekend() && rand(1, 3) === 1) {
                    continue;
                }
                
                // 出勤時間（8:00-10:00の間でランダム）
                $clockInHour = rand(8, 10);
                $clockInMinute = rand(0, 59);
                $clockIn = $workDate->copy()->setTime($clockInHour, $clockInMinute, 0);
                
                // 退勤時間（17:00-20:00の間でランダム）
                $clockOutHour = rand(17, 20);
                $clockOutMinute = rand(0, 59);
                $clockOut = $workDate->copy()->setTime($clockOutHour, $clockOutMinute, 0);
                
                // 休憩時間（1-3回、各30-60分）
                $breakCount = rand(1, 3);
                $breakTimes = [];
                $breakTotal = 0;
                
                for ($j = 0; $j < $breakCount; $j++) {
                    $breakStartHour = rand(12, 14);
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
                
                // 実働時間を計算
                $elapsed = $clockIn->diffInMinutes($clockOut);
                $workMinutes = max(0, $elapsed - $breakTotal);
                
                // 備考（50%の確率で追加）
                $note = null;
                if (rand(1, 2) === 1) {
                    $notes = [
                        '通常勤務',
                        '残業あり',
                        '会議参加',
                        '外出あり',
                        '在宅勤務',
                    ];
                    $note = $notes[array_rand($notes)];
                }
                
                DB::table('attendances')->insert([
                    'user_id' => $user->id,
                    'work_date' => $workDate->toDateString(),
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'break_time' => $breakTotal,
                    'break_times' => !empty($breakTimes) ? json_encode($breakTimes) : null,
                    'work_time' => $workMinutes,
                    'note' => $note,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
        
        $this->command->info('退勤データのシードが完了しました。');
    }
}


<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AdminAttendanceRequestController extends Controller
{
    /**
     * Display request list (pending and approved)
     */
    public function index()
    {
        $pendingRequests = DB::table('attendance_correction_requests')
            ->join('users', 'users.id', '=', 'attendance_correction_requests.user_id')
            ->where('attendance_correction_requests.status', 'pending')
            ->select(
                'attendance_correction_requests.*',
                'users.name as user_name'
            )
            ->orderBy('attendance_correction_requests.created_at', 'desc')
            ->get();

        $approvedRequests = DB::table('attendance_correction_requests')
            ->join('users', 'users.id', '=', 'attendance_correction_requests.user_id')
            ->where('attendance_correction_requests.status', 'approved')
            ->select(
                'attendance_correction_requests.*',
                'users.name as user_name'
            )
            ->orderBy('attendance_correction_requests.approved_at', 'desc')
            ->get();

        return view('admin.attendance.request', [
            'pendingRequests' => $pendingRequests,
            'approvedRequests' => $approvedRequests,
        ]);
    }

    /**
     * Show approval page for a specific request
     */
    public function approve($id)
    {
        $request = DB::table('attendance_correction_requests')
            ->join('users', 'users.id', '=', 'attendance_correction_requests.user_id')
            ->where('attendance_correction_requests.id', $id)
            ->select(
                'attendance_correction_requests.*',
                'users.name as user_name'
            )
            ->first();

        if (!$request) {
            abort(404, '申請が見つかりませんでした');
        }

        // Convert stdClass to array for easier manipulation
        $requestData = (array) $request;

        // Parse break_times from requested_break_times JSON column
        if (isset($requestData['requested_break_times']) && !empty($requestData['requested_break_times'])) {
            $requestData['break_times'] = json_decode($requestData['requested_break_times'], true);
        } else {
            // If break times are stored in requested_break_time as total minutes,
            // we don't have individual break periods, so leave it null
            $requestData['break_times'] = null;
        }

        // Format dates for display
        if (isset($requestData['created_at'])) {
            $requestData['created_at'] = Carbon::parse($requestData['created_at'])->format('Y-m-d H:i');
        }

        return view('admin.attendance.approve', [
            'request' => (object) $requestData,
        ]);
    }

    /**
     * Process approval (AJAX endpoint)
     */
    public function processApproval(Request $request, $id)
    {
        $correctionRequest = DB::table('attendance_correction_requests')
            ->where('id', $id)
            ->first();

        if (!$correctionRequest) {
            return response()->json(['error' => 'Request not found'], 404);
        }

        if ($correctionRequest->status === 'approved') {
            return response()->json(['error' => 'Already approved'], 400);
        }

        try {
            DB::beginTransaction();
            
            // Update the request status
            DB::table('attendance_correction_requests')
                ->where('id', $id)
                ->update([
                    'status' => 'approved',
                    'approved_at' => Carbon::now(),
                    'approved_by' => Auth::id(),
                    'updated_at' => Carbon::now(),
                ]);

            // Apply changes to attendances table
            $userId = $correctionRequest->user_id;
            $targetDate = $correctionRequest->target_date;
            
            // 休憩時間の合計を計算
            $breakTotal = $correctionRequest->requested_break_time ?? 0;
            
            // 実働時間を計算
            $workMinutes = $correctionRequest->requested_work_time ?? 0;
            
            // 個別の休憩時間を取得
            $breakTimes = null;
            if (!empty($correctionRequest->requested_break_times)) {
                $breakTimes = $correctionRequest->requested_break_times;
            }
            
            // 既存のattendanceレコードを検索
            $existingAttendance = null;
            if ($correctionRequest->attendance_id) {
                $existingAttendance = DB::table('attendances')
                    ->where('id', $correctionRequest->attendance_id)
                    ->where('user_id', $userId)
                    ->first();
            }
            
            if (!$existingAttendance) {
                // attendance_idがない、または見つからない場合は日付で検索
                $existingAttendance = DB::table('attendances')
                    ->where('user_id', $userId)
                    ->where('work_date', $targetDate)
                    ->first();
            }
            
            // attendancesテーブルを更新または作成
            $attendanceData = [
                'user_id' => $userId,
                'work_date' => $targetDate,
                'clock_in' => $correctionRequest->requested_clock_in,
                'clock_out' => $correctionRequest->requested_clock_out,
                'break_time' => $breakTotal,
                'break_times' => $breakTimes,
                'work_time' => $workMinutes,
                'note' => $correctionRequest->reason,
                'updated_at' => Carbon::now(),
            ];
            
            $attendanceId = null;
            if ($existingAttendance) {
                // 既存レコードを更新
                DB::table('attendances')
                    ->where('id', $existingAttendance->id)
                    ->update($attendanceData);
                $attendanceId = $existingAttendance->id;
            } else {
                // 新規レコードを作成
                $attendanceData['created_at'] = Carbon::now();
                $attendanceId = DB::table('attendances')->insertGetId($attendanceData);
            }
            
            // 承認後にattendance_idを更新（nullの場合のみ）
            if (!$correctionRequest->attendance_id && $attendanceId) {
                DB::table('attendance_correction_requests')
                    ->where('id', $id)
                    ->update(['attendance_id' => $attendanceId]);
            }
            
            DB::commit();
            
            return response()->json(['success' => true, 'message' => '承認が完了しました']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('承認処理に失敗しました: ' . $e->getMessage());
            return response()->json([
                'error' => '承認処理に失敗しました',
                'message' => 'データベースエラーが発生しました。もう一度お試しください。'
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UserAttendanceIndexController extends Controller
{
    /**
     * Display user's monthly attendance list
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        
        // Get month parameter or use current month
        $monthStr = $request->query('month');
        $month = $monthStr ? Carbon::parse($monthStr . '-01') : Carbon::now()->startOfMonth();
        
        // Calculate previous and next month
        $prevMonth = $month->copy()->subMonth()->format('Y-m');
        $nextMonth = $month->copy()->addMonth()->format('Y-m');
        
        // Get start and end of the month
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        
        // Fetch attendance records for the month
        $attendances = DB::table('attendances')
            ->where('user_id', $userId)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy('work_date');
        
        // Build rows for each day of the month
        $rows = [];
        $current = $start->copy();
        while ($current <= $end) {
            $dateStr = $current->toDateString();
            $rows[] = [
                'date' => $current->copy(),
                'attendance' => $attendances->get($dateStr),
            ];
            $current->addDay();
        }
        
        return view('user.index', [
            'month' => $month,
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
            'rows' => $rows,
        ]);
    }
}
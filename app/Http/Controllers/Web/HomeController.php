<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Tank;
use App\Models\WaterLog;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index()
    {
        $now = now();

        // Active tanks = tanks có water logs trong 30 ngày gần nhất
        $activeTanks = Tank::whereHas('waterLogs', function ($q) use ($now) {
            $q->where('logged_at', '>=', $now->copy()->subDays(30));
        })->count();

        // Plants in library = count plants table
        $plantsCount = 0;
        try {
            $plantsCount = DB::table('plants')->count();
        } catch (\Throwable $e) {
            $plantsCount = 0;
        }

        // Water logs this week = 7 ngày gần nhất
        $waterLogsWeek = WaterLog::where('logged_at', '>=', $now->copy()->subDays(7))->count();

        // Community answers = tổng answers
        $answersCount = Answer::count();

        return view('home', [
            'activeTanks'   => $activeTanks,
            'plantsCount'   => $plantsCount,
            'waterLogsWeek' => $waterLogsWeek,
            'answersCount'  => $answersCount,
        ]);
    }
}

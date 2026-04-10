<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Post;
use App\Models\Question;
use App\Models\Tank;
use App\Models\User;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $since = now()->subDays(30);

        $totalUsers  = User::count();
        $adminCount  = User::where('role', 'admin')->count();
        $expertCount = User::where('role', 'expert')->count();

        $activeTanks = Tank::whereHas('waterLogs', function ($q) use ($since) {
            $q->where('logged_at', '>=', $since);
        })->count();

        $questionsTotal    = Question::count();
        $questionsOpen     = Question::where('status', 'open')->count();
        $questionsResolved = Question::where('status', 'resolved')->count();

        $postsTotal = Post::count();

        // "pending review / flagged" -> hiện schema chỉ có pending/approved/rejected
        $postsPendingOrFlagged = Post::whereIn('status', ['pending', 'rejected'])->count();

        $questionsNeedingAttention = Question::query()
            ->with(['user'])
            ->withCount('answers')
            ->orderByRaw("CASE WHEN status = 'open' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $postsFlagged = Post::query()
            ->with(['user'])
            ->whereIn('status', ['pending', 'rejected'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('admin.dashboard_admin', [
            'stats' => [
                'total_users' => $totalUsers,
                'admin_count' => $adminCount,
                'expert_count' => $expertCount,

                'active_tanks' => $activeTanks,

                'questions_total' => $questionsTotal,
                'questions_open' => $questionsOpen,
                'questions_resolved' => $questionsResolved,

                'posts_total' => $postsTotal,
                'posts_pending_or_flagged' => $postsPendingOrFlagged,
            ],
            'questionsNeedingAttention' => $questionsNeedingAttention,
            'postsFlagged' => $postsFlagged,
        ]);
    }
}

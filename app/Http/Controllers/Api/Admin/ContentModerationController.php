<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Answer;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ContentModerationController extends BaseApiController
{
    // =========================
    // Q&A (GIỮ NGUYÊN)
    // =========================

    private function formatQuestionRow(Question $q): array
    {
        $tank = $q->tank ? ['id' => $q->tank->id, 'name' => $q->tank->name] : null;
        $user = $q->user ? ['id' => $q->user->id, 'name' => $q->user->name, 'email' => $q->user->email] : null;

        $content = (string)($q->content ?? '');
        $excerpt = trim(preg_replace('/\s+/', ' ', $content));
        if (mb_strlen($excerpt) > 80) $excerpt = mb_substr($excerpt, 0, 80) . '…';

        return [
            'id' => $q->id,
            'user' => $user,
            'tank' => $tank,
            'tank_id' => $q->tank_id,
            'title' => $q->title,
            'content_excerpt' => $excerpt,
            'status' => $q->status,
            'answers_count' => (int)($q->answers_count ?? 0),
            'has_best_answer' => (bool)($q->has_best_answer ?? false),
            'created_at' => optional($q->created_at)->toISOString(),
            'updated_at' => optional($q->updated_at)->toISOString(),
        ];
    }

    private function formatQuestionDetail(Question $q): array
    {
        $tank = $q->tank ? ['id' => $q->tank->id, 'name' => $q->tank->name] : null;
        $user = $q->user ? ['id' => $q->user->id, 'name' => $q->user->name, 'email' => $q->user->email] : null;

        return [
            'id' => $q->id,
            'user' => $user,
            'tank' => $tank,
            'tank_id' => $q->tank_id,
            'title' => $q->title,
            'content' => $q->content,
            'image_path' => $q->image_path,
            'status' => $q->status,
            'answers_count' => (int)($q->answers_count ?? 0),
            'has_best_answer' => (bool)($q->has_best_answer ?? false),
            'created_at' => optional($q->created_at)->toISOString(),
            'updated_at' => optional($q->updated_at)->toISOString(),
        ];
    }

    private function formatAnswer(Answer $a): array
    {
        $user = $a->user ? ['id' => $a->user->id, 'name' => $a->user->name, 'email' => $a->user->email] : null;

        return [
            'id' => $a->id,
            'question_id' => $a->question_id,
            'user' => $user,
            'user_id' => $a->user_id,
            'content' => $a->content,
            'is_accepted' => (bool)$a->is_accepted,
            'created_at' => optional($a->created_at)->toISOString(),
            'updated_at' => optional($a->updated_at)->toISOString(),
        ];
    }

    public function questionsIndex(Request $request)
    {
        $q = trim((string)$request->query('q', ''));
        $status = trim((string)$request->query('status', 'all')); // all|open|resolved

        $perPage = (int)$request->query('per_page', 15);
        if ($perPage <= 0 || $perPage > 100) $perPage = 15;

        $sortBy = trim((string)$request->query('sort_by', 'created_at'));
        $sortDir = strtolower(trim((string)$request->query('sort_dir', 'desc'))) === 'asc' ? 'asc' : 'desc';

        $allowedSort = ['created_at', 'answers_count', 'status', 'id'];
        if (!in_array($sortBy, $allowedSort, true)) $sortBy = 'created_at';

        $query = Question::query()
            ->with(['user:id,name,email', 'tank:id,name'])
            ->select('questions.*')
            ->addSelect([
                'answers_count' => DB::table('answers')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('answers.question_id', 'questions.id'),
                'has_best_answer' => DB::table('answers')
                    ->selectRaw('COUNT(*) > 0')
                    ->whereColumn('answers.question_id', 'questions.id')
                    ->where('answers.is_accepted', true)
                    ->limit(1),
            ]);

        if ($status !== '' && $status !== 'all') {
            if (!in_array($status, ['open', 'resolved'], true)) {
                return $this->error('Invalid status filter.', 422);
            }
            $query->where('status', $status);
        }

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")
                    ->orWhere('content', 'like', "%{$q}%")
                    ->orWhereHas('user', function ($u) use ($q) {
                        $u->where('name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%");
                    });
            });
        }

        if ($sortBy === 'answers_count') {
            $query->orderBy('answers_count', $sortDir)->orderByDesc('created_at');
        } else {
            $query->orderBy($sortBy, $sortDir);
        }

        $page = $query->paginate($perPage)->appends($request->query());

        $page->setCollection(
            $page->getCollection()
                ->map(fn($row) => $this->formatQuestionRow($row))
                ->values()
        );

        return $this->success($page);
    }

    public function questionShow(Request $request, Question $question)
    {
        $question->load(['user:id,name,email', 'tank:id,name']);

        $answers = Answer::query()
            ->where('question_id', $question->id)
            ->with('user:id,name,email')
            ->orderByDesc('is_accepted')
            ->orderBy('created_at', 'asc')
            ->get();

        $answersCount = $answers->count();
        $hasBest = $answers->contains(fn($a) => (bool)$a->is_accepted);

        $question->setAttribute('answers_count', $answersCount);
        $question->setAttribute('has_best_answer', $hasBest);

        return $this->success([
            'question' => $this->formatQuestionDetail($question),
            'answers' => $answers->map(fn($a) => $this->formatAnswer($a))->values(),
        ]);
    }

    public function updateQuestionStatus(Request $request, Question $question)
    {
        $status = trim((string)$request->input('status', ''));

        if (!in_array($status, ['open', 'resolved'], true)) {
            return $this->error('Invalid status. Allowed: open, resolved.', 422);
        }

        $question->status = $status;
        $question->save();

        return $this->success(null, 'Question status updated.');
    }

    public function deleteQuestion(Request $request, Question $question)
    {
        $qid = (int)$question->id;

        DB::transaction(function () use ($qid, $question) {
            Answer::query()->where('question_id', $qid)->delete();
            $question->delete();
        });

        return $this->success(null, 'Question deleted (hard).');
    }

    public function deleteAnswer(Request $request, Answer $answer)
    {
        $wasAccepted = (bool)$answer->is_accepted;
        $qid = (int)$answer->question_id;

        $answer->delete();

        if ($wasAccepted) {
            $question = Question::query()->find($qid);
            if ($question) {
                $question->status = 'open';
                $question->save();
            }
        }

        return $this->success(null, 'Answer deleted (hard).');
    }

    // =========================
    // ✅ COMMUNITY POSTS MODERATION (ADMIN)
    // =========================

    private function postImageUrl(?string $path): ?string
    {
        if (!$path) return null;
        return Storage::url($path);
    }

    private function formatPostRow(Post $p): array
    {
        $user = $p->user ? ['id' => $p->user->id, 'name' => $p->user->name, 'email' => $p->user->email] : null;

        $content = (string)($p->content ?? '');
        $excerpt = trim(preg_replace('/\s+/', ' ', $content));
        if (mb_strlen($excerpt) > 90) $excerpt = mb_substr($excerpt, 0, 90) . '…';

        return [
            'id' => $p->id,
            'user' => $user,
            'user_id' => $p->user_id,
            'title' => $p->title,
            'content_excerpt' => $excerpt,
            'status' => $p->status,
            'comments_count' => (int)($p->comments_count ?? 0),
            'created_at' => optional($p->created_at)->toISOString(),
            'updated_at' => optional($p->updated_at)->toISOString(),
        ];
    }

    private function formatPostDetail(Post $p): array
    {
        $user = $p->user ? ['id' => $p->user->id, 'name' => $p->user->name, 'email' => $p->user->email] : null;

        return [
            'id' => $p->id,
            'user' => $user,
            'user_id' => $p->user_id,
            'title' => $p->title,
            'content' => $p->content,
            'image_path' => $this->postImageUrl($p->image_path),
            'status' => $p->status,
            'reviewed_by' => $p->reviewed_by,
            'reviewed_at' => optional($p->reviewed_at)->toISOString(),
            'comments_count' => (int)($p->comments_count ?? 0),
            'created_at' => optional($p->created_at)->toISOString(),
            'updated_at' => optional($p->updated_at)->toISOString(),
        ];
    }

    // GET /api/admin/posts?q=&status=&sort_by=&sort_dir=&page=&per_page=
    public function postsIndex(Request $request)
    {
        $q = trim((string)$request->query('q', ''));
        $status = trim((string)$request->query('status', 'all')); // all|pending|approved|rejected

        $perPage = (int)$request->query('per_page', 15);
        if ($perPage <= 0 || $perPage > 100) $perPage = 15;

        $sortBy = trim((string)$request->query('sort_by', 'created_at'));
        $sortDir = strtolower(trim((string)$request->query('sort_dir', 'desc'))) === 'asc' ? 'asc' : 'desc';

        $allowedSort = ['created_at', 'comments_count', 'status', 'id'];
        if (!in_array($sortBy, $allowedSort, true)) $sortBy = 'created_at';

        $query = Post::query()
            ->select('posts.*')                // ✅ đặt select TRƯỚC
            ->with(['user:id,name,email'])
            ->withCount('comments');           // ✅ đặt withCount SAU select

        if ($status !== '' && $status !== 'all') {
            if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
                return $this->error('Invalid status filter. Allowed: pending, approved, rejected.', 422);
            }
            $query->where('status', $status);
        }

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")
                ->orWhere('content', 'like', "%{$q}%")
                ->orWhereHas('user', function ($u) use ($q) {
                    $u->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            });
        }

        if ($sortBy === 'comments_count') {
            $query->orderBy('comments_count', $sortDir)->orderByDesc('created_at');
        } else {
            $query->orderBy($sortBy, $sortDir);
        }

        $page = $query->paginate($perPage)->appends($request->query());

        $page->setCollection(
            $page->getCollection()->map(fn($row) => $this->formatPostRow($row))->values()
        );

        return $this->success($page);
    }


    // GET /api/admin/posts/{post}
    public function postShow(Request $request, Post $post)
    {
        $post->load(['user:id,name,email'])->loadCount('comments');

        $comments = $post->comments()
            ->with(['user:id,name,email'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'post_id' => $c->post_id,
                    'user_id' => $c->user_id,
                    'content' => $c->content,
                    'created_at' => optional($c->created_at)->toISOString(),
                    'updated_at' => optional($c->updated_at)->toISOString(),
                    'user' => $c->user ? [
                        'id' => $c->user->id,
                        'name' => $c->user->name,
                        'email' => $c->user->email,
                    ] : null,
                ];
            })
            ->values();

        return $this->success([
            'post' => $this->formatPostDetail($post),
            'comments' => $comments,
        ]);
    }

    // PATCH /api/admin/posts/{post}/status  body: {status: pending|approved|rejected}
    public function updatePostStatus(Request $request, Post $post)
    {
        $status = trim((string)$request->input('status', ''));

        if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
            return $this->error('Invalid status. Allowed: pending, approved, rejected.', 422);
        }

        $post->status = $status;

        // set review info khi admin can thiệp
        $post->reviewed_by = $request->user()->id;
        $post->reviewed_at = now();

        $post->save();

        return $this->success(null, 'Post status updated.');
    }

    // DELETE /api/admin/posts/{post}
    public function deletePost(Request $request, Post $post)
    {
        if ($post->image_path) {
            Storage::disk('public')->delete($post->image_path);
        }

        $post->delete();
        return $this->success(null, 'Post deleted.');
    }

    // DELETE /api/admin/comments/{comment}
    public function deleteComment(Request $request, Comment $comment)
    {
        $comment->delete();
        return $this->success(null, 'Comment deleted.');
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\UpdateQuestionRequest;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Tank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class QuestionController extends BaseApiController
{
    private function ensureOwner(Request $request, Question $question): void
    {
        if ((int)$question->user_id !== (int)$request->user()->id) {
            abort(403, 'You do not own this question.');
        }
    }

    private function ensureTankOwner(Request $request, ?int $tankId): void
    {
        if (!$tankId) return;

        $tank = Tank::query()->find($tankId);
        if (!$tank) abort(422, 'Tank not found.');

        if ((int)$tank->user_id !== (int)$request->user()->id) {
            abort(403, 'You do not own this tank.');
        }
    }

    private function formatQuestion(Question $q): array
    {
        $tank = $q->tank ? ['id' => $q->tank->id, 'name' => $q->tank->name] : null;
        $user = $q->user ? ['id' => $q->user->id, 'name' => $q->user->name] : null;

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
        $user = $a->user ? ['id' => $a->user->id, 'name' => $a->user->name] : null;

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

    // GET /api/questions?q=&status=&page=&per_page=
    public function index(Request $request)
    {
        $q = trim((string)$request->query('q', ''));
        $status = (string)$request->query('status', 'all'); // all|open|resolved

        $perPage = (int)$request->query('per_page', 10);
        $perPage = max(1, min(50, $perPage));

        $query = Question::query()
            ->with(['user:id,name', 'tank:id,name'])
            ->withCount('answers')
            ->select('questions.*')
            ->addSelect([
                'has_best_answer' => Answer::query()
                    ->selectRaw('COUNT(*) > 0')
                    ->whereColumn('answers.question_id', 'questions.id')
                    ->where('answers.is_accepted', true)
                    ->limit(1)
            ])
            ->orderByDesc('created_at');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")
                  ->orWhere('content', 'like', "%{$q}%");
            });
        }

        $page = $query->paginate($perPage);

        return $this->success([
            'items' => $page->getCollection()->map(fn($row) => $this->formatQuestion($row))->values(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'last_page' => $page->lastPage(),
            ],
        ]);
    }

    // GET /api/my-questions
    public function myQuestions(Request $request)
    {
        $userId = (int)$request->user()->id;

        $q = trim((string)$request->query('q', ''));
        $status = (string)$request->query('status', 'all');

        $perPage = (int)$request->query('per_page', 10);
        $perPage = max(1, min(50, $perPage));

        $query = Question::query()
            ->where('user_id', $userId)
            ->with(['user:id,name', 'tank:id,name'])
            ->withCount('answers')
            ->select('questions.*')
            ->addSelect([
                'has_best_answer' => Answer::query()
                    ->selectRaw('COUNT(*) > 0')
                    ->whereColumn('answers.question_id', 'questions.id')
                    ->where('answers.is_accepted', true)
                    ->limit(1)
            ])
            ->orderByDesc('created_at');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")
                  ->orWhere('content', 'like', "%{$q}%");
            });
        }

        $page = $query->paginate($perPage);

        return $this->success([
            'items' => $page->getCollection()->map(fn($row) => $this->formatQuestion($row))->values(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'last_page' => $page->lastPage(),
            ],
        ]);
    }

    // GET /api/questions/{question}
    public function show(Request $request, Question $question)
    {
        $question->load(['user:id,name', 'tank:id,name']);
        $question->loadCount('answers');

        $answers = Answer::query()
            ->where('question_id', $question->id)
            ->with('user:id,name')
            ->orderByDesc('is_accepted')
            ->orderBy('created_at', 'asc')
            ->get();

        $hasBest = $answers->contains(fn($a) => (bool)$a->is_accepted);

        return $this->success([
            'question' => $this->formatQuestion($question->setAttribute('has_best_answer', $hasBest)),
            'answers' => $answers->map(fn($a) => $this->formatAnswer($a))->values(),
        ]);
    }

    // POST /api/questions
    public function store(StoreQuestionRequest $request)
    {
        $data = $request->validated();

        $this->ensureTankOwner($request, $data['tank_id'] ?? null);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('qa/questions', 'public');
            $imagePath = '/storage/' . $path; // ✅ absolute path for <img src>
        }

        $q = Question::create([
            'user_id'    => $request->user()->id,
            'tank_id'    => $data['tank_id'] ?? null,
            'title'      => $data['title'],
            'content'    => $data['content'],
            'image_path' => $imagePath,
            'status'     => 'open',
        ]);

        $q->load(['user:id,name', 'tank:id,name']);
        $q->loadCount('answers');

        return $this->success([
            'question' => $this->formatQuestion($q->setAttribute('has_best_answer', false)),
        ], 'Question created.');
    }


    // PUT /api/questions/{question}
    public function update(UpdateQuestionRequest $request, Question $question)
    {
        $this->ensureOwner($request, $question);

        $data = $request->validated();

        if (array_key_exists('tank_id', $data)) {
            $this->ensureTankOwner($request, $data['tank_id']);
        }

        // remove image (optional)
        if (!empty($data['remove_image'])) {
            $this->deleteQuestionImage($question->image_path);
            $question->image_path = null;
        }

        // replace image (optional)
        if ($request->hasFile('image')) {
            $this->deleteQuestionImage($question->image_path);
            $path = $request->file('image')->store('qa/questions', 'public');
            $question->image_path = '/storage/' . $path;
        }

        $question->fill([
            'tank_id'  => array_key_exists('tank_id', $data) ? ($data['tank_id'] ?: null) : $question->tank_id,
            'title'    => array_key_exists('title', $data) ? $data['title'] : $question->title,
            'content'  => array_key_exists('content', $data) ? $data['content'] : $question->content,
        ])->save();

        $question->load(['user:id,name', 'tank:id,name']);
        $question->loadCount('answers');

        $hasBest = Answer::query()
            ->where('question_id', $question->id)
            ->where('is_accepted', true)
            ->exists();

        return $this->success([
            'question' => $this->formatQuestion($question->setAttribute('has_best_answer', $hasBest)),
        ], 'Question updated.');
    }


    // DELETE /api/questions/{question} (hard delete)
    public function destroy(Request $request, Question $question)
    {
        $this->ensureOwner($request, $question);

        $this->deleteQuestionImage($question->image_path);
        $question->delete();

        return $this->success(null, 'Question deleted.');
    }


    // PATCH /api/questions/{question}/answers/{answer}/accept
    public function acceptAnswer(Request $request, Question $question, Answer $answer)
    {
        $this->ensureOwner($request, $question);

        if ((int)$answer->question_id !== (int)$question->id) {
            abort(422, 'Answer does not belong to this question.');
        }

        Answer::query()
            ->where('question_id', $question->id)
            ->update(['is_accepted' => false]);

        $answer->is_accepted = true;
        $answer->save();

        if ($question->status !== 'resolved') {
            $question->status = 'resolved';
            $question->save();
        }

        return $this->success(null, 'Best answer selected.');
    }

    private function deleteQuestionImage(?string $imagePath): void
    {
        if (!$imagePath) return;

        // imagePath dạng: /storage/qa/questions/xxx.jpg
        $p = ltrim($imagePath, '/'); // storage/qa/questions/xxx.jpg
        if (str_starts_with($p, 'storage/')) {
            $rel = substr($p, strlen('storage/')); // qa/questions/xxx.jpg
            Storage::disk('public')->delete($rel);
        }
    }

}

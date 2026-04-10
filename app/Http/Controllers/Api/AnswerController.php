<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreAnswerRequest;
use App\Http\Requests\UpdateAnswerRequest;
use App\Models\Answer;
use App\Models\Question;
use Illuminate\Http\Request;

class AnswerController extends BaseApiController
{
    private function ensureCanDelete(Request $request, Answer $answer): void
    {
        $userId = (int)$request->user()->id;

        if ((int)$answer->user_id === $userId) return;

        $answer->load('question');
        if ($answer->question && (int)$answer->question->user_id === $userId) return;

        abort(403, 'You cannot delete this answer.');
    }

    // ✅ ONLY answer owner can update, and admins cannot update
    private function ensureCanUpdate(Request $request, Answer $answer): void
    {
        if (($request->user()->role ?? null) === 'admin') {
            abort(403, 'Admins cannot edit answers.');
        }

        $userId = (int)$request->user()->id;
        if ((int)$answer->user_id !== $userId) {
            abort(403, 'Only the answer author can edit this answer.');
        }
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

    // POST /api/questions/{question}/answers
    public function store(StoreAnswerRequest $request, Question $question)
    {
        $data = $request->validated();

        $answer = Answer::create([
            'question_id' => $question->id,
            'user_id' => $request->user()->id,
            'content' => $data['content'],
            'is_accepted' => false,
        ]);

        $answer->load('user:id,name');

        return $this->success([
            'answer' => $this->formatAnswer($answer),
        ], 'Answer created.');
    }

    // ✅ PUT /api/answers/{answer}
    public function update(UpdateAnswerRequest $request, Answer $answer)
    {
        $this->ensureCanUpdate($request, $answer);

        $data = $request->validated();

        $answer->content = $data['content'];
        $answer->save();

        $answer->load('user:id,name');

        return $this->success([
            'answer' => $this->formatAnswer($answer),
        ], 'Answer updated.');
    }

    // DELETE /api/answers/{answer} (hard delete)
    public function destroy(Request $request, Answer $answer)
    {
        $this->ensureCanDelete($request, $answer);

        $wasAccepted = (bool)$answer->is_accepted;

        $answer->load('question');
        $question = $answer->question;

        $answer->delete();

        if ($wasAccepted && $question) {
            $question->status = 'open';
            $question->save();
        }

        return $this->success(null, 'Answer deleted.');
    }
}

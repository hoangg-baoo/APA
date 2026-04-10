<?php

namespace App\Policies;

use App\Models\Question;

use App\Models\User;

class QuestionPolicy
{
    public function update(User $user, Question $question): bool
    {
        return $user->isAdmin() || $user->id === $question->user_id;
    }
    // Hàm update(): kiểm tra user có quyền sửa Question hay không
    // - admin (isAdmin) được sửa mọi question
    // - owner (người tạo question) được sửa question của mình
    // => trả true/false để controller/authorize quyết định cho phép hay chặn (403)

    public function delete(User $user, Question $question): bool
    {
        return $user->isAdmin() || $user->id === $question->user_id;
    }
    // Hàm delete(): kiểm tra user có quyền xóa Question hay không
    // Rule giống update:
    // - admin được xóa mọi question
    // - owner được xóa question của mình
}
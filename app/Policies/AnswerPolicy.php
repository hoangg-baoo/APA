<?php

namespace App\Policies;

use App\Models\Answer;

use App\Models\User;

class AnswerPolicy
{
    public function update(User $user, Answer $answer): bool
    {
        return $user->isAdmin() || $user->id === $answer->user_id;
    }
    // Hàm update(): kiểm tra user có quyền sửa Answer hay không
    // - $user->isAdmin(): nếu user là admin thì luôn được sửa
    // - $user->id === $answer->user_id: nếu user là chủ của answer (người tạo) thì được sửa
    // => Kết quả trả về true/false để controller/gate quyết định cho phép hay chặn (403)

    public function delete(User $user, Answer $answer): bool
    {
        return $user->isAdmin() || $user->id === $answer->user_id;
    }
    // Hàm delete(): kiểm tra user có quyền xóa Answer hay không
    // Rule giống update:
    // - admin được xóa tất cả
    // - owner (người tạo answer) được xóa answer của mình
}
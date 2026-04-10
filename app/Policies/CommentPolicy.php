<?php

namespace App\Policies;

use App\Models\Comment;

use App\Models\User;

class CommentPolicy
{
    public function delete(User $user, Comment $comment): bool
    {
        // Chỉ admin hoặc chính người comment được xóa
        return $user->isAdmin() || $user->id === $comment->user_id;
    }
    // Hàm delete(): kiểm tra user có quyền xóa comment hay không
    // - $user->isAdmin(): nếu user là admin -> được xóa mọi comment
    // - $user->id === $comment->user_id: nếu user là người tạo comment -> được xóa comment của mình
    // => Trả true/false để controller/authorize quyết định cho phép hay trả 403
}
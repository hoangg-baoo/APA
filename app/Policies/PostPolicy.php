<?php

namespace App\Policies;

use App\Models\Post;

use App\Models\User;

class PostPolicy
{
    public function update(User $user, Post $post): bool
    {
        return $user->isAdmin() || $user->id === $post->user_id;
    }
    // Hàm update(): kiểm tra user có quyền sửa Post hay không
    // - admin (isAdmin) được sửa mọi post
    // - owner (user_id của post) được sửa post của mình
    // => trả true/false để controller/authorize quyết định cho phép hay chặn (403)

    public function delete(User $user, Post $post): bool
    {
        return $user->isAdmin() || $user->id === $post->user_id;
    }
    // Hàm delete(): kiểm tra user có quyền xóa Post hay không
    // Rule giống update:
    // - admin được xóa mọi post
    // - owner được xóa post của mình
}
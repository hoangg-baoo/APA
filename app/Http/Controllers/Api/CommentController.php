<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;

class CommentController extends BaseApiController
{
    private function isPostOwner(Request $request, Post $post): bool
    {
        return (int)$request->user()->id === (int)$post->user_id;
    }

    private function isCommentOwner(Request $request, Comment $comment): bool
    {
        return (int)$request->user()->id === (int)$comment->user_id;
    }

    public function store(StoreCommentRequest $request, Post $post)
    {
        $data = $request->validated();

        $comment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $request->user()->id,
            'content' => $data['content'],
        ]);

        $comment->load(['user:id,name,role']);

        return $this->success([
            'comment' => [
                'id' => $comment->id,
                'post_id' => $comment->post_id,
                'user_id' => $comment->user_id,
                'content' => $comment->content,
                'created_at' => optional($comment->created_at)->toISOString(),
                'updated_at' => optional($comment->updated_at)->toISOString(),
                'user' => $comment->user ? [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                    'role' => $comment->user->role ?? null,
                ] : null,
            ],
        ], 'Comment created.');
    }

    public function update(UpdateCommentRequest $request, Comment $comment)
    {
        // only comment author can edit; admin not allowed to edit (even if admin)
        if (($request->user()->role ?? null) === 'admin') {
            abort(403, 'Admin cannot edit comments via this endpoint.');
        }

        if (!$this->isCommentOwner($request, $comment)) {
            abort(403, 'Only the comment author can edit this comment.');
        }

        $data = $request->validated();

        $comment->content = $data['content'];
        $comment->save();

        $comment->load(['user:id,name,role']);

        return $this->success([
            'comment' => [
                'id' => $comment->id,
                'post_id' => $comment->post_id,
                'user_id' => $comment->user_id,
                'content' => $comment->content,
                'created_at' => optional($comment->created_at)->toISOString(),
                'updated_at' => optional($comment->updated_at)->toISOString(),
                'user' => $comment->user ? [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                    'role' => $comment->user->role ?? null,
                ] : null,
            ],
        ], 'Comment updated.');
    }

    public function destroy(Request $request, Comment $comment)
    {
        $post = $comment->post;
        if (!$post) abort(404, 'Post not found.');

        // comment owner OR post owner can delete
        $canDelete = $this->isCommentOwner($request, $comment) || $this->isPostOwner($request, $post);
        if (!$canDelete) {
            abort(403, 'You cannot delete this comment.');
        }

        $comment->delete(); // hard delete

        return $this->success(null, 'Comment deleted.');
    }
}

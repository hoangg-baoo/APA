<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostController extends BaseApiController
{
    private function isOwner(Request $request, Post $post): bool
    {
        return (int)$request->user()->id === (int)$post->user_id;
    }

    private function isAdmin(Request $request): bool
    {
        return ($request->user()->role ?? null) === 'admin';
    }

    private function postImageUrl(?string $path): ?string
    {
        if (!$path) return null;
        return Storage::url($path);
    }

    private function formatPost(Post $p): array
    {
        return [
            'id' => $p->id,
            'user_id' => $p->user_id,
            'title' => $p->title,
            'content' => $p->content,
            'image_path' => $this->postImageUrl($p->image_path),

            // ✅ NEW: status info (FE có thể dùng nếu muốn)
            'status' => $p->status,
            'reviewed_by' => $p->reviewed_by,
            'reviewed_at' => optional($p->reviewed_at)->toISOString(),

            'created_at' => optional($p->created_at)->toISOString(),
            'updated_at' => optional($p->updated_at)->toISOString(),
            'user' => $p->relationLoaded('user') && $p->user ? [
                'id' => $p->user->id,
                'name' => $p->user->name,
                'role' => $p->user->role ?? null,
            ] : null,
            'comments_count' => isset($p->comments_count) ? (int)$p->comments_count : null,
        ];
    }

    // ✅ Public feed: chỉ approved
    public function index(Request $request)
    {
        $q = (string)$request->query('q', '');
        $limit = (int)$request->query('limit', 12);
        $limit = max(1, min(50, $limit));

        $query = Post::query()
            ->where('status', 'approved') // ✅ IMPORTANT
            ->with(['user:id,name,role'])
            ->withCount('comments')
            ->orderByDesc('created_at');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")
                  ->orWhere('content', 'like', "%{$q}%");
            });
        }

        $posts = $query->limit($limit)->get();

        return $this->success([
            'items' => $posts->map(fn($p) => $this->formatPost($p))->values(),
        ]);
    }

    // My posts: thấy cả pending/approved/rejected
    public function myPosts(Request $request)
    {
        $q = (string)$request->query('q', '');
        $limit = (int)$request->query('limit', 50);
        $limit = max(1, min(50, $limit));

        $query = Post::query()
            ->where('user_id', $request->user()->id)
            ->with(['user:id,name,role'])
            ->withCount('comments')
            ->orderByDesc('updated_at');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")
                  ->orWhere('content', 'like', "%{$q}%");
            });
        }

        $posts = $query->limit($limit)->get();

        return $this->success([
            'items' => $posts->map(fn($p) => $this->formatPost($p))->values(),
        ]);
    }

    // Show: nếu chưa approved, chỉ owner/admin được xem
    public function show(Request $request, Post $post)
    {
        $post->load(['user:id,name,role']);

        $canView = ($post->status === 'approved') || $this->isOwner($request, $post) || $this->isAdmin($request);
        if (!$canView) {
            return $this->error('This post is awaiting approval.', 403);
        }

        $comments = $post->comments()
            ->with(['user:id,name,role'])
            ->orderBy('created_at', 'asc')
            ->get();

        return $this->success([
            'post' => $this->formatPost($post->setAttribute('comments_count', $comments->count())),
            'comments' => $comments->map(function ($c) {
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
                        'role' => $c->user->role ?? null,
                    ] : null,
                ];
            })->values(),
        ]);
    }

    // Store: mặc định pending
    public function store(StorePostRequest $request)
    {
        $data = $request->validated();

        $path = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('community/posts', 'public');
        }

        $post = Post::create([
            'user_id' => $request->user()->id,
            'title' => $data['title'],
            'content' => $data['content'],
            'image_path' => $path,

            // ✅ NEW
            'status' => 'pending',
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);

        $post->load(['user:id,name,role']);
        $post->loadCount('comments');

        return $this->success([
            'post' => $this->formatPost($post),
        ], 'Post created and awaiting admin approval.');
    }

    // Update: owner sửa => reset pending để duyệt lại
    public function update(UpdatePostRequest $request, Post $post)
    {
        if (!$this->isOwner($request, $post)) {
            abort(403, 'Only the post owner can update this post.');
        }

        $data = $request->validated();

        $removeImage = (bool)($data['remove_image'] ?? false);

        if ($removeImage && $post->image_path) {
            Storage::disk('public')->delete($post->image_path);
            $post->image_path = null;
        }

        if ($request->hasFile('image')) {
            if ($post->image_path) Storage::disk('public')->delete($post->image_path);
            $post->image_path = $request->file('image')->store('community/posts', 'public');
        }

        if (array_key_exists('title', $data)) $post->title = $data['title'];
        if (array_key_exists('content', $data)) $post->content = $data['content'];

        // ✅ NEW: sửa nội dung => pending lại
        $post->status = 'pending';
        $post->reviewed_by = null;
        $post->reviewed_at = null;

        $post->save();

        $post->load(['user:id,name,role']);
        $post->loadCount('comments');

        return $this->success([
            'post' => $this->formatPost($post),
        ], 'Post updated and awaiting admin approval.');
    }

    public function destroy(Request $request, Post $post)
    {
        if (!$this->isOwner($request, $post)) {
            abort(403, 'Only the post owner can delete this post.');
        }

        if ($post->image_path) {
            Storage::disk('public')->delete($post->image_path);
        }

        $post->delete(); // hard delete; comments cascade

        return $this->success(null, 'Post deleted.');
    }
}

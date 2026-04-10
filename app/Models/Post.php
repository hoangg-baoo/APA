<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;
    // Gắn trait HasFactory để dùng factory cho seeding/testing

    protected $fillable = [
        'user_id',
        'title',
        'content',
        'image_path',

        // ✅ NEW
        'status',
        'reviewed_by',
        'reviewed_at',
    ];
    // $fillable: các field cho phép mass assignment (Post::create([...]) / update([...]))
    // - user_id: người đăng bài (author)
    // - title: tiêu đề bài viết
    // - content: nội dung bài viết
    // - image_path: đường dẫn ảnh đính kèm (nếu có)
    // - status: trạng thái duyệt bài (vd: pending/approved/rejected/hidden...)
    // - reviewed_by: user_id của người duyệt (admin/moderator)
    // - reviewed_at: thời điểm duyệt

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];
    // $casts: tự động ép kiểu
    // - reviewed_at: lấy ra thành datetime (Carbon) để format/so sánh thời gian dễ

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    // Relationship: Post thuộc về 1 User (tác giả)
    // Mặc định posts.user_id -> users.id
    // Dùng: $post->user (lấy thông tin người đăng)

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
    // Relationship: Post thuộc về 1 User khác với vai trò "reviewer"
    // - User model vẫn là User::class
    // - Nhưng khóa ngoại không phải user_id mà là reviewed_by
    // Dùng: $post->reviewer (lấy thông tin người duyệt)

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
    // Relationship: 1 Post có nhiều Comment
    // Mặc định comments.post_id -> posts.id
    // Dùng: $post->comments (lấy danh sách bình luận)
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Foundation\Auth\User as Authenticatable;

use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
// Khai báo class User kế thừa Authenticatable để dùng được Auth (login/session/token)
{
    use HasFactory, Notifiable, SoftDeletes;
    // Gắn 3 trait:
    // - HasFactory: dùng factory cho seed/test
    // - Notifiable: gửi notification
    // - SoftDeletes: có deleted_at, gọi delete() sẽ set deleted_at thay vì xóa row

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar',
        'bio',
        'status',
    ];
    // $fillable: danh sách field cho phép "mass assignment"
    // Nghĩa là bạn có thể User::create($request->all()) mà chỉ các field này được ghi vào DB
    // Giúp tránh lỗ hổng: user gửi thêm field nguy hiểm (vd: is_admin) để tự cấp quyền

    protected $hidden = [
        'password',
        'remember_token',
    ];
    // $hidden: các field sẽ bị ẩn khi convert User -> array/json
    // Tránh lộ password hash và remember_token khi trả API response

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    // $casts: tự động ép kiểu khi đọc/ghi
    // email_verified_at sẽ thành đối tượng datetime (Carbon) thay vì string

    public function tanks()
    {
        return $this->hasMany(Tank::class);
    }
    // Relationship: 1 user có nhiều tanks
    // Laravel mặc định tìm khóa ngoại tanks.user_id trỏ tới users.id

    public function questions()
    {
        return $this->hasMany(Question::class);
    }
    // Relationship: 1 user có nhiều questions (Q&A)
    // Mặc định questions.user_id

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }
    // Relationship: 1 user có nhiều answers
    // Mặc định answers.user_id

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
    // Relationship: 1 user có nhiều posts (community)
    // Mặc định posts.user_id

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
    // Relationship: 1 user có nhiều comments
    // Mặc định comments.user_id

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
    // Helper method: kiểm tra user có role admin không
    // Dùng trong middleware/admin gate để cho vào khu admin

    public function isExpert(): bool
    {
        return $this->role === 'expert';
    }
    // Helper method: kiểm tra user có role expert không
    // Thường dùng để cho phép trả lời/duyệt nội dung kiểu chuyên gia

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
    // Helper method: kiểm tra trạng thái tài khoản có đang hoạt động không
    // Middleware active_user thường dùng cái này để chặn user bị khóa

    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }
    // Helper method: kiểm tra user có bị block không
    // Thường dùng trong admin panel hoặc middleware để trả 403/redirect
}
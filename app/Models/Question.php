<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;
    // Gắn trait HasFactory để dùng factory cho seeding/testing

    protected $fillable = [
        'user_id',
        'tank_id',
        'title',
        'content',
        'image_path',
        'status',
    ];
    // $fillable: các field cho phép mass assignment (Question::create([...]) / update([...]))
    // - user_id: người tạo câu hỏi (author)
    // - tank_id: câu hỏi gắn với bể nào (ngữ cảnh tank-centric)
    // - title: tiêu đề câu hỏi
    // - content: nội dung chi tiết
    // - image_path: đường dẫn ảnh đính kèm (nếu có)
    // - status: trạng thái (vd: pending/approved/rejected...) để moderation

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    // Relationship: Question thuộc về 1 User
    // Mặc định questions.user_id -> users.id
    // Dùng: $question->user (lấy thông tin người hỏi)

    public function tank()
    {
        return $this->belongsTo(Tank::class);
    }
    // Relationship: Question thuộc về 1 Tank
    // Mặc định questions.tank_id -> tanks.id
    // Dùng: $question->tank (biết câu hỏi đang nói về bể nào)

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }
    // Relationship: 1 Question có nhiều Answer
    // Mặc định answers.question_id -> questions.id
    // Dùng: $question->answers (lấy danh sách câu trả lời)
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    use HasFactory;
    // Gắn trait HasFactory để dùng factory cho seeding/testing

    protected $fillable = [
        'question_id',
        'user_id',
        'content',
        'is_accepted',
    ];
    // $fillable: các field cho phép mass assignment (Answer::create([...]) / update([...]))
    // - question_id: câu trả lời thuộc câu hỏi nào
    // - user_id: người trả lời (author)
    // - content: nội dung câu trả lời
    // - is_accepted: đánh dấu câu trả lời được chấp nhận (best answer)

    protected $casts = [
        'is_accepted' => 'boolean',
    ];
    // $casts: tự động ép kiểu
    // - is_accepted: lấy từ DB ra sẽ là true/false thay vì 0/1 hoặc "0"/"1"

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
    // Relationship: Answer thuộc về 1 Question
    // Mặc định answers.question_id -> questions.id
    // Dùng: $answer->question (biết answer này của câu hỏi nào)

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    // Relationship: Answer thuộc về 1 User
    // Mặc định answers.user_id -> users.id
    // Dùng: $answer->user (biết ai trả lời)
}
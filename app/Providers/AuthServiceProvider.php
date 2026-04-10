<?php

namespace App\Providers;

use App\Models\Tank;      
use App\Models\Question;  
use App\Models\Answer;    
use App\Models\Post;      
use App\Models\Comment;   

use App\Policies\TankPolicy;      
use App\Policies\QuestionPolicy;  
use App\Policies\AnswerPolicy;    
use App\Policies\PostPolicy;     
use App\Policies\CommentPolicy;   

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Tank::class      => TankPolicy::class,
        Question::class  => QuestionPolicy::class,
        Answer::class    => AnswerPolicy::class,
        Post::class      => PostPolicy::class,
        Comment::class   => CommentPolicy::class,
    ];
    // $policies: map (Model => Policy)
    // Khi bạn gọi: $this->authorize('update', $tank)
    // Laravel sẽ tự tìm TankPolicy và gọi method update() trong đó
    // Tương tự cho Question/Answer/Post/Comment

    public function boot(): void
    {
        $this->registerPolicies();
    }
    // boot(): chạy khi app khởi động
    // registerPolicies(): đăng ký toàn bộ mapping trong $policies vào hệ thống authorization của Laravel
    // Nếu không gọi, Laravel sẽ không biết các policy này để authorize()
}
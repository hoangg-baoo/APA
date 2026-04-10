<?php
// Mở thẻ PHP, bắt đầu file route

use Illuminate\Http\Request;                 // Import Request: dùng để lấy thông tin request trong route closure (vd /ping)
use Illuminate\Support\Facades\Route;        // Import Route facade: khai báo routes (GET/POST/PUT/DELETE...)

// ===== WEB Controllers (render Blade / xử lý web form) =====
use App\Http\Controllers\Web\AuthWebController;      // Controller xử lý login/register/reset password (WEB session)
use App\Http\Controllers\Web\PlantWebController;     // Controller render Plant Library pages (WEB)

// ✅ NEW: Home controller (WEB)
use App\Http\Controllers\Web\HomeController;         // Controller trang home "/" (load stats thật)

// ===== API Controllers (trả JSON cho fetch/AJAX) =====
use App\Http\Controllers\Api\AuthController;         // API auth: register/login/me/logout (JSON)
use App\Http\Controllers\Api\PlantLibraryController; // API plant library (JSON, cần login trong web.php)
use App\Http\Controllers\Api\WaterLogReminderController;
// ===== Tank core (API) =====
use App\Http\Controllers\Api\TankApiController;      // API CRUD tanks
use App\Http\Controllers\Api\TankPlantController;    // API attach/detach/restore plant trong tank
use App\Http\Controllers\Api\IotTelemetryController;

// ===== Logs (API) =====
use App\Http\Controllers\Api\WaterLogController;     // API water logs (index/store/update/destroy/restore)
use App\Http\Controllers\Api\PlantLogController;     // API plant logs (index/store/update/destroy/restore + tankPlants)

// ===== Q&A (API) =====
use App\Http\Controllers\Api\QuestionController;     // API questions (index/show/store/update/destroy...)
use App\Http\Controllers\Api\AnswerController;       // API answers (store/update/destroy...)

// ✅ TV6 (Community API)
use App\Http\Controllers\Api\PostController;         // API posts (community)
use App\Http\Controllers\Api\CommentController;      // API comments (community)

// ===== Admin API controllers =====
use App\Http\Controllers\Api\Admin\UserManagementController;   // Admin API quản lý user (CRUD + role/status + restore)
use App\Http\Controllers\Api\Admin\ContentModerationController;// Admin API moderation Q&A + posts/comments
use App\Http\Controllers\Api\Admin\PlantAdminController;       // Admin API CRUD plant library

// ===== Identify (API) =====
use App\Http\Controllers\Api\IdentifyPlantController; // API identify: image-search + add-to-tank + history
use App\Http\Requests\IdentifySessionCreateRequest;
use App\Http\Requests\IdentifyRegionRequest;
use App\Http\Requests\IdentifySessionAddToTankRequest;

// ✅ NEW: Admin dashboard controller (WEB)
use App\Http\Controllers\Web\Admin\AdminDashboardController; // Controller render dashboard admin (load data thật)

// HOME (✅ CHANGED: use controller to load real stats)
Route::get('/', [HomeController::class, 'index'])->name('home'); // GET / -> gọi HomeController@index, đặt tên route = home

/*
|--------------------------------------------------------------------------
| ✅ PLANT LIBRARY UI (PUBLIC)
|--------------------------------------------------------------------------
*/
Route::prefix('plant-library')->group(function () {                 // Gom nhóm route có prefix /plant-library
    Route::get('/', [PlantWebController::class, 'index'])->name('plants.index'); // GET /plant-library -> render trang list plants
    Route::get('/{plant}', [PlantWebController::class, 'show'])     // GET /plant-library/{plant} -> render trang detail plant
        ->whereNumber('plant')                                      // Ràng buộc {plant} phải là số (tránh chuỗi linh tinh)
        ->name('plants.show');                                      // Đặt tên route plants.show
});

// TANKS UI
Route::prefix('tanks')->group(function () {                         // Gom nhóm route UI tank có prefix /tanks
    Route::view('/my_tanks',          'tanks.my_tanks')->name('tanks.my_tanks');             // GET /tanks/my_tanks -> trả Blade view trực tiếp (không qua controller)
    Route::view('/tank_detail',       'tanks.tank_detail')->name('tanks.tank_detail');       // GET /tanks/tank_detail -> view trang chi tiết bể (JS sẽ fetch API)
    Route::view('/add_plant_to_tank', 'tanks.add_plant_to_tank')->name('tanks.add_plant_to_tank'); // GET /tanks/add_plant_to_tank -> view trang add plant
    Route::view('/tank_form',         'tanks.tank_form')->name('tanks.tank_form');           // GET /tanks/tank_form -> view form create/update tank
});

// Q&A UI
Route::prefix('qa')->group(function () {                            // Gom nhóm route UI Q&A có prefix /qa
    Route::view('/questions_list',  'qa.questions_list')->name('qa.questions_list');  // Trang list câu hỏi
    Route::view('/my_questions',    'qa.my_questions')->name('qa.my_questions');      // Trang câu hỏi của tôi
    Route::view('/ask_question',    'qa.ask_question')->name('qa.ask_question');      // Trang form hỏi câu hỏi
    Route::view('/question_detail', 'qa.question_detail')->name('qa.question_detail');// Trang chi tiết câu hỏi
});

// COMMUNITY UI
Route::prefix('community')->group(function () {                     // Gom nhóm route UI community có prefix /community
    Route::view('/posts_list',  'community.posts_list')->name('community.posts_list'); // Trang list posts
    Route::view('/post_detail', 'community.post_detail')->name('community.post_detail'); // Trang detail post
    Route::view('/my_posts',    'community.my_posts')->name('community.my_posts'); // Trang posts của tôi
    Route::view('/create_post', 'community.create_post')->name('community.create_post'); // Trang tạo post
});

// PLANTLOG UI (protect UI)
Route::prefix('plantlog')->middleware(['auth', 'active_user'])->group(function () { // Prefix /plantlog + yêu cầu login + user active
    Route::view('/plant_logs_list', 'plantlog.plant_logs_list')->name('plantlog.plant_logs_list'); // Trang list plant logs
    Route::view('/plant_log_form',  'plantlog.plant_log_form')->name('plantlog.plant_log_form');   // Trang form plant log
});

// MONITORING UI (protect UI)
Route::prefix('monitoring')->middleware(['auth', 'active_user'])->group(function () { // Prefix /monitoring + yêu cầu login + active
    Route::view('/water_logs', 'monitoring.water_logs')->name('monitoring.water_logs'); // Trang water monitoring (JS gọi /api/.../water-logs)
});

// IMAGE IDENTIFY UI (protect)
Route::prefix('image')->middleware(['auth', 'active_user'])->group(function () { // Prefix /image + yêu cầu login + active
    Route::view('/identify_plant', 'image.identify_plant')->name('image.identify_plant');     // Trang identify plant (upload ảnh, search)
    Route::view('/identify_history', 'image.identify_history')->name('image.identify_history'); // Trang lịch sử identify
});

/*
|--------------------------------------------------------------------------
| AUTH UI (WEB - Session)
|--------------------------------------------------------------------------
*/
Route::view('/register', 'auth.register_user')->name('auth.register_user'); // GET /register -> view form đăng ký (WEB)

// Forgot / Reset password (WEB)
Route::get('/forgot-password', [AuthWebController::class, 'showForgotPassword'])->name('password.request'); // Hiện form quên mật khẩu
Route::post('/forgot-password', [AuthWebController::class, 'sendResetLink'])
    ->middleware('throttle:3,5')    // Tối đa 3 lần / 5 phút (chặn spam email reset)
    ->name('password.email');
Route::get('/reset-password/{token}', [AuthWebController::class, 'showResetPassword'])->name('password.reset'); // Hiện form nhập mật khẩu mới với token
Route::post('/reset-password', [AuthWebController::class, 'resetPassword'])->name('password.update');      // Submit mật khẩu mới

// USER login/logout (WEB)
Route::get('/login',  [AuthWebController::class, 'showUserLogin'])->name('login');         // GET /login -> form login user
Route::post('/login', [AuthWebController::class, 'loginUser'])                             // POST /login -> xử lý login user (tạo session)
    ->middleware('throttle:10,1')   // Tối đa 10 lần / 1 phút (chặn brute-force)
    ->name('login.perform');
Route::post('/logout', [AuthWebController::class, 'logout'])->name('logout');              // POST /logout -> logout user (xóa session)

// ADMIN login/logout (WEB)
Route::get('/admin/login',  [AuthWebController::class, 'showAdminLogin'])->name('admin.login');            // GET /admin/login -> form login admin
Route::post('/admin/login', [AuthWebController::class, 'loginAdmin'])                                      // POST /admin/login -> xử lý login admin (session/guard admin)
    ->middleware('throttle:5,1')    // Tối đa 5 lần / 1 phút (admin area bảo mật cao hơn)
    ->name('admin.login.perform');
Route::post('/admin/logout', [AuthWebController::class, 'adminLogout'])->name('admin.logout');             // POST /admin/logout -> logout admin

// ADMIN UI
Route::prefix('admin')->middleware(['admin_auth', 'active_user', 'admin'])->group(function () { // Prefix /admin + middleware: admin_auth + active_user + admin(role)

    // ✅ CHANGED: dashboard now uses controller to load real data
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard'); // GET /admin/dashboard -> controller load stats thật

    Route::view('/users',        'admin.users_list')->name('admin.users.index');   // Trang UI list users (admin)
    Route::view('/users/create', 'admin.user_form')->name('admin.users.create');   // Trang UI create user (admin)

    Route::view('/qa', 'admin.qa_moderation')->name('admin.qa_moderation');        // Trang UI moderation Q&A
    Route::view('/qa/{id}', 'admin.qa_detail')->whereNumber('id')->name('admin.qa_detail'); // Trang UI detail 1 câu hỏi Q&A trong admin

    Route::view('/community-posts', 'admin.posts_moderation')->name('admin.posts_moderation'); // Trang UI moderation posts

    // ✅ NEW: Post detail admin screen
    Route::view('/community-posts/{id}', 'admin.post_detail_admin') // Trang UI chi tiết 1 post trong admin
        ->whereNumber('id')                                         // {id} phải là số
        ->name('admin.post_detail_admin');                           // đặt tên route

    Route::view('/plants',        'admin.plants_list')->name('admin.plants_list'); // Trang UI list plants (admin)
    Route::view('/plants/create', 'admin.plant_form')->name('admin.plant_form');   // Trang UI create plant (admin)
});

/*
|--------------------------------------------------------------------------
| RESTORE ROUTES (WEB)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active_user'])->group(function () { // Nhóm route restore dạng web-level, cần login + active
    Route::patch('/water-logs/{waterLog}/restore', [WaterLogController::class, 'restore']);    // PATCH /water-logs/{id}/restore -> khôi phục soft delete water log
    Route::patch('/plant-logs/{plantLog}/restore', [PlantLogController::class, 'restore']);    // PATCH /plant-logs/{id}/restore -> khôi phục soft delete plant log
    Route::patch('/tank-plants/{tankPlant}/restore', [TankPlantController::class, 'restore']); // PATCH /tank-plants/{id}/restore -> khôi phục soft delete tankPlant
    // Lưu ý: bạn cũng có route restore tương tự trong /api/... phía dưới (đang bị trùng chức năng)
});

/*
|--------------------------------------------------------------------------
| API (auth + active_user)
|--------------------------------------------------------------------------
*/
Route::prefix('api')->group(function () { // Prefix /api cho các endpoint trả JSON (nhưng vẫn nằm trong web.php)

    // Public (có rate limiting để chặn brute-force / tạo tài khoản hàng loạt)
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:5,1');  // Tối đa 5 đăng ký / 1 phút / IP
    Route::post('/login',    [AuthController::class, 'login'])
        ->middleware('throttle:10,1'); // Tối đa 10 lần thử đăng nhập / 1 phút / IP

    // Need login + active
    Route::middleware(['auth', 'active_user'])->group(function () { // Nhóm API cần login (session auth) + user active

        Route::get('/me',      [AuthController::class, 'me']);      // GET /api/me -> trả info user hiện tại
        Route::post('/logout', [AuthController::class, 'logout']);  // POST /api/logout -> logout (xóa session/token tùy code)

        Route::get('/plants', [PlantLibraryController::class, 'index']); // GET /api/plants -> list plants (đang require login)

        // TV2 - Tanks
        Route::get('/tanks',           [TankApiController::class, 'index']);   // GET /api/tanks -> list tanks của user
        Route::post('/tanks',          [TankApiController::class, 'store']);   // POST /api/tanks -> tạo tank mới
        Route::get('/tanks/{tank}',    [TankApiController::class, 'show'])->whereNumber('tank');    // GET /api/tanks/{id} -> detail tank
        Route::put('/tanks/{tank}',    [TankApiController::class, 'update'])->whereNumber('tank');  // PUT /api/tanks/{id} -> update tank
        Route::delete('/tanks/{tank}', [TankApiController::class, 'destroy'])->whereNumber('tank'); // DELETE /api/tanks/{id} -> xóa tank

        Route::get('/iot/tanks/{tank}/latest', [IotTelemetryController::class, 'latest'])
            ->whereNumber('tank')
            ->name('api.iot.telemetry.latest');
        Route::post('/identify/session/{session}/propose-regions', [IdentifyPlantController::class, 'proposeRegions'])
            ->whereNumber('session')
            ->name('api.identify.session.propose_regions');

        Route::delete('/identify/session/{session}/regions/{region}', [IdentifyPlantController::class, 'deleteRegion'])
            ->whereNumber('session')
            ->whereNumber('region')
            ->name('api.identify.session.delete_region');

        // TV7 - Identify / Plant Image
        Route::post('/image-search', [IdentifyPlantController::class, 'search'])->name('api.identify.search'); // POST /api/image-search -> upload ảnh + embed + search
        Route::post('/identify/add-to-tank', [IdentifyPlantController::class, 'addToTank'])->name('api.identify.add_to_tank'); // POST /api/identify/add-to-tank -> add kết quả vào tank
        Route::get('/identify/history', [IdentifyPlantController::class, 'history'])->name('api.identify.history'); // GET /api/identify/history -> list history
        Route::get('/identify/history/{id}', [IdentifyPlantController::class, 'historyShow']) // GET /api/identify/history/{id} -> detail 1 record
            ->whereNumber('id')                                                              // {id} phải là số
            ->name('api.identify.history_show');                                              // đặt tên route

        // TV2 - TankPlants
        Route::post('/tanks/{tank}/plants',       [TankPlantController::class, 'store'])->whereNumber('tank'); // POST /api/tanks/{tank}/plants -> attach plant vào tank
        Route::delete('/tank-plants/{tankPlant}', [TankPlantController::class, 'destroy'])->whereNumber('tankPlant'); // DELETE /api/tank-plants/{id} -> detach/soft delete tankPlant

        Route::patch('/tank-plants/{tankPlant}/restore', [TankPlantController::class, 'restore']) // PATCH /api/tank-plants/{id}/restore -> restore tankPlant
            ->whereNumber('tankPlant')                                                            // {tankPlant} phải là số
            ->name('api.tank_plants.restore');                                                     // đặt tên route
        
        Route::post('/identify/session', [IdentifyPlantController::class, 'createSession'])
            ->name('api.identify.session.create');

        Route::post('/identify/session/{session}/regions', [IdentifyPlantController::class, 'addRegion'])
            ->whereNumber('session')
            ->name('api.identify.session.add_region');

        Route::get('/identify/session/{session}', [IdentifyPlantController::class, 'showSession'])
            ->whereNumber('session')
            ->name('api.identify.session.show');

        Route::post('/identify/session/{session}/add-to-tank', [IdentifyPlantController::class, 'addSessionToTankBatch'])
            ->whereNumber('session')
            ->name('api.identify.session.add_to_tank_batch');

        // TV4 - Water logs
        Route::get('/tanks/{tank}/water-logs', [WaterLogController::class, 'index']) // GET /api/tanks/{tank}/water-logs -> list logs + stats/advice (tùy code)
            ->whereNumber('tank')                                                    // {tank} phải là số
            ->name('api.water_logs.index');                                          // đặt tên route

        Route::post('/tanks/{tank}/water-logs', [WaterLogController::class, 'store']) // POST /api/tanks/{tank}/water-logs -> tạo log nước
            ->whereNumber('tank')                                                     // {tank} phải là số
            ->name('api.water_logs.store');                                           // đặt tên route

        Route::put('/water-logs/{waterLog}', [WaterLogController::class, 'update'])   // PUT /api/water-logs/{id} -> update log nước
            ->whereNumber('waterLog')                                                 // {waterLog} phải là số
            ->name('api.water_logs.update');                                          // đặt tên route

        Route::delete('/water-logs/{waterLog}', [WaterLogController::class, 'destroy']) // DELETE /api/water-logs/{id} -> soft delete log nước
            ->whereNumber('waterLog')                                                   // {waterLog} phải là số
            ->name('api.water_logs.destroy');                                           // đặt tên route

        Route::patch('/water-logs/{waterLog}/restore', [WaterLogController::class, 'restore']) // PATCH /api/water-logs/{id}/restore -> restore log nước
            ->whereNumber('waterLog')                                                       // {waterLog} phải là số
            ->name('api.water_logs.restore');                                                // đặt tên route

        Route::post('/tanks/{tank}/water-logs/import', [WaterLogController::class, 'import'])
            ->whereNumber('tank')
            ->name('api.water_logs.import');

        Route::get('/tanks/{tank}/water-log-reminder', [WaterLogReminderController::class, 'show'])
            ->whereNumber('tank')
            ->name('api.water_log_reminder.show');

        Route::put('/tanks/{tank}/water-log-reminder', [WaterLogReminderController::class, 'upsert'])
            ->whereNumber('tank')
            ->name('api.water_log_reminder.upsert');

        // TV4 - Plant logs
        Route::get('/tanks/{tank}/tank-plants', [PlantLogController::class, 'tankPlants']) // GET /api/tanks/{tank}/tank-plants -> list tankPlant để chọn cây khi log
            ->whereNumber('tank')                                                          // {tank} phải là số
            ->name('api.plant_logs.tank_plants');                                           // đặt tên route

        Route::get('/tank-plants/{tankPlant}/plant-logs', [PlantLogController::class, 'index']) // GET /api/tank-plants/{tp}/plant-logs -> list plant logs của 1 tankPlant
            ->whereNumber('tankPlant')                                                        // {tankPlant} phải là số
            ->name('api.plant_logs.index');                                                    // đặt tên route

        Route::post('/tank-plants/{tankPlant}/plant-logs', [PlantLogController::class, 'store']) // POST /api/tank-plants/{tp}/plant-logs -> tạo plant log (có thể upload ảnh)
            ->whereNumber('tankPlant')                                                          // {tankPlant} phải là số
            ->name('api.plant_logs.store');                                                      // đặt tên route

        Route::put('/plant-logs/{plantLog}', [PlantLogController::class, 'update']) // PUT /api/plant-logs/{id} -> update plant log
            ->whereNumber('plantLog')                                               // {plantLog} phải là số
            ->name('api.plant_logs.update');                                         // đặt tên route

        Route::delete('/plant-logs/{plantLog}', [PlantLogController::class, 'destroy']) // DELETE /api/plant-logs/{id} -> soft delete plant log
            ->whereNumber('plantLog')                                                  // {plantLog} phải là số
            ->name('api.plant_logs.destroy');                                          // đặt tên route

        Route::patch('/plant-logs/{plantLog}/restore', [PlantLogController::class, 'restore']) // PATCH /api/plant-logs/{id}/restore -> restore plant log
            ->whereNumber('plantLog')                                                       // {plantLog} phải là số
            ->name('api.plant_logs.restore');                                                // đặt tên route

        // TV5 - Q&A
        Route::get('/questions', [QuestionController::class, 'index'])->name('api.questions.index'); // GET /api/questions -> list questions
        Route::get('/my-questions', [QuestionController::class, 'myQuestions'])->name('api.questions.my'); // GET /api/my-questions -> list questions của user

        Route::get('/questions/{question}', [QuestionController::class, 'show']) // GET /api/questions/{id} -> detail question
            ->whereNumber('question')                                            // {question} phải là số
            ->name('api.questions.show');                                        // đặt tên route

        Route::post('/questions', [QuestionController::class, 'store'])->name('api.questions.store'); // POST /api/questions -> tạo question

        Route::put('/questions/{question}', [QuestionController::class, 'update']) // PUT /api/questions/{id} -> update question
            ->whereNumber('question')                                              // {question} phải là số
            ->name('api.questions.update');                                        // đặt tên route

        Route::delete('/questions/{question}', [QuestionController::class, 'destroy']) // DELETE /api/questions/{id} -> xóa question (soft/hard tùy model)
            ->whereNumber('question')                                                // {question} phải là số
            ->name('api.questions.destroy');                                         // đặt tên route

        Route::post('/questions/{question}/answers', [AnswerController::class, 'store']) // POST /api/questions/{q}/answers -> tạo answer cho question
            ->whereNumber('question')                                                 // {question} phải là số
            ->name('api.answers.store');                                              // đặt tên route

        Route::put('/answers/{answer}', [AnswerController::class, 'update']) // PUT /api/answers/{id} -> update answer
            ->whereNumber('answer')                                          // {answer} phải là số
            ->name('api.answers.update');                                    // đặt tên route

        Route::delete('/answers/{answer}', [AnswerController::class, 'destroy']) // DELETE /api/answers/{id} -> xóa answer
            ->whereNumber('answer')                                             // {answer} phải là số
            ->name('api.answers.destroy');                                      // đặt tên route

        Route::patch('/questions/{question}/answers/{answer}/accept', [QuestionController::class, 'acceptAnswer']) // PATCH -> accept best answer
            ->whereNumber('question')                                                                              // {question} phải là số
            ->whereNumber('answer')                                                                                // {answer} phải là số
            ->name('api.questions.accept_answer');                                                                  // đặt tên route

        // TV6 - COMMUNITY
        Route::get('/posts', [PostController::class, 'index'])->name('api.posts.index');       // GET /api/posts -> list posts (lọc status tùy controller)
        Route::get('/my-posts', [PostController::class, 'myPosts'])->name('api.posts.my');     // GET /api/my-posts -> posts của user
        Route::get('/posts/{post}', [PostController::class, 'show'])->whereNumber('post')->name('api.posts.show'); // GET /api/posts/{id} -> detail post
        Route::post('/posts', [PostController::class, 'store'])->name('api.posts.store');     // POST /api/posts -> tạo post
        Route::put('/posts/{post}', [PostController::class, 'update'])->whereNumber('post')->name('api.posts.update'); // PUT /api/posts/{id} -> update post
        Route::delete('/posts/{post}', [PostController::class, 'destroy'])->whereNumber('post')->name('api.posts.destroy'); // DELETE /api/posts/{id} -> xóa post

        Route::post('/posts/{post}/comments', [CommentController::class, 'store']) // POST /api/posts/{id}/comments -> tạo comment cho post
            ->whereNumber('post')                                                  // {post} phải là số
            ->name('api.comments.store');                                          // đặt tên route

        Route::put('/comments/{comment}', [CommentController::class, 'update']) // PUT /api/comments/{id} -> update comment
            ->whereNumber('comment')                                             // {comment} phải là số
            ->name('api.comments.update');                                       // đặt tên route

        Route::delete('/comments/{comment}', [CommentController::class, 'destroy']) // DELETE /api/comments/{id} -> xóa comment
            ->whereNumber('comment')                                               // {comment} phải là số
            ->name('api.comments.destroy');                                        // đặt tên route
    });
});

/*
|--------------------------------------------------------------------------
| ADMIN API
|--------------------------------------------------------------------------
*/
Route::prefix('api/admin')->middleware(['admin_auth', 'active_user', 'admin'])->group(function () { // Prefix /api/admin + middleware admin

    Route::get('/plants',            [PlantAdminController::class, 'index'])->name('api.admin.plants.index');    // GET /api/admin/plants -> list plants (admin)
    Route::post('/plants',           [PlantAdminController::class, 'store'])->name('api.admin.plants.store');    // POST /api/admin/plants -> tạo plant (admin)
    Route::get('/plants/{plant}',    [PlantAdminController::class, 'show'])->name('api.admin.plants.show');      // GET /api/admin/plants/{id} -> detail plant (admin)
    Route::put('/plants/{plant}',    [PlantAdminController::class, 'update'])->name('api.admin.plants.update');  // PUT /api/admin/plants/{id} -> update plant (admin)
    Route::delete('/plants/{plant}', [PlantAdminController::class, 'destroy'])->name('api.admin.plants.destroy');// DELETE /api/admin/plants/{id} -> delete plant (admin)

    Route::get('/users',                 [UserManagementController::class, 'index']);               // GET /api/admin/users -> list users
    Route::get('/users/{user}',          [UserManagementController::class, 'show']);                // GET /api/admin/users/{id} -> detail user
    Route::post('/users',                [UserManagementController::class, 'store']);               // POST /api/admin/users -> tạo user (admin)
    Route::put('/users/{user}',          [UserManagementController::class, 'update']);              // PUT /api/admin/users/{id} -> update user
    Route::patch('/users/{user}/role',   [UserManagementController::class, 'updateRole']);          // PATCH /api/admin/users/{id}/role -> đổi role
    Route::patch('/users/{user}/status', [UserManagementController::class, 'updateStatus']);        // PATCH /api/admin/users/{id}/status -> đổi status (active/blocked)
    Route::delete('/users/{user}',       [UserManagementController::class, 'destroy']);             // DELETE /api/admin/users/{id} -> xóa (soft/hard tùy model)

    Route::patch('/users/{id}/restore',  [UserManagementController::class, 'restore'])->whereNumber('id'); // PATCH /api/admin/users/{id}/restore -> restore soft delete user

    // Q&A moderation API
    Route::get('/questions', [ContentModerationController::class, 'questionsIndex']);                    // GET /api/admin/questions -> list questions (moderation)
    Route::get('/questions/{question}', [ContentModerationController::class, 'questionShow']);           // GET /api/admin/questions/{id} -> detail question (moderation)
    Route::patch('/questions/{question}/status', [ContentModerationController::class, 'updateQuestionStatus']); // PATCH -> đổi status question (approve/reject...)
    Route::delete('/questions/{question}', [ContentModerationController::class, 'deleteQuestion']);      // DELETE -> xóa question (moderation)
    Route::delete('/answers/{answer}', [ContentModerationController::class, 'deleteAnswer']);            // DELETE -> xóa answer (moderation)

    // ✅ NEW: Posts moderation API
    Route::get('/posts', [ContentModerationController::class, 'postsIndex']);                             // GET /api/admin/posts -> list posts (moderation)
    Route::get('/posts/{post}', [ContentModerationController::class, 'postShow'])->whereNumber('post');   // GET /api/admin/posts/{id} -> detail post
    Route::patch('/posts/{post}/status', [ContentModerationController::class, 'updatePostStatus'])->whereNumber('post'); // PATCH -> đổi status post

    // Community moderation (delete giữ nguyên)
    Route::delete('/posts/{post}',         [ContentModerationController::class, 'deletePost'])->whereNumber('post');       // DELETE -> xóa post
    Route::delete('/comments/{comment}',   [ContentModerationController::class, 'deleteComment'])->whereNumber('comment'); // DELETE -> xóa comment

    Route::get('/ping', function (Request $request) { // GET /api/admin/ping -> endpoint test "admin area OK"
        return response()->json([                       // Trả JSON response
            'success' => true,                          // success flag
            'data' => [                                 // data payload
                'message' => 'Admin area OK',           // message trong data
                'user' => [                             // trả thêm info user hiện tại (đã login admin)
                    'id' => $request->user()->id,       // lấy id user từ auth session/guard
                    'name' => $request->user()->name,   // lấy name user từ auth session/guard
                ],
            ],
            'message' => 'You are admin and active.',   // message tổng (thường frontend show)
        ]);
    });
});
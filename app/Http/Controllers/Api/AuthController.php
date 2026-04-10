<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\RegisterRequest;

use App\Http\Requests\LoginRequest;

use App\Models\User;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Hash;

class AuthController extends BaseApiController
// Khai báo AuthController kế thừa BaseApiController (thường có helper success()/error() trả JSON chuẩn)
{
    public function register(RegisterRequest $request)
    {
        // register(): endpoint đăng ký user (POST /api/register)

        $data = $request->validated();
        // validated(): lấy dữ liệu đã qua validate trong RegisterRequest (chỉ field hợp lệ)

        $user = User::create([
            // User::create(): tạo user mới bằng mass assignment (cần User::$fillable có các field này)

            'name'     => $data['name'],
            // Gán tên user từ request

            'email'    => $data['email'],
            // Gán email từ request

            'password' => Hash::make($data['password']),
            // Hash mật khẩu trước khi lưu DB (không lưu plain text)

            'role'     => 'user',
            // Set role mặc định là user (không cho client tự set role để tránh tự thành admin)

            'status'   => 'active',
            // Set status mặc định active (tài khoản hoạt động)
        ]);

        Auth::login($user);
        // Login user ngay sau khi đăng ký (tạo session đăng nhập)

        $request->session()->regenerate();
        // Regenerate session id để chống session fixation (bảo mật khi login)

        return $this->success([
            // success(): trả JSON success theo format chuẩn của BaseApiController

            'id'     => $user->id,
            // Trả id user

            'name'   => $user->name,
            // Trả name

            'email'  => $user->email,
            // Trả email

            'role'   => $user->role,
            // Trả role

            'status' => $user->status,
            // Trả status
        ], 'Registered & logged in.');
        // Message: đăng ký xong và đã login luôn
    }

    public function login(LoginRequest $request)
    {
        // login(): endpoint đăng nhập (POST /api/login)

        $data = $request->validated();
        // Lấy dữ liệu đã validate (email/password/remember)

        $remember = (bool)($data['remember'] ?? false);
        // Lấy remember từ request (nếu không có thì false)
        // Ép sang boolean để chắc chắn đúng kiểu

        if (!Auth::attempt(['email' => $data['email'], 'password' => $data['password']], $remember)) {
            // Auth::attempt(): thử đăng nhập bằng email/password
            // - Nếu đúng: trả true và set session đăng nhập
            // - Nếu sai: trả false

            return $this->error('Email hoặc mật khẩu không đúng.', 401);
            // Nếu sai thì trả error 401 (Unauthorized) với message tiếng Việt
        }

        $request->session()->regenerate();
        // Regenerate session id sau login để chống session fixation

        /** @var User $user */
        $user = $request->user();
        // Lấy user hiện tại sau khi login thành công (từ session)
        // Docblock để IDE hiểu $user là kiểu User

        if ($user && method_exists($user, 'isBlocked') && $user->isBlocked()) {
            // Nếu user tồn tại + có method isBlocked() + user đang bị block
            // method_exists(...) để tránh lỗi nếu User model không có isBlocked (defensive code)

            Auth::logout();
            // Logout ngay (xóa trạng thái auth)

            $request->session()->invalidate();
            // Invalidate session hiện tại (xóa dữ liệu session)

            $request->session()->regenerateToken();
            // Tạo CSRF token mới (bảo mật)

            return $this->error('Tài khoản đã bị khóa.', 403);
            // Trả error 403 Forbidden vì tài khoản bị khóa
        }

        return $this->success([
            // Nếu login OK và không bị block -> trả thông tin user

            'id'     => $user->id,
            'name'   => $user->name,
            'email'  => $user->email,
            'role'   => $user->role,
            'status' => $user->status,
        ], 'Logged in.');
        // Message: login thành công
    }

    public function me(Request $request)
    {
        // me(): endpoint lấy user hiện tại (GET /api/me)

        /** @var User $user */
        $user = $request->user();
        // Lấy user đang đăng nhập từ request (session)

        return $this->success([
            // Trả JSON thông tin user hiện tại

            'id'     => $user->id,
            'name'   => $user->name,
            'email'  => $user->email,
            'role'   => $user->role,
            'status' => $user->status,
        ], 'Current authenticated user.');
        // Message: user hiện tại đang authenticated
    }

    public function logout(Request $request)
    {
        // logout(): endpoint đăng xuất (POST /api/logout)

        Auth::logout();
        // Logout user: xóa trạng thái đăng nhập khỏi session

        $request->session()->invalidate();
        // Xóa session hiện tại (đảm bảo sạch dữ liệu session)

        $request->session()->regenerateToken();
        // Tạo CSRF token mới (tránh reuse token cũ)

        return $this->success(null, 'Logged out.');
        // Trả success, data = null, message = Logged out
    }
}
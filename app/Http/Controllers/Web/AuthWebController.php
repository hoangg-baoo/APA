<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Password;

use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Str;

use Illuminate\Auth\Events\PasswordReset;

class AuthWebController extends Controller
// Controller này xử lý AUTH cho giao diện WEB (session-based)
{
    public function showUserLogin()
    {
        return view('auth.login_user'); // Trả về view form login user
    }

    public function showAdminLogin()
    {
        return view('auth.login_admin'); // Trả về view form login admin
    }

    public function loginUser(Request $request)
    {
        $credentials = $request->validate([
            // validate input từ form login user

            'email'    => ['required', 'email'],  // email bắt buộc + đúng format email
            'password' => ['required', 'string'], // password bắt buộc + là chuỗi
        ]);

        $remember = $request->boolean('remember'); // lấy checkbox remember (true/false)

        if (!Auth::attempt($credentials, $remember)) {
            // attempt: thử đăng nhập bằng email/password (đúng thì tạo session)
            // nếu sai => return false

            return back()
                // back(): quay lại trang trước (form login)

                ->withErrors(['email' => 'Email hoặc mật khẩu không đúng.'])
                // withErrors: đính kèm lỗi để view hiển thị

                ->withInput();
                // withInput: giữ lại input đã nhập (thường giữ email)
        }

        $request->session()->regenerate();
        // regenerate session id sau login để chống session fixation (bảo mật)

        $user = Auth::user();
        // Lấy user vừa login thành công

        if (isset($user->status) && $user->status === 'blocked') {
            // Nếu user có field status và đang blocked => không cho login

            Auth::logout(); // logout ngay

            $request->session()->invalidate();
            // invalidate session: xóa session data hiện tại

            $request->session()->regenerateToken();
            // regenerate CSRF token mới

            return back()
                ->withErrors(['email' => 'Tài khoản đã bị khóa.']) // báo lỗi bị khóa
                ->withInput();
        }

        $intended = redirect()->intended('/')->getTargetUrl();
        // intended(): Laravel lưu URL user định vào trước khi bị redirect login
        // getTargetUrl(): lấy URL đích đó (string)

        if (str_contains($intended, '/login') || str_contains($intended, '/admin/login')) {
            // Nếu intended lại trỏ về trang login (loop) thì ép về home

            return redirect('/'); // tránh redirect vòng lặp
        }

        return redirect()->intended('/');
        // Nếu có intended hợp lệ -> đi intended, không có thì về '/'
    }

    public function loginAdmin(Request $request)
    {
        $credentials = $request->validate([
            // validate input từ form login admin

            'email'    => ['required', 'email'],  // email bắt buộc
            'password' => ['required', 'string'], // password bắt buộc
        ]);

        $remember = $request->boolean('remember'); // remember me

        if (!Auth::attempt($credentials, $remember)) {
            // Thử đăng nhập bằng email/password

            return back()
                ->withErrors(['email' => 'Email hoặc mật khẩu không đúng.']) // sai thì báo lỗi
                ->withInput();
        }

        $request->session()->regenerate();
        // regenerate session id sau login

        $user = Auth::user();
        // Lấy user vừa login

        if (isset($user->status) && $user->status === 'blocked') {
            // Nếu user bị block thì logout + chặn

            Auth::logout(); // logout

            $request->session()->invalidate(); // xóa session
            $request->session()->regenerateToken(); // đổi CSRF token

            return back()
                ->withErrors(['email' => 'Tài khoản đã bị khóa.'])
                ->withInput();
        }

        if (!isset($user->role) || $user->role !== 'admin') {
            // Nếu không có role hoặc role != admin => không cho vào admin

            Auth::logout(); // logout vì đã login nhưng không đủ quyền

            $request->session()->invalidate(); // xóa session
            $request->session()->regenerateToken(); // đổi CSRF token

            return back()
                ->withErrors(['email' => 'Bạn không có quyền admin.']) // báo lỗi không có quyền
                ->withInput();
        }

        return redirect()->intended('/admin/dashboard');
        // Login admin OK => redirect tới dashboard (hoặc intended nếu có)
    }

    public function logout(Request $request)
    {
        Auth::logout();
        // Logout user khỏi session

        $request->session()->invalidate();
        // Xóa session hiện tại

        $request->session()->regenerateToken();
        // Tạo CSRF token mới

        return redirect('/login');
        // Đăng xuất xong => về trang login user
    }

    public function adminLogout(Request $request)
    {
        Auth::logout();
        // Logout admin (thực tế vẫn là Auth session chung)

        $request->session()->invalidate(); // xóa session
        $request->session()->regenerateToken(); // đổi CSRF token

        return redirect('/admin/login');
        // Đăng xuất xong => về trang login admin
    }

    // =========================
    // FORGOT PASSWORD (WEB)
    // =========================

    public function showForgotPassword()
    {
        return view('auth.forgot_password');
        // Trả view form nhập email để xin reset link
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'], // validate email
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
            // Gửi reset link tới email (Laravel dùng password broker)
        );

        if ($status === Password::RESET_LINK_SENT) {
            // Nếu gửi thành công

            return back()->with('status', __($status));
            // with('status'): flash message lên view (Laravel message chuẩn)
        }

        return back()->withErrors([
            'email' => __($status),
            // Nếu lỗi (email không tồn tại, throttle...) trả message tương ứng
        ])->withInput();
        // Giữ input email
    }

    public function showResetPassword(Request $request, string $token)
    {
        return view('auth.reset_password', [
            'token' => $token,
            // Token reset (được gửi trong link email)

            'email' => $request->query('email', ''),
            // Lấy email từ query string ?email=... (nếu không có thì '')
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            // validate form reset password

            'token'                 => ['required'], // token bắt buộc
            'email'                 => ['required', 'email'], // email bắt buộc
            'password'              => ['required', 'string', 'min:6', 'confirmed'],
            // password: tối thiểu 6 ký tự + confirmed nghĩa là phải có password_confirmation khớp

            'password_confirmation' => ['required', 'string', 'min:6'],
            // confirmation bắt buộc + min 6
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            // Truyền dữ liệu cần cho broker để verify token + reset

            function ($user) use ($request) {
                // Callback sẽ chạy khi token hợp lệ và broker tìm được user

                $user->forceFill([
                    'password' => Hash::make($request->password),
                    // Hash password mới và set vào user (forceFill bỏ qua fillable)
                ])->setRememberToken(Str::random(60));
                // Set remember token mới để logout các phiên "remember me" cũ

                $user->save();
                // Lưu user vào DB

                event(new PasswordReset($user));
                // Bắn event PasswordReset (Laravel dùng cho listener/log)
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            // Nếu reset thành công

            return redirect('/login')->with('status', __($status));
            // Redirect về login user + flash message
        }

        return back()->withErrors([
            'email' => __($status),
            // Nếu reset fail thì trả message lỗi theo broker (token invalid, email mismatch...)
        ])->withInput();
        // Giữ input để user sửa lại
    }
}
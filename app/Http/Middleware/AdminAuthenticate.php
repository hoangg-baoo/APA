<?php

namespace App\Http\Middleware;

use Closure;

use Illuminate\Http\Request;

use Symfony\Component\HttpFoundation\Response;

class AdminAuthenticate
// Khai báo class AdminAuthenticate: middleware kiểm tra "đã đăng nhập chưa" cho khu admin, cổng login cho khu admin. Chỉ check “đã đăng nhập chưa”
{
    public function handle(Request $request, Closure $next): Response
    {
        // handle(): hàm chạy mỗi lần request đi qua middleware này

        if (!auth()->check()) {
            // auth()->check(): kiểm tra có user đang đăng nhập hay không (session auth)
            // Nếu false => chưa login

            if ($request->expectsJson()) {
                // expectsJson(): true khi request muốn nhận JSON (thường là fetch/AJAX hoặc header Accept: application/json)

                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
                // Trả JSON lỗi 401 cho client (API/admin ajax) thay vì redirect
            }

            return redirect()->route('admin.login');
            // Nếu không phải JSON (web browser bình thường) => redirect về trang login admin
        }

        return $next($request);
        // Nếu đã login thì cho đi tiếp (chạy middleware tiếp theo hoặc controller)
    }
}
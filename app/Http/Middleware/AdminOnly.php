<?php

namespace App\Http\Middleware;

use Closure;

use Illuminate\Http\Request;

use Symfony\Component\HttpFoundation\Response;

class AdminOnly
// Khai báo class AdminOnly: middleware chỉ cho phép user có role admin truy cập
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        // $request->user(): lấy user đang đăng nhập (từ auth middleware/guard)
        // Nếu chưa login thì $user = null

        if (!$user || $user->role !== 'admin') {
            // Nếu không có user (chưa login) HOẶC role không phải 'admin' => không cho truy cập

            abort(403, 'Only admin can access this resource.');
            // abort(403,...): dừng request và trả HTTP 403 Forbidden + message
        }

        return $next($request);
        // Nếu đúng admin => cho request đi tiếp (controller/route)
    }
}
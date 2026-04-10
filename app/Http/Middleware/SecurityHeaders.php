<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Chặn Clickjacking: trang chỉ được nhúng bởi cùng origin
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Chặn MIME sniffing: trình duyệt không được tự đoán content-type
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Bộ lọc XSS cho trình duyệt cũ (IE, Chrome cũ)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer Policy: chỉ gửi origin khi cross-origin (không lộ full URL)
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy: vô hiệu các API browser nhạy cảm
        $response->headers->set(
            'Permissions-Policy',
            'geolocation=(), microphone=(), camera=(), payment=()'
        );

        // Content Security Policy: giới hạn nguồn script/style/font/image
        // Điều chỉnh danh sách domain nếu bạn dùng thêm CDN khác
        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
            "font-src 'self' https://fonts.gstatic.com",
            "img-src 'self' data: blob: https:",
            "connect-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]));

        // HSTS: bỏ comment dòng dưới khi website đã có HTTPS/SSL
        // $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');

        return $response;
    }
}

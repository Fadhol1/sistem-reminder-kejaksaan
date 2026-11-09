<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Menangani request masuk dan menambahkan HTTP Security Headers
     * untuk melindungi dari serangan Clickjacking, MIME-sniffing, dan XSS.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Mencegah Clickjacking (tidak dapat diembed ke dalam iframe domain lain)
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Mencegah browser melakukan interpretasi tipe MIME yang salah (MIME Sniffing)
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Mengatur kebijakan pengiriman header Referer
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Batasi fitur hardware browser yang tidak digunakan aplikasi
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Perlindungan XSS dasar untuk browser lama
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // HSTS jika request menggunakan protokol aman HTTPS
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}


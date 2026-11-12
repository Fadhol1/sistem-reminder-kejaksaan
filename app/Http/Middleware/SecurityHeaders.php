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

        // Izinkan Vite dev server hanya di environment lokal saat file public/hot aktif
        $viteSrc = '';
        $viteWs = '';
        $hotFile = public_path('hot');
        if (app()->environment('local') && is_file($hotFile)) {
            $hotUrl = trim((string) @file_get_contents($hotFile));
            $parsed = $hotUrl !== '' ? parse_url($hotUrl) : false;
            if (is_array($parsed) && isset($parsed['scheme'], $parsed['host'])) {
                // Spesifikasi CSP tidak mendukung literal host IPv6 (mis. [::1]); lewati CSP
                // khusus saat Vite dev server lokal lama masih terikat ke IPv6.
                if (str_contains($parsed['host'], ':')) {
                    return $response;
                }

                $port = $parsed['port'] ?? 5173;
                $wsScheme = $parsed['scheme'] === 'https' ? 'wss' : 'ws';
                $httpHosts = array_unique([
                    "{$parsed['scheme']}://{$parsed['host']}:{$port}",
                    "http://localhost:{$port}",
                    "http://127.0.0.1:{$port}",
                ]);
                $wsHosts = array_unique([
                    "{$wsScheme}://{$parsed['host']}:{$port}",
                    "ws://localhost:{$port}",
                    "ws://127.0.0.1:{$port}",
                ]);
                $viteSrc = ' ' . implode(' ', $httpHosts);
                $viteWs = ' ' . implode(' ', $wsHosts);
            }
        }

        // Content Security Policy dasar untuk membatasi sumber skrip, gaya, dan frame
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline'{$viteSrc}; " .
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net{$viteSrc}; " .
            "font-src 'self' data: https://fonts.bunny.net https://cdn.jsdelivr.net; " .
            "img-src 'self' data: blob: https:; " .
            "connect-src 'self'{$viteSrc}{$viteWs}; " .
            "object-src 'none'; " .
            "base-uri 'self'; " .
            "form-action 'self'; " .
            "frame-ancestors 'self'"
        );

        return $response;
    }
}


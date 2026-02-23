<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request and add security headers.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only add security headers in production
        if (app()->environment('production')) {
            // Prevent clickjacking
            $response->headers->set('X-Frame-Options', 'DENY');

            // Prevent MIME type sniffing
            $response->headers->set('X-Content-Type-Options', 'nosniff');

            // XSS Protection (legacy browsers)
            $response->headers->set('X-XSS-Protection', '1; mode=block');

            // Referrer Policy
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

            // Permissions Policy
            $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

            // HTTP Strict Transport Security (HSTS)
            if (env('SECURITY_HSTS_ENABLE', true)) {
                $maxAge = env('SECURITY_HSTS_MAX_AGE', 31536000);
                $includeSubdomains = env('SECURITY_HSTS_INCLUDE_SUBDOMAINS', true) ? '; includeSubDomains' : '';
                $response->headers->set('Strict-Transport-Security', "max-age={$maxAge}{$includeSubdomains}; preload");
            }

            // Content Security Policy
            if (env('SECURITY_CSP_ENABLE', true)) {
                $csp = implode('; ', [
                    "default-src 'self'",
                    "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com",
                    "style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com",
                    "img-src 'self' data: https:",
                    "font-src 'self' data:",
                    "connect-src 'self' https://api.togetherpay.app",
                    "frame-ancestors 'none'",
                    "base-uri 'self'",
                    "form-action 'self'",
                ]);
                $response->headers->set('Content-Security-Policy', $csp);
            }
        }

        return $response;
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        if (! $this->isCrossOriginRedirect($request, $response)) {
            $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        }

        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self' https://wa.me https://api.whatsapp.com https://web.whatsapp.com",
            "frame-ancestors 'self'",
            "object-src 'none'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: blob: https:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "frame-src 'self' https://www.google.com https://maps.google.com https://www.google.com.tr https://www.youtube.com https://www.youtube-nocookie.com https://player.vimeo.com https://www.instagram.com",
        ]));

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function isCrossOriginRedirect(Request $request, Response $response): bool
    {
        if (! $response->isRedirection()) {
            return false;
        }

        $location = $response->headers->get('Location');

        if (! is_string($location) || $location === '') {
            return false;
        }

        $absolute = str_starts_with($location, 'http://') || str_starts_with($location, 'https://')
            ? $location
            : $request->getSchemeAndHttpHost().$location;

        $host = parse_url($absolute, PHP_URL_HOST);

        return is_string($host) && $host !== '' && $host !== $request->getHost();
    }
}

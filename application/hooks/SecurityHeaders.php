<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class SecurityHeaders
{
    public function set_headers()
    {
        $CI =& get_instance();

        // Stop the app from being loaded inside another site.
        $CI->output->set_header('X-Frame-Options: DENY');

        // Keep browsers from guessing file types.
        $CI->output->set_header('X-Content-Type-Options: nosniff');

        // Extra protection for older browsers that still support this header.
        $CI->output->set_header('X-XSS-Protection: 1; mode=block');

        // Do not leak full internal URLs to other sites.
        $CI->output->set_header('Referrer-Policy: strict-origin-when-cross-origin');

        // The script list is kept tight because the project loads CDN assets.
        $CI->output->set_header("Content-Security-Policy: default-src 'self'; connect-src 'self' https://cdn.jsdelivr.net; script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com 'sha256-+GhuR9N0hemzdPjFXU+5bdL9zc2DFZlacsPtdOhz4MI=' 'sha256-r/8zIMslx8vWlg9h8Hg+ygUXPd1Lyauf127yfXOUx/4='; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data:; frame-ancestors 'none';");

        // HSTS is only safe when the request is actually using HTTPS.
        if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        ) {
            $CI->output->set_header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        // These browser features are not needed in this coursework app.
        $CI->output->set_header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

        // Only API routes need CORS because browser pages use normal sessions.
        $uri = $CI->uri->uri_string();
        if (strpos($uri, 'api/') === 0) {
            $allowed_origin = getenv('CORS_ALLOWED_ORIGIN') ?: 'https://localhost';
            $CI->output->set_header('Access-Control-Allow-Origin: ' . $allowed_origin);
            $CI->output->set_header('Vary: Origin');
            $CI->output->set_header('Access-Control-Allow-Credentials: true');
            $CI->output->set_header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
            $CI->output->set_header('Access-Control-Allow-Headers: Authorization, Content-Type');
            $CI->output->set_header('Access-Control-Max-Age: 86400');
        }
    }
}



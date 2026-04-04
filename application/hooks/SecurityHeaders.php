<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * SecurityHeaders Hook
 *
 * Sets security headers on every response to protect against
 * XSS, clickjacking, MIME sniffing, and other common web vulnerabilities.
 * Equivalent to Helmet.js in Node.js applications.
 */
class SecurityHeaders
{
    /**
     * Set security headers on the response
     */
    public function set_headers()
    {
        $CI =& get_instance();

        // Prevent clickjacking
        $CI->output->set_header('X-Frame-Options: DENY');

        // Prevent MIME type sniffing
        $CI->output->set_header('X-Content-Type-Options: nosniff');

        // Enable XSS filter in browsers
        $CI->output->set_header('X-XSS-Protection: 1; mode=block');

        // Control referrer information
        $CI->output->set_header('Referrer-Policy: strict-origin-when-cross-origin');

        // Content Security Policy — 'unsafe-inline' remains in style-src for
        // Bootstrap 5 inline style attributes from CDN; script-src uses strict allowlist only.
        $CI->output->set_header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com 'sha256-+GhuR9N0hemzdPjFXU+5bdL9zc2DFZlacsPtdOhz4MI=' 'sha256-r/8zIMslx8vWlg9h8Hg+ygUXPd1Lyauf127yfXOUx/4='; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data:; frame-ancestors 'none';");

        // Strict Transport Security — only send when the request arrived over HTTPS
        if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        ) {
            $CI->output->set_header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        // Permissions Policy
        $CI->output->set_header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

        // CORS headers for API endpoints
        $uri = $CI->uri->uri_string();
        if (strpos($uri, 'api/') === 0) {
            $allowed_origin = getenv('CORS_ALLOWED_ORIGIN') ?: 'https://localhost';
            $CI->output->set_header('Access-Control-Allow-Origin: ' . $allowed_origin);
            $CI->output->set_header('Vary: Origin');
            $CI->output->set_header('Access-Control-Allow-Methods: GET, OPTIONS');
            $CI->output->set_header('Access-Control-Allow-Headers: Authorization, Content-Type');
            $CI->output->set_header('Access-Control-Max-Age: 86400');
        }
    }
}

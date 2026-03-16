<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Shared SMTP / email configuration helper
 *
 * Centralises the SMTP settings that were previously duplicated
 * across Auth, Bidding, and Cron controllers.
 */

if (!function_exists('get_smtp_config')) {
    /**
     * Return the common SMTP configuration array for CI's email library.
     *
     * @return array
     */
    function get_smtp_config()
    {
        return array(
            'protocol'  => 'smtp',
            'smtp_host' => getenv('SMTP_HOST') ?: 'smtp.gmail.com',
            'smtp_port' => getenv('SMTP_PORT') ?: 587,
            'smtp_crypto' => getenv('SMTP_CRYPTO') ?: '',
            'smtp_user' => getenv('SMTP_USER') ?: '',
            'smtp_pass' => getenv('SMTP_PASS') ?: '',
            'smtp_timeout' => 15,
            'mailtype'  => 'html',
            'charset'   => 'utf-8',
            'crlf'      => "\r\n",
            'newline'   => "\r\n"
        );
    }
}

if (!function_exists('send_email_safely')) {
    /**
     * Send email while gracefully handling unreachable SMTP sockets.
     *
     * @param CI_Email $email
     * @return bool
     */
    function send_email_safely($email)
    {
        $config = get_smtp_config();
        $protocol = isset($config['protocol']) ? strtolower((string)$config['protocol']) : 'smtp';

        if ($protocol === 'smtp') {
            $host = (string)($config['smtp_host'] ?? '');
            $port = (int)($config['smtp_port'] ?? 0);
            $timeout = (int)($config['smtp_timeout'] ?? 15);
            $crypto = strtolower((string)($config['smtp_crypto'] ?? ''));

            if ($host !== '' && $port > 0) {
                $socket_host = ($crypto === 'ssl' ? 'ssl://' : '') . $host;
                $errno = 0;
                $errstr = '';
                $socket = @fsockopen($socket_host, $port, $errno, $errstr, $timeout);
                if (!$socket) {
                    log_message('error', 'SMTP preflight check failed for ' . $host . ':' . $port . ' - [' . $errno . '] ' . $errstr);
                    return FALSE;
                }
                fclose($socket);
            }
        }

        return (bool)$email->send();
    }
}

if (!function_exists('get_smtp_from')) {
    /**
     * Return the "From" address and name for outgoing emails.
     *
     * @return array ['email' => …, 'name' => …]
     */
    function get_smtp_from()
    {
        return array(
            'email' => getenv('SMTP_FROM') ?: 'noreply@westminster.ac.uk',
            'name'  => getenv('SMTP_FROM_NAME') ?: 'Alumni Platform'
        );
    }
}

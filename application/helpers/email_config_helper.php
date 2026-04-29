<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('get_smtp_config')) {
    function get_smtp_config()
    {
        // One place for SMTP settings used by verification, reset and bid emails.
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
                // Fail fast when SMTP is not reachable instead of hanging the page.
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
    function get_smtp_from()
    {
        return array(
            'email' => getenv('SMTP_FROM') ?: 'noreply@westminster.ac.uk',
            'name'  => getenv('SMTP_FROM_NAME') ?: 'Alumni Platform'
        );
    }
}


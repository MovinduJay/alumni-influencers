<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Hooks
| -------------------------------------------------------------------------
| This file lets you define "hooks" to extend CI without hacking the core
| files.  Please see the user guide for info:
|
|	https://codeigniter.com/userguide3/general/hooks.html
|
*/

/**
 * Security Headers Hook
 * Sets important security headers on every response to protect against
 * common web vulnerabilities (XSS, clickjacking, MIME sniffing, etc.)
 */
$hook['post_controller_constructor'][] = array(
    'class'    => 'SecurityHeaders',
    'function' => 'set_headers',
    'filename' => 'SecurityHeaders.php',
    'filepath' => 'hooks'
);

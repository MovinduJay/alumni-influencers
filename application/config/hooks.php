<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Register project hooks used by the application.
// CodeIgniter runs this after the controller is created.
$hook['post_controller_constructor'][] = array(
    'class'    => 'SecurityHeaders',
    'function' => 'set_headers',
    'filename' => 'SecurityHeaders.php',
    'filepath' => 'hooks'
);


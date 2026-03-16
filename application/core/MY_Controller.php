<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Shared application controller utilities.
 *
 * Centralizes session timeout enforcement and auth/role gates so
 * feature controllers stay focused on request handling.
 */
class MY_Controller extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->enforce_idle_timeout();
    }

    /**
     * Enforce an inactivity timeout for authenticated sessions.
     */
    protected function enforce_idle_timeout()
    {
        if (!$this->session->userdata('logged_in')) {
            return;
        }

        $timeout = (int)(getenv('SESSION_TIMEOUT') ?: 7200);
        $last_activity = (int)$this->session->userdata('last_activity');

        if ($last_activity > 0 && (time() - $last_activity) > $timeout) {
            $this->session->unset_userdata(array(
                'alumni_id',
                'email',
                'first_name',
                'last_name',
                'role',
                'logged_in',
                'login_time',
                'last_activity'
            ));
            $this->session->sess_regenerate(TRUE);
            $this->session->set_flashdata('error', 'Your session expired due to inactivity. Please log in again.');
            redirect('auth/login');
            return;
        }

        $this->session->set_userdata('last_activity', time());
    }

    /**
     * Require an authenticated session.
     *
     * @param string $message
     */
    protected function require_auth($message = 'Please log in to continue.')
    {
        if ($this->session->userdata('logged_in')) {
            return;
        }

        $this->session->set_flashdata('error', $message);
        redirect('auth/login');
    }

    /**
     * Require auth except for allowlisted public methods.
     *
     * @param array $public_methods
     * @param string $message
     */
    protected function require_auth_except(array $public_methods, $message = 'Please log in to continue.')
    {
        $method = $this->get_current_method();
        if (in_array($method, $public_methods, TRUE)) {
            return;
        }

        $this->require_auth($message);
    }

    /**
     * Require an authenticated admin session.
     */
    protected function require_admin()
    {
        $this->require_auth('Please log in to access admin features.');

        if ($this->session->userdata('role') === 'admin') {
            return;
        }

        show_error('Access denied. Admin privileges required.', 403);
    }

    /**
     * Resolve the current controller method across CI router variants.
     *
     * @return string
     */
    protected function get_current_method()
    {
        if (is_object($this->router) && method_exists($this->router, 'fetch_method')) {
            return (string) $this->router->fetch_method();
        }

        if (is_object($this->router) && isset($this->router->method)) {
            return (string) $this->router->method;
        }

        return 'index';
    }
}

class MY_Authenticated_Controller extends MY_Controller
{
    public function __construct($message = 'Please log in to continue.')
    {
        parent::__construct();
        $this->require_auth($message);
    }
}

class MY_Admin_Controller extends MY_Authenticated_Controller
{
    public function __construct()
    {
        parent::__construct('Please log in to access admin features.');
        $this->require_admin();
    }
}

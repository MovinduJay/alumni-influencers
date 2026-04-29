<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->enforce_idle_timeout();
    }

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
                'admin_id',
                'email',
                'first_name',
                'last_name',
                'role',
                'user_type',
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

    protected function require_auth($message = 'Please log in to continue.')
    {
        if ($this->session->userdata('logged_in')) {
            return;
        }

        $this->session->set_flashdata('error', $message);
        redirect('auth/login');
    }

    protected function require_auth_except(array $public_methods, $message = 'Please log in to continue.')
    {
        $method = $this->get_current_method();
        if (in_array($method, $public_methods, TRUE)) {
            return;
        }

        $this->require_auth($message);
    }

    protected function require_admin()
    {
        $this->require_auth('Please log in to access admin features.');

        if ($this->session->userdata('user_type') === 'admin' && (int) $this->session->userdata('admin_id') > 0) {
            return;
        }

        show_error('Access denied. Admin privileges required.', 403);
    }

    protected function require_alumni()
    {
        $this->require_auth('Please log in to access alumni features.');

        if ($this->session->userdata('user_type') === 'alumni' && (int) $this->session->userdata('alumni_id') > 0) {
            return;
        }

        show_error('Access denied. Alumni account required.', 403);
    }

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
        $this->require_alumni();
    }
}

class MY_Admin_Controller extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->require_admin();
    }
}



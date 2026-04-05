<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Auth Controller
 *
 * Handles the web authentication flow while delegating the security-critical
 * business rules to Auth_service. This keeps the controller focused on form
 * validation, flash messages, redirects, and view rendering.
 */
class Auth extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('auth_service');
        $this->load->library('form_validation');
    }

    /**
     * Registration page and handler.
     */
    public function register()
    {
        if ($this->input->method() !== 'post') {
            $this->render_view('auth/register', 'Register');
            return;
        }

        if (!$this->auth_service->check_rate_limit('register')) {
            $this->session->set_flashdata('error', 'Too many requests. Please try again later.');
            redirect('auth/register');
            return;
        }

        $this->form_validation->set_rules('first_name', 'First Name', 'required|trim|min_length[2]|max_length[100]|alpha_numeric_spaces');
        $this->form_validation->set_rules('last_name', 'Last Name', 'required|trim|min_length[2]|max_length[100]|alpha_numeric_spaces');
        $this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email|max_length[255]');
        $this->form_validation->set_rules('password', 'Password', 'required|min_length[8]|callback_validate_password_strength');
        $this->form_validation->set_rules('confirm_password', 'Confirm Password', 'required|matches[password]');

        if ($this->form_validation->run() === FALSE) {
            $this->render_view('auth/register', 'Register');
            return;
        }

        $result = $this->auth_service->register_alumni(array(
            'email' => $this->input->post('email', TRUE),
            'password' => $this->input->post('password'),
            'first_name' => $this->input->post('first_name', TRUE),
            'last_name' => $this->input->post('last_name', TRUE)
        ));

        if (!$result['ok']) {
            $this->session->set_flashdata('error', $result['message']);
            redirect('auth/register');
            return;
        }

        $this->session->set_flashdata('success', 'Registration successful! Please check your email to verify your account.');
        redirect('auth/login');
    }

    /**
     * Form-validation callback that delegates the actual policy.
     *
     * @param string $password
     * @return bool
     */
    public function validate_password_strength($password)
    {
        $result = $this->auth_service->validate_password_strength($password);
        if ($result === TRUE) {
            return TRUE;
        }

        $this->form_validation->set_message('validate_password_strength', $result);
        return FALSE;
    }

    /**
     * Email verification handler.
     *
     * @param string $token
     */
    public function verify($token)
    {
        if (empty($token)) {
            $this->session->set_flashdata('error', 'Invalid verification link.');
            redirect('auth/login');
            return;
        }

        $result = $this->auth_service->verify_email_token($token);
        if (!$result['ok']) {
            $this->session->set_flashdata('error', $result['message']);
            redirect($result['status'] === 410 ? 'auth/register' : 'auth/login');
            return;
        }

        $this->session->set_flashdata('success', 'Email verified successfully! You can now log in.');
        redirect('auth/login');
    }

    /**
     * Login page and handler.
     */
    public function login()
    {
        if ($this->session->userdata('alumni_id')) {
            redirect('profile');
            return;
        }

        if ($this->input->method() !== 'post') {
            $this->render_view('auth/login', 'Login');
            return;
        }

        if (!$this->auth_service->check_rate_limit('login')) {
            $this->session->set_flashdata('error', 'Too many login attempts. Please try again later.');
            redirect('auth/login');
            return;
        }

        $this->form_validation->set_rules('email', 'Email', 'required|valid_email');
        $this->form_validation->set_rules('password', 'Password', 'required');

        if ($this->form_validation->run() === FALSE) {
            $this->render_view('auth/login', 'Login');
            return;
        }

        $result = $this->auth_service->authenticate_credentials(
            $this->input->post('email', TRUE),
            $this->input->post('password')
        );

        if (!$result['ok']) {
            $this->session->set_flashdata('error', $result['message']);
            redirect('auth/login');
            return;
        }

        $alumni = $result['alumni'];
        $this->auth_service->start_session($alumni);

        $this->session->set_flashdata('success', 'Welcome back, ' . htmlspecialchars($alumni->first_name, ENT_QUOTES, 'UTF-8') . '!');
        redirect('profile');
    }

    /**
     * Logout handler.
     */
    public function logout()
    {
        $this->session->sess_destroy();
        redirect('auth/login');
    }

    /**
     * Forgot-password page and handler.
     */
    public function forgot_password()
    {
        if ($this->input->method() !== 'post') {
            $this->render_view('auth/forgot_password', 'Forgot Password');
            return;
        }

        if (!$this->auth_service->check_rate_limit('forgot_password')) {
            $this->session->set_flashdata('error', 'Too many requests. Please try again later.');
            redirect('auth/forgot-password');
            return;
        }

        $this->form_validation->set_rules('email', 'Email', 'required|valid_email');

        if ($this->form_validation->run() === FALSE) {
            $this->render_view('auth/forgot_password', 'Forgot Password');
            return;
        }

        $result = $this->auth_service->request_password_reset($this->input->post('email', TRUE));
        if (!$result['ok']) {
            $this->session->set_flashdata('error', $result['message']);
            redirect('auth/forgot-password');
            return;
        }

        $this->session->set_flashdata('success', 'If an account with that email exists, a password reset link has been sent.');
        redirect('auth/forgot-password');
    }

    /**
     * Reset-password page and handler.
     *
     * @param string $token
     */
    public function reset_password($token)
    {
        $verification = $this->auth_service->verify_reset_token($token);
        if (!$verification['ok']) {
            $this->session->set_flashdata('error', $verification['message']);
            redirect('auth/forgot-password');
            return;
        }

        if ($this->input->method() !== 'post') {
            $data = array('title' => 'Reset Password', 'token' => $token);
            $this->load->view('layouts/header', $data);
            $this->load->view('auth/reset_password', $data);
            $this->load->view('layouts/footer');
            return;
        }

        $this->form_validation->set_rules('password', 'Password', 'required|min_length[8]|callback_validate_password_strength');
        $this->form_validation->set_rules('confirm_password', 'Confirm Password', 'required|matches[password]');

        if ($this->form_validation->run() === FALSE) {
            $data = array('title' => 'Reset Password', 'token' => $token);
            $this->load->view('layouts/header', $data);
            $this->load->view('auth/reset_password', $data);
            $this->load->view('layouts/footer');
            return;
        }

        $result = $this->auth_service->reset_password(
            $token,
            $this->input->post('password'),
            $this->input->post('confirm_password')
        );

        if (!$result['ok']) {
            $this->session->set_flashdata('error', $result['message']);
            redirect($result['status'] === 410 ? 'auth/forgot-password' : 'auth/reset-password/' . rawurlencode($token));
            return;
        }

        $this->session->set_flashdata('success', 'Password reset successfully. You can now log in.');
        redirect('auth/login');
    }

    /**
     * Render a standard auth view with the shared layout.
     *
     * @param string $view
     * @param string $title
     * @return void
     */
    protected function render_view($view, $title)
    {
        $data = array('title' => $title);
        $this->load->view('layouts/header', $data);
        $this->load->view($view);
        $this->load->view('layouts/footer');
    }
}

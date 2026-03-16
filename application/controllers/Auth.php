<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Auth Controller
 *
 * Handles alumni registration, email verification, login/logout,
 * and password reset functionality.
 *
 * Security features:
 * - University domain email validation
 * - Strong password requirements (min 8 chars, uppercase, lowercase, number, special)
 * - Bcrypt password hashing with appropriate cost factor
 * - Cryptographically secure tokens for verification and password reset
 * - Tokens hashed with SHA-256 before storage (only hash stored at rest)
 * - Token expiry enforcement
 * - Session regeneration on login
 * - IP-based rate limiting backed by database (rate_limits table)
 */
class Auth extends MY_Controller
{
    /**
     * Constructor - load required models, helpers, and database
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Alumni_model');
        $this->load->library('form_validation');
        $this->load->database();
    }

    /**
     * Check rate limit for sensitive endpoints
     *
     * Uses database-backed IP-based tracking so limits cannot be bypassed by
     * clearing cookies.  Falls back to session tracking when the DB table is
     * unavailable (e.g. during initial setup).
     *
     * Reads RATE_LIMIT from env (max requests per minute).
     *
     * @param string $action Action identifier (e.g., 'login', 'register')
     * @return bool TRUE if within limit, FALSE if rate-limited
     */
    private function _check_rate_limit($action)
    {
        $max_requests = (int)(getenv('RATE_LIMIT') ?: 60);
        $window = 60; // 1 minute window

        // Fallback during initial setup before the rate_limits table exists.
        if (!$this->db->table_exists('rate_limits')) {
            $key = 'rate_limit_' . $action;
            $attempts = $this->session->userdata($key);
            if (!is_array($attempts)) {
                $attempts = array();
            }
            $cutoff_ts = time() - $window;
            $attempts = array_filter($attempts, function ($ts) use ($cutoff_ts) {
                return $ts > $cutoff_ts;
            });
            if (count($attempts) >= $max_requests) {
                return FALSE;
            }
            $attempts[] = time();
            $this->session->set_userdata($key, $attempts);
            return TRUE;
        }

        $ip = $this->input->ip_address();
        $cutoff = date('Y-m-d H:i:s', time() - $window);

        // Probabilistic purge: clean stale entries ~5% of requests to reduce overhead
        if (mt_rand(1, 20) === 1) {
            $this->db->where('attempted_at <', $cutoff);
            $this->db->delete('rate_limits');
        }

        // Count recent attempts for this IP + action
        $this->db->where('ip_address', $ip);
        $this->db->where('action', $action);
        $this->db->where('attempted_at >=', $cutoff);
        $count = $this->db->count_all_results('rate_limits');

        if ($count >= $max_requests) {
            return FALSE;
        }

        // Record this attempt
        $this->db->insert('rate_limits', array(
            'ip_address' => $ip,
            'action'     => $action
        ));
        return TRUE;
    }

    /**
     * Registration page and handler
     *
     * GET:  Display registration form
     * POST: Process registration
     */
    public function register()
    {
        if ($this->input->method() === 'post') {
            // Rate limit check
            if (!$this->_check_rate_limit('register')) {
                $this->session->set_flashdata('error', 'Too many requests. Please try again later.');
                redirect('auth/register');
                return;
            }

            // Set validation rules
            $this->form_validation->set_rules('first_name', 'First Name', 'required|trim|min_length[2]|max_length[100]|alpha_numeric_spaces');
            $this->form_validation->set_rules('last_name', 'Last Name', 'required|trim|min_length[2]|max_length[100]|alpha_numeric_spaces');
            $this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email|max_length[255]');
            $this->form_validation->set_rules('password', 'Password', 'required|min_length[8]|callback_validate_password_strength');
            $this->form_validation->set_rules('confirm_password', 'Confirm Password', 'required|matches[password]');

            if ($this->form_validation->run() === FALSE) {
                $this->load->view('layouts/header', array('title' => 'Register'));
                $this->load->view('auth/register');
                $this->load->view('layouts/footer');
                return;
            }

            $email = $this->input->post('email', TRUE);
            $university_domain = getenv('UNIVERSITY_DOMAIN') ?: 'my.westminster.ac.uk';

            // Validate university domain
            $email_domain = substr(strrchr($email, "@"), 1);
            if ($email_domain !== $university_domain) {
                $this->session->set_flashdata('error', 'Registration is restricted to @' . htmlspecialchars($university_domain, ENT_QUOTES, 'UTF-8') . ' email addresses.');
                redirect('auth/register');
                return;
            }

            // Check for duplicate email
            if ($this->Alumni_model->email_exists($email)) {
                $this->session->set_flashdata('error', 'An account with this email already exists.');
                redirect('auth/register');
                return;
            }

            // Generate verification token (cryptographically secure)
            $verification_token = bin2hex(random_bytes(32));
            $expiry_hours = getenv('VERIFICATION_TOKEN_EXPIRY') ?: 24;

            // Create alumni record with bcrypt hashed password
            // Store SHA-256 hash of token — raw token is sent in the email only
            $alumni_data = array(
                'email'                => $email,
                'password'             => password_hash($this->input->post('password'), PASSWORD_BCRYPT, ['cost' => 12]),
                'first_name'           => $this->input->post('first_name', TRUE),
                'last_name'            => $this->input->post('last_name', TRUE),
                'email_verified'       => 0,
                'verification_token'   => hash('sha256', $verification_token),
                'verification_expires' => date('Y-m-d H:i:s', strtotime("+{$expiry_hours} hours")),
                'is_active'            => 1
            );

            $alumni_id = $this->Alumni_model->create($alumni_data);

            if ($alumni_id) {
                // Send verification email
                $this->_send_verification_email($email, $verification_token);

                $this->session->set_flashdata('success', 'Registration successful! Please check your email to verify your account.');
                redirect('auth/login');
            } else {
                $this->session->set_flashdata('error', 'Registration failed. Please try again.');
                redirect('auth/register');
            }
        } else {
            $this->load->view('layouts/header', array('title' => 'Register'));
            $this->load->view('auth/register');
            $this->load->view('layouts/footer');
        }
    }

    /**
     * Custom password strength validation callback
     *
     * Requires: min 8 chars, 1 uppercase, 1 lowercase, 1 number, 1 special character
     *
     * @param string $password Password to validate
     * @return bool
     */
    public function validate_password_strength($password)
    {
        if (strlen($password) < 8) {
            $this->form_validation->set_message('validate_password_strength', 'Password must be at least 8 characters.');
            return FALSE;
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $this->form_validation->set_message('validate_password_strength', 'Password must contain at least one uppercase letter.');
            return FALSE;
        }
        if (!preg_match('/[a-z]/', $password)) {
            $this->form_validation->set_message('validate_password_strength', 'Password must contain at least one lowercase letter.');
            return FALSE;
        }
        if (!preg_match('/[0-9]/', $password)) {
            $this->form_validation->set_message('validate_password_strength', 'Password must contain at least one number.');
            return FALSE;
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $this->form_validation->set_message('validate_password_strength', 'Password must contain at least one special character.');
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Email verification handler
     *
     * Validates the verification token and activates the account.
     * The token from the URL is hashed before DB lookup (tokens are stored hashed).
     *
     * @param string $token Verification token from email link
     */
    public function verify($token)
    {
        if (empty($token)) {
            $this->session->set_flashdata('error', 'Invalid verification link.');
            redirect('auth/login');
            return;
        }

        $hashed_token = hash('sha256', $token);
        $alumni = $this->Alumni_model->find_by_verification_token($hashed_token);

        if (!$alumni) {
            $this->session->set_flashdata('error', 'Invalid or expired verification link.');
            redirect('auth/login');
            return;
        }

        // Check token expiry
        if (strtotime($alumni->verification_expires) < time()) {
            $this->session->set_flashdata('error', 'Verification link has expired. Please register again.');
            redirect('auth/register');
            return;
        }

        // Verify the email
        $this->Alumni_model->verify_email($alumni->id);

        $this->session->set_flashdata('success', 'Email verified successfully! You can now log in.');
        redirect('auth/login');
    }

    /**
     * Login page and handler
     *
     * GET:  Display login form
     * POST: Process login with session creation
     */
    public function login()
    {
        // Redirect if already logged in
        if ($this->session->userdata('alumni_id')) {
            redirect('profile');
            return;
        }

        if ($this->input->method() === 'post') {
            // Rate limit check
            if (!$this->_check_rate_limit('login')) {
                $this->session->set_flashdata('error', 'Too many login attempts. Please try again later.');
                redirect('auth/login');
                return;
            }

            $this->form_validation->set_rules('email', 'Email', 'required|valid_email');
            $this->form_validation->set_rules('password', 'Password', 'required');

            if ($this->form_validation->run() === FALSE) {
                $this->load->view('layouts/header', array('title' => 'Login'));
                $this->load->view('auth/login');
                $this->load->view('layouts/footer');
                return;
            }

            $email = $this->input->post('email', TRUE);
            $password = $this->input->post('password');

            $alumni = $this->Alumni_model->find_by_email($email);

            if (!$alumni || !password_verify($password, $alumni->password)) {
                $this->session->set_flashdata('error', 'Invalid email or password.');
                redirect('auth/login');
                return;
            }

            // Check if email is verified
            if (!$alumni->email_verified) {
                $this->session->set_flashdata('error', 'Please verify your email before logging in. Check your inbox.');
                redirect('auth/login');
                return;
            }

            // Check if account is active
            if (!$alumni->is_active) {
                $this->session->set_flashdata('error', 'Your account has been deactivated.');
                redirect('auth/login');
                return;
            }

            // Regenerate session ID for security
            $this->session->sess_regenerate(TRUE);

            // Set session data
            $this->session->set_userdata(array(
                'alumni_id'  => $alumni->id,
                'email'      => $alumni->email,
                'first_name' => $alumni->first_name,
                'last_name'  => $alumni->last_name,
                'role'       => $alumni->role ?? 'alumni',
                'logged_in'  => TRUE,
                'login_time' => time(),
                'last_activity' => time()
            ));

            $this->session->set_flashdata('success', 'Welcome back, ' . htmlspecialchars($alumni->first_name, ENT_QUOTES, 'UTF-8') . '!');
            redirect('profile');
        } else {
            $this->load->view('layouts/header', array('title' => 'Login'));
            $this->load->view('auth/login');
            $this->load->view('layouts/footer');
        }
    }

    /**
     * Logout handler
     *
     * Destroys session and redirects to login page
     */
    public function logout()
    {
        $this->session->sess_destroy();
        redirect('auth/login');
    }

    /**
     * Forgot password page and handler
     *
     * GET:  Display forgot password form
     * POST: Send password reset email
     */
    public function forgot_password()
    {
        if ($this->input->method() === 'post') {
            // Rate limit check
            if (!$this->_check_rate_limit('forgot_password')) {
                $this->session->set_flashdata('error', 'Too many requests. Please try again later.');
                redirect('auth/forgot-password');
                return;
            }

            $this->form_validation->set_rules('email', 'Email', 'required|valid_email');

            if ($this->form_validation->run() === FALSE) {
                $this->load->view('layouts/header', array('title' => 'Forgot Password'));
                $this->load->view('auth/forgot_password');
                $this->load->view('layouts/footer');
                return;
            }

            $email = $this->input->post('email', TRUE);
            $alumni = $this->Alumni_model->find_by_email($email);

            // Always show success message to prevent email enumeration
            if ($alumni) {
                $reset_token = bin2hex(random_bytes(32));
                $expiry_hours = getenv('RESET_TOKEN_EXPIRY') ?: 1;
                $expires = date('Y-m-d H:i:s', strtotime("+{$expiry_hours} hours"));

                // Model stores SHA-256 hash; raw token is sent in the email only.
                $hashed_reset_token = hash('sha256', $reset_token);
                $this->Alumni_model->set_reset_token($alumni->id, $hashed_reset_token, $expires);
                $this->_send_reset_email($email, $reset_token);
            }

            $this->session->set_flashdata('success', 'If an account with that email exists, a password reset link has been sent.');
            redirect('auth/forgot-password');
        } else {
            $this->load->view('layouts/header', array('title' => 'Forgot Password'));
            $this->load->view('auth/forgot_password');
            $this->load->view('layouts/footer');
        }
    }

    /**
     * Password reset page and handler
     *
     * GET:  Display reset form (with valid token)
     * POST: Process password reset
     * The token from the URL is hashed before DB lookup (tokens are stored hashed).
     *
     * @param string $token Reset token
     */
    public function reset_password($token)
    {
        if (empty($token)) {
            $this->session->set_flashdata('error', 'Invalid reset link.');
            redirect('auth/login');
            return;
        }

        $hashed_token = hash('sha256', $token);
        $alumni = $this->Alumni_model->find_by_reset_token($hashed_token);

        if (!$alumni) {
            $this->session->set_flashdata('error', 'Invalid or expired reset link.');
            redirect('auth/forgot-password');
            return;
        }

        // Check token expiry
        if (strtotime($alumni->reset_expires) < time()) {
            $this->session->set_flashdata('error', 'Reset link has expired. Please request a new one.');
            redirect('auth/forgot-password');
            return;
        }

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('password', 'Password', 'required|min_length[8]|callback_validate_password_strength');
            $this->form_validation->set_rules('confirm_password', 'Confirm Password', 'required|matches[password]');

            if ($this->form_validation->run() === FALSE) {
                $data = array('title' => 'Reset Password', 'token' => $token);
                $this->load->view('layouts/header', $data);
                $this->load->view('auth/reset_password', $data);
                $this->load->view('layouts/footer');
                return;
            }

            $new_password = password_hash($this->input->post('password'), PASSWORD_BCRYPT, ['cost' => 12]);
            $this->Alumni_model->update_password($alumni->id, $new_password);

            $this->session->set_flashdata('success', 'Password reset successfully. You can now log in.');
            redirect('auth/login');
        } else {
            $data = array('title' => 'Reset Password', 'token' => $token);
            $this->load->view('layouts/header', $data);
            $this->load->view('auth/reset_password', $data);
            $this->load->view('layouts/footer');
        }
    }

    /**
     * Send verification email with secure token link
     *
     * @param string $email Email address
     * @param string $token Verification token
     */
    private function _send_verification_email($email, $token)
    {
        $this->load->library('email');

        $from = get_smtp_from();
        $this->email->initialize(get_smtp_config());
        $this->email->from($from['email'], $from['name']);
        $this->email->to($email);
        $this->email->subject('Verify Your Email - Alumni Influencers Platform');

        $verify_url = site_url('auth/verify/' . $token);
        $message = '<html><body>';
        $message .= '<h2>Welcome to the Alumni Influencers Platform!</h2>';
        $message .= '<p>Please click the link below to verify your email address:</p>';
        $message .= '<p><a href="' . htmlspecialchars($verify_url, ENT_QUOTES, 'UTF-8') . '">Verify Email Address</a></p>';
        $message .= '<p>This link will expire in 24 hours.</p>';
        $message .= '<p>If you did not create an account, please ignore this email.</p>';
        $message .= '</body></html>';

        $this->email->message($message);
        send_email_safely($this->email);
    }

    /**
     * Send password reset email with secure token link
     *
     * @param string $email Email address
     * @param string $token Reset token
     */
    private function _send_reset_email($email, $token)
    {
        $this->load->library('email');

        $from = get_smtp_from();
        $this->email->initialize(get_smtp_config());
        $this->email->from($from['email'], $from['name']);
        $this->email->to($email);
        $this->email->subject('Password Reset - Alumni Influencers Platform');

        $reset_url = site_url('auth/reset-password/' . $token);
        $message = '<html><body>';
        $message .= '<h2>Password Reset Request</h2>';
        $message .= '<p>Click the link below to reset your password:</p>';
        $message .= '<p><a href="' . htmlspecialchars($reset_url, ENT_QUOTES, 'UTF-8') . '">Reset Password</a></p>';
        $message .= '<p>This link will expire in 1 hour.</p>';
        $message .= '<p>If you did not request a password reset, please ignore this email.</p>';
        $message .= '</body></html>';

        $this->email->message($message);
        send_email_safely($this->email);
    }
}

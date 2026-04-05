<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Shared authentication service.
 *
 * Centralizes security-sensitive auth logic so the web and API controllers
 * reuse the same rules for registration, login, verification, password reset,
 * email delivery, and rate limiting.
 */
class Auth_service
{
    /**
     * @var CI_Controller
     */
    protected $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->database();
        $this->CI->load->model('Alumni_model');
        $this->CI->load->helper('email_config');
    }

    /**
     * Enforce a per-IP rate limit for sensitive auth actions.
     *
     * Falls back to session-backed tracking during initial setup when the
     * database table is unavailable.
     *
     * @param string $action
     * @return bool
     */
    public function check_rate_limit($action)
    {
        $max_requests = (int) (getenv('RATE_LIMIT') ?: 60);
        $window = 60;

        if (!$this->CI->db->table_exists('rate_limits')) {
            $key = 'rate_limit_' . $action;
            $attempts = $this->CI->session->userdata($key);
            if (!is_array($attempts)) {
                $attempts = array();
            }

            $cutoff_ts = time() - $window;
            $attempts = array_filter($attempts, function ($timestamp) use ($cutoff_ts) {
                return $timestamp > $cutoff_ts;
            });

            if (count($attempts) >= $max_requests) {
                return FALSE;
            }

            $attempts[] = time();
            $this->CI->session->set_userdata($key, $attempts);
            return TRUE;
        }

        $ip = $this->CI->input->ip_address();
        $cutoff = date('Y-m-d H:i:s', time() - $window);

        // Opportunistically purge old rows to keep the table bounded.
        if (mt_rand(1, 20) === 1) {
            $this->CI->db->where('attempted_at <', $cutoff);
            $this->CI->db->delete('rate_limits');
        }

        $this->CI->db->where('ip_address', $ip);
        $this->CI->db->where('action', $action);
        $this->CI->db->where('attempted_at >=', $cutoff);
        $count = $this->CI->db->count_all_results('rate_limits');

        if ($count >= $max_requests) {
            return FALSE;
        }

        $this->CI->db->insert('rate_limits', array(
            'ip_address' => $ip,
            'action' => $action
        ));

        return TRUE;
    }

    /**
     * Validate password strength.
     *
     * @param string $password
     * @return true|string
     */
    public function validate_password_strength($password)
    {
        if (strlen($password) < 8) {
            return 'Password must be at least 8 characters.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return 'Password must contain at least one uppercase letter.';
        }
        if (!preg_match('/[a-z]/', $password)) {
            return 'Password must contain at least one lowercase letter.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            return 'Password must contain at least one number.';
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            return 'Password must contain at least one special character.';
        }

        return TRUE;
    }

    /**
     * Validate the API registration payload.
     *
     * @param array $payload
     * @return true|string
     */
    public function validate_registration_payload($payload)
    {
        $payload = $this->sanitize_payload($payload, array('password', 'confirm_password'));

        foreach (array('first_name', 'last_name', 'email', 'password', 'confirm_password') as $field) {
            if (empty($payload[$field])) {
                return 'Missing required field: ' . $field;
            }
        }

        if (!$this->valid_length($payload['first_name'], 2, 100) || !$this->valid_length($payload['last_name'], 2, 100)) {
            return 'first_name and last_name must be between 2 and 100 characters.';
        }

        if (!$this->valid_alpha_numeric_spaces($payload['first_name']) || !$this->valid_alpha_numeric_spaces($payload['last_name'])) {
            return 'first_name and last_name may only contain letters, numbers, and spaces.';
        }

        if (!filter_var($payload['email'], FILTER_VALIDATE_EMAIL) || strlen((string) $payload['email']) > 255) {
            return 'email must be valid and 255 characters or fewer.';
        }

        if ($payload['password'] !== $payload['confirm_password']) {
            return 'confirm_password must match password.';
        }

        return $this->validate_password_strength($payload['password']);
    }

    /**
     * Register a new alumni account and send the verification email.
     *
     * @param array $payload
     * @return array
     */
    public function register_alumni($payload)
    {
        $payload = $this->sanitize_payload($payload, array('password', 'confirm_password'));
        $email = strtolower(trim((string) $payload['email']));
        $email_domain = substr(strrchr($email, '@'), 1);
        $university_domain = getenv('UNIVERSITY_DOMAIN') ?: 'my.westminster.ac.uk';

        if ($email_domain !== $university_domain) {
            return array(
                'ok' => FALSE,
                'status' => 422,
                'error' => 'Validation failed',
                'message' => 'Registration is restricted to @' . $university_domain . ' email addresses.'
            );
        }

        if ($this->CI->Alumni_model->email_exists($email)) {
            return array(
                'ok' => FALSE,
                'status' => 409,
                'error' => 'Conflict',
                'message' => 'An account with this email already exists.'
            );
        }

        $verification_token = bin2hex(random_bytes(32));
        $expiry_hours = getenv('VERIFICATION_TOKEN_EXPIRY') ?: 24;
        $alumni_id = $this->CI->Alumni_model->create(array(
            'email' => $email,
            'password' => password_hash((string) $payload['password'], PASSWORD_BCRYPT, array('cost' => 12)),
            'first_name' => $this->sanitize_plain_text($payload['first_name']),
            'last_name' => $this->sanitize_plain_text($payload['last_name']),
            'email_verified' => 0,
            'verification_token' => hash('sha256', $verification_token),
            'verification_expires' => date('Y-m-d H:i:s', strtotime('+' . $expiry_hours . ' hours')),
            'is_active' => 1
        ));

        if (!$alumni_id) {
            return array(
                'ok' => FALSE,
                'status' => 500,
                'error' => 'Server error',
                'message' => 'Registration failed.'
            );
        }

        $this->send_verification_email($email, $verification_token);

        return array(
            'ok' => TRUE,
            'status' => 201,
            'message' => 'Registration successful. Please verify your email.',
            'alumni_id' => $alumni_id,
            'verification_token' => $verification_token
        );
    }

    /**
     * Verify an email token and activate the account.
     *
     * @param string $token
     * @return array
     */
    public function verify_email_token($token)
    {
        $token = trim((string) $token);
        if ($token === '') {
            return array(
                'ok' => FALSE,
                'status' => 422,
                'error' => 'Validation failed',
                'message' => 'token is required.'
            );
        }

        $alumni = $this->CI->Alumni_model->find_by_verification_token(hash('sha256', $token));
        if (!$alumni) {
            return array(
                'ok' => FALSE,
                'status' => 404,
                'error' => 'Invalid token',
                'message' => 'Invalid or expired verification link.'
            );
        }

        if (strtotime($alumni->verification_expires) < time()) {
            return array(
                'ok' => FALSE,
                'status' => 410,
                'error' => 'Expired token',
                'message' => 'Verification link has expired.'
            );
        }

        $this->CI->Alumni_model->verify_email($alumni->id);

        return array(
            'ok' => TRUE,
            'status' => 200,
            'message' => 'Email verified successfully.'
        );
    }

    /**
     * Create and email a password reset token for a valid alumni email.
     *
     * @param string $email
     * @return array
     */
    public function request_password_reset($email)
    {
        $email = strtolower(trim((string) $email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return array(
                'ok' => FALSE,
                'status' => 422,
                'error' => 'Validation failed',
                'message' => 'Email must be valid.'
            );
        }

        $response = array(
            'ok' => TRUE,
            'status' => 200,
            'message' => 'If an account with that email exists, a password reset link has been sent.'
        );

        $alumni = $this->CI->Alumni_model->find_by_email($email);
        if (!$alumni) {
            return $response;
        }

        $reset_token = bin2hex(random_bytes(32));
        $expiry_hours = getenv('RESET_TOKEN_EXPIRY') ?: 1;
        $expires = date('Y-m-d H:i:s', strtotime('+' . $expiry_hours . ' hours'));

        $this->CI->Alumni_model->set_reset_token($alumni->id, hash('sha256', $reset_token), $expires);
        $this->send_reset_email($email, $reset_token);

        $response['reset_token'] = $reset_token;
        return $response;
    }

    /**
     * Reset an alumni password using the emailed token.
     *
     * @param string $token
     * @param string $password
     * @param string $confirm_password
     * @return array
     */
    public function reset_password($token, $password, $confirm_password)
    {
        $token = trim((string) $token);
        $password = (string) $password;
        $confirm_password = (string) $confirm_password;

        if ($token === '' || $password === '' || $confirm_password === '') {
            return array(
                'ok' => FALSE,
                'status' => 422,
                'error' => 'Validation failed',
                'message' => 'token, password, and confirm_password are required.'
            );
        }

        if ($password !== $confirm_password) {
            return array(
                'ok' => FALSE,
                'status' => 422,
                'error' => 'Validation failed',
                'message' => 'confirm_password must match password.'
            );
        }

        $strength = $this->validate_password_strength($password);
        if ($strength !== TRUE) {
            return array(
                'ok' => FALSE,
                'status' => 422,
                'error' => 'Validation failed',
                'message' => $strength
            );
        }

        $alumni = $this->CI->Alumni_model->find_by_reset_token(hash('sha256', $token));
        if (!$alumni) {
            return array(
                'ok' => FALSE,
                'status' => 404,
                'error' => 'Invalid token',
                'message' => 'Invalid or expired reset link.'
            );
        }

        if (strtotime($alumni->reset_expires) < time()) {
            return array(
                'ok' => FALSE,
                'status' => 410,
                'error' => 'Expired token',
                'message' => 'Reset link has expired.'
            );
        }

        $this->CI->Alumni_model->update_password(
            $alumni->id,
            password_hash($password, PASSWORD_BCRYPT, array('cost' => 12))
        );

        return array(
            'ok' => TRUE,
            'status' => 200,
            'message' => 'Password reset successfully.'
        );
    }

    /**
     * Verify a password reset token without changing the password.
     *
     * @param string $token
     * @return array
     */
    public function verify_reset_token($token)
    {
        $token = trim((string) $token);
        if ($token === '') {
            return array(
                'ok' => FALSE,
                'status' => 422,
                'error' => 'Validation failed',
                'message' => 'Invalid reset link.'
            );
        }

        $alumni = $this->CI->Alumni_model->find_by_reset_token(hash('sha256', $token));
        if (!$alumni) {
            return array(
                'ok' => FALSE,
                'status' => 404,
                'error' => 'Invalid token',
                'message' => 'Invalid or expired reset link.'
            );
        }

        if (strtotime($alumni->reset_expires) < time()) {
            return array(
                'ok' => FALSE,
                'status' => 410,
                'error' => 'Expired token',
                'message' => 'Reset link has expired.'
            );
        }

        return array(
            'ok' => TRUE,
            'status' => 200,
            'alumni' => $alumni
        );
    }

    /**
     * Authenticate alumni credentials and return the account state result.
     *
     * @param string $email
     * @param string $password
     * @return array
     */
    public function authenticate_credentials($email, $password)
    {
        $email = strtolower(trim((string) $email));
        $password = (string) $password;

        if ($email === '' || $password === '') {
            return array(
                'ok' => FALSE,
                'status' => 422,
                'error' => 'Validation failed',
                'message' => 'Email and password are required.'
            );
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return array(
                'ok' => FALSE,
                'status' => 422,
                'error' => 'Validation failed',
                'message' => 'Email must be valid.'
            );
        }

        $alumni = $this->CI->Alumni_model->find_by_email($email);
        if (!$alumni || !password_verify($password, $alumni->password)) {
            return array(
                'ok' => FALSE,
                'status' => 401,
                'error' => 'Unauthorized',
                'message' => 'Invalid email or password.'
            );
        }

        if (!$alumni->email_verified) {
            return array(
                'ok' => FALSE,
                'status' => 403,
                'error' => 'Forbidden',
                'message' => 'Please verify your email before logging in.'
            );
        }

        if (!$alumni->is_active) {
            return array(
                'ok' => FALSE,
                'status' => 403,
                'error' => 'Forbidden',
                'message' => 'Your account has been deactivated.'
            );
        }

        return array(
            'ok' => TRUE,
            'status' => 200,
            'alumni' => $alumni
        );
    }

    /**
     * Regenerate the session and persist the authenticated user state.
     *
     * @param object $alumni
     * @return array
     */
    public function start_session($alumni)
    {
        $this->CI->session->sess_regenerate(TRUE);
        $session_data = $this->build_session_data($alumni);
        $this->CI->session->set_userdata($session_data);

        return $session_data;
    }

    /**
     * Build the canonical alumni session payload.
     *
     * @param object $alumni
     * @return array
     */
    public function build_session_data($alumni)
    {
        $now = time();

        return array(
            'alumni_id' => $alumni->id,
            'email' => $alumni->email,
            'first_name' => $alumni->first_name,
            'last_name' => $alumni->last_name,
            'role' => $alumni->role ?? 'alumni',
            'logged_in' => TRUE,
            'login_time' => $now,
            'last_activity' => $now
        );
    }

    /**
     * Remove sensitive fields from the API session-user response.
     *
     * @param object $alumni
     * @return object
     */
    public function session_user_payload($alumni)
    {
        $user = clone $alumni;
        unset($user->password);
        unset($user->verification_token);
        unset($user->verification_expires);
        unset($user->reset_token);
        unset($user->reset_expires);

        return $user;
    }

    /**
     * Expose verification/reset tokens only in development and testing.
     *
     * @param string $type
     * @param string $token
     * @return array
     */
    public function debug_token_payload($type, $token)
    {
        if (!in_array((string) getenv('CI_ENV'), array('development', 'testing'), TRUE)) {
            return array();
        }

        $path = $type === 'verification'
            ? 'auth/verify/' . $token
            : 'auth/reset-password/' . $token;

        return array(
            $type . '_token' => $token,
            $type . '_url' => site_url($path)
        );
    }

    /**
     * Deliver a verification email.
     *
     * @param string $email
     * @param string $token
     * @return void
     */
    public function send_verification_email($email, $token)
    {
        $this->CI->load->library('email');

        $from = get_smtp_from();
        $this->CI->email->initialize(get_smtp_config());
        $this->CI->email->from($from['email'], $from['name']);
        $this->CI->email->to($email);
        $this->CI->email->subject('Verify Your Email - Alumni Influencers Platform');

        $verify_url = site_url('auth/verify/' . $token);
        $message = '<html><body>';
        $message .= '<h2>Welcome to the Alumni Influencers Platform!</h2>';
        $message .= '<p>Please click the link below to verify your email address:</p>';
        $message .= '<p><a href="' . htmlspecialchars($verify_url, ENT_QUOTES, 'UTF-8') . '">Verify Email Address</a></p>';
        $message .= '<p>This link will expire in 24 hours.</p>';
        $message .= '<p>If you did not create an account, please ignore this email.</p>';
        $message .= '</body></html>';

        $this->CI->email->message($message);
        send_email_safely($this->CI->email);
    }

    /**
     * Deliver a password reset email.
     *
     * @param string $email
     * @param string $token
     * @return void
     */
    public function send_reset_email($email, $token)
    {
        $this->CI->load->library('email');

        $from = get_smtp_from();
        $this->CI->email->initialize(get_smtp_config());
        $this->CI->email->from($from['email'], $from['name']);
        $this->CI->email->to($email);
        $this->CI->email->subject('Password Reset - Alumni Influencers Platform');

        $reset_url = site_url('auth/reset-password/' . $token);
        $message = '<html><body>';
        $message .= '<h2>Password Reset Request</h2>';
        $message .= '<p>Click the link below to reset your password:</p>';
        $message .= '<p><a href="' . htmlspecialchars($reset_url, ENT_QUOTES, 'UTF-8') . '">Reset Password</a></p>';
        $message .= '<p>This link will expire in 1 hour.</p>';
        $message .= '<p>If you did not request a password reset, please ignore this email.</p>';
        $message .= '</body></html>';

        $this->CI->email->message($message);
        send_email_safely($this->CI->email);
    }

    /**
     * Shared string length validation used by registration rules.
     *
     * @param string $value
     * @param int $min
     * @param int $max
     * @return bool
     */
    protected function valid_length($value, $min, $max)
    {
        $length = strlen(trim((string) $value));
        return $length >= $min && $length <= $max;
    }

    /**
     * Restrict names to the same character set across UI and API flows.
     *
     * @param string $value
     * @return bool
     */
    protected function valid_alpha_numeric_spaces($value)
    {
        return preg_match('/^[a-z0-9 ]+$/i', trim((string) $value)) === 1;
    }

    /**
     * Recursively sanitize payload data while preserving raw password fields.
     *
     * @param mixed $value
     * @param array $raw_fields
     * @param string|null $field_name
     * @return mixed
     */
    protected function sanitize_payload($value, $raw_fields = array(), $field_name = NULL)
    {
        if (is_array($value)) {
            $clean = array();
            foreach ($value as $key => $item) {
                $clean[$key] = $this->sanitize_payload($item, $raw_fields, is_string($key) ? $key : $field_name);
            }
            return $clean;
        }

        if (!is_string($value)) {
            return $value;
        }

        if ($field_name !== NULL && in_array($field_name, $raw_fields, TRUE)) {
            return trim($value);
        }

        return $this->sanitize_plain_text($value);
    }

    /**
     * Normalize plain-text user input before validation or persistence.
     *
     * @param mixed $value
     * @return string
     */
    protected function sanitize_plain_text($value)
    {
        $value = trim((string) $value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        $value = strip_tags($value);

        if (isset($this->CI->security) && is_object($this->CI->security) && method_exists($this->CI->security, 'xss_clean')) {
            $value = $this->CI->security->xss_clean($value);
        }

        return is_string($value) ? trim($value) : '';
    }
}

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * API Controller
 *
 * Public REST API endpoints secured with bearer tokens.
 * Provides read endpoints for featured alumni and alumni resources,
 * plus controlled write endpoints for alumni via HTTP POST/PATCH/DELETE.
 */
class Api extends CI_Controller
{
    /**
     * @var object|null Authenticated API client
     */
    protected $api_client = NULL;

    /**
     * Normalized scope strings for the authenticated client.
     *
     * @var array
     */
    protected $api_scopes = array();

    /**
     * API methods that do not require bearer-token authentication.
     *
     * @var array
     */
    protected $public_methods = array(
        'auth_register',
        'auth_verify',
        'auth_forgot_password',
        'auth_reset_password',
        'auth_login',
        'auth_logout',
        'auth_me'
    );

    /**
     * API methods that use the logged-in alumni session rather than bearer tokens.
     *
     * @var array
     */
    protected $session_methods = array(
        'auth_me',
        'profile_me',
        'profile_image_upload',
        'degrees',
        'degree_item',
        'certifications',
        'certification_item',
        'licences',
        'licence_item',
        'courses',
        'course_item',
        'employment',
        'employment_item',
        'bidding_dashboard',
        'bids',
        'bid_item',
        'sponsorships',
        'sponsorship_item',
        'events',
        'event_item',
        'admin_api_clients',
        'admin_api_client_item',
        'admin_api_client_logs',
        'admin_api_stats',
        'admin_select_winner'
    );

    public function __construct()
    {
        parent::__construct();
        $this->load->library('auth_service');
        $this->load->library('admin_service');
        $this->load->model('Api_client_model');
        $this->load->model('Alumni_model');
        $this->load->model('Profile_model');
        $this->load->model('Bid_model');

        if ($this->input->method() === 'options') {
            $this->output->set_status_header(204)->_display();
            exit;
        }

        $current_method = $this->_current_method();
        if (!in_array($current_method, $this->public_methods, TRUE)
            && !in_array($current_method, $this->session_methods, TRUE)
        ) {
            $this->_authenticate();
        }
    }

    /**
     * Register a new alumni account using JSON.
     *
     * POST /api/v1/auth/register
     */
    public function auth_register()
    {
        if ($this->input->method() !== 'post') {
            $this->_json_response(array('error' => 'Method not allowed'), 405);
            return;
        }

        if (!$this->auth_service->check_rate_limit('register')) {
            $this->_json_response(array('error' => 'Too many requests', 'message' => 'Please try again later.'), 429);
            return;
        }

        $payload = $this->_request_payload();
        $validation = $this->auth_service->validate_registration_payload($payload);
        if ($validation !== TRUE) {
            $this->_json_response(array('error' => 'Validation failed', 'message' => $validation), 422);
            return;
        }

        $result = $this->auth_service->register_alumni($payload);
        if (!$result['ok']) {
            $this->_json_response(array(
                'error' => $result['error'],
                'message' => $result['message']
            ), $result['status']);
            return;
        }

        $response = array(
            'status' => 'created',
            'message' => $result['message']
        );
        $response = array_merge($response, $this->auth_service->debug_token_payload('verification', $result['verification_token']));
        $this->_json_response($response, 201);
    }

    /**
     * Verify an email using the token from email.
     *
     * POST /api/v1/auth/verify
     */
    public function auth_verify()
    {
        if ($this->input->method() !== 'post') {
            $this->_json_response(array('error' => 'Method not allowed'), 405);
            return;
        }

        $payload = $this->_request_payload();
        $result = $this->auth_service->verify_email_token(isset($payload['token']) ? $payload['token'] : '');
        if (!$result['ok']) {
            $this->_json_response(array('error' => $result['error'], 'message' => $result['message']), $result['status']);
            return;
        }

        $this->_json_response(array('status' => 'success', 'message' => 'Email verified successfully.'), 200);
    }

    /**
     * Request a password reset email.
     *
     * POST /api/v1/auth/forgot-password
     */
    public function auth_forgot_password()
    {
        if ($this->input->method() !== 'post') {
            $this->_json_response(array('error' => 'Method not allowed'), 405);
            return;
        }

        if (!$this->auth_service->check_rate_limit('forgot_password')) {
            $this->_json_response(array('error' => 'Too many requests', 'message' => 'Please try again later.'), 429);
            return;
        }

        $payload = $this->_request_payload();
        $result = $this->auth_service->request_password_reset(isset($payload['email']) ? $payload['email'] : '');
        if (!$result['ok']) {
            $this->_json_response(array('error' => $result['error'], 'message' => $result['message']), $result['status']);
            return;
        }

        $response = array(
            'status' => 'success',
            'message' => $result['message']
        );
        if (!empty($result['reset_token'])) {
            $response = array_merge($response, $this->auth_service->debug_token_payload('reset', $result['reset_token']));
        }

        $this->_json_response($response, 200);
    }

    /**
     * Reset a password using the emailed token.
     *
     * POST /api/v1/auth/reset-password
     */
    public function auth_reset_password()
    {
        if ($this->input->method() !== 'post') {
            $this->_json_response(array('error' => 'Method not allowed'), 405);
            return;
        }

        $payload = $this->_request_payload();
        $result = $this->auth_service->reset_password(
            isset($payload['token']) ? $payload['token'] : '',
            isset($payload['password']) ? $payload['password'] : '',
            isset($payload['confirm_password']) ? $payload['confirm_password'] : ''
        );
        if (!$result['ok']) {
            $this->_json_response(array('error' => $result['error'], 'message' => $result['message']), $result['status']);
            return;
        }

        $this->_json_response(array('status' => 'success', 'message' => 'Password reset successfully.'), 200);
    }

    /**
     * Session-based JSON login endpoint for browser or Postman clients.
     *
     * POST /api/v1/auth/login
     */
    public function auth_login()
    {
        if ($this->input->method() !== 'post') {
            $this->_json_response(array('error' => 'Method not allowed'), 405);
            return;
        }

        if (!$this->auth_service->check_rate_limit('login')) {
            $this->_json_response(array('error' => 'Too many requests', 'message' => 'Please try again later.'), 429);
            return;
        }

        $payload = $this->_request_payload();
        $result = $this->auth_service->authenticate_credentials(
            isset($payload['email']) ? $payload['email'] : '',
            isset($payload['password']) ? $payload['password'] : ''
        );
        if (!$result['ok']) {
            $this->_json_response(array('error' => $result['error'], 'message' => $result['message']), $result['status']);
            return;
        }

        $alumni = $result['alumni'];
        $session_data = $this->auth_service->start_session($alumni);
        $profile = $this->Alumni_model->get_full_profile($alumni->id);

        $this->_json_response(array(
            'status' => 'success',
            'message' => 'Login successful.',
            'user' => $this->auth_service->session_user_payload($profile['alumni']),
            'session' => array(
                'logged_in' => TRUE,
                'login_time' => $session_data['login_time'],
                'last_activity' => $session_data['last_activity']
            )
        ), 200);
    }

    /**
     * Return the current authenticated session user for API clients.
     *
     * GET /api/v1/auth/me
     */
    public function auth_me()
    {
        if ($this->input->method() !== 'get') {
            $this->_json_response(array('error' => 'Method not allowed'), 405);
            return;
        }

        if (!$this->session->userdata('logged_in') || !$this->session->userdata('alumni_id')) {
            $this->_json_response(array(
                'error' => 'Unauthorized',
                'message' => 'No authenticated session.'
            ), 401);
            return;
        }

        $profile = $this->Alumni_model->get_full_profile((int)$this->session->userdata('alumni_id'));
        if (!$profile) {
            $this->session->sess_destroy();
            $this->_json_response(array(
                'error' => 'Unauthorized',
                'message' => 'Session user no longer exists.'
            ), 401);
            return;
        }

        $this->_json_response(array(
            'status' => 'success',
            'user' => $this->auth_service->session_user_payload($profile['alumni'])
        ), 200);
    }

    /**
     * Destroy the current authenticated session.
     *
     * POST /api/v1/auth/logout
     */
    public function auth_logout()
    {
        if ($this->input->method() !== 'post') {
            $this->_json_response(array('error' => 'Method not allowed'), 405);
            return;
        }

        $this->session->sess_destroy();

        $this->_json_response(array(
            'status' => 'success',
            'message' => 'Logout successful.'
        ), 200);
    }

    /**
     * Session-authenticated current profile endpoint.
     *
     * GET/PATCH /api/v1/me/profile
     */
    public function profile_me()
    {
        $alumni_id = $this->_require_session_auth();
        if (!$alumni_id) {
            return;
        }

        $method = $this->input->method();
        if ($method === 'get') {
            $profile = $this->Alumni_model->get_full_profile($alumni_id);
            $this->_json_response(array(
                'status' => 'success',
                'profile' => $profile
            ), 200);
            return;
        }

        if ($method === 'patch') {
            $payload = $this->_request_payload();
            $validation = $this->_validate_profile_update($payload);
            if ($validation !== TRUE) {
                $this->_json_response(array('error' => 'Validation failed', 'message' => $validation), 422);
                return;
            }

            $update = array(
                'first_name' => trim($payload['first_name']),
                'last_name' => trim($payload['last_name']),
                'bio' => isset($payload['bio']) ? trim((string)$payload['bio']) : NULL,
                'linkedin_url' => isset($payload['linkedin_url']) && $payload['linkedin_url'] !== '' ? trim((string)$payload['linkedin_url']) : NULL
            );

            $this->Alumni_model->update($alumni_id, $update);
            $this->session->set_userdata('first_name', $update['first_name']);
            $this->session->set_userdata('last_name', $update['last_name']);

            $profile = $this->Alumni_model->get_full_profile($alumni_id);
            $this->_json_response(array(
                'status' => 'success',
                'message' => 'Profile updated successfully.',
                'profile' => $profile
            ), 200);
            return;
        }

        $this->_json_response(array('error' => 'Method not allowed'), 405);
    }

    /**
     * Multipart profile image upload endpoint.
     *
     * POST /api/v1/me/profile/image
     */
    public function profile_image_upload()
    {
        $alumni_id = $this->_require_session_auth();
        if (!$alumni_id) {
            return;
        }

        if ($this->input->method() !== 'post') {
            $this->_json_response(array('error' => 'Method not allowed'), 405);
            return;
        }

        if (empty($_FILES['profile_image'])) {
            $this->_json_response(array(
                'error' => 'Validation failed',
                'message' => 'profile_image file is required.'
            ), 422);
            return;
        }

        $upload_path = getenv('UPLOAD_PATH') ?: './uploads/profile_images/';
        if (!is_dir($upload_path)) {
            @mkdir($upload_path, 0755, TRUE);
        }

        $config = array(
            'upload_path'   => $upload_path,
            'allowed_types' => 'gif|jpg|jpeg|png',
            'max_size'      => (int)(getenv('MAX_IMAGE_SIZE') ?: 2048),
            'max_width'     => (int)(getenv('MAX_IMAGE_WIDTH') ?: 4000),
            'max_height'    => (int)(getenv('MAX_IMAGE_HEIGHT') ?: 4000),
            'encrypt_name'  => TRUE,
            'detect_mime'   => TRUE,
            'mod_mime_fix'  => TRUE,
            'remove_spaces' => TRUE
        );

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('profile_image')) {
            $this->_json_response(array(
                'error' => 'Validation failed',
                'message' => strip_tags($this->upload->display_errors('', ''))
            ), 422);
            return;
        }

        $upload_data = $this->upload->data();
        $image_info = @getimagesize($upload_data['full_path']);
        if ($image_info === FALSE) {
            @unlink($upload_data['full_path']);
            $this->_json_response(array(
                'error' => 'Validation failed',
                'message' => 'Uploaded file is not a valid image.'
            ), 422);
            return;
        }

        $allowed_mimes = array('image/gif', 'image/jpeg', 'image/png');
        if (!in_array($image_info['mime'], $allowed_mimes, TRUE)) {
            @unlink($upload_data['full_path']);
            $this->_json_response(array(
                'error' => 'Validation failed',
                'message' => 'Uploaded file type is not allowed.'
            ), 422);
            return;
        }

        $alumni = $this->Alumni_model->find_by_id($alumni_id);
        $old_file = $alumni && $alumni->profile_image ? rtrim($upload_path, '/\\') . DIRECTORY_SEPARATOR . basename($alumni->profile_image) : NULL;
        if ($old_file && is_file($old_file)) {
            @unlink($old_file);
        }

        $this->Alumni_model->update($alumni_id, array('profile_image' => $upload_data['file_name']));
        $profile = $this->Alumni_model->get_full_profile($alumni_id);

        $this->_json_response(array(
            'status' => 'success',
            'message' => 'Profile image uploaded successfully.',
            'profile' => $profile
        ), 200);
    }

    public function degrees()
    {
        $alumni_id = $this->_require_session_auth();
        if (!$alumni_id) {
            return;
        }

        $method = $this->input->method();
        if ($method === 'get') {
            $this->_json_response(array('status' => 'success', 'degrees' => $this->Profile_model->get_degrees($alumni_id)), 200);
            return;
        }

        if ($method === 'post') {
            $payload = $this->_request_payload();
            $validation = $this->_validate_profile_record($payload, array('title', 'institution'), 'optional_date');
            if ($validation !== TRUE) {
                $this->_json_response(array('error' => 'Validation failed', 'message' => $validation), 422);
                return;
            }

            $id = $this->Profile_model->add_degree(array(
                'alumni_id' => $alumni_id,
                'title' => trim($payload['title']),
                'institution' => trim($payload['institution']),
                'url' => $this->_nullable_string($payload, 'url'),
                'completion_date' => $this->_nullable_string($payload, 'completion_date')
            ));

            $this->_json_response(array(
                'status' => 'created',
                'message' => 'Degree added successfully.',
                'degree' => $this->Profile_model->get_degree($id)
            ), 201);
            return;
        }

        $this->_json_response(array('error' => 'Method not allowed'), 405);
    }

    public function degree_item($id)
    {
        $this->_profile_record_item(
            'degree',
            $id,
            'get_degree',
            'update_degree',
            'delete_degree',
            array('title', 'institution'),
            'optional_date'
        );
    }

    public function certifications()
    {
        $alumni_id = $this->_require_session_auth();
        if (!$alumni_id) {
            return;
        }

        $method = $this->input->method();
        if ($method === 'get') {
            $this->_json_response(array('status' => 'success', 'certifications' => $this->Profile_model->get_certifications($alumni_id)), 200);
            return;
        }

        if ($method === 'post') {
            $payload = $this->_request_payload();
            $validation = $this->_validate_profile_record($payload, array('title', 'issuer'), 'optional_date');
            if ($validation !== TRUE) {
                $this->_json_response(array('error' => 'Validation failed', 'message' => $validation), 422);
                return;
            }

            $id = $this->Profile_model->add_certification(array(
                'alumni_id' => $alumni_id,
                'title' => trim($payload['title']),
                'issuer' => trim($payload['issuer']),
                'url' => $this->_nullable_string($payload, 'url'),
                'completion_date' => $this->_nullable_string($payload, 'completion_date')
            ));

            $this->_json_response(array(
                'status' => 'created',
                'message' => 'Certification added successfully.',
                'certification' => $this->Profile_model->get_certification($id)
            ), 201);
            return;
        }

        $this->_json_response(array('error' => 'Method not allowed'), 405);
    }

    public function certification_item($id)
    {
        $this->_profile_record_item(
            'certification',
            $id,
            'get_certification',
            'update_certification',
            'delete_certification',
            array('title', 'issuer'),
            'optional_date'
        );
    }

    public function licences()
    {
        $alumni_id = $this->_require_session_auth();
        if (!$alumni_id) {
            return;
        }

        $method = $this->input->method();
        if ($method === 'get') {
            $this->_json_response(array('status' => 'success', 'licences' => $this->Profile_model->get_licences($alumni_id)), 200);
            return;
        }

        if ($method === 'post') {
            $payload = $this->_request_payload();
            $validation = $this->_validate_profile_record($payload, array('title', 'awarding_body'), 'optional_date');
            if ($validation !== TRUE) {
                $this->_json_response(array('error' => 'Validation failed', 'message' => $validation), 422);
                return;
            }

            $id = $this->Profile_model->add_licence(array(
                'alumni_id' => $alumni_id,
                'title' => trim($payload['title']),
                'awarding_body' => trim($payload['awarding_body']),
                'url' => $this->_nullable_string($payload, 'url'),
                'completion_date' => $this->_nullable_string($payload, 'completion_date')
            ));

            $this->_json_response(array(
                'status' => 'created',
                'message' => 'Licence added successfully.',
                'licence' => $this->Profile_model->get_licence($id)
            ), 201);
            return;
        }

        $this->_json_response(array('error' => 'Method not allowed'), 405);
    }

    public function licence_item($id)
    {
        $this->_profile_record_item(
            'licence',
            $id,
            'get_licence',
            'update_licence',
            'delete_licence',
            array('title', 'awarding_body'),
            'optional_date'
        );
    }

    public function courses()
    {
        $alumni_id = $this->_require_session_auth();
        if (!$alumni_id) {
            return;
        }

        $method = $this->input->method();
        if ($method === 'get') {
            $this->_json_response(array('status' => 'success', 'courses' => $this->Profile_model->get_courses($alumni_id)), 200);
            return;
        }

        if ($method === 'post') {
            $payload = $this->_request_payload();
            $validation = $this->_validate_profile_record($payload, array('title', 'provider'), 'optional_date');
            if ($validation !== TRUE) {
                $this->_json_response(array('error' => 'Validation failed', 'message' => $validation), 422);
                return;
            }

            $id = $this->Profile_model->add_course(array(
                'alumni_id' => $alumni_id,
                'title' => trim($payload['title']),
                'provider' => trim($payload['provider']),
                'url' => $this->_nullable_string($payload, 'url'),
                'completion_date' => $this->_nullable_string($payload, 'completion_date')
            ));

            $this->_json_response(array(
                'status' => 'created',
                'message' => 'Course added successfully.',
                'course' => $this->Profile_model->get_course($id)
            ), 201);
            return;
        }

        $this->_json_response(array('error' => 'Method not allowed'), 405);
    }

    public function course_item($id)
    {
        $this->_profile_record_item(
            'course',
            $id,
            'get_course',
            'update_course',
            'delete_course',
            array('title', 'provider'),
            'optional_date'
        );
    }

    public function employment()
    {
        $alumni_id = $this->_require_session_auth();
        if (!$alumni_id) {
            return;
        }

        $method = $this->input->method();
        if ($method === 'get') {
            $this->_json_response(array('status' => 'success', 'employment' => $this->Profile_model->get_employment_history($alumni_id)), 200);
            return;
        }

        if ($method === 'post') {
            $payload = $this->_request_payload();
            $validation = $this->_validate_employment_payload($payload);
            if ($validation !== TRUE) {
                $this->_json_response(array('error' => 'Validation failed', 'message' => $validation), 422);
                return;
            }

            $id = $this->Profile_model->add_employment(array(
                'alumni_id' => $alumni_id,
                'company' => trim($payload['company']),
                'position' => trim($payload['position']),
                'start_date' => trim($payload['start_date']),
                'end_date' => $this->_nullable_string($payload, 'end_date')
            ));

            $this->_json_response(array(
                'status' => 'created',
                'message' => 'Employment record added successfully.',
                'employment' => $this->Profile_model->get_employment($id)
            ), 201);
            return;
        }

        $this->_json_response(array('error' => 'Method not allowed'), 405);
    }

    public function employment_item($id)
    {
        $this->_profile_record_item(
            'employment',
            $id,
            'get_employment',
            'update_employment',
            'delete_employment',
            array('company', 'position', 'start_date'),
            'employment'
        );
    }

    public function bidding_dashboard()
    {
        $alumni_id = $this->_require_session_auth();
        if (!$alumni_id) {
            return;
        }

        if ($this->input->method() !== 'get') {
            $this->_json_response(array('error' => 'Method not allowed'), 405);
            return;
        }

        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $current_bid = $this->Bid_model->get_alumni_bid_for_date($alumni_id, $tomorrow);
        $monthly_wins = $this->Bid_model->get_monthly_wins($alumni_id);
        $max_wins = $this->Bid_model->get_max_monthly_wins($alumni_id);

        $this->_json_response(array(
            'status' => 'success',
            'bidding' => array(
                'current_bid' => $current_bid,
                'is_winning' => $current_bid ? $this->Bid_model->is_winning($alumni_id, $tomorrow) : FALSE,
                'monthly_wins' => $monthly_wins,
                'max_wins' => $max_wins,
                'can_bid' => $this->Bid_model->can_bid($alumni_id),
                'remaining_slots' => $max_wins - $monthly_wins,
                'sponsorship_total' => $this->Bid_model->get_accepted_sponsorship_total($alumni_id),
                'sponsorships' => $this->Bid_model->get_sponsorships($alumni_id),
                'events' => $this->Bid_model->get_event_participations($alumni_id),
                'featured_today' => $this->Bid_model->get_featured_today(),
                'bid_date' => $tomorrow
            )
        ), 200);
    }

    public function bids()
    {
        $alumni_id = $this->_require_session_auth();
        if (!$alumni_id) {
            return;
        }

        $method = $this->input->method();
        if ($method === 'get') {
            $this->_json_response(array('status' => 'success', 'bids' => $this->Bid_model->get_alumni_bids($alumni_id)), 200);
            return;
        }

        if ($method === 'post') {
            $payload = $this->_request_payload();
            $validation = $this->_validate_bid_create_payload($payload, $alumni_id);
            if ($validation !== TRUE) {
                $this->_json_response(array('error' => 'Validation failed', 'message' => $validation), 422);
                return;
            }

            $amount = (float)$payload['amount'];
            $bid_date = trim($payload['bid_date']);
            $bid_id = $this->Bid_model->place_bid($alumni_id, $amount, $bid_date);

            $this->_json_response(array(
                'status' => 'created',
                'message' => $this->Bid_model->is_winning($alumni_id, $bid_date)
                    ? 'Bid placed successfully. You are currently in the lead!'
                    : 'Bid placed successfully. You are not currently in the lead.',
                'bid' => $this->Bid_model->get_bid($bid_id)
            ), 201);
            return;
        }

        $this->_json_response(array('error' => 'Method not allowed'), 405);
    }

    public function bid_item($id)
    {
        $alumni_id = $this->_require_session_auth();
        if (!$alumni_id) {
            return;
        }

        $bid = $this->Bid_model->get_bid($id);
        if (!$bid || (int)$bid->alumni_id !== (int)$alumni_id) {
            $this->_json_response(array('error' => 'Not found', 'message' => 'Bid not found.'), 404);
            return;
        }

        $method = $this->input->method();
        if ($method === 'get') {
            $this->_json_response(array('status' => 'success', 'bid' => $bid), 200);
            return;
        }

        if ($method === 'patch') {
            $payload = $this->_request_payload();
            if (!isset($payload['amount']) || !is_numeric($payload['amount']) || (float)$payload['amount'] <= 0) {
                $this->_json_response(array('error' => 'Validation failed', 'message' => 'amount must be greater than 0.'), 422);
                return;
            }

            if ($bid->status !== 'pending') {
                $this->_json_response(array('error' => 'Validation failed', 'message' => 'This bid can no longer be updated.'), 422);
                return;
            }

            $new_amount = (float)$payload['amount'];
            if ($new_amount <= (float)$bid->amount) {
                $this->_json_response(array('error' => 'Validation failed', 'message' => 'You can only increase your bid.'), 422);
                return;
            }

            $sponsorship_total = $this->Bid_model->get_accepted_sponsorship_total($alumni_id);
            if ($sponsorship_total <= 0 || $new_amount > $sponsorship_total) {
                $this->_json_response(array(
                    'error' => 'Validation failed',
                    'message' => 'Your updated bid cannot exceed your accepted sponsorship funding.'
                ), 422);
                return;
            }

            $this->Bid_model->update_bid($id, $new_amount);
            $updated_bid = $this->Bid_model->get_bid($id);
            $this->_json_response(array(
                'status' => 'success',
                'message' => $this->Bid_model->is_winning($alumni_id, $updated_bid->bid_date)
                    ? 'Bid updated. You are now in the lead!'
                    : 'Bid updated. You are not currently in the lead.',
                'bid' => $updated_bid
            ), 200);
            return;
        }

        $this->_json_response(array('error' => 'Method not allowed'), 405);
    }

    public function sponsorships()
    {
        $alumni_id = $this->_require_session_auth();
        if (!$alumni_id) {
            return;
        }

        $method = $this->input->method();
        if ($method === 'get') {
            $this->_json_response(array(
                'status' => 'success',
                'accepted_total' => $this->Bid_model->get_accepted_sponsorship_total($alumni_id),
                'sponsorships' => $this->Bid_model->get_sponsorships($alumni_id)
            ), 200);
            return;
        }

        if ($method === 'post') {
            $payload = $this->_request_payload();
            $validation = $this->_validate_sponsorship_payload($payload);
            if ($validation !== TRUE) {
                $this->_json_response(array('error' => 'Validation failed', 'message' => $validation), 422);
                return;
            }

            $id = $this->Bid_model->add_sponsorship(array(
                'alumni_id' => $alumni_id,
                'sponsor_name' => trim($payload['sponsor_name']),
                'amount_offered' => (float)$payload['amount_offered'],
                'status' => trim($payload['status'])
            ));

            $this->_json_response(array(
                'status' => 'created',
                'message' => 'Sponsorship offer saved successfully.',
                'sponsorship' => $this->Bid_model->get_sponsorship($id, $alumni_id)
            ), 201);
            return;
        }

        $this->_json_response(array('error' => 'Method not allowed'), 405);
    }

    public function sponsorship_item($id)
    {
        $alumni_id = $this->_require_session_auth();
        if (!$alumni_id) {
            return;
        }

        $item = $this->Bid_model->get_sponsorship($id, $alumni_id);
        if (!$item) {
            $this->_json_response(array('error' => 'Not found', 'message' => 'Sponsorship not found.'), 404);
            return;
        }

        $method = $this->input->method();
        if ($method === 'get') {
            $this->_json_response(array('status' => 'success', 'sponsorship' => $item), 200);
            return;
        }

        if ($method === 'patch') {
            $payload = $this->_request_payload();
            $status = isset($payload['status']) ? trim((string)$payload['status']) : '';
            if (!in_array($status, array('pending', 'accepted', 'rejected'), TRUE)) {
                $this->_json_response(array('error' => 'Validation failed', 'message' => 'Invalid sponsorship status.'), 422);
                return;
            }

            $this->Bid_model->update_sponsorship($id, $alumni_id, array('status' => $status));
            $this->_json_response(array(
                'status' => 'success',
                'message' => 'Sponsorship status updated.',
                'sponsorship' => $this->Bid_model->get_sponsorship($id, $alumni_id)
            ), 200);
            return;
        }

        if ($method === 'delete') {
            $this->Bid_model->delete_sponsorship($id, $alumni_id);
            $this->output->set_status_header(204);
            return;
        }

        $this->_json_response(array('error' => 'Method not allowed'), 405);
    }

    public function events()
    {
        $alumni_id = $this->_require_session_auth();
        if (!$alumni_id) {
            return;
        }

        $method = $this->input->method();
        if ($method === 'get') {
            $this->_json_response(array(
                'status' => 'success',
                'max_wins' => $this->Bid_model->get_max_monthly_wins($alumni_id),
                'events' => $this->Bid_model->get_event_participations($alumni_id)
            ), 200);
            return;
        }

        if ($method === 'post') {
            $payload = $this->_request_payload();
            $validation = $this->_validate_event_payload($payload);
            if ($validation !== TRUE) {
                $this->_json_response(array('error' => 'Validation failed', 'message' => $validation), 422);
                return;
            }

            $id = $this->Bid_model->add_event_participation(array(
                'alumni_id' => $alumni_id,
                'event_name' => trim($payload['event_name']),
                'event_date' => trim($payload['event_date'])
            ));

            $events = $this->Bid_model->get_event_participations($alumni_id);
            $created = NULL;
            foreach ($events as $event) {
                if ((int)$event->id === (int)$id) {
                    $created = $event;
                    break;
                }
            }

            $this->_json_response(array(
                'status' => 'created',
                'message' => 'Event participation recorded.',
                'event' => $created
            ), 201);
            return;
        }

        $this->_json_response(array('error' => 'Method not allowed'), 405);
    }

    public function event_item($id)
    {
        $alumni_id = $this->_require_session_auth();
        if (!$alumni_id) {
            return;
        }

        $event = $this->_find_owned_record($this->Bid_model->get_event_participations($alumni_id), $id);
        if (!$event) {
            $this->_json_response(array('error' => 'Not found', 'message' => 'Event participation not found.'), 404);
            return;
        }

        $method = $this->input->method();
        if ($method === 'get') {
            $this->_json_response(array('status' => 'success', 'event' => $event), 200);
            return;
        }

        if ($method === 'delete') {
            $this->Bid_model->delete_event_participation($id, $alumni_id);
            $this->output->set_status_header(204);
            return;
        }

        $this->_json_response(array('error' => 'Method not allowed'), 405);
    }

    /**
     * Admin API client management.
     *
     * GET/POST /api/v1/admin/api-clients
     */
    public function admin_api_clients()
    {
        $this->_require_admin_session() || exit;

        $method = $this->input->method();
        if ($method === 'get') {
            $this->_json_response(array(
                'status' => 'success',
                'clients' => $this->admin_service->get_api_clients()
            ), 200);
            return;
        }

        if ($method === 'post') {
            $payload = $this->_request_payload();
            $result = $this->admin_service->create_api_client(
                isset($payload['client_name']) ? $payload['client_name'] : '',
                isset($payload['scope']) ? $payload['scope'] : ''
            );
            if (!$result['ok']) {
                $this->_json_response(array('error' => $result['error'], 'message' => $result['message']), $result['status']);
                return;
            }

            $this->_json_response(array(
                'status' => 'created',
                'message' => $result['message'],
                'client' => $result['client']
            ), 201);
            return;
        }

        $this->_json_response(array('error' => 'Method not allowed'), 405);
    }

    /**
     * Admin API client activation/revocation endpoint.
     *
     * PATCH /api/v1/admin/api-clients/{id}
     */
    public function admin_api_client_item($id)
    {
        $this->_require_admin_session() || exit;

        if ($this->input->method() !== 'patch') {
            $this->_json_response(array('error' => 'Method not allowed'), 405);
            return;
        }

        $payload = $this->_request_payload();
        if (!array_key_exists('is_active', $payload)) {
            $this->_json_response(array('error' => 'Validation failed', 'message' => 'is_active is required.'), 422);
            return;
        }

        $is_active = filter_var($payload['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($is_active === NULL) {
            $this->_json_response(array('error' => 'Validation failed', 'message' => 'is_active must be true or false.'), 422);
            return;
        }

        $result = $this->admin_service->set_api_client_active($id, $is_active);
        if (!$result['ok']) {
            $this->_json_response(array('error' => $result['error'], 'message' => $result['message']), $result['status']);
            return;
        }

        $this->_json_response(array(
            'status' => 'success',
            'message' => $result['message'],
            'client' => $result['client']
        ), 200);
    }

    /**
     * View logs for an API client.
     *
     * GET /api/v1/admin/api-clients/{id}/logs
     */
    public function admin_api_client_logs($id)
    {
        $this->_require_admin_session() || exit;

        if ($this->input->method() !== 'get') {
            $this->_json_response(array('error' => 'Method not allowed'), 405);
            return;
        }

        $this->_json_response(array(
            'status' => 'success',
            'logs' => $this->admin_service->get_api_client_logs($id)
        ), 200);
    }

    /**
     * API usage statistics for admins.
     *
     * GET /api/v1/admin/api-stats
     */
    public function admin_api_stats()
    {
        $this->_require_admin_session() || exit;

        if ($this->input->method() !== 'get') {
            $this->_json_response(array('error' => 'Method not allowed'), 405);
            return;
        }

        $this->_json_response(array(
            'status' => 'success',
            'stats' => $this->admin_service->get_api_usage_stats()
        ), 200);
    }

    /**
     * Manually trigger featured alumni winner selection from a session-authenticated admin API call.
     *
     * POST /api/v1/admin/select-winner
     */
    public function admin_select_winner()
    {
        $this->_require_admin_session() || exit;

        if ($this->input->method() !== 'post') {
            $this->_json_response(array('error' => 'Method not allowed'), 405);
            return;
        }

        $payload = $this->_request_payload();
        $result = $this->admin_service->select_winner(isset($payload['featured_date']) ? $payload['featured_date'] : '');
        if (!$result['ok']) {
            $this->_json_response(array('error' => $result['error'], 'message' => $result['message']), $result['status']);
            return;
        }

        if ($result['state'] !== 'selected') {
            $this->_json_response(array(
                'status' => 'noop',
                'message' => $result['message'],
                'featured_date' => $result['featured_date']
            ), 200);
            return;
        }

        $selection = $result['result'];
        $winner = $selection['winner'];
        $this->_json_response(array(
            'status' => 'success',
            'message' => 'Winner selected successfully.',
            'featured_date' => $selection['featured_date'],
            'winner' => array(
                'id' => $winner ? (int)$winner->id : NULL,
                'first_name' => $winner ? $winner->first_name : NULL,
                'last_name' => $winner ? $winner->last_name : NULL
            ),
            'bid' => $selection['bid'],
            'notifications' => array(
                'winner_email_sent' => $selection['winner_email_sent'],
                'loser_emails' => $selection['loser_emails']
            )
        ), 200);
    }

    /**
     * Authenticate API request using bearer token.
     */
    private function _authenticate()
    {
        $auth_header = $this->input->get_request_header('Authorization');

        if (!$auth_header || strpos($auth_header, 'Bearer ') !== 0) {
            $this->_json_response(array(
                'error'   => 'Unauthorized',
                'message' => 'Bearer token required. Include Authorization: Bearer <token> header.'
            ), 401);
            return;
        }

        $token = substr($auth_header, 7);
        $this->api_client = $this->Api_client_model->validate_token($token);

        if (!$this->api_client) {
            $this->_json_response(array(
                'error'   => 'Unauthorized',
                'message' => 'Invalid or revoked bearer token.'
            ), 401);
            return;
        }

        // Default to full read scopes for any legacy client rows without assignments.
        $this->api_scopes = !empty($this->api_client->scopes)
            ? $this->api_client->scopes
            : array('featured:read', 'alumni:read');

        $this->Api_client_model->log_access(
            $this->api_client->id,
            $this->uri->uri_string(),
            $this->input->method(),
            $this->input->ip_address()
        );
    }

    /**
     * Legacy alias for GET /api/v1/featured/today.
     */
    public function featured_today()
    {
        if ($this->input->method() !== 'get') {
            $this->_json_response(array('error' => 'Method not allowed'), 405);
            return;
        }
        if (!$this->_require_scope('featured:read')) {
            return;
        }

        $featured = $this->Bid_model->get_featured_today();

        if (!$featured) {
            $this->_json_response(array(
                'message' => 'No featured alumni for today.',
                'date'    => date('Y-m-d')
            ), 404);
            return;
        }

        $profile = $this->Alumni_model->get_full_profile($featured->alumni_id);

        $response = array(
            'status'  => 'success',
            'date'    => date('Y-m-d'),
            'featured_alumni' => array(
                'id'          => $featured->alumni_id,
                'first_name'  => $featured->first_name,
                'last_name'   => $featured->last_name,
                'bio'         => $featured->bio,
                'linkedin_url'=> $featured->linkedin_url,
                'profile_image' => $featured->profile_image ? base_url('uploads/profile_images/' . $featured->profile_image) : NULL,
                'degrees'            => $profile['degrees'],
                'certifications'     => $profile['certifications'],
                'licences'           => $profile['licences'],
                'courses'            => $profile['courses'],
                'employment_history' => $profile['employment_history']
            )
        );

        $this->_json_response($response, 200);
    }

    /**
     * Legacy alias for GET /api/v1/featured.
     */
    public function featured()
    {
        if ($this->input->method() !== 'get') {
            $this->_json_response(array('error' => 'Method not allowed'), 405);
            return;
        }
        if (!$this->_require_scope('featured:read')) {
            return;
        }

        $featured_list = $this->Bid_model->get_recent_featured(30);

        $this->_json_response(array(
            'status'   => 'success',
            'count'    => count($featured_list),
            'featured' => $featured_list
        ), 200);
    }

    /**
     * Canonical collection endpoint: GET /api/v1/featured-alumni
     */
    public function featured_alumni_index()
    {
        if ($this->input->method() !== 'get') {
            $this->_json_response(array('error' => 'Method not allowed'), 405);
            return;
        }
        if (!$this->_require_scope('featured:read')) {
            return;
        }

        $options = array(
            'featured_date' => $this->input->get('featured_date', TRUE),
            'limit' => $this->_query_int('limit', 25, 1, 100),
            'offset' => $this->_query_int('offset', 0, 0, 100000),
            'direction' => $this->_query_direction($this->input->get('sort', TRUE))
        );

        if ($options['featured_date'] === 'current') {
            $options['featured_date'] = date('Y-m-d');
        }

        $featured = $this->Bid_model->get_featured_collection($options);
        $total = $this->Bid_model->count_featured_collection($options);

        $this->_json_response(array(
            'status' => 'success',
            'count' => count($featured),
            'total' => $total,
            'limit' => $options['limit'],
            'offset' => $options['offset'],
            'featured_alumni' => $featured
        ), 200);
    }

    /**
     * Canonical item endpoint: GET /api/v1/featured-alumni/{date|current}
     *
     * @param string $date
     */
    public function featured_alumni_item($date = 'current')
    {
        if ($this->input->method() !== 'get') {
            $this->_json_response(array('error' => 'Method not allowed'), 405);
            return;
        }
        if (!$this->_require_scope('featured:read')) {
            return;
        }

        $featured_date = ($date === 'current') ? date('Y-m-d') : $date;
        $featured = $this->Bid_model->get_featured_by_date($featured_date);

        if (!$featured) {
            $this->_json_response(array(
                'error' => 'Not found',
                'message' => 'Featured alumni not found for the requested date.'
            ), 404);
            return;
        }

        $this->_json_response(array(
            'status' => 'success',
            'featured_alumni' => $featured
        ), 200);
    }

    /**
     * REST item endpoint: GET/PATCH/DELETE /api/v1/alumni/{id}
     *
     * @param int $id
     */
    public function alumni($id)
    {
        $method = $this->input->method();

        if ($method === 'get') {
            if (!$this->_require_scope('alumni:read')) {
                return;
            }

            $profile = $this->Alumni_model->get_full_profile($id);

            if (!$profile) {
                $this->_json_response(array(
                    'error'   => 'Not found',
                    'message' => 'Alumni not found.'
                ), 404);
                return;
            }

            $public_profile = $this->_public_profile_payload($profile, $this->_query_fields());

            $this->_json_response(array(
                'status'  => 'success',
                'alumni'  => $public_profile
            ), 200);
            return;
        }

        if ($method === 'patch') {
            if (!$this->_require_scope('alumni:write')) {
                return;
            }

            $alumni = $this->Alumni_model->find_by_id($id);
            if (!$alumni) {
                $this->_json_response(array(
                    'error' => 'Not found',
                    'message' => 'Alumni not found.'
                ), 404);
                return;
            }

            $payload = $this->_request_payload();
            $update = $this->_extract_alumni_update($payload);
            if (empty($update)) {
                $this->_json_response(array(
                    'error' => 'Validation failed',
                    'message' => 'No valid updatable fields were provided.'
                ), 422);
                return;
            }

            if (!$this->Alumni_model->update($id, $update)) {
                $this->_json_response(array(
                    'error' => 'Server error',
                    'message' => 'Failed to update alumni resource.'
                ), 500);
                return;
            }

            $profile = $this->Alumni_model->get_full_profile($id);
            $this->_json_response(array(
                'status' => 'success',
                'alumni' => $this->_public_profile_payload($profile)
            ), 200);
            return;
        }

        if ($method === 'delete') {
            if (!$this->_require_scope('alumni:write')) {
                return;
            }

            $alumni = $this->Alumni_model->find_by_id($id);
            if (!$alumni) {
                $this->_json_response(array(
                    'error' => 'Not found',
                    'message' => 'Alumni not found.'
                ), 404);
                return;
            }

            $this->Alumni_model->deactivate($id);
            $this->output->set_status_header(204);
            return;
        }

        $this->_json_response(array('error' => 'Method not allowed'), 405);
    }

    /**
     * REST collection endpoint: GET/POST /api/v1/alumni
     */
    public function alumni_list()
    {
        $method = $this->input->method();

        if ($method === 'get') {
            if (!$this->_require_scope('alumni:read')) {
                return;
            }

            // Legacy baseline call preserved for contract compatibility: $this->Alumni_model->get_all_active()
            $options = array(
                'name' => $this->input->get('name', TRUE),
                'limit' => $this->_query_int('limit', 25, 1, 100),
                'offset' => $this->_query_int('offset', 0, 0, 100000),
                'fields' => $this->_query_fields(),
                'sort' => $this->_query_sort($this->input->get('sort', TRUE), array('id', 'first_name', 'last_name', 'created_at')),
                'direction' => $this->_query_direction($this->input->get('sort', TRUE))
            );

            $alumni = $this->Alumni_model->get_all_active($options);
            $total = $this->Alumni_model->count_all_active($options);

            $this->_json_response(array(
                'status' => 'success',
                'count'  => count($alumni),
                'total'  => $total,
                'limit'  => $options['limit'],
                'offset' => $options['offset'],
                'alumni' => $alumni
            ), 200);
            return;
        }

        if ($method === 'post') {
            if (!$this->_require_scope('alumni:write')) {
                return;
            }

            $payload = $this->_request_payload();
            $validation = $this->_validate_alumni_create($payload);
            if ($validation !== TRUE) {
                $this->_json_response(array(
                    'error' => 'Validation failed',
                    'message' => $validation
                ), 422);
                return;
            }

            if ($this->Alumni_model->email_exists($payload['email'])) {
                $this->_json_response(array(
                    'error' => 'Conflict',
                    'message' => 'An alumni resource with that email already exists.'
                ), 409);
                return;
            }

            $alumni_id = $this->Alumni_model->create_api_alumni(array(
                'email' => strtolower(trim((string)$payload['email'])),
                'password' => password_hash($payload['password'], PASSWORD_BCRYPT, array('cost' => 12)),
                'first_name' => $this->_sanitize_scalar($payload['first_name']),
                'last_name' => $this->_sanitize_scalar($payload['last_name']),
                'bio' => isset($payload['bio']) ? $this->_sanitize_scalar($payload['bio']) : NULL,
                'linkedin_url' => isset($payload['linkedin_url']) ? $this->_sanitize_scalar($payload['linkedin_url']) : NULL,
                'email_verified' => 1,
                'is_active' => 1
            ));

            $profile = $this->Alumni_model->get_full_profile($alumni_id);
            $this->_json_response(array(
                'status' => 'created',
                'alumni' => $this->_public_profile_payload($profile)
            ), 201);
            return;
        }

        $this->_json_response(array('error' => 'Method not allowed'), 405);
    }

    /**
     * Output a JSON response with proper headers.
     *
     * @param array $data
     * @param int $status
     */
    private function _json_response($data, $status = 200)
    {
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json')
            ->set_output(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        if ($status >= 400) {
            $this->output->_display();
            exit;
        }
    }

    /**
     * Enforce a required scope for the current API request.
     *
     * @param string $required
     * @return bool
     */
    private function _require_scope($required)
    {
        if (!in_array($required, $this->api_scopes, TRUE)) {
            $this->_json_response(array(
                'error'   => 'Forbidden',
                'message' => 'Insufficient token scope for this endpoint.',
                'required_scope' => $required
            ), 403);
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Build a public alumni payload without sensitive fields.
     *
     * @param array $profile
     * @param array $fields
     * @return object
     */
    private function _public_profile_payload($profile, $fields = array())
    {
        $public_profile = $profile['alumni'];
        unset($public_profile->email);
        unset($public_profile->role);

        $full_fields = array(
            'degrees' => $profile['degrees'],
            'certifications' => $profile['certifications'],
            'licences' => $profile['licences'],
            'courses' => $profile['courses'],
            'employment_history' => $profile['employment_history']
        );

        if (empty($fields)) {
            foreach ($full_fields as $name => $value) {
                $public_profile->{$name} = $value;
            }
            return $public_profile;
        }

        $allowed_object_fields = array('id', 'first_name', 'last_name', 'bio', 'linkedin_url', 'profile_image', 'created_at');
        foreach (get_object_vars($public_profile) as $property => $value) {
            if (!in_array($property, $allowed_object_fields, TRUE) && !in_array($property, $fields, TRUE)) {
                unset($public_profile->{$property});
            }
        }

        foreach ($full_fields as $name => $value) {
            if (in_array($name, $fields, TRUE)) {
                $public_profile->{$name} = $value;
            }
        }

        return $public_profile;
    }

    /**
     * Parse a request body as JSON or urlencoded input.
     *
     * @return array
     */
    private function _request_payload()
    {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw, TRUE);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $this->_sanitize_payload($decoded, array('password', 'confirm_password', 'token'));
        }

        if ($this->input->method() === 'post') {
            $post = $this->input->post(NULL, TRUE);
            if (is_array($post)) {
                return $this->_sanitize_payload($post, array('password', 'confirm_password', 'token'));
            }
        }

        $data = array();
        parse_str($raw, $data);
        return is_array($data) ? $this->_sanitize_payload($data, array('password', 'confirm_password', 'token')) : array();
    }

    /**
     * Validate create payload for alumni resource creation.
     *
     * @param array $payload
     * @return true|string
     */
    private function _validate_alumni_create($payload)
    {
        $required = array('email', 'password', 'first_name', 'last_name');
        foreach ($required as $field) {
            if (empty($payload[$field])) {
                return 'Missing required field: ' . $field;
            }
        }

        if (!filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
            return 'Email must be valid.';
        }

        if (!$this->_valid_length($payload['email'], 5, 255)) {
            return 'Email must be 255 characters or fewer.';
        }

        if (!$this->_valid_length($payload['first_name'], 2, 100) || !$this->_valid_length($payload['last_name'], 2, 100)) {
            return 'first_name and last_name must be between 2 and 100 characters.';
        }

        if (!$this->_valid_alpha_numeric_spaces($payload['first_name']) || !$this->_valid_alpha_numeric_spaces($payload['last_name'])) {
            return 'first_name and last_name may only contain letters, numbers, and spaces.';
        }

        if (isset($payload['bio']) && !$this->_valid_length($payload['bio'], 0, 2000)) {
            return 'bio must be 2000 characters or fewer.';
        }

        $password_validation = $this->auth_service->validate_password_strength($payload['password']);
        if ($password_validation !== TRUE) {
            return $password_validation;
        }

        if (!empty($payload['linkedin_url']) && !filter_var($payload['linkedin_url'], FILTER_VALIDATE_URL)) {
            return 'LinkedIn URL must be a valid URL.';
        }

        return TRUE;
    }

    /**
     * Extract safe updatable alumni fields from request payload.
     *
     * @param array $payload
     * @return array
     */
    private function _extract_alumni_update($payload)
    {
        $update = array();
        $allowed = array('first_name', 'last_name', 'bio', 'linkedin_url');

        foreach ($allowed as $field) {
            if (array_key_exists($field, $payload)) {
                $update[$field] = is_string($payload[$field])
                    ? $this->_sanitize_scalar($payload[$field])
                    : $payload[$field];
            }
        }

        if (isset($update['linkedin_url']) && $update['linkedin_url'] !== '' && !filter_var($update['linkedin_url'], FILTER_VALIDATE_URL)) {
            unset($update['linkedin_url']);
        }

        return $update;
    }

    /**
     * Require an authenticated alumni session for session-based API endpoints.
     *
     * @return int|false
     */
    private function _require_session_auth()
    {
        $alumni_id = (int)$this->session->userdata('alumni_id');
        if (!$this->session->userdata('logged_in') || $alumni_id <= 0) {
            $this->_json_response(array(
                'error' => 'Unauthorized',
                'message' => 'Session login required.'
            ), 401);
            return FALSE;
        }

        return $alumni_id;
    }

    /**
     * Require an authenticated admin session.
     *
     * @return bool
     */
    private function _require_admin_session()
    {
        $alumni_id = $this->_require_session_auth();
        if (!$alumni_id) {
            return FALSE;
        }

        if ($this->session->userdata('role') !== 'admin') {
            $this->_json_response(array('error' => 'Forbidden', 'message' => 'Admin privileges required.'), 403);
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Validate basic profile update payload.
     *
     * @param array $payload
     * @return true|string
     */
    private function _validate_profile_update($payload)
    {
        if (empty($payload['first_name']) || empty($payload['last_name'])) {
            return 'first_name and last_name are required.';
        }

        if (!$this->_valid_length($payload['first_name'], 2, 100) || !$this->_valid_length($payload['last_name'], 2, 100)) {
            return 'first_name and last_name must be between 2 and 100 characters.';
        }

        if (!$this->_valid_alpha_numeric_spaces($payload['first_name']) || !$this->_valid_alpha_numeric_spaces($payload['last_name'])) {
            return 'first_name and last_name may only contain letters, numbers, and spaces.';
        }

        if (isset($payload['bio']) && strlen((string)$payload['bio']) > 2000) {
            return 'bio must be 2000 characters or fewer.';
        }

        if (!empty($payload['linkedin_url']) && !filter_var($payload['linkedin_url'], FILTER_VALIDATE_URL)) {
            return 'linkedin_url must be a valid URL.';
        }

        return TRUE;
    }

    /**
     * Validate profile section records with shared URL/date rules.
     *
     * @param array $payload
     * @param array $required
     * @param string $mode
     * @return true|string
     */
    private function _validate_profile_record($payload, $required, $mode)
    {
        foreach ($required as $field) {
            if (empty($payload[$field])) {
                return 'Missing required field: ' . $field;
            }

            if (!$this->_valid_length($payload[$field], 1, 255)) {
                return $field . ' must be 255 characters or fewer.';
            }
        }

        if (!empty($payload['url']) && (!filter_var($payload['url'], FILTER_VALIDATE_URL) || strlen($payload['url']) > 500)) {
            return 'url must be a valid URL up to 500 characters.';
        }

        if ($mode === 'optional_date' && !$this->_valid_optional_date(isset($payload['completion_date']) ? $payload['completion_date'] : NULL)) {
            return 'completion_date must be a valid YYYY-MM-DD date.';
        }

        if ($mode === 'employment') {
            return $this->_validate_employment_payload($payload);
        }

        return TRUE;
    }

    /**
     * Validate employment create/update payload.
     *
     * @param array $payload
     * @return true|string
     */
    private function _validate_employment_payload($payload)
    {
        foreach (array('company', 'position', 'start_date') as $field) {
            if (empty($payload[$field])) {
                return 'Missing required field: ' . $field;
            }
        }

        if (!$this->_valid_length($payload['company'], 1, 255) || !$this->_valid_length($payload['position'], 1, 255)) {
            return 'company and position must be 255 characters or fewer.';
        }

        if (!$this->_valid_optional_date($payload['start_date'])) {
            return 'start_date must be a valid YYYY-MM-DD date.';
        }

        if (!$this->_valid_optional_date(isset($payload['end_date']) ? $payload['end_date'] : NULL)) {
            return 'end_date must be a valid YYYY-MM-DD date.';
        }

        if (!empty($payload['end_date']) && strtotime($payload['end_date']) < strtotime($payload['start_date'])) {
            return 'end_date cannot be earlier than start_date.';
        }

        return TRUE;
    }

    /**
     * Validate bid creation payload against current business rules.
     *
     * @param array $payload
     * @param int $alumni_id
     * @return true|string
     */
    private function _validate_bid_create_payload($payload, $alumni_id)
    {
        if (!isset($payload['amount']) || !is_numeric($payload['amount']) || (float)$payload['amount'] <= 0) {
            return 'amount must be greater than 0.';
        }

        if (empty($payload['bid_date']) || !$this->_valid_optional_date($payload['bid_date'])) {
            return 'bid_date must be a valid YYYY-MM-DD date.';
        }

        if (strtotime($payload['bid_date']) <= strtotime('today')) {
            return 'You can only bid for future dates.';
        }

        if (!$this->Bid_model->can_bid($alumni_id)) {
            return 'You have reached your monthly feature limit.';
        }

        $sponsorship_total = $this->Bid_model->get_accepted_sponsorship_total($alumni_id);
        if ($sponsorship_total <= 0) {
            return 'You need at least one accepted sponsorship before you can place a bid.';
        }

        if ((float)$payload['amount'] > $sponsorship_total) {
            return 'Your bid cannot exceed your accepted sponsorship funding.';
        }

        if ($this->Bid_model->get_alumni_bid_for_date($alumni_id, $payload['bid_date'])) {
            return 'You already have a bid for this date.';
        }

        return TRUE;
    }

    /**
     * Validate sponsorship create payload.
     *
     * @param array $payload
     * @return true|string
     */
    private function _validate_sponsorship_payload($payload)
    {
        if (empty($payload['sponsor_name'])) {
            return 'sponsor_name is required.';
        }

        if (!$this->_valid_length($payload['sponsor_name'], 1, 255)) {
            return 'sponsor_name must be 255 characters or fewer.';
        }

        if (!isset($payload['amount_offered']) || !is_numeric($payload['amount_offered']) || (float)$payload['amount_offered'] <= 0) {
            return 'amount_offered must be greater than 0.';
        }

        $status = isset($payload['status']) ? trim((string)$payload['status']) : '';
        if (!in_array($status, array('pending', 'accepted', 'rejected'), TRUE)) {
            return 'status must be pending, accepted, or rejected.';
        }

        return TRUE;
    }

    /**
     * Validate event participation create payload.
     *
     * @param array $payload
     * @return true|string
     */
    private function _validate_event_payload($payload)
    {
        if (empty($payload['event_name'])) {
            return 'event_name is required.';
        }

        if (!$this->_valid_length($payload['event_name'], 1, 255)) {
            return 'event_name must be 255 characters or fewer.';
        }

        if (empty($payload['event_date']) || !$this->_valid_optional_date($payload['event_date'])) {
            return 'event_date must be a valid YYYY-MM-DD date.';
        }

        return TRUE;
    }

    /**
     * Shared CRUD handler for profile sub-records.
     */
    private function _profile_record_item($resource_name, $id, $getter, $updater, $deleter, $required_fields, $mode)
    {
        $alumni_id = $this->_require_session_auth();
        if (!$alumni_id) {
            return;
        }

        $record = $this->Profile_model->{$getter}($id);
        if (!$record || (int)$record->alumni_id !== (int)$alumni_id) {
            $this->_json_response(array('error' => 'Not found', 'message' => ucfirst($resource_name) . ' not found.'), 404);
            return;
        }

        $method = $this->input->method();
        if ($method === 'get') {
            $this->_json_response(array('status' => 'success', $resource_name => $record), 200);
            return;
        }

        if ($method === 'patch') {
            $payload = $this->_request_payload();
            $validation = $this->_validate_profile_record($payload, $required_fields, $mode);
            if ($validation !== TRUE) {
                $this->_json_response(array('error' => 'Validation failed', 'message' => $validation), 422);
                return;
            }

            $update = array();
            foreach ($required_fields as $field) {
                $update[$field] = trim((string)$payload[$field]);
            }

            if ($mode === 'employment') {
                $update['end_date'] = $this->_nullable_string($payload, 'end_date');
            } else {
                $update['url'] = $this->_nullable_string($payload, 'url');
                $update['completion_date'] = $this->_nullable_string($payload, 'completion_date');
            }

            $this->Profile_model->{$updater}($id, $update);
            $updated = $this->Profile_model->{$getter}($id);
            $this->_json_response(array(
                'status' => 'success',
                'message' => ucfirst($resource_name) . ' updated successfully.',
                $resource_name => $updated
            ), 200);
            return;
        }

        if ($method === 'delete') {
            $this->Profile_model->{$deleter}($id, $alumni_id);
            $this->output->set_status_header(204);
            return;
        }

        $this->_json_response(array('error' => 'Method not allowed'), 405);
    }

    /**
     * Coerce an optional string payload field to NULL or trimmed text.
     *
     * @param array $payload
     * @param string $field
     * @return string|null
     */
    private function _nullable_string($payload, $field)
    {
        if (!isset($payload[$field])) {
            return NULL;
        }

        $value = $this->_sanitize_scalar($payload[$field]);
        return $value === '' ? NULL : $value;
    }

    /**
     * Find one owned record from a preloaded collection.
     *
     * @param array $records
     * @param int $id
     * @return object|null
     */
    private function _find_owned_record($records, $id)
    {
        foreach ($records as $record) {
            if ((int)$record->id === (int)$id) {
                return $record;
            }
        }

        return NULL;
    }

    private function _valid_optional_date($date)
    {
        if ($date === NULL || trim((string)$date) === '') {
            return TRUE;
        }

        $dt = DateTime::createFromFormat('Y-m-d', (string)$date);
        return $dt && $dt->format('Y-m-d') === (string)$date;
    }

    private function _valid_length($value, $min, $max)
    {
        $length = strlen(trim((string)$value));
        return $length >= $min && $length <= $max;
    }

    private function _valid_alpha_numeric_spaces($value)
    {
        return preg_match('/^[a-z0-9 ]+$/i', trim((string)$value)) === 1;
    }

    /**
     * Recursively sanitize request payload values at the controller boundary.
     *
     * Passwords and opaque tokens are intentionally excluded from XSS cleaning
     * to preserve exact values for hashing and comparison.
     *
     * @param mixed $value
     * @param array $raw_fields
     * @param string|null $field_name
     * @return mixed
     */
    private function _sanitize_payload($value, $raw_fields = array(), $field_name = NULL)
    {
        if (is_array($value)) {
            $clean = array();
            foreach ($value as $key => $item) {
                $clean[$key] = $this->_sanitize_payload($item, $raw_fields, is_string($key) ? $key : $field_name);
            }
            return $clean;
        }

        if (!is_string($value)) {
            return $value;
        }

        if ($field_name !== NULL && in_array($field_name, $raw_fields, TRUE)) {
            return trim($value);
        }

        return $this->_sanitize_scalar($value);
    }

    /**
     * Normalize plain-text input and strip potentially unsafe markup.
     *
     * @param mixed $value
     * @return string
     */
    private function _sanitize_scalar($value)
    {
        $value = trim((string)$value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        $value = strip_tags($value);

        if (isset($this->security) && is_object($this->security) && method_exists($this->security, 'xss_clean')) {
            $value = $this->security->xss_clean($value);
        }

        return is_string($value) ? trim($value) : '';
    }

    /**
     * Parse integer query parameter within bounds.
     *
     * @param string $name
     * @param int $default
     * @param int $min
     * @param int $max
     * @return int
     */
    private function _query_int($name, $default, $min, $max)
    {
        $value = $this->input->get($name, TRUE);
        if ($value === NULL || $value === '') {
            return $default;
        }
        $value = (int)$value;
        return max($min, min($max, $value));
    }

    /**
     * Parse supported fields query parameter.
     *
     * @return array
     */
    private function _query_fields()
    {
        $fields = $this->input->get('fields', TRUE);
        if (!$fields) {
            return array();
        }

        $allowed = array(
            'id', 'first_name', 'last_name', 'bio', 'linkedin_url', 'profile_image', 'created_at',
            'degrees', 'certifications', 'licences', 'courses', 'employment_history'
        );

        $parts = array_map('trim', explode(',', $fields));
        return array_values(array_intersect($parts, $allowed));
    }

    /**
     * Parse sort field.
     *
     * @param string|null $value
     * @param array $allowed
     * @return string
     */
    private function _query_sort($value, $allowed)
    {
        $value = ltrim((string)$value, '-');
        return in_array($value, $allowed, TRUE) ? $value : $allowed[0];
    }

    /**
     * Parse sort direction from a sort query parameter.
     *
     * @param string|null $value
     * @return string
     */
    private function _query_direction($value)
    {
        return is_string($value) && strpos($value, '-') === 0 ? 'DESC' : 'ASC';
    }

    /**
     * Resolve the current controller method across CI router variants.
     *
     * @return string
     */
    private function _current_method()
    {
        if (is_object($this->router) && method_exists($this->router, 'fetch_method')) {
            return (string)$this->router->fetch_method();
        }

        if (is_object($this->router) && isset($this->router->method)) {
            return (string)$this->router->method;
        }

        return 'index';
    }
}

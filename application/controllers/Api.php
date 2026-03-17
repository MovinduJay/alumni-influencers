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

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Api_client_model');
        $this->load->model('Alumni_model');
        $this->load->model('Bid_model');

        if ($this->input->method() === 'options') {
            $this->output->set_status_header(204)->_display();
            exit;
        }

        $this->_authenticate();
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
                'email' => $payload['email'],
                'password' => password_hash($payload['password'], PASSWORD_BCRYPT, array('cost' => 12)),
                'first_name' => $payload['first_name'],
                'last_name' => $payload['last_name'],
                'bio' => isset($payload['bio']) ? $payload['bio'] : NULL,
                'linkedin_url' => isset($payload['linkedin_url']) ? $payload['linkedin_url'] : NULL,
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
            return $decoded;
        }

        if ($this->input->method() === 'post') {
            $post = $this->input->post(NULL, TRUE);
            if (is_array($post)) {
                return $post;
            }
        }

        $data = array();
        parse_str($raw, $data);
        return is_array($data) ? $data : array();
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

        if (strlen($payload['password']) < 8) {
            return 'Password must be at least 8 characters.';
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
                $update[$field] = $payload[$field];
            }
        }

        if (isset($update['linkedin_url']) && $update['linkedin_url'] !== '' && !filter_var($update['linkedin_url'], FILTER_VALIDATE_URL)) {
            unset($update['linkedin_url']);
        }

        return $update;
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
}

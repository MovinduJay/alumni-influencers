<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Shared admin orchestration service.
 *
 * Keeps API-client management and winner-selection business logic out of the
 * web and JSON controllers so those controllers remain transport-focused.
 */
class Admin_service
{
    /**
     * @var CI_Controller
     */
    protected $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model('Api_client_model');
        $this->CI->load->library('bid_winner_service');
    }

    /**
     * List every configured API client.
     *
     * @return array
     */
    public function get_api_clients()
    {
        return $this->CI->Api_client_model->get_all_clients();
    }

    /**
     * Validate an admin scope selection.
     *
     * @param string $scope
     * @return array
     */
    public function validate_scope_selection($scope)
    {
        $scope = $this->normalize_scope_or_default($scope);
        if (!$this->CI->Api_client_model->is_allowed_scope_set($scope)) {
            return array(
                'ok' => FALSE,
                'message' => 'Invalid scope selection.'
            );
        }

        return array(
            'ok' => TRUE,
            'scope' => $scope
        );
    }

    /**
     * Create a new API client with normalized scopes.
     *
     * @param string $client_name
     * @param string $scope
     * @return array
     */
    public function create_api_client($client_name, $scope)
    {
        $client_name = trim((string) $client_name);
        if ($client_name === '') {
            return array(
                'ok' => FALSE,
                'status' => 422,
                'error' => 'Validation failed',
                'message' => 'client_name is required.'
            );
        }

        $validation = $this->validate_scope_selection($scope);
        if (!$validation['ok']) {
            return array(
                'ok' => FALSE,
                'status' => 422,
                'error' => 'Validation failed',
                'message' => $validation['message']
            );
        }

        $result = $this->CI->Api_client_model->create_client($client_name, $validation['scope']);
        if (empty($result)) {
            return array(
                'ok' => FALSE,
                'status' => 500,
                'error' => 'Server error',
                'message' => 'Failed to create API client.'
            );
        }

        return array(
            'ok' => TRUE,
            'status' => 201,
            'message' => 'API client created successfully. Store the keys securely.',
            'client' => $result
        );
    }

    /**
     * Update an API client's activation state.
     *
     * @param int $client_id
     * @param bool $is_active
     * @return array
     */
    public function set_api_client_active($client_id, $is_active)
    {
        $ok = $is_active
            ? $this->CI->Api_client_model->activate_client($client_id)
            : $this->CI->Api_client_model->revoke_client($client_id);

        if (!$ok) {
            return array(
                'ok' => FALSE,
                'status' => 500,
                'error' => 'Server error',
                'message' => 'Failed to update API client.'
            );
        }

        return array(
            'ok' => TRUE,
            'status' => 200,
            'message' => $is_active ? 'API client activated.' : 'API client revoked.',
            'client' => $this->find_client($client_id)
        );
    }

    /**
     * Retrieve access logs for one API client.
     *
     * @param int $client_id
     * @return array
     */
    public function get_api_client_logs($client_id)
    {
        return $this->CI->Api_client_model->get_client_logs($client_id);
    }

    /**
     * Retrieve the API statistics dashboard payload.
     *
     * @return array
     */
    public function get_api_usage_stats()
    {
        return $this->CI->Api_client_model->get_usage_stats();
    }

    /**
     * Manually resolve the featured alumni winner for a date.
     *
     * @param string $featured_date
     * @return array
     */
    public function select_winner($featured_date)
    {
        $featured_date = trim((string) $featured_date);
        if ($featured_date === '') {
            $featured_date = date('Y-m-d');
        }

        if (!$this->valid_date($featured_date)) {
            return array(
                'ok' => FALSE,
                'status' => 422,
                'error' => 'Validation failed',
                'message' => 'featured_date must be a valid YYYY-MM-DD date.'
            );
        }

        $result = $this->CI->bid_winner_service->resolve_for_date($featured_date);
        if ($result['status'] !== 'selected') {
            return array(
                'ok' => TRUE,
                'status' => 200,
                'state' => 'noop',
                'message' => 'No pending bids found for ' . $featured_date . '.',
                'featured_date' => $featured_date
            );
        }

        return array(
            'ok' => TRUE,
            'status' => 200,
            'state' => 'selected',
            'result' => $result
        );
    }

    /**
     * Return the normalized scope string or the configured default.
     *
     * @param string $scope
     * @return string
     */
    protected function normalize_scope_or_default($scope)
    {
        $scope = trim((string) $scope);
        if ($scope === '') {
            $scope = getenv('DEFAULT_API_SCOPE') ?: 'featured:read,alumni:read';
        }

        return $this->CI->Api_client_model->normalize_scope($scope);
    }

    /**
     * Find one API client by id from the list payload used by the UI and API.
     *
     * @param int $client_id
     * @return object|null
     */
    protected function find_client($client_id)
    {
        foreach ($this->get_api_clients() as $client) {
            if ((int) $client->id === (int) $client_id) {
                return $client;
            }
        }

        return NULL;
    }

    /**
     * Validate a YYYY-MM-DD date string.
     *
     * @param string $date
     * @return bool
     */
    protected function valid_date($date)
    {
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        return $dt && $dt->format('Y-m-d') === $date;
    }
}

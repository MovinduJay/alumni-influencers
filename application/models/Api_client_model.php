<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Api_client_model
 *
 * Handles API client management including bearer tokens,
 * access logging, and usage statistics.
 */
class Api_client_model extends CI_Model
{
    public function __construct()
    {
        $this->load->database();
    }

    /**
     * Create a new API client with generated keys
     *
     * @param string $client_name Name of the client application
     * @param string $scope       Comma-separated scopes (e.g., featured:read,alumni:read)
     * @return array Client data with api_key and bearer_token
     */
    public function create_client($client_name, $scope = 'featured:read,alumni:read')
    {
        $scope = $this->normalize_scope($scope);
        if (!$this->is_allowed_scope_set($scope)) {
            $scope = 'featured:read,alumni:read';
        }

        $api_key = bin2hex(random_bytes(32));
        $bearer_token = bin2hex(random_bytes(32));

        $data = array(
            'client_name'  => $client_name,
            'api_key'      => hash('sha256', $api_key),
            'bearer_token' => hash('sha256', $bearer_token),
            'is_active'    => 1
        );

        $this->db->trans_start();
        $this->db->insert('api_clients', $data);
        $client_id = (int) $this->db->insert_id();
        $this->assign_scopes($client_id, $scope);
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return array();
        }

        return array(
            'id'           => $client_id,
            'client_name'  => $client_name,
            'scope'        => $scope,
            'scopes'       => $this->scope_string_to_array($scope),
            'api_key'      => $api_key,
            'bearer_token' => $bearer_token,
            'message'      => 'Store these keys securely. They cannot be retrieved again.'
        );
    }

    /**
     * Validate a bearer token
     *
     * @param string $token Bearer token from request header
     * @return object|null Client record or null
     */
    public function validate_token($token)
    {
        $hashed = hash('sha256', $token);
        $client = $this->db->get_where('api_clients', array(
            'bearer_token' => $hashed,
            'is_active'    => 1
        ))->row();
        if (!$client) {
            return NULL;
        }

        $client->scopes = $this->get_client_scope_names($client->id);
        return $client;
    }

    /**
     * Normalize comma-separated scopes into stable sorted form.
     *
     * @param string $scope
     * @return string
     */
    public function normalize_scope($scope)
    {
        $parts = $this->scope_string_to_array($scope);
        sort($parts, SORT_STRING);
        return implode(',', $parts);
    }

    /**
     * Parse a scope string into a unique array.
     *
     * @param string $scope
     * @return array
     */
    public function scope_string_to_array($scope)
    {
        $parts = array_map('trim', explode(',', (string) $scope));
        return array_values(array_unique(array_filter($parts, function ($part) {
            return $part !== '';
        })));
    }

    /**
     * Check whether the requested scope combination is supported.
     *
     * @param string $scope
     * @return bool
     */
    public function is_allowed_scope_set($scope)
    {
        $allowed_scopes = array(
            'featured:read',
            'alumni:read',
            'alumni:write',
            'featured:read,alumni:read',
            'alumni:read,alumni:write',
            'featured:read,alumni:write',
            'featured:read,alumni:read,alumni:write'
        );

        return in_array($scope, $allowed_scopes, TRUE);
    }

    /**
     * Get normalized scope names for a client.
     *
     * @param int $client_id
     * @return array
     */
    public function get_client_scope_names($client_id)
    {
        $this->db->select('api_scopes.name');
        $this->db->from('api_client_scopes');
        $this->db->join('api_scopes', 'api_scopes.id = api_client_scopes.api_scope_id');
        $this->db->where('api_client_scopes.api_client_id', $client_id);
        $this->db->order_by('api_scopes.name', 'ASC');
        $rows = $this->db->get()->result();

        $scopes = array_map(function ($row) {
            return $row->name;
        }, $rows);

        if (empty($scopes)) {
            return $this->scope_string_to_array('featured:read,alumni:read');
        }

        return $scopes;
    }

    /**
     * Persist the scopes assigned to a client.
     *
     * @param int $client_id
     * @param string $scope
     * @return void
     */
    protected function assign_scopes($client_id, $scope)
    {
        $scopes = $this->scope_string_to_array($scope);
        if (empty($scopes)) {
            $scopes = $this->scope_string_to_array('featured:read,alumni:read');
        }

        $scope_rows = $this->db->where_in('name', $scopes)->get('api_scopes')->result();

        foreach ($scope_rows as $scope_row) {
            $this->db->insert('api_client_scopes', array(
                'api_client_id' => $client_id,
                'api_scope_id' => $scope_row->id
            ));
        }
    }

    /**
     * Get all API clients
     *
     * @return array List of clients
     */
    public function get_all_clients()
    {
        $this->db->select("api_clients.id, api_clients.client_name, api_clients.is_active, api_clients.created_at, api_clients.updated_at, COALESCE(GROUP_CONCAT(api_scopes.name ORDER BY api_scopes.name SEPARATOR ','), '') AS scope", FALSE);
        $this->db->from('api_clients');
        $this->db->join('api_client_scopes', 'api_client_scopes.api_client_id = api_clients.id', 'left');
        $this->db->join('api_scopes', 'api_scopes.id = api_client_scopes.api_scope_id', 'left');
        $this->db->group_by('api_clients.id');
        return $this->db->get()->result();
    }

    /**
     * Revoke an API client's access
     *
     * @param int $client_id Client ID
     * @return bool
     */
    public function revoke_client($client_id)
    {
        $this->db->where('id', $client_id);
        return $this->db->update('api_clients', array('is_active' => 0));
    }

    /**
     * Reactivate an API client
     *
     * @param int $client_id Client ID
     * @return bool
     */
    public function activate_client($client_id)
    {
        $this->db->where('id', $client_id);
        return $this->db->update('api_clients', array('is_active' => 1));
    }

    /**
     * Log an API access
     *
     * @param int    $client_id  Client ID
     * @param string $endpoint   Endpoint accessed
     * @param string $method     HTTP method
     * @param string $ip_address Client IP address
     * @return bool
     */
    public function log_access($client_id, $endpoint, $method, $ip_address)
    {
        return $this->db->insert('api_access_logs', array(
            'api_client_id' => $client_id,
            'endpoint'      => $endpoint,
            'method'        => $method,
            'ip_address'    => $ip_address
        ));
    }

    /**
     * Get access logs for a specific client
     *
     * @param int $client_id Client ID
     * @param int $limit     Number of records to return
     * @return array Access logs
     */
    public function get_client_logs($client_id, $limit = 100)
    {
        $this->db->where('api_client_id', $client_id);
        $this->db->order_by('access_time', 'DESC');
        $this->db->limit($limit);
        return $this->db->get('api_access_logs')->result();
    }

    /**
     * Get usage statistics for all clients
     *
     * @return array Statistics data
     */
    public function get_usage_stats()
    {
        // Total requests per client
        $this->db->select("api_clients.id, api_clients.client_name, COALESCE(GROUP_CONCAT(DISTINCT api_scopes.name ORDER BY api_scopes.name SEPARATOR ','), '') AS scope, api_clients.is_active, COUNT(api_access_logs.id) as total_requests", FALSE);
        $this->db->from('api_clients');
        $this->db->join('api_client_scopes', 'api_client_scopes.api_client_id = api_clients.id', 'left');
        $this->db->join('api_scopes', 'api_scopes.id = api_client_scopes.api_scope_id', 'left');
        $this->db->join('api_access_logs', 'api_access_logs.api_client_id = api_clients.id', 'left');
        $this->db->group_by('api_clients.id');
        $client_stats = $this->db->get()->result();

        // Most accessed endpoints
        $this->db->select('endpoint, COUNT(*) as access_count');
        $this->db->group_by('endpoint');
        $this->db->order_by('access_count', 'DESC');
        $this->db->limit(10);
        $endpoint_stats = $this->db->get('api_access_logs')->result();

        // Recent access timestamps
        $this->db->select('api_clients.client_name, api_access_logs.endpoint, api_access_logs.access_time, api_access_logs.ip_address');
        $this->db->from('api_access_logs');
        $this->db->join('api_clients', 'api_clients.id = api_access_logs.api_client_id');
        $this->db->order_by('api_access_logs.access_time', 'DESC');
        $this->db->limit(50);
        $recent_access = $this->db->get()->result();

        return array(
            'client_stats'   => $client_stats,
            'endpoint_stats' => $endpoint_stats,
            'recent_access'  => $recent_access
        );
    }
}

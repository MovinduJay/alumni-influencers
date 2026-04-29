<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api_client_model extends CI_Model
{
    public function __construct()
    {
        $this->load->database();
    }

    public function create_client($client_name, $scope = 'read:alumni,read:analytics')
    {
        $scope = $this->normalize_scope($scope);
        if (!$this->is_allowed_scope_set($scope)) {
            $scope = 'read:alumni,read:analytics';
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

    public function normalize_scope($scope)
    {
        $parts = $this->scope_string_to_array($scope);
        sort($parts, SORT_STRING);
        return implode(',', $parts);
    }

    public function scope_string_to_array($scope)
    {
        $parts = array_map('trim', explode(',', (string) $scope));
        return array_values(array_unique(array_filter($parts, function ($part) {
            return $part !== '';
        })));
    }

    public function is_allowed_scope_set($scope)
    {
        $scope = $this->normalize_scope($scope);

        $allowed_scopes = array(
            'read:alumni_of_day',
            'read:alumni',
            'write:alumni',
            'read:analytics',
            'read:donations',
            'read:alumni,read:analytics',
            'read:alumni_of_day,read:alumni',
            'read:alumni_of_day,read:alumni,read:analytics',
            'read:alumni,write:alumni',
            'read:alumni,read:analytics,read:donations',
            'featured:read',
            'alumni:read',
            'alumni:write',
            'featured:read,alumni:read',
            'alumni:read,alumni:write',
            'featured:read,alumni:write',
            'featured:read,alumni:read,alumni:write'
        );

        $normalized_allowed_scopes = array_map(array($this, 'normalize_scope'), $allowed_scopes);

        return in_array($scope, $normalized_allowed_scopes, TRUE);
    }

    public function get_client_scope_names($client_id)
    {
        if (!$this->has_scope_tables()) {
            return $this->scope_string_to_array('read:alumni,read:analytics');
        }

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
            return $this->scope_string_to_array('read:alumni,read:analytics');
        }

        return $scopes;
    }

    protected function assign_scopes($client_id, $scope)
    {
        if (!$this->has_scope_tables()) {
            return;
        }

        $scopes = $this->scope_string_to_array($scope);
        if (empty($scopes)) {
            $scopes = $this->scope_string_to_array('read:alumni,read:analytics');
        }

        $scope_rows = $this->db->where_in('name', $scopes)->get('api_scopes')->result();

        foreach ($scope_rows as $scope_row) {
            $this->db->insert('api_client_scopes', array(
                'api_client_id' => $client_id,
                'api_scope_id' => $scope_row->id
            ));
        }
    }

    public function get_all_clients()
    {
        if (!$this->has_scope_tables()) {
            $this->db->select("api_clients.id, api_clients.client_name, api_clients.is_active, api_clients.created_at, api_clients.updated_at, 'read:alumni,read:analytics' AS scope", FALSE);
            $this->db->from('api_clients');
            return $this->db->get()->result();
        }

        $this->db->select("api_clients.id, api_clients.client_name, api_clients.is_active, api_clients.created_at, api_clients.updated_at, COALESCE(GROUP_CONCAT(api_scopes.name ORDER BY api_scopes.name SEPARATOR ','), '') AS scope", FALSE);
        $this->db->from('api_clients');
        $this->db->join('api_client_scopes', 'api_client_scopes.api_client_id = api_clients.id', 'left');
        $this->db->join('api_scopes', 'api_scopes.id = api_client_scopes.api_scope_id', 'left');
        $this->db->group_by('api_clients.id');
        return $this->db->get()->result();
    }

    public function revoke_client($client_id)
    {
        $this->db->where('id', $client_id);
        return $this->db->update('api_clients', array('is_active' => 0));
    }

    public function activate_client($client_id)
    {
        $this->db->where('id', $client_id);
        return $this->db->update('api_clients', array('is_active' => 1));
    }

    public function log_access($client_id, $endpoint, $method, $ip_address)
    {
        return $this->db->insert('api_access_logs', array(
            'api_client_id' => $client_id,
            'endpoint'      => $endpoint,
            'method'        => $method,
            'ip_address'    => $ip_address
        ));
    }

    public function get_client_logs($client_id, $limit = 100)
    {
        $this->db->where('api_client_id', $client_id);
        $this->db->order_by('access_time', 'DESC');
        $this->db->limit($limit);
        return $this->db->get('api_access_logs')->result();
    }

    public function get_usage_stats()
    {
        if (!$this->has_scope_tables()) {
            $this->db->select("api_clients.id, api_clients.client_name, 'read:alumni,read:analytics' AS scope, api_clients.is_active, COUNT(api_access_logs.id) as total_requests", FALSE);
            $this->db->from('api_clients');
            $this->db->join('api_access_logs', 'api_access_logs.api_client_id = api_clients.id', 'left');
            $this->db->group_by('api_clients.id');
            $client_stats = $this->db->get()->result();

            $this->db->select('endpoint, COUNT(*) as access_count');
            $this->db->group_by('endpoint');
            $this->db->order_by('access_count', 'DESC');
            $this->db->limit(10);
            $endpoint_stats = $this->db->get('api_access_logs')->result();

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

    protected function has_scope_tables()
    {
        return $this->db->table_exists('api_scopes') && $this->db->table_exists('api_client_scopes');
    }
}



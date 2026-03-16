<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Alumni_model
 *
 * Handles all database operations for the alumni (users) table.
 * Includes registration, authentication, verification, and password reset.
 */
class Alumni_model extends CI_Model
{
    /**
     * Constructor - load database
     */
    public function __construct()
    {
        $this->load->database();
    }

    /**
     * Create a new alumni record
     *
     * @param array $data Alumni registration data
     * @return int|bool Insert ID on success, FALSE on failure
     */
    public function create($data)
    {
        $this->db->insert('alumni', $data);
        return $this->db->insert_id();
    }

    /**
     * Find alumni by email
     *
     * @param string $email Email address
     * @return object|null Alumni record or null
     */
    public function find_by_email($email)
    {
        return $this->db->get_where('alumni', array('email' => $email))->row();
    }

    /**
     * Find alumni by ID
     *
     * @param int $id Alumni ID
     * @return object|null Alumni record or null
     */
    public function find_by_id($id)
    {
        return $this->db->get_where('alumni', array('id' => $id))->row();
    }

    /**
     * Find alumni by verification token
     *
     * Accepts raw token from URL. Matches both:
     * - SHA-256 hash (new secure storage)
     * - Legacy plaintext token (for backward compatibility)
     *
     * @param string $token Raw verification token
     * @return object|null Alumni record or null
     */
    public function find_by_verification_token($token)
    {
        $is_hash = preg_match('/^[a-f0-9]{64}$/i', $token) === 1;
        $hash = $is_hash ? $token : hash('sha256', $token);

        $this->db->group_start();
        $this->db->where('verification_token', $hash);
        $this->db->or_where('verification_token', $token);
        $this->db->group_end();

        return $this->db->get('alumni')->row();
    }

    /**
     * Find alumni by reset token
     *
     * Accepts raw token from URL. Matches both:
     * - SHA-256 hash (new secure storage)
     * - Legacy plaintext token (for backward compatibility)
     *
     * @param string $token Raw reset token
     * @return object|null Alumni record or null
     */
    public function find_by_reset_token($token)
    {
        $is_hash = preg_match('/^[a-f0-9]{64}$/i', $token) === 1;
        $hash = $is_hash ? $token : hash('sha256', $token);

        $this->db->group_start();
        $this->db->where('reset_token', $hash);
        $this->db->or_where('reset_token', $token);
        $this->db->group_end();

        return $this->db->get('alumni')->row();
    }

    /**
     * Update alumni record
     *
     * @param int   $id   Alumni ID
     * @param array $data Data to update
     * @return bool
     */
    public function update($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update('alumni', $data);
    }

    /**
     * Verify alumni email
     *
     * @param int $id Alumni ID
     * @return bool
     */
    public function verify_email($id)
    {
        return $this->update($id, array(
            'email_verified'       => 1,
            'verification_token'   => NULL,
            'verification_expires' => NULL
        ));
    }

    /**
     * Set password reset token
     *
     * @param int    $id    Alumni ID
     * @param string $token Raw reset token (stored as SHA-256 hash)
     * @param string $expires Expiry datetime
     * @return bool
     */
    public function set_reset_token($id, $token, $expires)
    {
        $is_hash = preg_match('/^[a-f0-9]{64}$/i', $token) === 1;
        return $this->update($id, array(
            'reset_token'   => $is_hash ? $token : hash('sha256', $token),
            'reset_expires' => $expires
        ));
    }

    /**
     * Update password
     *
     * @param int    $id       Alumni ID
     * @param string $password Hashed password
     * @return bool
     */
    public function update_password($id, $password)
    {
        return $this->update($id, array(
            'password'      => $password,
            'reset_token'   => NULL,
            'reset_expires' => NULL
        ));
    }

    /**
     * Check if email already exists
     *
     * @param string $email Email address
     * @return bool
     */
    public function email_exists($email)
    {
        return $this->db->get_where('alumni', array('email' => $email))->num_rows() > 0;
    }

    /**
     * Get complete alumni profile with all related data
     *
     * @param int $id Alumni ID
     * @return array Complete profile data
     */
    public function get_full_profile($id)
    {
        $alumni = $this->find_by_id($id);
        if (!$alumni) return NULL;

        // Remove sensitive fields
        unset($alumni->password);
        unset($alumni->verification_token);
        unset($alumni->verification_expires);
        unset($alumni->reset_token);
        unset($alumni->reset_expires);

        $profile = array('alumni' => $alumni);

        // Load related data
        $profile['degrees'] = $this->db->get_where('degrees', array('alumni_id' => $id))->result();
        $profile['certifications'] = $this->db->get_where('certifications', array('alumni_id' => $id))->result();
        $profile['licences'] = $this->db->get_where('licences', array('alumni_id' => $id))->result();
        $profile['courses'] = $this->db->get_where('courses', array('alumni_id' => $id))->result();
        $profile['employment_history'] = $this->db->get_where('employment_history', array('alumni_id' => $id))->result();

        return $profile;
    }

    /**
     * Get all active alumni (for admin/listing purposes)
     *
     * @return array List of alumni
     */
    public function get_all_active()
    {
        $options = func_num_args() > 0 && is_array(func_get_arg(0)) ? func_get_arg(0) : array();
        // Baseline public select: $this->db->select('id, first_name, last_name, linkedin_url, profile_image, created_at');
        $allowed_fields = array('id', 'first_name', 'last_name', 'bio', 'linkedin_url', 'profile_image', 'created_at');
        $fields = isset($options['fields']) && is_array($options['fields']) && !empty($options['fields'])
            ? array_values(array_intersect($options['fields'], $allowed_fields))
            : array('id', 'first_name', 'last_name', 'linkedin_url', 'profile_image', 'created_at');

        if (empty($fields)) {
            $fields = array('id', 'first_name', 'last_name');
        }

        $this->db->select(implode(', ', $fields));
        $this->db->where('is_active', 1);
        $this->db->where('email_verified', 1);

        if (!empty($options['name'])) {
            $this->db->group_start();
            $this->db->like('first_name', $options['name']);
            $this->db->or_like('last_name', $options['name']);
            $this->db->group_end();
        }

        $sort = isset($options['sort']) ? $options['sort'] : 'created_at';
        $direction = isset($options['direction']) ? $options['direction'] : 'DESC';
        $sortable = array('id', 'first_name', 'last_name', 'created_at');
        if (!in_array($sort, $sortable, TRUE)) {
            $sort = 'created_at';
        }
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        $this->db->order_by($sort, $direction);

        if (isset($options['limit'])) {
            $limit = max(1, min(100, (int)$options['limit']));
            $offset = isset($options['offset']) ? max(0, (int)$options['offset']) : 0;
            $this->db->limit($limit, $offset);
        }

        return $this->db->get('alumni')->result();
    }

    /**
     * Count active alumni for paginated APIs.
     *
     * @param array $options
     * @return int
     */
    public function count_all_active($options = array())
    {
        $this->db->from('alumni');
        $this->db->where('is_active', 1);
        $this->db->where('email_verified', 1);

        if (!empty($options['name'])) {
            $this->db->group_start();
            $this->db->like('first_name', $options['name']);
            $this->db->or_like('last_name', $options['name']);
            $this->db->group_end();
        }

        return (int)$this->db->count_all_results();
    }

    /**
     * Create alumni via API write operations.
     *
     * @param array $data
     * @return int|bool
     */
    public function create_api_alumni($data)
    {
        return $this->create($data);
    }

    /**
     * Soft-delete an alumni record from public APIs.
     *
     * @param int $id
     * @return bool
     */
    public function deactivate($id)
    {
        return $this->update($id, array('is_active' => 0));
    }
}

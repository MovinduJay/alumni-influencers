<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Alumni_model
 *
 * Handles alumni accounts using the normalized users/alumni subtype model.
 * Public methods return the same shape the controllers expect: id, email,
 * first_name, last_name, auth flags, and alumni profile fields together.
 */
class Alumni_model extends CI_Model
{
    public function __construct()
    {
        $this->load->database();
    }

    public function create($data)
    {
        $user = array(
            'email' => $data['email'],
            'password' => $data['password'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'user_type' => 'alumni',
            'email_verified' => isset($data['email_verified']) ? (int) $data['email_verified'] : 0,
            'verification_token' => isset($data['verification_token']) ? $data['verification_token'] : NULL,
            'verification_expires' => isset($data['verification_expires']) ? $data['verification_expires'] : NULL,
            'reset_token' => isset($data['reset_token']) ? $data['reset_token'] : NULL,
            'reset_expires' => isset($data['reset_expires']) ? $data['reset_expires'] : NULL,
            'is_active' => isset($data['is_active']) ? (int) $data['is_active'] : 1
        );

        $profile = array(
            'bio' => isset($data['bio']) ? $data['bio'] : NULL,
            'linkedin_url' => isset($data['linkedin_url']) ? $data['linkedin_url'] : NULL,
            'profile_image' => isset($data['profile_image']) ? $data['profile_image'] : NULL
        );

        $this->db->trans_start();
        $this->db->insert('users', $user);
        $id = (int) $this->db->insert_id();
        $profile['id'] = $id;
        $this->db->insert('alumni', $profile);
        $this->db->trans_complete();

        return $this->db->trans_status() === FALSE ? FALSE : $id;
    }

    public function find_by_email($email)
    {
        return $this->base_select()
            ->where('users.email', strtolower(trim((string) $email)))
            ->where('users.user_type', 'alumni')
            ->get()->row();
    }

    public function find_by_id($id)
    {
        return $this->base_select()
            ->where('users.id', (int) $id)
            ->where('users.user_type', 'alumni')
            ->get()->row();
    }

    public function find_by_verification_token($token)
    {
        $is_hash = preg_match('/^[a-f0-9]{64}$/i', $token) === 1;
        $hash = $is_hash ? $token : hash('sha256', $token);

        return $this->base_select()
            ->where('users.user_type', 'alumni')
            ->group_start()
            ->where('users.verification_token', $hash)
            ->or_where('users.verification_token', $token)
            ->group_end()
            ->get()->row();
    }

    public function find_by_reset_token($token)
    {
        $is_hash = preg_match('/^[a-f0-9]{64}$/i', $token) === 1;
        $hash = $is_hash ? $token : hash('sha256', $token);

        return $this->base_select()
            ->where('users.user_type', 'alumni')
            ->group_start()
            ->where('users.reset_token', $hash)
            ->or_where('users.reset_token', $token)
            ->group_end()
            ->get()->row();
    }

    public function update($id, $data)
    {
        $user_fields = array('email', 'password', 'first_name', 'last_name', 'email_verified', 'verification_token', 'verification_expires', 'reset_token', 'reset_expires', 'is_active');
        $profile_fields = array('bio', 'linkedin_url', 'profile_image');
        $user_update = array();
        $profile_update = array();

        foreach ($user_fields as $field) {
            if (array_key_exists($field, $data)) {
                $user_update[$field] = $data[$field];
            }
        }

        foreach ($profile_fields as $field) {
            if (array_key_exists($field, $data)) {
                $profile_update[$field] = $data[$field];
            }
        }

        $this->db->trans_start();
        if (!empty($user_update)) {
            $this->db->where('id', (int) $id)->where('user_type', 'alumni')->update('users', $user_update);
        }
        if (!empty($profile_update)) {
            $this->db->where('id', (int) $id)->update('alumni', $profile_update);
        }
        $this->db->trans_complete();

        return $this->db->trans_status() !== FALSE;
    }

    public function verify_email($id)
    {
        return $this->update($id, array(
            'email_verified' => 1,
            'verification_token' => NULL,
            'verification_expires' => NULL
        ));
    }

    public function set_reset_token($id, $token, $expires)
    {
        $is_hash = preg_match('/^[a-f0-9]{64}$/i', $token) === 1;
        return $this->update($id, array(
            'reset_token' => $is_hash ? $token : hash('sha256', $token),
            'reset_expires' => $expires
        ));
    }

    public function update_password($id, $password)
    {
        return $this->update($id, array(
            'password' => $password,
            'reset_token' => NULL,
            'reset_expires' => NULL
        ));
    }

    public function email_exists($email)
    {
        return $this->db->get_where('users', array('email' => strtolower(trim((string) $email))))->num_rows() > 0;
    }

    public function get_full_profile($id)
    {
        $alumni = $this->find_by_id($id);
        if (!$alumni) {
            return NULL;
        }

        unset($alumni->password, $alumni->verification_token, $alumni->verification_expires, $alumni->reset_token, $alumni->reset_expires);

        return array(
            'alumni' => $alumni,
            'degrees' => $this->db->get_where('degrees', array('alumni_id' => $id))->result(),
            'certifications' => $this->db->get_where('certifications', array('alumni_id' => $id))->result(),
            'licences' => $this->db->get_where('licences', array('alumni_id' => $id))->result(),
            'courses' => $this->db->get_where('courses', array('alumni_id' => $id))->result(),
            'employment_history' => $this->db->get_where('employment_history', array('alumni_id' => $id))->result()
        );
    }

    public function get_all_active()
    {
        $options = func_num_args() > 0 && is_array(func_get_arg(0)) ? func_get_arg(0) : array();
        $allowed_fields = array('id', 'first_name', 'last_name', 'bio', 'linkedin_url', 'profile_image', 'created_at');
        $fields = isset($options['fields']) && is_array($options['fields']) && !empty($options['fields'])
            ? array_values(array_intersect($options['fields'], $allowed_fields))
            : array('id', 'first_name', 'last_name', 'linkedin_url', 'profile_image', 'created_at');

        if (empty($fields)) {
            $fields = array('id', 'first_name', 'last_name');
        }

        $select = array();
        foreach ($fields as $field) {
            $select[] = in_array($field, array('bio', 'linkedin_url', 'profile_image'), TRUE)
                ? 'alumni.' . $field
                : 'users.' . $field;
        }

        $this->db->select(implode(', ', $select));
        $this->db->from('users');
        $this->db->join('alumni', 'alumni.id = users.id', 'inner');
        $this->db->where('users.user_type', 'alumni');
        $this->db->where('users.is_active', 1);
        $this->db->where('users.email_verified', 1);

        if (!empty($options['name'])) {
            $this->db->group_start();
            $this->db->like('users.first_name', $options['name']);
            $this->db->or_like('users.last_name', $options['name']);
            $this->db->group_end();
        }

        $sort = isset($options['sort']) ? $options['sort'] : 'created_at';
        $direction = isset($options['direction']) ? $options['direction'] : 'DESC';
        $sortable = array('id', 'first_name', 'last_name', 'created_at');
        $sort = in_array($sort, $sortable, TRUE) ? $sort : 'created_at';
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        $this->db->order_by('users.' . $sort, $direction);

        if (isset($options['limit'])) {
            $this->db->limit(max(1, min(100, (int) $options['limit'])), isset($options['offset']) ? max(0, (int) $options['offset']) : 0);
        }

        return $this->db->get()->result();
    }

    public function count_all_active($options = array())
    {
        $this->db->from('users');
        $this->db->join('alumni', 'alumni.id = users.id', 'inner');
        $this->db->where('users.user_type', 'alumni');
        $this->db->where('users.is_active', 1);
        $this->db->where('users.email_verified', 1);

        if (!empty($options['name'])) {
            $this->db->group_start();
            $this->db->like('users.first_name', $options['name']);
            $this->db->or_like('users.last_name', $options['name']);
            $this->db->group_end();
        }

        return (int) $this->db->count_all_results();
    }

    public function create_api_alumni($data)
    {
        return $this->create($data);
    }

    public function deactivate($id)
    {
        return $this->update($id, array('is_active' => 0));
    }

    protected function base_select()
    {
        return $this->db
            ->select('users.*, alumni.bio, alumni.linkedin_url, alumni.profile_image')
            ->from('users')
            ->join('alumni', 'alumni.id = users.id', 'inner');
    }
}

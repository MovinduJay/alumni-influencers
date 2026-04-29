<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_model extends CI_Model
{
    public function __construct()
    {
        $this->load->database();
    }

    public function find_active_by_email($email)
    {
        return $this->db->get_where('users', array(
            'email' => strtolower(trim((string) $email)),
            'user_type' => 'admin',
            'is_active' => 1
        ))->row();
    }

    public function find_active_by_id($id)
    {
        return $this->db->get_where('users', array(
            'id' => (int) $id,
            'user_type' => 'admin',
            'is_active' => 1
        ))->row();
    }
}



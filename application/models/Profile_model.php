<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Profile_model
 *
 * Handles CRUD operations for all profile sub-sections:
 * degrees, certifications, licences, courses, and employment history.
 */
class Profile_model extends CI_Model
{
    public function __construct()
    {
        $this->load->database();
    }

    // =========================================================================
    // DEGREES
    // =========================================================================

    /**
     * Add a degree record
     *
     * @param array $data Degree data
     * @return int Insert ID
     */
    public function add_degree($data)
    {
        $this->db->insert('degrees', $data);
        return $this->db->insert_id();
    }

    /**
     * Get a specific degree
     *
     * @param int $id Degree ID
     * @return object|null
     */
    public function get_degree($id)
    {
        return $this->db->get_where('degrees', array('id' => $id))->row();
    }

    /**
     * Get all degrees for an alumni
     *
     * @param int $alumni_id Alumni ID
     * @return array
     */
    public function get_degrees($alumni_id)
    {
        return $this->db->get_where('degrees', array('alumni_id' => $alumni_id))->result();
    }

    /**
     * Update a degree record
     *
     * @param int   $id   Degree ID
     * @param array $data Data to update
     * @return bool
     */
    public function update_degree($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update('degrees', $data);
    }

    /**
     * Delete a degree record
     *
     * @param int $id        Degree ID
     * @param int $alumni_id Alumni ID (for ownership verification)
     * @return bool
     */
    public function delete_degree($id, $alumni_id)
    {
        $this->db->where('id', $id);
        $this->db->where('alumni_id', $alumni_id);
        return $this->db->delete('degrees');
    }

    // =========================================================================
    // CERTIFICATIONS
    // =========================================================================

    public function add_certification($data)
    {
        $this->db->insert('certifications', $data);
        return $this->db->insert_id();
    }

    public function get_certification($id)
    {
        return $this->db->get_where('certifications', array('id' => $id))->row();
    }

    public function get_certifications($alumni_id)
    {
        return $this->db->get_where('certifications', array('alumni_id' => $alumni_id))->result();
    }

    public function update_certification($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update('certifications', $data);
    }

    public function delete_certification($id, $alumni_id)
    {
        $this->db->where('id', $id);
        $this->db->where('alumni_id', $alumni_id);
        return $this->db->delete('certifications');
    }

    // =========================================================================
    // LICENCES
    // =========================================================================

    public function add_licence($data)
    {
        $this->db->insert('licences', $data);
        return $this->db->insert_id();
    }

    public function get_licence($id)
    {
        return $this->db->get_where('licences', array('id' => $id))->row();
    }

    public function get_licences($alumni_id)
    {
        return $this->db->get_where('licences', array('alumni_id' => $alumni_id))->result();
    }

    public function update_licence($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update('licences', $data);
    }

    public function delete_licence($id, $alumni_id)
    {
        $this->db->where('id', $id);
        $this->db->where('alumni_id', $alumni_id);
        return $this->db->delete('licences');
    }

    // =========================================================================
    // COURSES
    // =========================================================================

    public function add_course($data)
    {
        $this->db->insert('courses', $data);
        return $this->db->insert_id();
    }

    public function get_course($id)
    {
        return $this->db->get_where('courses', array('id' => $id))->row();
    }

    public function get_courses($alumni_id)
    {
        return $this->db->get_where('courses', array('alumni_id' => $alumni_id))->result();
    }

    public function update_course($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update('courses', $data);
    }

    public function delete_course($id, $alumni_id)
    {
        $this->db->where('id', $id);
        $this->db->where('alumni_id', $alumni_id);
        return $this->db->delete('courses');
    }

    // =========================================================================
    // EMPLOYMENT HISTORY
    // =========================================================================

    public function add_employment($data)
    {
        $this->db->insert('employment_history', $data);
        return $this->db->insert_id();
    }

    public function get_employment($id)
    {
        return $this->db->get_where('employment_history', array('id' => $id))->row();
    }

    public function get_employment_history($alumni_id)
    {
        $this->db->where('alumni_id', $alumni_id);
        $this->db->order_by('start_date', 'DESC');
        return $this->db->get('employment_history')->result();
    }

    public function update_employment($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update('employment_history', $data);
    }

    public function delete_employment($id, $alumni_id)
    {
        $this->db->where('id', $id);
        $this->db->where('alumni_id', $alumni_id);
        return $this->db->delete('employment_history');
    }
}

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Profile Controller
 *
 * Manages alumni profiles including personal information, degrees,
 * certifications, licences, courses, employment history, and image uploads.
 *
 * All endpoints require authentication (logged-in session).
 */
class Profile extends MY_Controller
{
    /**
     * Constructor - verify authentication and load models
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Alumni_model');
        $this->load->model('Profile_model');
        $this->load->library('form_validation');

        // Public profile viewing is allowed; edit/manage actions require auth.
        $this->require_auth_except(array('view'), 'Please log in to access your profile.');
    }

    /**
     * Profile dashboard - shows complete profile
     */
    public function index()
    {
        $alumni_id = $this->session->userdata('alumni_id');
        $data = array(
            'title'   => 'My Profile',
            'profile' => $this->Alumni_model->get_full_profile($alumni_id)
        );

        $this->load->view('layouts/header', $data);
        $this->load->view('profile/index', $data);
        $this->load->view('layouts/footer');
    }

    /**
     * Edit basic profile information (name, bio, LinkedIn URL)
     */
    public function edit()
    {
        $alumni_id = $this->session->userdata('alumni_id');

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('first_name', 'First Name', 'required|trim|min_length[2]|max_length[100]|alpha_numeric_spaces');
            $this->form_validation->set_rules('last_name', 'Last Name', 'required|trim|min_length[2]|max_length[100]|alpha_numeric_spaces');
            $this->form_validation->set_rules('bio', 'Biography', 'trim|max_length[2000]');
            $this->form_validation->set_rules('linkedin_url', 'LinkedIn URL', 'trim|max_length[500]|callback_validate_url');

            if ($this->form_validation->run() === FALSE) {
                $data = array(
                    'title'   => 'Edit Profile',
                    'alumni'  => $this->Alumni_model->find_by_id($alumni_id)
                );
                $this->load->view('layouts/header', $data);
                $this->load->view('profile/edit', $data);
                $this->load->view('layouts/footer');
                return;
            }

            $update_data = array(
                'first_name'   => $this->input->post('first_name', TRUE),
                'last_name'    => $this->input->post('last_name', TRUE),
                'bio'          => $this->input->post('bio', TRUE),
                'linkedin_url' => $this->input->post('linkedin_url', TRUE)
            );

            $this->Alumni_model->update($alumni_id, $update_data);

            // Update session data
            $this->session->set_userdata('first_name', $update_data['first_name']);
            $this->session->set_userdata('last_name', $update_data['last_name']);

            $this->session->set_flashdata('success', 'Profile updated successfully.');
            redirect('profile');
        } else {
            $data = array(
                'title'   => 'Edit Profile',
                'alumni'  => $this->Alumni_model->find_by_id($alumni_id)
            );
            $this->load->view('layouts/header', $data);
            $this->load->view('profile/edit', $data);
            $this->load->view('layouts/footer');
        }
    }

    /**
     * View any alumni's public profile
     *
     * @param int $id Alumni ID
     */
    public function view($id)
    {
        $profile = $this->Alumni_model->get_full_profile($id);

        if (!$profile) {
            show_404();
            return;
        }

        $data = array(
            'title'   => $profile['alumni']->first_name . ' ' . $profile['alumni']->last_name,
            'profile' => $profile
        );

        $this->load->view('layouts/header', $data);
        $this->load->view('profile/view', $data);
        $this->load->view('layouts/footer');
    }

    /**
     * Profile image upload handler
     *
     * Validates file extension, MIME type, and actual image content
     * (getimagesize) to prevent disguised non-image uploads.
     */
    public function image_upload()
    {
        $alumni_id = $this->session->userdata('alumni_id');

        if ($this->input->method() !== 'post') {
            redirect('profile/edit');
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
            $this->session->set_flashdata('error', $this->upload->display_errors('', ''));
            redirect('profile/edit');
            return;
        }

        $upload_data = $this->upload->data();

        // Harden: verify the file is a genuine image via getimagesize()
        $image_info = @getimagesize($upload_data['full_path']);
        if ($image_info === FALSE) {
            @unlink($upload_data['full_path']);
            $this->session->set_flashdata('error', 'Uploaded file is not a valid image.');
            redirect('profile/edit');
            return;
        }

        // Harden: check MIME type from actual content matches allowed types
        $allowed_mimes = array('image/gif', 'image/jpeg', 'image/png');
        if (!in_array($image_info['mime'], $allowed_mimes, TRUE)) {
            @unlink($upload_data['full_path']);
            $this->session->set_flashdata('error', 'Uploaded file type is not allowed.');
            redirect('profile/edit');
            return;
        }

        // Delete old profile image if exists (basename prevents path traversal)
        $alumni = $this->Alumni_model->find_by_id($alumni_id);
        $old_file = $alumni && $alumni->profile_image ? rtrim($upload_path, '/\\') . DIRECTORY_SEPARATOR . basename($alumni->profile_image) : NULL;
        if ($old_file && is_file($old_file)) {
            @unlink($old_file);
        }

        $this->Alumni_model->update($alumni_id, array(
            'profile_image' => $upload_data['file_name']
        ));

        $this->session->set_flashdata('success', 'Profile image uploaded successfully.');
        redirect('profile');
    }

    /**
     * URL validation callback
     *
     * @param string $url URL to validate
     * @return bool
     */
    public function validate_url($url)
    {
        if (empty($url)) return TRUE;
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->form_validation->set_message('validate_url', 'Please enter a valid URL (e.g., https://example.com).');
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Validate optional date fields (YYYY-MM-DD).
     *
     * @param string $date
     * @return bool
     */
    public function validate_optional_date($date)
    {
        if ($date === NULL || trim($date) === '') {
            return TRUE;
        }
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        $is_valid = $dt && $dt->format('Y-m-d') === $date;
        if (!$is_valid) {
            $this->form_validation->set_message('validate_optional_date', 'Please enter a valid date in YYYY-MM-DD format.');
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Validate required date fields (YYYY-MM-DD).
     *
     * @param string $date
     * @return bool
     */
    public function validate_required_date($date)
    {
        if ($date === NULL || trim($date) === '') {
            $this->form_validation->set_message('validate_required_date', 'This date field is required.');
            return FALSE;
        }
        return $this->validate_optional_date($date);
    }

    /**
     * Validate end_date format and chronology (end_date >= start_date).
     *
     * @param string $end_date
     * @return bool
     */
    public function validate_end_date($end_date)
    {
        if (!$this->validate_optional_date($end_date)) {
            $this->form_validation->set_message('validate_end_date', 'Please enter a valid end date in YYYY-MM-DD format.');
            return FALSE;
        }
        if ($end_date === NULL || trim($end_date) === '') {
            return TRUE;
        }

        $start_date = $this->input->post('start_date', TRUE);
        if ($start_date && $this->validate_optional_date($start_date)) {
            if (strtotime($end_date) < strtotime($start_date)) {
                $this->form_validation->set_message('validate_end_date', 'End date cannot be earlier than start date.');
                return FALSE;
            }
        }
        return TRUE;
    }

    // =========================================================================
    // DEGREES CRUD
    // =========================================================================

    /**
     * List all degrees for current alumni
     */
    public function degrees()
    {
        $alumni_id = $this->session->userdata('alumni_id');
        $data = array(
            'title'   => 'My Degrees',
            'degrees' => $this->Profile_model->get_degrees($alumni_id)
        );
        $this->load->view('layouts/header', $data);
        $this->load->view('profile/degrees', $data);
        $this->load->view('layouts/footer');
    }

    /**
     * Add a new degree
     */
    public function add_degree()
    {
        $alumni_id = $this->session->userdata('alumni_id');

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('title', 'Degree Title', 'required|trim|max_length[255]');
            $this->form_validation->set_rules('institution', 'Institution', 'required|trim|max_length[255]');
            $this->form_validation->set_rules('url', 'URL', 'trim|max_length[500]|callback_validate_url');
            $this->form_validation->set_rules('completion_date', 'Completion Date', 'trim|callback_validate_optional_date');

            if ($this->form_validation->run() === FALSE) {
                $data = array('title' => 'Add Degree');
                $this->load->view('layouts/header', $data);
                $this->load->view('profile/add_degree', $data);
                $this->load->view('layouts/footer');
                return;
            }

            $this->Profile_model->add_degree(array(
                'alumni_id'       => $alumni_id,
                'title'           => $this->input->post('title', TRUE),
                'institution'     => $this->input->post('institution', TRUE),
                'url'             => $this->input->post('url', TRUE),
                'completion_date' => $this->input->post('completion_date', TRUE) ?: NULL
            ));

            $this->session->set_flashdata('success', 'Degree added successfully.');
            redirect('profile/degrees');
        } else {
            $data = array('title' => 'Add Degree');
            $this->load->view('layouts/header', $data);
            $this->load->view('profile/add_degree', $data);
            $this->load->view('layouts/footer');
        }
    }

    /**
     * Edit a degree
     *
     * @param int $id Degree ID
     */
    public function edit_degree($id)
    {
        $alumni_id = $this->session->userdata('alumni_id');
        $degree = $this->Profile_model->get_degree($id);

        if (!$degree || $degree->alumni_id != $alumni_id) {
            show_404();
            return;
        }

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('title', 'Degree Title', 'required|trim|max_length[255]');
            $this->form_validation->set_rules('institution', 'Institution', 'required|trim|max_length[255]');
            $this->form_validation->set_rules('url', 'URL', 'trim|max_length[500]|callback_validate_url');
            $this->form_validation->set_rules('completion_date', 'Completion Date', 'trim|callback_validate_optional_date');

            if ($this->form_validation->run() === FALSE) {
                $data = array('title' => 'Edit Degree', 'degree' => $degree);
                $this->load->view('layouts/header', $data);
                $this->load->view('profile/edit_degree', $data);
                $this->load->view('layouts/footer');
                return;
            }

            $this->Profile_model->update_degree($id, array(
                'title'           => $this->input->post('title', TRUE),
                'institution'     => $this->input->post('institution', TRUE),
                'url'             => $this->input->post('url', TRUE),
                'completion_date' => $this->input->post('completion_date', TRUE) ?: NULL
            ));

            $this->session->set_flashdata('success', 'Degree updated successfully.');
            redirect('profile/degrees');
        } else {
            $data = array('title' => 'Edit Degree', 'degree' => $degree);
            $this->load->view('layouts/header', $data);
            $this->load->view('profile/edit_degree', $data);
            $this->load->view('layouts/footer');
        }
    }

    /**
     * Delete a degree
     *
     * @param int $id Degree ID
     */
    public function delete_degree($id)
    {
        if ($this->input->method() !== 'post') {
            show_error('Method not allowed.', 405);
            return;
        }
        $alumni_id = $this->session->userdata('alumni_id');
        $this->Profile_model->delete_degree($id, $alumni_id);
        $this->session->set_flashdata('success', 'Degree deleted successfully.');
        redirect('profile/degrees');
    }

    // =========================================================================
    // CERTIFICATIONS CRUD
    // =========================================================================

    public function certifications()
    {
        $alumni_id = $this->session->userdata('alumni_id');
        $data = array(
            'title'          => 'My Certifications',
            'certifications' => $this->Profile_model->get_certifications($alumni_id)
        );
        $this->load->view('layouts/header', $data);
        $this->load->view('profile/certifications', $data);
        $this->load->view('layouts/footer');
    }

    public function add_certification()
    {
        $alumni_id = $this->session->userdata('alumni_id');

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('title', 'Certification Title', 'required|trim|max_length[255]');
            $this->form_validation->set_rules('issuer', 'Issuer', 'required|trim|max_length[255]');
            $this->form_validation->set_rules('url', 'URL', 'trim|max_length[500]|callback_validate_url');
            $this->form_validation->set_rules('completion_date', 'Completion Date', 'trim|callback_validate_optional_date');

            if ($this->form_validation->run() === FALSE) {
                $data = array('title' => 'Add Certification');
                $this->load->view('layouts/header', $data);
                $this->load->view('profile/add_certification', $data);
                $this->load->view('layouts/footer');
                return;
            }

            $this->Profile_model->add_certification(array(
                'alumni_id'       => $alumni_id,
                'title'           => $this->input->post('title', TRUE),
                'issuer'          => $this->input->post('issuer', TRUE),
                'url'             => $this->input->post('url', TRUE),
                'completion_date' => $this->input->post('completion_date', TRUE) ?: NULL
            ));

            $this->session->set_flashdata('success', 'Certification added successfully.');
            redirect('profile/certifications');
        } else {
            $data = array('title' => 'Add Certification');
            $this->load->view('layouts/header', $data);
            $this->load->view('profile/add_certification', $data);
            $this->load->view('layouts/footer');
        }
    }

    public function edit_certification($id)
    {
        $alumni_id = $this->session->userdata('alumni_id');
        $cert = $this->Profile_model->get_certification($id);

        if (!$cert || $cert->alumni_id != $alumni_id) {
            show_404();
            return;
        }

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('title', 'Certification Title', 'required|trim|max_length[255]');
            $this->form_validation->set_rules('issuer', 'Issuer', 'required|trim|max_length[255]');
            $this->form_validation->set_rules('url', 'URL', 'trim|max_length[500]|callback_validate_url');
            $this->form_validation->set_rules('completion_date', 'Completion Date', 'trim|callback_validate_optional_date');

            if ($this->form_validation->run() === FALSE) {
                $data = array('title' => 'Edit Certification', 'certification' => $cert);
                $this->load->view('layouts/header', $data);
                $this->load->view('profile/edit_certification', $data);
                $this->load->view('layouts/footer');
                return;
            }

            $this->Profile_model->update_certification($id, array(
                'title'           => $this->input->post('title', TRUE),
                'issuer'          => $this->input->post('issuer', TRUE),
                'url'             => $this->input->post('url', TRUE),
                'completion_date' => $this->input->post('completion_date', TRUE) ?: NULL
            ));

            $this->session->set_flashdata('success', 'Certification updated successfully.');
            redirect('profile/certifications');
        } else {
            $data = array('title' => 'Edit Certification', 'certification' => $cert);
            $this->load->view('layouts/header', $data);
            $this->load->view('profile/edit_certification', $data);
            $this->load->view('layouts/footer');
        }
    }

    public function delete_certification($id)
    {
        if ($this->input->method() !== 'post') {
            show_error('Method not allowed.', 405);
            return;
        }
        $alumni_id = $this->session->userdata('alumni_id');
        $this->Profile_model->delete_certification($id, $alumni_id);
        $this->session->set_flashdata('success', 'Certification deleted successfully.');
        redirect('profile/certifications');
    }

    // =========================================================================
    // LICENCES CRUD
    // =========================================================================

    public function licences()
    {
        $alumni_id = $this->session->userdata('alumni_id');
        $data = array(
            'title'    => 'My Licences',
            'licences' => $this->Profile_model->get_licences($alumni_id)
        );
        $this->load->view('layouts/header', $data);
        $this->load->view('profile/licences', $data);
        $this->load->view('layouts/footer');
    }

    public function add_licence()
    {
        $alumni_id = $this->session->userdata('alumni_id');

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('title', 'Licence Title', 'required|trim|max_length[255]');
            $this->form_validation->set_rules('awarding_body', 'Awarding Body', 'required|trim|max_length[255]');
            $this->form_validation->set_rules('url', 'URL', 'trim|max_length[500]|callback_validate_url');
            $this->form_validation->set_rules('completion_date', 'Completion Date', 'trim|callback_validate_optional_date');

            if ($this->form_validation->run() === FALSE) {
                $data = array('title' => 'Add Licence');
                $this->load->view('layouts/header', $data);
                $this->load->view('profile/add_licence', $data);
                $this->load->view('layouts/footer');
                return;
            }

            $this->Profile_model->add_licence(array(
                'alumni_id'       => $alumni_id,
                'title'           => $this->input->post('title', TRUE),
                'awarding_body'   => $this->input->post('awarding_body', TRUE),
                'url'             => $this->input->post('url', TRUE),
                'completion_date' => $this->input->post('completion_date', TRUE) ?: NULL
            ));

            $this->session->set_flashdata('success', 'Licence added successfully.');
            redirect('profile/licences');
        } else {
            $data = array('title' => 'Add Licence');
            $this->load->view('layouts/header', $data);
            $this->load->view('profile/add_licence', $data);
            $this->load->view('layouts/footer');
        }
    }

    public function edit_licence($id)
    {
        $alumni_id = $this->session->userdata('alumni_id');
        $licence = $this->Profile_model->get_licence($id);

        if (!$licence || $licence->alumni_id != $alumni_id) {
            show_404();
            return;
        }

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('title', 'Licence Title', 'required|trim|max_length[255]');
            $this->form_validation->set_rules('awarding_body', 'Awarding Body', 'required|trim|max_length[255]');
            $this->form_validation->set_rules('url', 'URL', 'trim|max_length[500]|callback_validate_url');
            $this->form_validation->set_rules('completion_date', 'Completion Date', 'trim|callback_validate_optional_date');

            if ($this->form_validation->run() === FALSE) {
                $data = array('title' => 'Edit Licence', 'licence' => $licence);
                $this->load->view('layouts/header', $data);
                $this->load->view('profile/edit_licence', $data);
                $this->load->view('layouts/footer');
                return;
            }

            $this->Profile_model->update_licence($id, array(
                'title'           => $this->input->post('title', TRUE),
                'awarding_body'   => $this->input->post('awarding_body', TRUE),
                'url'             => $this->input->post('url', TRUE),
                'completion_date' => $this->input->post('completion_date', TRUE) ?: NULL
            ));

            $this->session->set_flashdata('success', 'Licence updated successfully.');
            redirect('profile/licences');
        } else {
            $data = array('title' => 'Edit Licence', 'licence' => $licence);
            $this->load->view('layouts/header', $data);
            $this->load->view('profile/edit_licence', $data);
            $this->load->view('layouts/footer');
        }
    }

    public function delete_licence($id)
    {
        if ($this->input->method() !== 'post') {
            show_error('Method not allowed.', 405);
            return;
        }
        $alumni_id = $this->session->userdata('alumni_id');
        $this->Profile_model->delete_licence($id, $alumni_id);
        $this->session->set_flashdata('success', 'Licence deleted successfully.');
        redirect('profile/licences');
    }

    // =========================================================================
    // COURSES CRUD
    // =========================================================================

    public function courses()
    {
        $alumni_id = $this->session->userdata('alumni_id');
        $data = array(
            'title'   => 'My Courses',
            'courses' => $this->Profile_model->get_courses($alumni_id)
        );
        $this->load->view('layouts/header', $data);
        $this->load->view('profile/courses', $data);
        $this->load->view('layouts/footer');
    }

    public function add_course()
    {
        $alumni_id = $this->session->userdata('alumni_id');

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('title', 'Course Title', 'required|trim|max_length[255]');
            $this->form_validation->set_rules('provider', 'Provider', 'required|trim|max_length[255]');
            $this->form_validation->set_rules('url', 'URL', 'trim|max_length[500]|callback_validate_url');
            $this->form_validation->set_rules('completion_date', 'Completion Date', 'trim|callback_validate_optional_date');

            if ($this->form_validation->run() === FALSE) {
                $data = array('title' => 'Add Course');
                $this->load->view('layouts/header', $data);
                $this->load->view('profile/add_course', $data);
                $this->load->view('layouts/footer');
                return;
            }

            $this->Profile_model->add_course(array(
                'alumni_id'       => $alumni_id,
                'title'           => $this->input->post('title', TRUE),
                'provider'        => $this->input->post('provider', TRUE),
                'url'             => $this->input->post('url', TRUE),
                'completion_date' => $this->input->post('completion_date', TRUE) ?: NULL
            ));

            $this->session->set_flashdata('success', 'Course added successfully.');
            redirect('profile/courses');
        } else {
            $data = array('title' => 'Add Course');
            $this->load->view('layouts/header', $data);
            $this->load->view('profile/add_course', $data);
            $this->load->view('layouts/footer');
        }
    }

    public function edit_course($id)
    {
        $alumni_id = $this->session->userdata('alumni_id');
        $course = $this->Profile_model->get_course($id);

        if (!$course || $course->alumni_id != $alumni_id) {
            show_404();
            return;
        }

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('title', 'Course Title', 'required|trim|max_length[255]');
            $this->form_validation->set_rules('provider', 'Provider', 'required|trim|max_length[255]');
            $this->form_validation->set_rules('url', 'URL', 'trim|max_length[500]|callback_validate_url');
            $this->form_validation->set_rules('completion_date', 'Completion Date', 'trim|callback_validate_optional_date');

            if ($this->form_validation->run() === FALSE) {
                $data = array('title' => 'Edit Course', 'course' => $course);
                $this->load->view('layouts/header', $data);
                $this->load->view('profile/edit_course', $data);
                $this->load->view('layouts/footer');
                return;
            }

            $this->Profile_model->update_course($id, array(
                'title'           => $this->input->post('title', TRUE),
                'provider'        => $this->input->post('provider', TRUE),
                'url'             => $this->input->post('url', TRUE),
                'completion_date' => $this->input->post('completion_date', TRUE) ?: NULL
            ));

            $this->session->set_flashdata('success', 'Course updated successfully.');
            redirect('profile/courses');
        } else {
            $data = array('title' => 'Edit Course', 'course' => $course);
            $this->load->view('layouts/header', $data);
            $this->load->view('profile/edit_course', $data);
            $this->load->view('layouts/footer');
        }
    }

    public function delete_course($id)
    {
        if ($this->input->method() !== 'post') {
            show_error('Method not allowed.', 405);
            return;
        }
        $alumni_id = $this->session->userdata('alumni_id');
        $this->Profile_model->delete_course($id, $alumni_id);
        $this->session->set_flashdata('success', 'Course deleted successfully.');
        redirect('profile/courses');
    }

    // =========================================================================
    // EMPLOYMENT HISTORY CRUD
    // =========================================================================

    public function employment()
    {
        $alumni_id = $this->session->userdata('alumni_id');
        $data = array(
            'title'      => 'Employment History',
            'employment' => $this->Profile_model->get_employment_history($alumni_id)
        );
        $this->load->view('layouts/header', $data);
        $this->load->view('profile/employment', $data);
        $this->load->view('layouts/footer');
    }

    public function add_employment()
    {
        $alumni_id = $this->session->userdata('alumni_id');

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('company', 'Company', 'required|trim|max_length[255]');
            $this->form_validation->set_rules('position', 'Position', 'required|trim|max_length[255]');
            $this->form_validation->set_rules('start_date', 'Start Date', 'required|trim|callback_validate_required_date');
            $this->form_validation->set_rules('end_date', 'End Date', 'trim|callback_validate_end_date');

            if ($this->form_validation->run() === FALSE) {
                $data = array('title' => 'Add Employment');
                $this->load->view('layouts/header', $data);
                $this->load->view('profile/add_employment', $data);
                $this->load->view('layouts/footer');
                return;
            }

            $this->Profile_model->add_employment(array(
                'alumni_id'  => $alumni_id,
                'company'    => $this->input->post('company', TRUE),
                'position'   => $this->input->post('position', TRUE),
                'start_date' => $this->input->post('start_date', TRUE),
                'end_date'   => $this->input->post('end_date', TRUE) ?: NULL
            ));

            $this->session->set_flashdata('success', 'Employment record added successfully.');
            redirect('profile/employment');
        } else {
            $data = array('title' => 'Add Employment');
            $this->load->view('layouts/header', $data);
            $this->load->view('profile/add_employment', $data);
            $this->load->view('layouts/footer');
        }
    }

    public function edit_employment($id)
    {
        $alumni_id = $this->session->userdata('alumni_id');
        $employment = $this->Profile_model->get_employment($id);

        if (!$employment || $employment->alumni_id != $alumni_id) {
            show_404();
            return;
        }

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('company', 'Company', 'required|trim|max_length[255]');
            $this->form_validation->set_rules('position', 'Position', 'required|trim|max_length[255]');
            $this->form_validation->set_rules('start_date', 'Start Date', 'required|trim|callback_validate_required_date');
            $this->form_validation->set_rules('end_date', 'End Date', 'trim|callback_validate_end_date');

            if ($this->form_validation->run() === FALSE) {
                $data = array('title' => 'Edit Employment', 'employment' => $employment);
                $this->load->view('layouts/header', $data);
                $this->load->view('profile/edit_employment', $data);
                $this->load->view('layouts/footer');
                return;
            }

            $this->Profile_model->update_employment($id, array(
                'company'    => $this->input->post('company', TRUE),
                'position'   => $this->input->post('position', TRUE),
                'start_date' => $this->input->post('start_date', TRUE),
                'end_date'   => $this->input->post('end_date', TRUE) ?: NULL
            ));

            $this->session->set_flashdata('success', 'Employment record updated successfully.');
            redirect('profile/employment');
        } else {
            $data = array('title' => 'Edit Employment', 'employment' => $employment);
            $this->load->view('layouts/header', $data);
            $this->load->view('profile/edit_employment', $data);
            $this->load->view('layouts/footer');
        }
    }

    public function delete_employment($id)
    {
        if ($this->input->method() !== 'post') {
            show_error('Method not allowed.', 405);
            return;
        }
        $alumni_id = $this->session->userdata('alumni_id');
        $this->Profile_model->delete_employment($id, $alumni_id);
        $this->session->set_flashdata('success', 'Employment record deleted successfully.');
        redirect('profile/employment');
    }
}


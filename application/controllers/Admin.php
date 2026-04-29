<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends MY_Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('admin_service');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $stats = $this->admin_service->get_api_usage_stats();
        $data = array(
            'title' => 'Dashboard',
            'clients' => $this->admin_service->get_api_clients(),
            'stats' => $stats
        );

        $this->load->view('layouts/header', $data);
        $this->load->view('admin/dashboard', $data);
        $this->load->view('layouts/footer');
    }

    public function api_clients()
    {
        $data = array(
            'title'   => 'API Client Management',
            'clients' => $this->admin_service->get_api_clients()
        );

        $this->load->view('layouts/header', $data);
        $this->load->view('admin/api_clients', $data);
        $this->load->view('layouts/footer');
    }

    public function create_client()
    {
        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('client_name', 'Client Name', 'required|trim|max_length[255]');
            $this->form_validation->set_rules('scope', 'Scope', 'required|trim|callback_validate_scope');

            if ($this->form_validation->run() === FALSE) {
                $this->session->set_flashdata('error', validation_errors());
                redirect('admin/api-clients');
                return;
            }

            $client_name = $this->input->post('client_name', TRUE);
            $scope = $this->input->post('scope', TRUE);
            $result = $this->admin_service->create_api_client($client_name, $scope);
            if (!$result['ok']) {
                $this->session->set_flashdata('error', $result['message']);
                redirect('admin/api-clients');
                return;
            }

            // Store the tokens in flash data so they can be shown once
            $this->session->set_flashdata('new_client', $result['client']);
            $this->session->set_flashdata('success', 'API client created successfully. Save the keys below - they will not be shown again!');
            redirect('admin/api-clients');
        } else {
            redirect('admin/api-clients');
        }
    }

    public function validate_scope($scope)
    {
        $validation = $this->admin_service->validate_scope_selection($scope);
        if (!$validation['ok']) {
            $this->form_validation->set_message('validate_scope', 'Invalid scope selection.');
            return FALSE;
        }
        return TRUE;
    }

    public function revoke_client($client_id)
    {
        if ($this->input->method() !== 'post') {
            show_error('Method not allowed.', 405);
            return;
        }
        $this->admin_service->set_api_client_active($client_id, FALSE);
        $this->session->set_flashdata('success', 'API client access revoked.');
        redirect('admin/api-clients');
    }

    public function client_logs($client_id)
    {
        $data = array(
            'title' => 'Client Access Logs',
            'logs'  => $this->admin_service->get_api_client_logs($client_id)
        );

        $this->load->view('layouts/header', $data);
        $this->load->view('admin/client_logs', $data);
        $this->load->view('layouts/footer');
    }

    public function featured_alumni()
    {
        $data = array(
            'title' => 'Last 30 Days Alumni of the Day',
            'featured_alumni' => $this->admin_service->get_recent_featured_alumni(30)
        );

        $this->load->view('layouts/header', $data);
        $this->load->view('admin/featured_alumni', $data);
        $this->load->view('layouts/footer');
    }

    public function api_stats()
    {
        redirect('admin');
    }
}



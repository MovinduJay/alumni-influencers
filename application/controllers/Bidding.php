<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Bidding Controller
 *
 * Implements the blind bidding system for Alumni of the Day feature.
 */
class Bidding extends MY_Authenticated_Controller
{
    public function __construct()
    {
        parent::__construct('Please log in to access bidding.');
        $this->load->model('Bid_model');
        $this->load->model('Alumni_model');
        $this->load->library('form_validation');
        $this->load->library('bid_winner_service');
    }

    /**
     * Bidding dashboard - shows current bid status and sponsorship context.
     */
    public function index()
    {
        $alumni_id = $this->session->userdata('alumni_id');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));

        $current_bid = $this->Bid_model->get_alumni_bid_for_date($alumni_id, $tomorrow);
        $is_winning = $current_bid ? $this->Bid_model->is_winning($alumni_id, $tomorrow) : FALSE;
        $monthly_wins = $this->Bid_model->get_monthly_wins($alumni_id);
        $max_wins = $this->Bid_model->get_max_monthly_wins($alumni_id);

        $data = array(
            'title' => 'Bidding System',
            'current_bid' => $current_bid,
            'is_winning' => $is_winning,
            'monthly_wins' => $monthly_wins,
            'max_wins' => $max_wins,
            'can_bid' => $this->Bid_model->can_bid($alumni_id),
            'remaining_slots' => $max_wins - $monthly_wins,
            'sponsorship_total' => $this->Bid_model->get_accepted_sponsorship_total($alumni_id),
            'sponsorships' => $this->Bid_model->get_sponsorships($alumni_id),
            'event_participations' => $this->Bid_model->get_event_participations($alumni_id),
            'featured_today' => $this->Bid_model->get_featured_today(),
            'bid_date' => $tomorrow
        );

        $this->load->view('layouts/header', $data);
        $this->load->view('bidding/index', $data);
        $this->load->view('layouts/footer');
    }

    /**
     * Place a new bid backed by accepted sponsorships.
     */
    public function place()
    {
        $alumni_id = $this->session->userdata('alumni_id');

        if ($this->input->method() !== 'post') {
            redirect('bidding');
            return;
        }

        if (!$this->Bid_model->can_bid($alumni_id)) {
            $this->session->set_flashdata('error', 'You have reached your monthly feature limit.');
            redirect('bidding');
            return;
        }

        $this->form_validation->set_rules('amount', 'Bid Amount', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('bid_date', 'Bid Date', 'required|callback_validate_bid_date');

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('bidding');
            return;
        }

        $amount = (float) $this->input->post('amount', TRUE);
        $bid_date = $this->input->post('bid_date', TRUE);
        $sponsorship_total = $this->Bid_model->get_accepted_sponsorship_total($alumni_id);

        if (strtotime($bid_date) <= strtotime('today')) {
            $this->session->set_flashdata('error', 'You can only bid for future dates.');
            redirect('bidding');
            return;
        }

        if ($sponsorship_total <= 0) {
            $this->session->set_flashdata('error', 'You need at least one accepted sponsorship before you can place a bid.');
            redirect('bidding');
            return;
        }

        if ($amount > $sponsorship_total) {
            $this->session->set_flashdata('error', 'Your bid cannot exceed your accepted sponsorship funding of £' . number_format($sponsorship_total, 2) . '.');
            redirect('bidding');
            return;
        }

        $existing = $this->Bid_model->get_alumni_bid_for_date($alumni_id, $bid_date);
        if ($existing) {
            $this->session->set_flashdata('error', 'You already have a bid for this date. Please update your existing bid.');
            redirect('bidding');
            return;
        }

        $bid_id = $this->Bid_model->place_bid($alumni_id, $amount, $bid_date);
        if (!$bid_id) {
            $this->session->set_flashdata('error', 'Failed to place bid. Please try again.');
            redirect('bidding');
            return;
        }

        $is_winning = $this->Bid_model->is_winning($alumni_id, $bid_date);
        $status_msg = $is_winning ? 'You are currently in the lead!' : 'Your bid has been placed. You are not currently in the lead.';
        $this->session->set_flashdata('success', 'Bid placed successfully. ' . $status_msg);
        redirect('bidding');
    }

    /**
     * Update an existing bid (increase only, and not beyond sponsorship budget).
     *
     * @param int $bid_id
     */
    public function update_bid($bid_id)
    {
        $alumni_id = $this->session->userdata('alumni_id');

        if ($this->input->method() !== 'post') {
            redirect('bidding');
            return;
        }

        $bid = $this->Bid_model->get_bid($bid_id);
        if (!$bid || (int) $bid->alumni_id !== (int) $alumni_id) {
            $this->session->set_flashdata('error', 'Bid not found.');
            redirect('bidding');
            return;
        }

        if ($bid->status !== 'pending') {
            $this->session->set_flashdata('error', 'This bid can no longer be updated.');
            redirect('bidding');
            return;
        }

        $this->form_validation->set_rules('amount', 'New Amount', 'required|numeric|greater_than[0]');
        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('bidding');
            return;
        }

        $new_amount = (float) $this->input->post('amount', TRUE);
        $sponsorship_total = $this->Bid_model->get_accepted_sponsorship_total($alumni_id);

        if ($new_amount <= (float) $bid->amount) {
            $this->session->set_flashdata('error', 'You can only increase your bid. Current bid: £' . number_format($bid->amount, 2));
            redirect('bidding');
            return;
        }

        if ($sponsorship_total <= 0 || $new_amount > $sponsorship_total) {
            $this->session->set_flashdata('error', 'Your updated bid cannot exceed your accepted sponsorship funding of £' . number_format($sponsorship_total, 2) . '.');
            redirect('bidding');
            return;
        }

        $this->Bid_model->update_bid($bid_id, $new_amount);

        $is_winning = $this->Bid_model->is_winning($alumni_id, $bid->bid_date);
        $status_msg = $is_winning ? 'You are now in the lead!' : 'Bid updated. You are not currently in the lead.';
        $this->session->set_flashdata('success', 'Bid updated to £' . number_format($new_amount, 2) . '. ' . $status_msg);
        redirect('bidding');
    }

    /**
     * Bid history page.
     */
    public function history()
    {
        $alumni_id = $this->session->userdata('alumni_id');
        $data = array(
            'title' => 'Bid History',
            'bids' => $this->Bid_model->get_alumni_bids($alumni_id)
        );

        $this->load->view('layouts/header', $data);
        $this->load->view('bidding/history', $data);
        $this->load->view('layouts/footer');
    }

    /**
     * Sponsorship management page.
     */
    public function sponsorships()
    {
        $alumni_id = $this->session->userdata('alumni_id');
        $data = array(
            'title' => 'Sponsorships',
            'sponsorships' => $this->Bid_model->get_sponsorships($alumni_id),
            'accepted_total' => $this->Bid_model->get_accepted_sponsorship_total($alumni_id)
        );

        $this->load->view('layouts/header', $data);
        $this->load->view('bidding/sponsorships', $data);
        $this->load->view('layouts/footer');
    }

    /**
     * Add a sponsorship offer.
     */
    public function add_sponsorship()
    {
        if ($this->input->method() !== 'post') {
            redirect('bidding/sponsorships');
            return;
        }

        $alumni_id = $this->session->userdata('alumni_id');
        $this->form_validation->set_rules('sponsor_name', 'Sponsor Name', 'required|trim|max_length[255]');
        $this->form_validation->set_rules('amount_offered', 'Amount Offered', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('status', 'Status', 'required|trim|in_list[pending,accepted,rejected]');

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('bidding/sponsorships');
            return;
        }

        $this->Bid_model->add_sponsorship(array(
            'alumni_id' => $alumni_id,
            'sponsor_name' => $this->input->post('sponsor_name', TRUE),
            'amount_offered' => $this->input->post('amount_offered', TRUE),
            'status' => $this->input->post('status', TRUE)
        ));

        $this->session->set_flashdata('success', 'Sponsorship offer saved successfully.');
        redirect('bidding/sponsorships');
    }

    /**
     * Update sponsorship status.
     *
     * @param int $id
     */
    public function update_sponsorship($id)
    {
        if ($this->input->method() !== 'post') {
            redirect('bidding/sponsorships');
            return;
        }

        $alumni_id = $this->session->userdata('alumni_id');
        $sponsorship = $this->Bid_model->get_sponsorship($id, $alumni_id);
        if (!$sponsorship) {
            show_404();
            return;
        }

        $status = $this->input->post('status', TRUE);
        if (!in_array($status, array('pending', 'accepted', 'rejected'), TRUE)) {
            $this->session->set_flashdata('error', 'Invalid sponsorship status.');
            redirect('bidding/sponsorships');
            return;
        }

        $this->Bid_model->update_sponsorship($id, $alumni_id, array('status' => $status));
        $this->session->set_flashdata('success', 'Sponsorship status updated.');
        redirect('bidding/sponsorships');
    }

    /**
     * Delete a sponsorship.
     *
     * @param int $id
     */
    public function delete_sponsorship($id)
    {
        if ($this->input->method() !== 'post') {
            show_error('Method not allowed.', 405);
            return;
        }

        $alumni_id = $this->session->userdata('alumni_id');
        $this->Bid_model->delete_sponsorship($id, $alumni_id);
        $this->session->set_flashdata('success', 'Sponsorship deleted.');
        redirect('bidding/sponsorships');
    }

    /**
     * Event participation management page.
     */
    public function events()
    {
        $alumni_id = $this->session->userdata('alumni_id');
        $data = array(
            'title' => 'Event Participations',
            'events' => $this->Bid_model->get_event_participations($alumni_id),
            'max_wins' => $this->Bid_model->get_max_monthly_wins($alumni_id)
        );

        $this->load->view('layouts/header', $data);
        $this->load->view('bidding/events', $data);
        $this->load->view('layouts/footer');
    }

    /**
     * Record alumni event participation.
     */
    public function add_event()
    {
        if ($this->input->method() !== 'post') {
            redirect('bidding/events');
            return;
        }

        $alumni_id = $this->session->userdata('alumni_id');
        $this->form_validation->set_rules('event_name', 'Event Name', 'required|trim|max_length[255]');
        $this->form_validation->set_rules('event_date', 'Event Date', 'required|callback_validate_bid_date');

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('bidding/events');
            return;
        }

        $this->Bid_model->add_event_participation(array(
            'alumni_id' => $alumni_id,
            'event_name' => $this->input->post('event_name', TRUE),
            'event_date' => $this->input->post('event_date', TRUE)
        ));

        $this->session->set_flashdata('success', 'Event participation recorded. Your monthly bidding allowance has been recalculated.');
        redirect('bidding/events');
    }

    /**
     * Delete an event participation.
     *
     * @param int $id
     */
    public function delete_event($id)
    {
        if ($this->input->method() !== 'post') {
            show_error('Method not allowed.', 405);
            return;
        }

        $alumni_id = $this->session->userdata('alumni_id');
        $this->Bid_model->delete_event_participation($id, $alumni_id);
        $this->session->set_flashdata('success', 'Event participation removed.');
        redirect('bidding/events');
    }

    /**
     * Validate bid/event date format (YYYY-MM-DD).
     *
     * @param string $bid_date
     * @return bool
     */
    public function validate_bid_date($bid_date)
    {
        $dt = DateTime::createFromFormat('Y-m-d', (string) $bid_date);
        if (!$dt || $dt->format('Y-m-d') !== $bid_date) {
            $this->form_validation->set_message('validate_bid_date', 'Please provide a valid date in YYYY-MM-DD format.');
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Manual winner selection trigger.
     */
    public function select_winner()
    {
        if (!is_cli()) {
            if ($this->session->userdata('role') !== 'admin') {
                show_error('Access denied. Admin privileges required.', 403);
                return;
            }
            if ($this->input->method() !== 'post') {
                show_error('Method not allowed.', 405);
                return;
            }
        }

        $featured_date = date('Y-m-d');
        $result = $this->bid_winner_service->resolve_for_date($featured_date);

        if ($result['status'] === 'selected') {
            $winner = $result['winner'];
            $this->session->set_flashdata('success', 'Winner selected: ' . $winner->first_name . ' ' . $winner->last_name . ' for ' . $result['featured_date']);
        } else {
            $this->session->set_flashdata('info', 'No bids found for ' . $featured_date);
        }

        redirect('bidding');
    }
}

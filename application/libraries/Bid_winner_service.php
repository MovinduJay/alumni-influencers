<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Coordinates winner selection and bidder notifications.
 */
class Bid_winner_service
{
    /**
     * @var CI_Controller
     */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('Bid_model');
        $this->CI->load->model('Alumni_model');
        $this->CI->load->library('email');
    }

    /**
     * Resolve the featured alumni for the target featured date and notify bidders.
     *
     * @param string $featured_date
     * @return array
     */
    public function resolve_for_date($featured_date)
    {
        $result = $this->CI->Bid_model->select_winner($featured_date);

        if (!$result) {
            return array(
                'status' => 'none',
                'featured_date' => $featured_date
            );
        }

        $winner = $this->CI->Alumni_model->find_by_id($result['bid']->alumni_id);

        return array(
            'status' => 'selected',
            'bid' => $result['bid'],
            'winner' => $winner,
            'featured_date' => $result['featured_date'],
            'winner_email_sent' => $this->notify_winner($winner, $result['featured_date']),
            'loser_emails' => $this->notify_losers($featured_date)
        );
    }

    /**
     * Notify the winning bidder.
     *
     * @param object|null $alumni
     * @param string $featured_date
     * @return bool
     */
    protected function notify_winner($alumni, $featured_date)
    {
        if (!$alumni) {
            return FALSE;
        }

        $from = get_smtp_from();
        $this->CI->email->initialize(get_smtp_config());
        $this->CI->email->from($from['email'], $from['name']);
        $this->CI->email->to($alumni->email);
        $this->CI->email->subject('Congratulations! You are Alumni of the Day!');

        $message = '<html><body>';
        $message .= '<h2>Congratulations, ' . htmlspecialchars($alumni->first_name, ENT_QUOTES, 'UTF-8') . '!</h2>';
        $message .= '<p>Your bid has won! You will be the featured Alumni of the Day on <strong>' . htmlspecialchars($featured_date, ENT_QUOTES, 'UTF-8') . '</strong>.</p>';
        $message .= '<p>Your profile will be visible to all students for the entire day.</p>';
        $message .= '</body></html>';

        $this->CI->email->message($message);
        return send_email_safely($this->CI->email);
    }

    /**
     * Notify all losing bidders for a date.
     *
     * @param string $bid_date
     * @return array
     */
    protected function notify_losers($bid_date)
    {
        $this->CI->db->where('bid_date', $bid_date);
        $this->CI->db->where('status', 'lost');
        $losers = $this->CI->db->get('bids')->result();

        $from = get_smtp_from();
        $sent = 0;
        $failed = 0;

        foreach ($losers as $bid) {
            $alumni = $this->CI->Alumni_model->find_by_id($bid->alumni_id);
            if (!$alumni) {
                $failed++;
                continue;
            }

            $this->CI->email->initialize(get_smtp_config());
            $this->CI->email->from($from['email'], $from['name']);
            $this->CI->email->to($alumni->email);
            $this->CI->email->subject('Bid Result - Alumni of the Day');

            $message = '<html><body>';
            $message .= '<h2>Bid Result</h2>';
            $message .= '<p>Unfortunately, your bid for ' . htmlspecialchars($bid_date, ENT_QUOTES, 'UTF-8') . ' was not the winning bid.</p>';
            $message .= '<p>Don\'t give up! Place a new bid for another day.</p>';
            $message .= '</body></html>';

            $this->CI->email->message($message);
            if (send_email_safely($this->CI->email)) {
                $sent++;
            } else {
                $failed++;
            }
        }

        return array(
            'sent' => $sent,
            'failed' => $failed
        );
    }
}

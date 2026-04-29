<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cron extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!is_cli()) {
            show_error('This controller is only accessible via the command line.', 403);
        }

        $this->load->library('bid_winner_service');
    }

    public function select_winner($featured_date = NULL)
    {
        if ($featured_date === NULL || $featured_date === '') {
            $featured_date = date('Y-m-d');
        } else {
            $dt = DateTime::createFromFormat('Y-m-d', (string) $featured_date);
            if (!$dt || $dt->format('Y-m-d') !== $featured_date) {
                echo "[ERROR] Invalid date format. Use YYYY-MM-DD.\n";
                return;
            }
        }

        echo '[' . date('Y-m-d H:i:s') . "] Running winner selection for {$featured_date}...\n";

        $result = $this->bid_winner_service->resolve_for_date($featured_date);

        if ($result['status'] !== 'selected') {
            echo "[INFO] No pending bids found for {$featured_date}.\n";
            return;
        }

        $winner = $result['winner'];
        $name = $winner ? ($winner->first_name . ' ' . $winner->last_name) : 'Unknown';

        echo "[OK] Winner selected: {$name} (Alumni ID: {$result['bid']->alumni_id})\n";
        echo "     Featured date: {$result['featured_date']}\n";
        echo "     Winning bid: GBP " . number_format($result['bid']->amount, 2) . "\n";

        $winner_sent = $result['winner_email_sent'];
        $loser_results = $result['loser_emails'];

        if ($winner_sent && $loser_results['failed'] === 0) {
            echo "[OK] Notification emails sent.\n";
            return;
        }

        echo "[WARN] Notification delivery incomplete. ";
        echo 'Winner email: ' . ($winner_sent ? 'sent' : 'failed');
        echo '; loser emails sent: ' . $loser_results['sent'];
        echo '; loser emails failed: ' . $loser_results['failed'] . "\n";
    }
}



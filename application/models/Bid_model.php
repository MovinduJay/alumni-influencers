<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Bid_model
 *
 * Handles all database operations for the bidding system.
 * Implements blind bidding, monthly limits, and automated winner selection.
 */
class Bid_model extends CI_Model
{
    public function __construct()
    {
        $this->load->database();
    }

    /**
     * Place a new bid for a specific date
     *
     * @param int    $alumni_id Alumni ID
     * @param float  $amount    Bid amount
     * @param string $bid_date  Target date (YYYY-MM-DD)
     * @return int|bool Insert ID on success, FALSE on failure
     */
    public function place_bid($alumni_id, $amount, $bid_date)
    {
        $data = array(
            'alumni_id' => $alumni_id,
            'amount'    => $amount,
            'bid_date'  => $bid_date,
            'status'    => 'pending'
        );
        $this->db->insert('bids', $data);
        return $this->db->insert_id();
    }

    /**
     * Get sponsorship offers for an alumni.
     *
     * @param int $alumni_id
     * @return array
     */
    public function get_sponsorships($alumni_id)
    {
        $this->db->where('alumni_id', $alumni_id);
        $this->db->order_by('created_at', 'DESC');
        return $this->db->get('sponsorships')->result();
    }

    /**
     * Get a single sponsorship owned by an alumni.
     *
     * @param int $id
     * @param int $alumni_id
     * @return object|null
     */
    public function get_sponsorship($id, $alumni_id)
    {
        return $this->db->get_where('sponsorships', array(
            'id' => $id,
            'alumni_id' => $alumni_id
        ))->row();
    }

    /**
     * Create a sponsorship offer entry.
     *
     * @param array $data
     * @return int
     */
    public function add_sponsorship($data)
    {
        $this->db->insert('sponsorships', $data);
        return $this->db->insert_id();
    }

    /**
     * Update an alumni sponsorship status.
     *
     * @param int $id
     * @param int $alumni_id
     * @param array $data
     * @return bool
     */
    public function update_sponsorship($id, $alumni_id, $data)
    {
        $this->db->where('id', $id);
        $this->db->where('alumni_id', $alumni_id);
        return $this->db->update('sponsorships', $data);
    }

    /**
     * Delete a sponsorship entry owned by an alumni.
     *
     * @param int $id
     * @param int $alumni_id
     * @return bool
     */
    public function delete_sponsorship($id, $alumni_id)
    {
        $this->db->where('id', $id);
        $this->db->where('alumni_id', $alumni_id);
        return $this->db->delete('sponsorships');
    }

    /**
     * Sum accepted sponsorship offers for an alumni.
     *
     * @param int $alumni_id
     * @return float
     */
    public function get_accepted_sponsorship_total($alumni_id)
    {
        $this->db->select_sum('amount_offered');
        $row = $this->db->get_where('sponsorships', array(
            'alumni_id' => $alumni_id,
            'status' => 'accepted'
        ))->row();

        return $row && $row->amount_offered !== NULL ? (float) $row->amount_offered : 0.0;
    }

    /**
     * List recorded event participations for an alumni.
     *
     * @param int $alumni_id
     * @return array
     */
    public function get_event_participations($alumni_id)
    {
        $this->db->where('alumni_id', $alumni_id);
        $this->db->order_by('event_date', 'DESC');
        return $this->db->get('event_participations')->result();
    }

    /**
     * Add a university event participation entry.
     *
     * @param array $data
     * @return int
     */
    public function add_event_participation($data)
    {
        $this->db->insert('event_participations', $data);
        return $this->db->insert_id();
    }

    /**
     * Delete an event participation entry owned by an alumni.
     *
     * @param int $id
     * @param int $alumni_id
     * @return bool
     */
    public function delete_event_participation($id, $alumni_id)
    {
        $this->db->where('id', $id);
        $this->db->where('alumni_id', $alumni_id);
        return $this->db->delete('event_participations');
    }

    /**
     * Update an existing bid (increase only)
     *
     * @param int   $bid_id     Bid ID
     * @param float $new_amount New bid amount
     * @return bool
     */
    public function update_bid($bid_id, $new_amount)
    {
        $this->db->where('id', $bid_id);
        return $this->db->update('bids', array(
            'amount' => $new_amount
        ));
    }

    /**
     * Get bid by ID
     *
     * @param int $bid_id Bid ID
     * @return object|null
     */
    public function get_bid($bid_id)
    {
        return $this->db->get_where('bids', array('id' => $bid_id))->row();
    }

    /**
     * Get alumni's bid for a specific date
     *
     * @param int    $alumni_id Alumni ID
     * @param string $bid_date  Date (YYYY-MM-DD)
     * @return object|null
     */
    public function get_alumni_bid_for_date($alumni_id, $bid_date)
    {
        return $this->db->get_where('bids', array(
            'alumni_id' => $alumni_id,
            'bid_date'  => $bid_date
        ))->row();
    }

    /**
     * Check if alumni is currently the highest bidder for a date
     * (Does NOT reveal the actual highest bid amount - blind bidding)
     *
     * @param int    $alumni_id Alumni ID
     * @param string $bid_date  Date (YYYY-MM-DD)
     * @return bool TRUE if winning, FALSE if losing
     */
    public function is_winning($alumni_id, $bid_date)
    {
        // Get the highest bid for this date
        $this->db->select_max('amount');
        $this->db->where('bid_date', $bid_date);
        $this->db->where('status', 'pending');
        $highest = $this->db->get('bids')->row();

        if (!$highest || !$highest->amount) return FALSE;

        // Get this alumni's bid
        $my_bid = $this->get_alumni_bid_for_date($alumni_id, $bid_date);
        if (!$my_bid) return FALSE;

        return ($my_bid->amount >= $highest->amount);
    }

    /**
     * Get number of times alumni has been featured this month
     *
     * @param int    $alumni_id Alumni ID
     * @param string $month     Month (YYYY-MM format)
     * @return int
     */
    public function get_monthly_wins($alumni_id, $month = NULL)
    {
        if (!$month) {
            $month = date('Y-m');
        }
        $start = $month . '-01';
        $end = date('Y-m-t', strtotime($start));

        $this->db->from('featured_alumni');
        $this->db->join('bids', 'bids.id = featured_alumni.bid_id');
        $this->db->where('bids.alumni_id', $alumni_id);
        $this->db->where('featured_alumni.featured_date >=', $start);
        $this->db->where('featured_alumni.featured_date <=', $end);
        return $this->db->count_all_results();
    }

    /**
     * Check if alumni has event participation this month (for 4th bid allowance)
     *
     * @param int    $alumni_id Alumni ID
     * @param string $month     Month (YYYY-MM format)
     * @return bool
     */
    public function has_event_participation($alumni_id, $month = NULL)
    {
        if (!$month) {
            $month = date('Y-m');
        }
        $start = $month . '-01';
        $end = date('Y-m-t', strtotime($start));

        $this->db->where('alumni_id', $alumni_id);
        $this->db->where('event_date >=', $start);
        $this->db->where('event_date <=', $end);
        return $this->db->count_all_results('event_participations') > 0;
    }

    /**
     * Get the maximum allowed wins for alumni this month
     *
     * @param int $alumni_id Alumni ID
     * @return int Maximum allowed wins (3 or 4)
     */
    public function get_max_monthly_wins($alumni_id)
    {
        $base_limit = (int)(getenv('MAX_FEATURES_PER_MONTH') ?: 3);
        if ($this->has_event_participation($alumni_id)) {
            return $base_limit + 1;
        }
        return $base_limit;
    }

    /**
     * Check if alumni can still bid (hasn't exceeded monthly limit)
     *
     * @param int $alumni_id Alumni ID
     * @return bool
     */
    public function can_bid($alumni_id)
    {
        $wins = $this->get_monthly_wins($alumni_id);
        $max = $this->get_max_monthly_wins($alumni_id);
        return $wins < $max;
    }

    /**
     * Select winner for a given date - automated winner selection
     * Called at midnight each day (via Cron controller) to determine the winner.
     *
     * Uses a database transaction to ensure all updates (won/lost/featured)
     * are atomic. Idempotent: returns FALSE if a winner already exists for the date.
     *
     * @param string $bid_date The date to select winner for (also the featured date)
     * @return array|bool Winner info or FALSE if no pending bids or already resolved
     */
    public function select_winner($bid_date)
    {
        // Idempotency: skip if a winner already exists for this date
        $existing = $this->db->get_where('featured_alumni', array('featured_date' => $bid_date))->row();
        if ($existing) return FALSE;

        // Get the highest bid for the date
        $this->db->where('bid_date', $bid_date);
        $this->db->where('status', 'pending');
        $this->db->order_by('amount', 'DESC');
        $this->db->limit(1);
        $winner = $this->db->get('bids')->row();

        if (!$winner) return FALSE;

        // Wrap all writes in a transaction for atomicity
        $this->db->trans_start();

        // Mark the winner
        $this->db->where('id', $winner->id);
        $this->db->update('bids', array('status' => 'won'));

        // Mark all other bids as lost
        $this->db->where('bid_date', $bid_date);
        $this->db->where('id !=', $winner->id);
        $this->db->where('status', 'pending');
        $this->db->update('bids', array('status' => 'lost'));

        // Create featured alumni record — bid_date IS the featured date
        $this->db->insert('featured_alumni', array(
            'bid_id'        => $winner->id,
            'featured_date' => $bid_date
        ));

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return FALSE;
        }

        return array(
            'bid'           => $winner,
            'featured_date' => $bid_date
        );
    }

    /**
     * Get today's featured alumnus
     *
     * @return object|null Featured alumni data or null
     */
    public function get_featured_today()
    {
        $today = date('Y-m-d');

        $this->db->select('featured_alumni.*, bids.alumni_id, alumni.first_name, alumni.last_name, alumni.bio, alumni.linkedin_url, alumni.profile_image, alumni.email, bids.amount as winning_bid');
        $this->db->from('featured_alumni');
        $this->db->join('bids', 'bids.id = featured_alumni.bid_id');
        $this->db->join('alumni', 'alumni.id = bids.alumni_id');
        $this->db->where('featured_alumni.featured_date', $today);
        $this->db->limit(1);

        return $this->db->get()->row();
    }

    /**
     * Get recent featured alumni entries for API listing.
     *
     * @param int $limit Maximum rows to return
     * @return array
     */
    public function get_recent_featured($limit = 30)
    {
        $this->db->select('featured_alumni.*, bids.alumni_id, alumni.first_name, alumni.last_name, alumni.linkedin_url, alumni.profile_image');
        $this->db->from('featured_alumni');
        $this->db->join('bids', 'bids.id = featured_alumni.bid_id');
        $this->db->join('alumni', 'alumni.id = bids.alumni_id');
        $this->db->order_by('featured_alumni.featured_date', 'DESC');
        $this->db->limit((int)$limit);
        return $this->db->get()->result();
    }

    /**
     * Get a featured alumni entry by date.
     *
     * @param string $featured_date
     * @return object|null
     */
    public function get_featured_by_date($featured_date)
    {
        $this->db->select('featured_alumni.*, bids.alumni_id, alumni.first_name, alumni.last_name, alumni.bio, alumni.linkedin_url, alumni.profile_image');
        $this->db->from('featured_alumni');
        $this->db->join('bids', 'bids.id = featured_alumni.bid_id');
        $this->db->join('alumni', 'alumni.id = bids.alumni_id');
        $this->db->where('featured_alumni.featured_date', $featured_date);
        return $this->db->get()->row();
    }

    /**
     * List featured alumni with filtering and pagination.
     *
     * @param array $options
     * @return array
     */
    public function get_featured_collection($options = array())
    {
        $this->db->select('featured_alumni.featured_date, bids.alumni_id, featured_alumni.bid_id, alumni.first_name, alumni.last_name, alumni.bio, alumni.linkedin_url, alumni.profile_image');
        $this->db->from('featured_alumni');
        $this->db->join('bids', 'bids.id = featured_alumni.bid_id');
        $this->db->join('alumni', 'alumni.id = bids.alumni_id');

        if (!empty($options['featured_date'])) {
            $this->db->where('featured_alumni.featured_date', $options['featured_date']);
        }

        $direction = isset($options['direction']) && strtoupper($options['direction']) === 'ASC' ? 'ASC' : 'DESC';
        $this->db->order_by('featured_alumni.featured_date', $direction);

        if (isset($options['limit'])) {
            $limit = max(1, min(100, (int)$options['limit']));
            $offset = isset($options['offset']) ? max(0, (int)$options['offset']) : 0;
            $this->db->limit($limit, $offset);
        }

        return $this->db->get()->result();
    }

    /**
     * Count featured alumni entries for a filtered collection.
     *
     * @param array $options
     * @return int
     */
    public function count_featured_collection($options = array())
    {
        $this->db->from('featured_alumni');

        if (!empty($options['featured_date'])) {
            $this->db->where('featured_date', $options['featured_date']);
        }

        return (int)$this->db->count_all_results();
    }

    /**
     * Get bid history for an alumni
     *
     * @param int $alumni_id Alumni ID
     * @return array List of bids
     */
    public function get_alumni_bids($alumni_id)
    {
        $this->db->where('alumni_id', $alumni_id);
        $this->db->order_by('bid_date', 'DESC');
        return $this->db->get('bids')->result();
    }

    /**
     * Get all pending bids for a specific date
     *
     * @param string $bid_date Date (YYYY-MM-DD)
     * @return array List of bids
     */
    public function get_pending_bids($bid_date)
    {
        $this->db->where('bid_date', $bid_date);
        $this->db->where('status', 'pending');
        $this->db->order_by('amount', 'DESC');
        return $this->db->get('bids')->result();
    }
}

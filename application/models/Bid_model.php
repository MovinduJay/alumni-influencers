<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bid_model extends CI_Model
{
    public function __construct()
    {
        $this->load->database();
    }

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

    public function get_sponsorships($alumni_id)
    {
        $this->db->where('alumni_id', $alumni_id);
        $this->db->order_by('created_at', 'DESC');
        return $this->db->get('sponsorships')->result();
    }

    public function get_sponsorship($id, $alumni_id)
    {
        return $this->db->get_where('sponsorships', array(
            'id' => $id,
            'alumni_id' => $alumni_id
        ))->row();
    }

    public function add_sponsorship($data)
    {
        $this->db->insert('sponsorships', $data);
        return $this->db->insert_id();
    }

    public function update_sponsorship($id, $alumni_id, $data)
    {
        $this->db->where('id', $id);
        $this->db->where('alumni_id', $alumni_id);
        return $this->db->update('sponsorships', $data);
    }

    public function delete_sponsorship($id, $alumni_id)
    {
        $this->db->where('id', $id);
        $this->db->where('alumni_id', $alumni_id);
        return $this->db->delete('sponsorships');
    }

    public function get_accepted_sponsorship_total($alumni_id)
    {
        $this->db->select_sum('amount_offered');
        $row = $this->db->get_where('sponsorships', array(
            'alumni_id' => $alumni_id,
            'status' => 'accepted'
        ))->row();

        return $row && $row->amount_offered !== NULL ? (float) $row->amount_offered : 0.0;
    }

    public function get_event_participations($alumni_id)
    {
        $this->db->where('alumni_id', $alumni_id);
        $this->db->order_by('event_date', 'DESC');
        return $this->db->get('event_participations')->result();
    }

    public function add_event_participation($data)
    {
        $this->db->insert('event_participations', $data);
        return $this->db->insert_id();
    }

    public function delete_event_participation($id, $alumni_id)
    {
        $this->db->where('id', $id);
        $this->db->where('alumni_id', $alumni_id);
        return $this->db->delete('event_participations');
    }

    public function update_bid($bid_id, $new_amount)
    {
        $this->db->where('id', $bid_id);
        return $this->db->update('bids', array(
            'amount' => $new_amount
        ));
    }

    public function get_bid($bid_id)
    {
        return $this->db->get_where('bids', array('id' => $bid_id))->row();
    }

    public function get_alumni_bid_for_date($alumni_id, $bid_date)
    {
        return $this->db->get_where('bids', array(
            'alumni_id' => $alumni_id,
            'bid_date'  => $bid_date
        ))->row();
    }

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

    public function get_max_monthly_wins($alumni_id, $month = NULL)
    {
        $base_limit = (int)(getenv('MAX_FEATURES_PER_MONTH') ?: 3);
        if ($this->has_event_participation($alumni_id, $month)) {
            return $base_limit + 1;
        }
        return $base_limit;
    }

    public function can_bid($alumni_id, $month = NULL)
    {
        $wins = $this->get_monthly_wins($alumni_id, $month);
        $max = $this->get_max_monthly_wins($alumni_id, $month);
        return $wins < $max;
    }

    public function select_winner($bid_date)
    {
        // Do not run the daily selection twice for the same date.
        $existing = $this->db->get_where('featured_alumni', array('featured_date' => $bid_date))->row();
        if ($existing) return FALSE;

        $month = date('Y-m', strtotime($bid_date));

        // Highest bid wins, unless that alumni has already used the monthly limit.
        $this->db->where('bid_date', $bid_date);
        $this->db->where('status', 'pending');
        $this->db->order_by('amount', 'DESC');
        $pending_bids = $this->db->get('bids')->result();

        $winner = NULL;
        foreach ($pending_bids as $candidate) {
            if ($this->can_bid($candidate->alumni_id, $month)) {
                $winner = $candidate;
                break;
            }
        }

        if (!$winner) return FALSE;

        // These updates must succeed or fail together.
        $this->db->trans_start();

        // Store the selected bid as the winner.
        $this->db->where('id', $winner->id);
        $this->db->update('bids', array('status' => 'won'));

        // All other pending bids for that date lose automatically.
        $this->db->where('bid_date', $bid_date);
        $this->db->where('id !=', $winner->id);
        $this->db->where('status', 'pending');
        $this->db->update('bids', array('status' => 'lost'));

        // This table is what the home page and API use for Alumni of the Day.
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

    public function get_featured_today()
    {
        $today = date('Y-m-d');

        $this->db->select('featured_alumni.*, bids.alumni_id, users.first_name, users.last_name, alumni.bio, alumni.linkedin_url, alumni.profile_image, users.email, bids.amount as winning_bid');
        $this->db->from('featured_alumni');
        $this->db->join('bids', 'bids.id = featured_alumni.bid_id');
        $this->db->join('alumni', 'alumni.id = bids.alumni_id');
        $this->db->join('users', 'users.id = alumni.id');
        $this->db->where('featured_alumni.featured_date', $today);
        $this->db->limit(1);

        return $this->db->get()->row();
    }

    public function get_recent_featured($limit = 30)
    {
        $this->db->select('featured_alumni.*, bids.alumni_id, users.first_name, users.last_name, alumni.linkedin_url, alumni.profile_image');
        $this->db->from('featured_alumni');
        $this->db->join('bids', 'bids.id = featured_alumni.bid_id');
        $this->db->join('alumni', 'alumni.id = bids.alumni_id');
        $this->db->join('users', 'users.id = alumni.id');
        $this->db->order_by('featured_alumni.featured_date', 'DESC');
        $this->db->limit((int)$limit);
        return $this->db->get()->result();
    }

    public function get_featured_last_days($days = 30)
    {
        $days = max(1, min(365, (int) $days));
        $from = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
        $to = date('Y-m-d');

        $this->db->select('featured_alumni.*, bids.alumni_id, bids.amount as winning_bid, users.first_name, users.last_name, users.email, alumni.linkedin_url, alumni.profile_image');
        $this->db->from('featured_alumni');
        $this->db->join('bids', 'bids.id = featured_alumni.bid_id');
        $this->db->join('alumni', 'alumni.id = bids.alumni_id');
        $this->db->join('users', 'users.id = alumni.id');
        $this->db->where('featured_alumni.featured_date >=', $from);
        $this->db->where('featured_alumni.featured_date <=', $to);
        $this->db->order_by('featured_alumni.featured_date', 'DESC');

        return $this->db->get()->result();
    }

    public function get_featured_by_date($featured_date)
    {
        $this->db->select('featured_alumni.*, bids.alumni_id, users.first_name, users.last_name, alumni.bio, alumni.linkedin_url, alumni.profile_image');
        $this->db->from('featured_alumni');
        $this->db->join('bids', 'bids.id = featured_alumni.bid_id');
        $this->db->join('alumni', 'alumni.id = bids.alumni_id');
        $this->db->join('users', 'users.id = alumni.id');
        $this->db->where('featured_alumni.featured_date', $featured_date);
        return $this->db->get()->row();
    }

    public function get_featured_collection($options = array())
    {
        $this->db->select('featured_alumni.featured_date, bids.alumni_id, featured_alumni.bid_id, users.first_name, users.last_name, alumni.bio, alumni.linkedin_url, alumni.profile_image');
        $this->db->from('featured_alumni');
        $this->db->join('bids', 'bids.id = featured_alumni.bid_id');
        $this->db->join('alumni', 'alumni.id = bids.alumni_id');
        $this->db->join('users', 'users.id = alumni.id');

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

    public function count_featured_collection($options = array())
    {
        $this->db->from('featured_alumni');

        if (!empty($options['featured_date'])) {
            $this->db->where('featured_date', $options['featured_date']);
        }

        return (int)$this->db->count_all_results();
    }

    public function get_alumni_bids($alumni_id)
    {
        $this->db->where('alumni_id', $alumni_id);
        $this->db->order_by('bid_date', 'DESC');
        return $this->db->get('bids')->result();
    }

    public function get_pending_bids($bid_date)
    {
        $this->db->where('bid_date', $bid_date);
        $this->db->where('status', 'pending');
        $this->db->order_by('amount', 'DESC');
        return $this->db->get('bids')->result();
    }
}



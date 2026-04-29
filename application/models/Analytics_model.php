<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Analytics_model extends CI_Model
{
    public function __construct()
    {
        $this->load->database();
    }

    public function filter_options()
    {
        return array(
            'programmes' => $this->db->order_by('name', 'ASC')->get('programmes')->result(),
            'industry_sectors' => $this->db->order_by('name', 'ASC')->get('industry_sectors')->result(),
            'skills' => $this->db->order_by('name', 'ASC')->get('skills')->result()
        );
    }

    public function normalize_filters($input)
    {
        // Clean filter input once so the dashboard, API and exports use the same values.
        $filters = array(
            'programme_id' => $this->positive_int(isset($input['programme_id']) ? $input['programme_id'] : NULL),
            'industry_sector_id' => $this->positive_int(isset($input['industry_sector_id']) ? $input['industry_sector_id'] : NULL),
            'graduation_from' => $this->valid_date(isset($input['graduation_from']) ? $input['graduation_from'] : NULL),
            'graduation_to' => $this->valid_date(isset($input['graduation_to']) ? $input['graduation_to'] : NULL),
            'skill_id' => $this->positive_int(isset($input['skill_id']) ? $input['skill_id'] : NULL),
            'keyword' => $this->plain_keyword(isset($input['keyword']) ? $input['keyword'] : '')
        );

        if ($filters['graduation_from'] && $filters['graduation_to']
            && strtotime($filters['graduation_from']) > strtotime($filters['graduation_to'])) {
            // If the dates are entered the wrong way round, still return a useful result.
            $swap = $filters['graduation_from'];
            $filters['graduation_from'] = $filters['graduation_to'];
            $filters['graduation_to'] = $swap;
        }

        return $filters;
    }

    public function dashboard_payload($filters)
    {
        return array(
            'filters' => $filters,
            'summary' => $this->summary($filters),
            'charts' => array(
                'programme_distribution' => $this->programme_distribution($filters),
                'industry_distribution' => $this->industry_distribution($filters),
                'graduation_trend' => $this->graduation_trend($filters),
                'skill_demand' => $this->skill_demand($filters),
                'curriculum_gap' => $this->curriculum_gap($filters),
                'professional_development' => $this->professional_development($filters),
                'sector_programme_matrix' => $this->sector_programme_matrix($filters),
                'bidding_outcomes' => $this->bidding_outcomes($filters)
            ),
            'insights' => $this->insights($filters),
            'alumni' => $this->alumni_rows($filters, 50)
        );
    }

    public function summary($filters)
    {
        $rows = $this->base_query($filters)
            ->select('COUNT(DISTINCT alumni.id) AS alumni_count', FALSE)
            ->select('COUNT(DISTINCT alumni_outcomes.programme_id) AS programme_count', FALSE)
            ->select('COUNT(DISTINCT alumni_outcomes.industry_sector_id) AS sector_count', FALSE)
            ->select('COUNT(DISTINCT alumni_skills.skill_id) AS skill_count', FALSE)
            ->get()->row();

        return array(
            'alumni_count' => (int) ($rows ? $rows->alumni_count : 0),
            'programme_count' => (int) ($rows ? $rows->programme_count : 0),
            'sector_count' => (int) ($rows ? $rows->sector_count : 0),
            'skill_count' => (int) ($rows ? $rows->skill_count : 0)
        );
    }

    public function alumni_rows($filters, $limit = 500)
    {
        return $this->base_query($filters)
            ->select('alumni.id, users.first_name, users.last_name, users.email, programmes.name AS programme, industry_sectors.name AS industry_sector, alumni_outcomes.graduation_date, alumni_outcomes.current_role, alumni_outcomes.current_company')
            ->group_by('alumni.id')
            ->order_by('alumni_outcomes.graduation_date', 'DESC')
            ->limit(max(1, min(1000, (int) $limit)))
            ->get()->result();
    }

    public function programme_distribution($filters)
    {
        return $this->base_query($filters)
            ->select('programmes.name AS label, COUNT(DISTINCT alumni.id) AS value', FALSE)
            ->group_by('programmes.id')
            ->order_by('value', 'DESC')
            ->get()->result();
    }

    public function industry_distribution($filters)
    {
        return $this->base_query($filters)
            ->select('industry_sectors.name AS label, COUNT(DISTINCT alumni.id) AS value', FALSE)
            ->group_by('industry_sectors.id')
            ->order_by('value', 'DESC')
            ->get()->result();
    }

    public function graduation_trend($filters)
    {
        return $this->base_query($filters)
            ->select('YEAR(alumni_outcomes.graduation_date) AS label, COUNT(DISTINCT alumni.id) AS value', FALSE)
            ->group_by('YEAR(alumni_outcomes.graduation_date)')
            ->order_by('label', 'ASC')
            ->get()->result();
    }

    public function skill_demand($filters)
    {
        return $this->base_query($filters)
            ->select('skills.name AS label, skills.category, skills.curriculum_status, COUNT(DISTINCT alumni_skills.alumni_id) AS value', FALSE)
            ->where('skills.id IS NOT NULL', NULL, FALSE)
            ->group_by('skills.id')
            ->order_by('value', 'DESC')
            ->limit(12)
            ->get()->result();
    }

    public function curriculum_gap($filters)
    {
        return $this->base_query($filters)
            ->select('skills.curriculum_status AS label, COUNT(DISTINCT alumni_skills.alumni_id, alumni_skills.skill_id) AS value', FALSE)
            ->where('skills.id IS NOT NULL', NULL, FALSE)
            ->group_by('skills.curriculum_status')
            ->order_by('value', 'DESC')
            ->get()->result();
    }

    public function professional_development($filters)
    {
        return $this->base_query($filters)
            ->select('alumni_skills.source_type AS label, COUNT(*) AS value', FALSE)
            ->where('alumni_skills.skill_id IS NOT NULL', NULL, FALSE)
            ->group_by('alumni_skills.source_type')
            ->order_by('value', 'DESC')
            ->get()->result();
    }

    public function sector_programme_matrix($filters)
    {
        return $this->base_query($filters)
            ->select('programmes.name AS programme, industry_sectors.name AS sector, COUNT(DISTINCT alumni.id) AS value', FALSE)
            ->group_by(array('programmes.id', 'industry_sectors.id'))
            ->order_by('programmes.name', 'ASC')
            ->get()->result();
    }

    public function bidding_outcomes($filters)
    {
        return $this->base_query($filters)
            ->select('bids.status AS label, COUNT(bids.id) AS value', FALSE)
            ->join('bids', 'bids.alumni_id = alumni.id', 'left')
            ->where('bids.id IS NOT NULL', NULL, FALSE)
            ->group_by('bids.status')
            ->order_by('value', 'DESC')
            ->get()->result();
    }

    public function insights($filters)
    {
        $summary = $this->summary($filters);
        $skills = $this->skill_demand($filters);
        $insights = array();

        foreach ($skills as $skill) {
            $coverage = $summary['alumni_count'] > 0 ? ((int) $skill->value / $summary['alumni_count']) * 100 : 0;
            $severity = $this->severity($coverage, $skill->curriculum_status);
            if ($severity !== 'covered') {
                $insights[] = array(
                    'label' => $skill->label,
                    'category' => $skill->category,
                    'coverage_percent' => round($coverage, 1),
                    'curriculum_status' => $skill->curriculum_status,
                    'severity' => $severity
                );
            }
        }

        return $insights;
    }

    public function get_presets($admin_id)
    {
        return $this->db->where('admin_id', (int) $admin_id)
            ->order_by('updated_at', 'DESC')
            ->get('analytics_filter_presets')->result();
    }

    public function save_preset($admin_id, $name, $filters)
    {
        $name = trim(strip_tags((string) $name));
        if ($name === '' || strlen($name) > 100) {
            return FALSE;
        }

        $data = array(
            'admin_id' => (int) $admin_id,
            'name' => $name,
            'filters_json' => json_encode($filters)
        );

        $existing = $this->db->where('admin_id', (int) $admin_id)->where('name', $name)
            ->get('analytics_filter_presets')->row();

        if ($existing) {
            $this->db->where('id', $existing->id);
            return $this->db->update('analytics_filter_presets', array(
                'filters_json' => $data['filters_json']
            ));
        }

        return $this->db->insert('analytics_filter_presets', $data);
    }

    protected function base_query($filters)
    {
        // Common query used by all analytics charts to avoid different filter results.
        $this->db->reset_query();
        $this->db->from('alumni');
        $this->db->join('users', 'users.id = alumni.id', 'inner');
        $this->db->join('alumni_outcomes', 'alumni_outcomes.alumni_id = alumni.id', 'inner');
        $this->db->join('programmes', 'programmes.id = alumni_outcomes.programme_id', 'inner');
        $this->db->join('industry_sectors', 'industry_sectors.id = alumni_outcomes.industry_sector_id', 'inner');
        $this->db->join('alumni_skills', 'alumni_skills.alumni_id = alumni.id', 'left');
        $this->db->join('skills', 'skills.id = alumni_skills.skill_id', 'left');
        $this->db->where('users.user_type', 'alumni');
        $this->db->where('users.is_active', 1);
        $this->db->where('users.email_verified', 1);

        if (!empty($filters['programme_id'])) {
            $this->db->where('alumni_outcomes.programme_id', (int) $filters['programme_id']);
        }
        if (!empty($filters['industry_sector_id'])) {
            $this->db->where('alumni_outcomes.industry_sector_id', (int) $filters['industry_sector_id']);
        }
        if (!empty($filters['graduation_from'])) {
            $this->db->where('alumni_outcomes.graduation_date >=', $filters['graduation_from']);
        }
        if (!empty($filters['graduation_to'])) {
            $this->db->where('alumni_outcomes.graduation_date <=', $filters['graduation_to']);
        }
        if (!empty($filters['skill_id'])) {
            $this->db->where('alumni_skills.skill_id', (int) $filters['skill_id']);
        }
        if (!empty($filters['keyword'])) {
            $this->db->group_start();
            $this->db->like('users.first_name', $filters['keyword']);
            $this->db->or_like('users.last_name', $filters['keyword']);
            $this->db->or_like('alumni_outcomes.current_role', $filters['keyword']);
            $this->db->or_like('alumni_outcomes.current_company', $filters['keyword']);
            $this->db->or_like('skills.name', $filters['keyword']);
            $this->db->group_end();
        }

        return $this->db;
    }

    protected function severity($coverage, $status)
    {
        if ($status === 'missing' && $coverage >= 50) {
            return 'critical';
        }
        if (($status === 'missing' || $status === 'partial') && $coverage >= 25) {
            return 'significant';
        }
        if ($status === 'missing' || $status === 'partial') {
            return 'emerging';
        }
        return 'covered';
    }

    protected function positive_int($value)
    {
        $value = (int) $value;
        return $value > 0 ? $value : NULL;
    }

    protected function valid_date($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return NULL;
        }
        $dt = DateTime::createFromFormat('Y-m-d', $value);
        return $dt && $dt->format('Y-m-d') === $value ? $value : NULL;
    }

    protected function plain_keyword($value)
    {
        $value = trim(strip_tags((string) $value));
        $value = preg_replace('/[\x00-\x1F\x7F]/', '', $value);
        return substr($value, 0, 80);
    }
}



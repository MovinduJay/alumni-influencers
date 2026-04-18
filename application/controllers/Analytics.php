<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Analytics Controller
 *
 * Hosts the University Analytics Dashboard client and report/export actions.
 * The chart data itself is loaded from bearer-token protected API endpoints.
 */
class Analytics extends MY_Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Analytics_model');
        $this->load->helper('download');
    }

    public function index()
    {
        $data = array(
            'title' => 'University Analytics',
            'options' => $this->Analytics_model->filter_options(),
            'presets' => $this->Analytics_model->get_presets((int) $this->session->userdata('alumni_id')),
            'analytics_token' => getenv('ANALYTICS_DASHBOARD_TOKEN') ?: 'test-bearer-token-12345',
            'csrf_name' => $this->security->get_csrf_token_name(),
            'csrf_hash' => $this->security->get_csrf_hash()
        );

        $this->load->view('layouts/header', $data);
        $this->load->view('analytics/index', $data);
        $this->load->view('layouts/footer');
    }

    public function presets()
    {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'status' => 'success',
                'presets' => $this->Analytics_model->get_presets((int) $this->session->userdata('alumni_id'))
            )));
    }

    public function save_preset()
    {
        if ($this->input->method() !== 'post') {
            show_error('Method not allowed.', 405);
            return;
        }

        $this->form_validation->set_rules('name', 'Preset Name', 'required|trim|max_length[100]');
        if ($this->form_validation->run() === FALSE) {
            $this->output
                ->set_status_header(422)
                ->set_content_type('application/json')
                ->set_output(json_encode(array(
                    'status' => 'error',
                    'message' => strip_tags(validation_errors()),
                    'csrf_hash' => $this->security->get_csrf_hash()
                )));
            return;
        }

        $payload = array(
            'programme_id' => $this->input->post('programme_id', TRUE),
            'industry_sector_id' => $this->input->post('industry_sector_id', TRUE),
            'graduation_from' => $this->input->post('graduation_from', TRUE),
            'graduation_to' => $this->input->post('graduation_to', TRUE),
            'skill_id' => $this->input->post('skill_id', TRUE),
            'keyword' => $this->input->post('keyword', TRUE)
        );
        $filters = $this->Analytics_model->normalize_filters($payload);
        $saved = $this->Analytics_model->save_preset(
            (int) $this->session->userdata('alumni_id'),
            $this->input->post('name', TRUE),
            $filters
        );

        $status = $saved ? 200 : 422;
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'status' => $saved ? 'success' : 'error',
                'message' => $saved ? 'Preset saved.' : 'Preset name is required and must be 100 characters or fewer.',
                'csrf_hash' => $this->security->get_csrf_hash()
            )));
    }

    public function export_csv()
    {
        $filters = $this->Analytics_model->normalize_filters($this->input->get(NULL, TRUE));
        $rows = $this->Analytics_model->alumni_rows($filters, 1000);

        $filename = 'alumni-analytics-' . date('Ymd-His') . '.csv';
        $out = fopen('php://temp', 'r+');

        fputcsv($out, array('ID', 'First name', 'Last name', 'Email', 'Programme', 'Industry sector', 'Graduation date', 'Current role', 'Current company'));
        foreach ($rows as $row) {
            fputcsv($out, array(
                $row->id,
                $row->first_name,
                $row->last_name,
                $row->email,
                $row->programme,
                $row->industry_sector,
                $row->graduation_date,
                $row->current_role,
                $row->current_company
            ));
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        force_download($filename, $csv);
    }

    public function export_pdf()
    {
        $filters = $this->Analytics_model->normalize_filters($this->input->get(NULL, TRUE));
        $payload = $this->Analytics_model->dashboard_payload($filters);
        $include_summary = $this->input->get('metric_summary', TRUE) !== '0';
        $include_insights = $this->input->get('metric_insights', TRUE) !== '0';
        $include_alumni = $this->input->get('metric_alumni', TRUE) === '1';

        $lines = array(
            'University Analytics Report',
            'Generated: ' . date('Y-m-d H:i:s')
        );

        if ($include_summary) {
            $lines[] = '';
            $lines[] = 'Summary';
            $lines[] = 'Filtered alumni: ' . $payload['summary']['alumni_count'];
            $lines[] = 'Programmes: ' . $payload['summary']['programme_count'];
            $lines[] = 'Industry sectors: ' . $payload['summary']['sector_count'];
            $lines[] = 'Observed skills: ' . $payload['summary']['skill_count'];
        }

        if ($include_insights) {
            $lines[] = '';
            $lines[] = 'Curriculum gap insights';
            foreach ($payload['insights'] as $insight) {
                $lines[] = strtoupper($insight['severity']) . ' - ' . $insight['label'] . ' (' . $insight['coverage_percent'] . '% of filtered alumni)';
            }
            if (count($payload['insights']) === 0) {
                $lines[] = 'No curriculum gap insights found for the selected filters.';
            }
        }

        if ($include_alumni) {
            $lines[] = '';
            $lines[] = 'Alumni sample';
            foreach (array_slice($payload['alumni'], 0, 10) as $row) {
                $lines[] = $row->first_name . ' ' . $row->last_name . ' - ' . $row->programme . ' - ' . $row->industry_sector;
            }
        }

        $pdf = $this->simple_pdf($lines);
        $this->output->set_header('Content-Type: application/pdf');
        $this->output->set_header('Content-Disposition: attachment; filename="alumni-analytics-report-' . date('Ymd-His') . '.pdf"');
        $this->output->set_output($pdf);
    }

    /**
     * Generate a compact text-only PDF without external dependencies.
     */
    private function simple_pdf($lines)
    {
        $content = "BT\n/F1 12 Tf\n50 780 Td\n";
        foreach ($lines as $index => $line) {
            $safe = str_replace(array('\\', '(', ')'), array('\\\\', '\\(', '\\)'), (string) $line);
            if ($index > 0) {
                $content .= "0 -18 Td\n";
            }
            $content .= '(' . $safe . ") Tj\n";
        }
        $content .= "ET";

        $objects = array();
        $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objects[] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
        $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>";
        $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
        $objects[] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "\nendstream";

        $pdf = "%PDF-1.4\n";
        $offsets = array(0);
        foreach ($objects as $i => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1) . " 0 obj\n" . $object . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";

        return $pdf;
    }
}

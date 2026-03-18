<link rel="stylesheet" href="<?php echo base_url('assets/css/analytics-dashboard.css') . '?v=' . filemtime(FCPATH . 'assets/css/analytics-dashboard.css'); ?>">

<div id="analytics-dashboard"
     data-api-base="<?php echo htmlspecialchars(base_url('api/v1'), ENT_QUOTES, 'UTF-8'); ?>"
     data-token="<?php echo htmlspecialchars($analytics_token, ENT_QUOTES, 'UTF-8'); ?>"
     data-csv-url="<?php echo htmlspecialchars(site_url('analytics/export/csv'), ENT_QUOTES, 'UTF-8'); ?>"
     data-pdf-url="<?php echo htmlspecialchars(site_url('analytics/export/pdf'), ENT_QUOTES, 'UTF-8'); ?>"
     data-save-preset-url="<?php echo htmlspecialchars(site_url('analytics/presets/save'), ENT_QUOTES, 'UTF-8'); ?>"
     data-csrf-name="<?php echo htmlspecialchars($csrf_name, ENT_QUOTES, 'UTF-8'); ?>"
     data-csrf-hash="<?php echo htmlspecialchars($csrf_hash, ENT_QUOTES, 'UTF-8'); ?>">

    <div class="analytics-shell">
        <aside class="analytics-sidebar">
            <div class="sidebar-title">University Analytics</div>
            <a href="#overview" class="active"><i class="fas fa-gauge-high"></i> Overview</a>
            <a href="#charts"><i class="fas fa-chart-line"></i> Charts</a>
            <a href="#insights"><i class="fas fa-triangle-exclamation"></i> Gaps</a>
            <a href="#alumni-table"><i class="fas fa-users"></i> Alumni</a>
            <a href="#exports"><i class="fas fa-file-export"></i> Reports</a>
        </aside>

        <main class="analytics-main">
            <section id="overview" class="analytics-header">
                <div>
                    <h1>Graduate Intelligence Dashboard</h1>
                    <p>Live alumni outcome signals for curriculum planning, skills-gap detection, and industry trend monitoring.</p>
                </div>
                <div class="header-actions" id="exports">
                    <button type="button" class="btn btn-outline-secondary" id="download-csv"><i class="fas fa-file-csv"></i> CSV</button>
                    <button type="button" class="btn btn-outline-secondary" id="download-pdf"><i class="fas fa-file-pdf"></i> PDF</button>
                    <button type="button" class="btn btn-primary" id="save-preset"><i class="fas fa-bookmark"></i> Save Preset</button>
                </div>
            </section>

            <section class="filter-panel">
                <form id="analytics-filters" autocomplete="off">
                    <div class="filter-grid">
                        <label>Programme
                            <select name="programme_id" class="form-select">
                                <option value="">All programmes</option>
                                <?php foreach ($options['programmes'] as $programme): ?>
                                    <option value="<?php echo (int) $programme->id; ?>"><?php echo htmlspecialchars($programme->name, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Industry Sector
                            <select name="industry_sector_id" class="form-select">
                                <option value="">All sectors</option>
                                <?php foreach ($options['industry_sectors'] as $sector): ?>
                                    <option value="<?php echo (int) $sector->id; ?>"><?php echo htmlspecialchars($sector->name, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Skill
                            <select name="skill_id" class="form-select">
                                <option value="">All skills</option>
                                <?php foreach ($options['skills'] as $skill): ?>
                                    <option value="<?php echo (int) $skill->id; ?>"><?php echo htmlspecialchars($skill->name, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Graduated From
                            <input type="date" name="graduation_from" class="form-control">
                        </label>
                        <label>Graduated To
                            <input type="date" name="graduation_to" class="form-control">
                        </label>
                        <label>Keyword
                            <input type="search" name="keyword" class="form-control" maxlength="80" placeholder="Role, company, skill">
                        </label>
                    </div>
                    <div class="filter-actions">
                        <select id="preset-select" class="form-select">
                            <option value="">Load saved preset</option>
                            <?php foreach ($presets as $preset): ?>
                                <option value="<?php echo htmlspecialchars($preset->filters_json, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($preset->name, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Apply</button>
                        <button type="button" class="btn btn-outline-secondary" id="reset-filters"><i class="fas fa-rotate-left"></i> Reset</button>
                    </div>
                    <div class="report-options">
                        <span>PDF sections</span>
                        <input type="hidden" name="metric_summary" value="0">
                        <label><input type="checkbox" name="metric_summary" value="1" checked> Summary</label>
                        <input type="hidden" name="metric_insights" value="0">
                        <label><input type="checkbox" name="metric_insights" value="1" checked> Insights</label>
                        <input type="hidden" name="metric_alumni" value="0">
                        <label><input type="checkbox" name="metric_alumni" value="1"> Alumni sample</label>
                    </div>
                </form>
            </section>

            <div id="loading-state" class="loading-state">
                <div class="spinner-border text-primary" role="status"></div>
                <span>Loading analytics from the API...</span>
            </div>
            <div id="error-state" class="alert alert-danger d-none"></div>

            <section class="metric-grid">
                <article><span>Filtered Alumni</span><strong id="metric-alumni">0</strong></article>
                <article><span>Programmes</span><strong id="metric-programmes">0</strong></article>
                <article><span>Industry Sectors</span><strong id="metric-sectors">0</strong></article>
                <article><span>Observed Skills</span><strong id="metric-skills">0</strong></article>
            </section>

            <section id="charts" class="chart-grid">
                <?php
                $charts = array(
                    'programmeChart' => 'Alumni by Programme',
                    'industryChart' => 'Industry Sector Distribution',
                    'graduationChart' => 'Graduation Year Trend',
                    'skillChart' => 'Top Skills in Demand',
                    'gapChart' => 'Curriculum Gap Severity',
                    'developmentChart' => 'Professional Development Sources',
                    'matrixChart' => 'Programme to Sector Pathways',
                    'biddingChart' => 'Featured Placement Outcomes'
                );
                foreach ($charts as $id => $title):
                ?>
                <article class="chart-card">
                    <div class="chart-card-header">
                        <h2><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h2>
                        <button type="button" class="btn btn-sm btn-outline-secondary chart-download" data-chart="<?php echo $id; ?>" title="Download chart image">
                            <i class="fas fa-image"></i>
                        </button>
                    </div>
                    <div class="chart-frame"><canvas id="<?php echo $id; ?>"></canvas></div>
                </article>
                <?php endforeach; ?>
            </section>

            <section id="insights" class="insight-panel">
                <div class="section-heading">
                    <h2>Curriculum Gap Insights</h2>
                    <span>Critical, significant, and emerging signals are calculated from filtered alumni evidence.</span>
                </div>
                <div id="insight-list" class="insight-list"></div>
            </section>

            <section id="alumni-table" class="table-panel">
                <div class="section-heading">
                    <h2>Filtered Alumni</h2>
                    <span>Programme, graduation date, and industry sector view.</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Programme</th>
                                <th>Graduation</th>
                                <th>Industry</th>
                                <th>Current Role</th>
                                <th>Company</th>
                            </tr>
                        </thead>
                        <tbody id="alumni-rows"></tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <div class="modal fade" id="save-preset-modal" tabindex="-1" aria-labelledby="save-preset-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="save-preset-form">
                    <div class="modal-header">
                        <h5 class="modal-title" id="save-preset-modal-title">Save Preset</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <label for="preset-name" class="form-label">Preset name</label>
                        <input type="text" id="preset-name" class="form-control" maxlength="80" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-bookmark"></i> Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js" defer></script>
<script src="<?php echo base_url('assets/vendor/jspdf.umd.min.js'); ?>" defer></script>
<script src="<?php echo base_url('assets/js/analytics-dashboard.js') . '?v=' . filemtime(FCPATH . 'assets/js/analytics-dashboard.js'); ?>" defer></script>

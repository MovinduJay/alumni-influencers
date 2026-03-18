<?php
    $client_count = count($clients);
    $active_clients = 0;
    $revoked_clients = 0;
    foreach ($clients as $client) {
        if ((int) $client->is_active === 1) {
            $active_clients++;
        } else {
            $revoked_clients++;
        }
    }

    $client_stats = isset($stats['client_stats']) ? $stats['client_stats'] : array();
    $endpoint_stats = isset($stats['endpoint_stats']) ? $stats['endpoint_stats'] : array();
    $recent_access = isset($stats['recent_access']) ? $stats['recent_access'] : array();
    $total_requests = 0;
    foreach ($client_stats as $stat) {
        $total_requests += (int) $stat->total_requests;
    }
?>

<section class="admin-page-title">
    <div>
        <div class="admin-section-title">Dashboard</div>
        <h1>API access and usage monitoring</h1>
        <p>Manage API consumers, request activity, analytics, and documentation.</p>
    </div>
    <div class="admin-page-actions">
        <a href="<?php echo site_url('admin/api-clients'); ?>" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-key"></i> API Clients
        </a>
    </div>
</section>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card admin-kpi h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small text-uppercase">API Clients</div>
                        <div class="display-6 fw-semibold"><?php echo $client_count; ?></div>
                    </div>
                    <span class="kpi-icon"><i class="fas fa-plug"></i></span>
                </div>
                <div class="small text-muted"><?php echo $active_clients; ?> active, <?php echo $revoked_clients; ?> revoked</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card admin-kpi h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small text-uppercase">Total API Calls</div>
                        <div class="display-6 fw-semibold"><?php echo number_format($total_requests); ?></div>
                    </div>
                    <span class="kpi-icon"><i class="fas fa-signal"></i></span>
                </div>
                <div class="small text-muted">Across registered clients</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card admin-kpi h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small text-uppercase">Recent Activity</div>
                        <div class="display-6 fw-semibold"><?php echo count($recent_access); ?></div>
                    </div>
                    <span class="kpi-icon"><i class="fas fa-clock"></i></span>
                </div>
                <div class="small text-muted">Latest access log entries</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card admin-kpi h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase">Signed In Admin</div>
                <div class="h5 mt-2 mb-1">
                    <?php echo htmlspecialchars($this->session->userdata('first_name') . ' ' . $this->session->userdata('last_name'), ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <div class="small text-muted text-truncate">
                    <?php echo htmlspecialchars($this->session->userdata('email'), ENT_QUOTES, 'UTF-8'); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="card admin-panel">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <a class="quick-action" href="<?php echo site_url('analytics'); ?>">
                            <div class="card border mb-0 h-100">
                                <div class="card-body py-3">
                                    <strong><i class="fas fa-chart-line text-primary"></i> University Analytics</strong>
                                    <div class="small text-muted">Graduate outcomes, filters, reports, and exports.</div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a class="quick-action" href="<?php echo site_url('admin/api-clients'); ?>">
                            <div class="card border mb-0 h-100">
                                <div class="card-body py-3">
                                    <strong><i class="fas fa-key text-primary"></i> Register API Client</strong>
                                    <div class="small text-muted">Create scoped tokens for approved consumers.</div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a class="quick-action" href="<?php echo site_url('admin/featured-alumni'); ?>">
                            <div class="card border mb-0 h-100">
                                <div class="card-body py-3">
                                    <strong><i class="fas fa-star text-primary"></i> Alumni of the Day</strong>
                                    <div class="small text-muted">Review the last 30 days of featured alumni.</div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a class="quick-action" href="<?php echo site_url('api-docs'); ?>">
                            <div class="card border mb-0 h-100">
                                <div class="card-body py-3">
                                    <strong><i class="fas fa-book text-primary"></i> API Documentation</strong>
                                    <div class="small text-muted">Review endpoints, scopes, and payload contracts.</div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card admin-panel">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-users-gear"></i> Client Health</h5></div>
            <div class="card-body">
                <?php if (!empty($client_stats)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead><tr><th>Client</th><th>Scopes</th><th>Status</th><th class="text-end">Calls</th></tr></thead>
                            <tbody>
                                <?php foreach (array_slice($client_stats, 0, 6) as $client_stat): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($client_stat->client_name, ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><code><?php echo htmlspecialchars($client_stat->scope, ENT_QUOTES, 'UTF-8'); ?></code></td>
                                        <td><?php echo $client_stat->is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Revoked</span>'; ?></td>
                                        <td class="text-end"><strong><?php echo number_format((int) $client_stat->total_requests); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No API clients have usage data yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card admin-panel">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-route"></i> Endpoint Totals</h5></div>
            <div class="card-body">
                <?php if (!empty($endpoint_stats)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead><tr><th>Endpoint</th><th class="text-end">Calls</th></tr></thead>
                            <tbody>
                                <?php foreach (array_slice($endpoint_stats, 0, 6) as $endpoint): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($endpoint->endpoint, ENT_QUOTES, 'UTF-8'); ?></code></td>
                                        <td class="text-end"><strong><?php echo number_format((int) $endpoint->access_count); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No endpoint totals are available yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

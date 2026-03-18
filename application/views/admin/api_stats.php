<?php
    $client_stats = isset($stats['client_stats']) ? $stats['client_stats'] : array();
    $endpoint_stats = isset($stats['endpoint_stats']) ? $stats['endpoint_stats'] : array();
    $recent_access = isset($stats['recent_access']) ? $stats['recent_access'] : array();
    $total_requests = 0;
    $active_clients = 0;
    $revoked_clients = 0;
    foreach ($client_stats as $stat) {
        $total_requests += (int) $stat->total_requests;
        if ((int) $stat->is_active === 1) {
            $active_clients++;
        } else {
            $revoked_clients++;
        }
    }
?>

<section class="admin-hero">
    <div class="row align-items-center g-3">
        <div class="col-lg-8">
            <div class="admin-section-title text-white-50">API Usage</div>
            <h1 class="h2 mb-2">Request volume, endpoints, and client activity</h1>
            <p class="mb-0 text-white-50">
                This page tracks API consumption. University Analytics remains the separate graduate outcomes dashboard.
            </p>
        </div>
        <div class="col-lg-4 text-lg-end">
            <a href="<?php echo site_url('admin'); ?>" class="btn btn-outline-light me-2 mb-2">
                <i class="fas fa-shield-alt"></i> Dashboard
            </a>
            <a href="<?php echo site_url('admin/api-clients'); ?>" class="btn btn-light mb-2">
                <i class="fas fa-key"></i> API Clients
            </a>
        </div>
    </div>
</section>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card admin-kpi h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase">Total Requests</div>
                <div class="display-6 fw-semibold"><?php echo number_format($total_requests); ?></div>
                <div class="small text-muted">Logged API calls</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card admin-kpi h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase">Active Clients</div>
                <div class="display-6 fw-semibold"><?php echo $active_clients; ?></div>
                <div class="small text-muted"><?php echo $revoked_clients; ?> revoked clients</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card admin-kpi h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase">Tracked Endpoints</div>
                <div class="display-6 fw-semibold"><?php echo count($endpoint_stats); ?></div>
                <div class="small text-muted">Top endpoints shown below</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card admin-kpi h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase">Recent Entries</div>
                <div class="display-6 fw-semibold"><?php echo count($recent_access); ?></div>
                <div class="small text-muted">Latest access log window</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-users-gear"></i> Requests Per Client</h5></div>
            <div class="card-body">
                <?php if (!empty($client_stats)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead><tr><th>Client</th><th>Status</th><th>Scopes</th><th class="text-end">Requests</th></tr></thead>
                            <tbody>
                                <?php foreach ($client_stats as $s): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($s->client_name, ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo $s->is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Revoked</span>'; ?></td>
                                        <td><code><?php echo htmlspecialchars($s->scope, ENT_QUOTES, 'UTF-8'); ?></code></td>
                                        <td class="text-end"><strong><?php echo number_format((int) $s->total_requests); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No client usage has been logged yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-route"></i> Most Accessed Endpoints</h5></div>
            <div class="card-body">
                <?php if (!empty($endpoint_stats)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Endpoint</th><th class="text-end">Calls</th></tr></thead>
                            <tbody>
                                <?php foreach ($endpoint_stats as $e): ?>
                                    <tr>
                                        <td><code class="text-break"><?php echo htmlspecialchars($e->endpoint, ENT_QUOTES, 'UTF-8'); ?></code></td>
                                        <td class="text-end"><strong><?php echo number_format((int) $e->access_count); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No endpoint statistics available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-list-check"></i> Recent API Access</h5>
        <a href="<?php echo site_url('admin/api-clients'); ?>" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-key"></i> Manage Clients
        </a>
    </div>
    <div class="card-body">
        <?php if (!empty($recent_access)): ?>
            <div class="table-responsive">
                <table class="table table-striped table-sm align-middle">
                    <thead><tr><th>Client</th><th>Endpoint</th><th>Time</th><th>IP</th></tr></thead>
                    <tbody>
                        <?php foreach ($recent_access as $a): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($a->client_name, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><code><?php echo htmlspecialchars($a->endpoint, ENT_QUOTES, 'UTF-8'); ?></code></td>
                                <td class="text-nowrap"><?php echo htmlspecialchars($a->access_time, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($a->ip_address, ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted mb-0">No recent access logs.</p>
        <?php endif; ?>
    </div>
</div>

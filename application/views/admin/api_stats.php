<h3><i class="fas fa-chart-bar"></i> API Usage Statistics</h3>
<a href="<?php echo site_url('admin/api-clients'); ?>" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to Clients</a>

<div class="row">
    <!-- Client Stats -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Requests Per Client</h5></div>
            <div class="card-body">
                <?php if (!empty($stats['client_stats'])): ?>
                <table class="table table-sm">
                    <thead><tr><th>Client</th><th>Status</th><th>Total Requests</th></tr></thead>
                    <tbody>
                        <?php foreach ($stats['client_stats'] as $s): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($s->client_name, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo $s->is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Revoked</span>'; ?></td>
                            <td><strong><?php echo $s->total_requests; ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="text-muted">No statistics available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Endpoint Stats -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Most Accessed Endpoints</h5></div>
            <div class="card-body">
                <?php if (!empty($stats['endpoint_stats'])): ?>
                <table class="table table-sm">
                    <thead><tr><th>Endpoint</th><th>Requests</th></tr></thead>
                    <tbody>
                        <?php foreach ($stats['endpoint_stats'] as $e): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($e->endpoint, ENT_QUOTES, 'UTF-8'); ?></code></td>
                            <td><strong><?php echo $e->access_count; ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="text-muted">No endpoint statistics available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Access Log -->
<div class="card mt-3">
    <div class="card-header"><h5 class="mb-0">Recent API Access</h5></div>
    <div class="card-body">
        <?php if (!empty($stats['recent_access'])): ?>
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead><tr><th>Client</th><th>Endpoint</th><th>Time</th><th>IP</th></tr></thead>
                <tbody>
                    <?php foreach ($stats['recent_access'] as $a): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($a->client_name, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><code><?php echo htmlspecialchars($a->endpoint, ENT_QUOTES, 'UTF-8'); ?></code></td>
                        <td><?php echo htmlspecialchars($a->access_time, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($a->ip_address, ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="text-muted">No recent access logs.</p>
        <?php endif; ?>
    </div>
</div>

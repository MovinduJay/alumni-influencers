<h3><i class="fas fa-list"></i> API Access Logs</h3>
<a href="<?php echo site_url('admin/api-clients'); ?>" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to Clients</a>

<?php if (!empty($logs)): ?>
<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead>
            <tr><th>Time</th><th>Endpoint</th><th>Method</th><th>IP Address</th></tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
            <tr>
                <td><?php echo htmlspecialchars($log->access_time, ENT_QUOTES, 'UTF-8'); ?></td>
                <td><code><?php echo htmlspecialchars($log->endpoint, ENT_QUOTES, 'UTF-8'); ?></code></td>
                <td><span class="badge bg-primary"><?php echo htmlspecialchars(strtoupper($log->method), ENT_QUOTES, 'UTF-8'); ?></span></td>
                <td><?php echo htmlspecialchars($log->ip_address, ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<div class="alert alert-info">No access logs for this client.</div>
<?php endif; ?>

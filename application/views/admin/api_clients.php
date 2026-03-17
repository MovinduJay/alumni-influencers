<h3><i class="fas fa-key"></i> API Client Management</h3>

<!-- New Client Keys Display -->
<?php $new_client = $this->session->flashdata('new_client'); ?>
<?php if ($new_client): ?>
<div class="alert alert-warning">
    <h5><i class="fas fa-exclamation-triangle"></i> New API Client Created - Save These Keys!</h5>
    <p><strong>Client Name:</strong> <?php echo htmlspecialchars($new_client['client_name'], ENT_QUOTES, 'UTF-8'); ?></p>
    <p><strong>Scopes:</strong> <code><?php echo htmlspecialchars($new_client['scope'], ENT_QUOTES, 'UTF-8'); ?></code></p>
    <p><strong>API Key:</strong> <code><?php echo htmlspecialchars($new_client['api_key'], ENT_QUOTES, 'UTF-8'); ?></code></p>
    <p><strong>Bearer Token:</strong> <code><?php echo htmlspecialchars($new_client['bearer_token'], ENT_QUOTES, 'UTF-8'); ?></code></p>
    <p class="text-danger mb-0"><strong>⚠ These keys will NOT be shown again. Store them securely!</strong></p>
</div>
<?php endif; ?>

<!-- Create New Client -->
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0"><i class="fas fa-plus"></i> Register New API Client</h5></div>
    <div class="card-body">
        <?php echo form_open('admin/api-clients/create'); ?>
            <div class="row">
                <div class="col-md-5">
                    <input type="text" class="form-control" name="client_name" placeholder="Client application name (e.g., AR Mobile App)" required>
                </div>
                <div class="col-md-4">
                    <select name="scope" class="form-select" required>
                        <option value="featured:read,alumni:read">Featured + Alumni Read (Recommended)</option>
                        <option value="featured:read,alumni:read,alumni:write">Featured Read + Alumni Read/Write</option>
                        <option value="featured:read">Featured Read Only</option>
                        <option value="alumni:read">Alumni Read Only</option>
                        <option value="alumni:read,alumni:write">Alumni Read + Write</option>
                        <option value="alumni:write">Alumni Write Only</option>
                        <option value="featured:read,alumni:write">Featured Read + Alumni Write</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-plus"></i> Create Client</button>
                </div>
            </div>
        <?php echo form_close(); ?>
    </div>
</div>

<!-- Existing Clients -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Registered API Clients</h5>
        <a href="<?php echo site_url('admin/api-clients/stats'); ?>" class="btn btn-sm btn-info"><i class="fas fa-chart-bar"></i> Usage Stats</a>
    </div>
    <div class="card-body">
        <?php if (!empty($clients)): ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr><th>ID</th><th>Client Name</th><th>Scopes</th><th>Status</th><th>Created</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $client): ?>
                    <tr>
                        <td><?php echo $client->id; ?></td>
                        <td><?php echo htmlspecialchars($client->client_name, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><code><?php echo htmlspecialchars($client->scope, ENT_QUOTES, 'UTF-8'); ?></code></td>
                        <td>
                            <?php if ($client->is_active): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Revoked</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($client->created_at, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <a href="<?php echo site_url('admin/api-clients/logs/' . $client->id); ?>" class="btn btn-sm btn-info"><i class="fas fa-list"></i> Logs</a>
                            <?php if ($client->is_active): ?>
                                <?php echo form_open('admin/api-clients/revoke/' . $client->id, array('style' => 'display:inline')); ?>
                                    <button type="submit" class="btn btn-sm btn-danger"
                                       onclick="return confirm('Revoke access for this client?');"><i class="fas fa-ban"></i> Revoke</button>
                                <?php echo form_close(); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="text-muted">No API clients registered yet.</p>
        <?php endif; ?>
    </div>
</div>

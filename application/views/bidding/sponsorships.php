<h3><i class="fas fa-handshake"></i> Sponsorship Offers</h3>
<a href="<?php echo site_url('bidding'); ?>" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to Bidding</a>

<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Add Sponsorship Offer</h5></div>
    <div class="card-body">
        <p class="text-muted">Accepted offers define the maximum amount you can bid.</p>
        <?php echo form_open('bidding/sponsorships/add'); ?>
            <div class="row g-3">
                <div class="col-md-5">
                    <input type="text" name="sponsor_name" class="form-control" placeholder="Sponsor name" required>
                </div>
                <div class="col-md-3">
                    <input type="number" step="0.01" min="0.01" name="amount_offered" class="form-control" placeholder="Amount (£)" required>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select" required>
                        <option value="accepted">Accepted</option>
                        <option value="pending">Pending</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-plus"></i> Save</button>
                </div>
            </div>
        <?php echo form_close(); ?>
    </div>
</div>

<div class="alert alert-info">
    <strong>Accepted Sponsorship Budget:</strong> £<?php echo number_format($accepted_total, 2); ?>
</div>

<?php if (!empty($sponsorships)): ?>
<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Sponsor</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sponsorships as $sponsorship): ?>
            <tr>
                <td><?php echo htmlspecialchars($sponsorship->sponsor_name, ENT_QUOTES, 'UTF-8'); ?></td>
                <td>£<?php echo number_format($sponsorship->amount_offered, 2); ?></td>
                <td>
                    <?php echo form_open('bidding/sponsorships/update/' . $sponsorship->id, array('class' => 'd-flex gap-2')); ?>
                        <select name="status" class="form-select form-select-sm">
                            <option value="pending" <?php echo $sponsorship->status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="accepted" <?php echo $sponsorship->status === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                            <option value="rejected" <?php echo $sponsorship->status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-outline-primary">Update</button>
                    <?php echo form_close(); ?>
                </td>
                <td><?php echo htmlspecialchars($sponsorship->created_at, ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <?php echo form_open('bidding/sponsorships/delete/' . $sponsorship->id, array('onsubmit' => "return confirm('Delete this sponsorship offer?');")); ?>
                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i> Delete</button>
                    <?php echo form_close(); ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<div class="alert alert-warning mb-0">No sponsorship offers recorded yet.</div>
<?php endif; ?>

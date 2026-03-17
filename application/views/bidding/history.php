<h3><i class="fas fa-history"></i> Bid History</h3>
<a href="<?php echo site_url('bidding'); ?>" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to Bidding</a>

<?php if (!empty($bids)): ?>
<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Bid Date</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Placed On</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bids as $bid): ?>
            <tr>
                <td><?php echo htmlspecialchars($bid->bid_date, ENT_QUOTES, 'UTF-8'); ?></td>
                <td>&pound;<?php echo number_format($bid->amount, 2); ?></td>
                <td>
                    <?php
                    switch ($bid->status) {
                        case 'won':
                            echo '<span class="badge bg-success"><i class="fas fa-trophy"></i> Won</span>';
                            break;
                        case 'lost':
                            echo '<span class="badge bg-danger"><i class="fas fa-times"></i> Lost</span>';
                            break;
                        case 'pending':
                            echo '<span class="badge bg-warning"><i class="fas fa-clock"></i> Pending</span>';
                            break;
                    }
                    ?>
                </td>
                <td><?php echo htmlspecialchars($bid->created_at, ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<div class="alert alert-info">No bids placed yet. <a href="<?php echo site_url('bidding'); ?>">Place your first bid!</a></div>
<?php endif; ?>

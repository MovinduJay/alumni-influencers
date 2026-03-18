<section class="admin-page-title">
    <div>
        <div class="admin-section-title">Alumni of the Day</div>
        <h1>Last 30 Days</h1>
        <p>Recent featured alumni selected through the bidding workflow.</p>
    </div>
    <div class="admin-page-actions">
        <a href="<?php echo site_url('admin'); ?>" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-shield-alt"></i> Dashboard
        </a>
    </div>
</section>

<div class="card admin-panel">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-star"></i> Featured Alumni History</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($featured_alumni)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Alumnus</th>
                            <th>Email</th>
                            <th class="text-end">Winning Bid</th>
                            <th class="text-end">Profile</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($featured_alumni as $item): ?>
                            <tr>
                                <td class="text-nowrap"><?php echo htmlspecialchars(date('M j, Y', strtotime($item->featured_date)), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($item->first_name . ' ' . $item->last_name, ENT_QUOTES, 'UTF-8'); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($item->email, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-end"><?php echo '&pound;' . number_format((float) $item->winning_bid, 2); ?></td>
                                <td class="text-end">
                                    <a href="<?php echo site_url('profile/view/' . (int) $item->alumni_id); ?>" class="btn btn-sm btn-outline-primary">
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted mb-0">No Alumni of the Day records found in the last 30 days.</p>
        <?php endif; ?>
    </div>
</div>

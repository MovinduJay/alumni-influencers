<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card border-primary">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-star"></i> Alumni of the Day - <?php echo date('F j, Y'); ?></h4>
            </div>
            <div class="card-body">
                <?php if ($featured_today): ?>
                    <div class="row align-items-center">
                        <div class="col-md-2 text-center">
                            <?php if ($featured_today->profile_image): ?>
                                <img src="<?php echo base_url('uploads/profile_images/' . htmlspecialchars($featured_today->profile_image, ENT_QUOTES, 'UTF-8')); ?>" alt="Featured" class="profile-image">
                            <?php else: ?>
                                <div class="profile-image mx-auto d-flex align-items-center justify-content-center bg-secondary text-white" style="font-size: 36px;">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-10">
                            <h3><?php echo htmlspecialchars($featured_today->first_name . ' ' . $featured_today->last_name, ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p><?php echo htmlspecialchars($featured_today->bio, ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php if ($featured_today->linkedin_url): ?>
                                <a href="<?php echo htmlspecialchars($featured_today->linkedin_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                                    <i class="fab fa-linkedin"></i> View LinkedIn Profile
                                </a>
                            <?php endif; ?>
                            <a href="<?php echo site_url('profile/view/' . $featured_today->alumni_id); ?>" class="btn btn-outline-info btn-sm">
                                <i class="fas fa-user"></i> View Full Profile
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center mb-0"><i class="fas fa-info-circle"></i> No featured alumni for today. The next selection will promote tomorrow's featured profile.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-gavel"></i> Bid For <?php echo htmlspecialchars($bid_date, ENT_QUOTES, 'UTF-8'); ?></h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Monthly Status:</strong>
                    <?php echo $monthly_wins; ?> / <?php echo $max_wins; ?> featured slots used this month.
                    <?php echo $remaining_slots; ?> remaining.
                    <br>
                    <strong>Accepted Sponsorship Budget:</strong> £<?php echo number_format($sponsorship_total, 2); ?>
                    <?php if ($max_wins > 3): ?>
                        <br><small class="text-success"><i class="fas fa-check-circle"></i> Bonus slot unlocked from a recorded alumni event this month.</small>
                    <?php endif; ?>
                </div>

                <?php if ($can_bid): ?>
                    <?php if ($current_bid): ?>
                        <div class="alert <?php echo $is_winning ? 'alert-success' : 'alert-warning'; ?>">
                            <strong>Your Current Bid:</strong> £<?php echo number_format($current_bid->amount, 2); ?><br>
                            <strong>Status:</strong>
                            <?php if ($is_winning): ?>
                                <span class="bid-status-winning"><i class="fas fa-trophy"></i> You are currently in the lead.</span>
                            <?php else: ?>
                                <span class="bid-status-losing"><i class="fas fa-arrow-down"></i> You are not currently in the lead.</span>
                            <?php endif; ?>
                        </div>

                        <?php echo form_open('bidding/update/' . $current_bid->id); ?>
                            <div class="mb-3">
                                <label for="amount" class="form-label">Increase Bid Amount (£)</label>
                                <input type="number" step="0.01" min="<?php echo $current_bid->amount + 0.01; ?>" max="<?php echo max($sponsorship_total, $current_bid->amount + 0.01); ?>" class="form-control" id="amount" name="amount" required>
                                <small class="form-text text-muted">Increase only. Maximum allowed by accepted sponsorships: £<?php echo number_format($sponsorship_total, 2); ?>.</small>
                            </div>
                            <button type="submit" class="btn btn-warning w-100">
                                <i class="fas fa-arrow-up"></i> Increase Bid
                            </button>
                        <?php echo form_close(); ?>
                    <?php else: ?>
                        <?php if ($sponsorship_total > 0): ?>
                            <?php echo form_open('bidding/place'); ?>
                                <input type="hidden" name="bid_date" value="<?php echo htmlspecialchars($bid_date, ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="mb-3">
                                    <label class="form-label">Featured Date</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($bid_date, ENT_QUOTES, 'UTF-8'); ?>" disabled>
                                    <small class="form-text text-muted">This bid targets tomorrow's 24-hour featured slot.</small>
                                </div>
                                <div class="mb-3">
                                    <label for="amount" class="form-label">Bid Amount (£)</label>
                                    <input type="number" step="0.01" min="0.01" max="<?php echo $sponsorship_total; ?>" class="form-control" id="amount" name="amount" required>
                                    <small class="form-text text-muted">Blind bid. You will only see lead/not-lead feedback. Maximum available sponsorship funding: £<?php echo number_format($sponsorship_total, 2); ?>.</small>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-gavel"></i> Place Bid
                                </button>
                            <?php echo form_close(); ?>
                        <?php else: ?>
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-hand-holding-usd"></i> Add and accept at least one sponsorship offer before placing a bid.
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-danger mb-0">
                        <i class="fas fa-ban"></i> You have reached your maximum number of featured slots this month (<?php echo $max_wins; ?>).
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-info-circle"></i> How It Works</h5></div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><i class="fas fa-handshake text-secondary"></i> Sponsorship offers fund your bid. Accepted offers set your bidding ceiling.</li>
                    <li class="mb-2"><i class="fas fa-eye-slash text-primary"></i> Blind bidding hides the top amount. You only see whether you are leading.</li>
                    <li class="mb-2"><i class="fas fa-clock text-warning"></i> Bidding closes at 6 PM. The selected winner becomes the featured alumnus for the next day.</li>
                    <li class="mb-2"><i class="fas fa-calendar text-info"></i> You can win up to 3 times per month, with a 4th slot unlocked by event participation.</li>
                </ul>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5></div>
            <div class="card-body">
                <a href="<?php echo site_url('bidding/sponsorships'); ?>" class="btn btn-outline-primary w-100 mb-2">
                    <i class="fas fa-handshake"></i> Manage Sponsorships
                </a>
                <a href="<?php echo site_url('bidding/events'); ?>" class="btn btn-outline-success w-100 mb-2">
                    <i class="fas fa-calendar-check"></i> Record Alumni Events
                </a>
                <a href="<?php echo site_url('bidding/history'); ?>" class="btn btn-outline-secondary w-100">
                    <i class="fas fa-list"></i> View Bid History
                </a>
                <?php if ($this->session->userdata('role') === 'admin'): ?>
                    <?php echo form_open('bidding/select-winner', array('class' => 'mt-2')); ?>
                        <button type="submit" class="btn btn-outline-dark w-100">
                            <i class="fas fa-magic"></i> Finalize Tomorrow's Winner
                        </button>
                    <?php echo form_close(); ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-chart-pie"></i> Snapshot</h5></div>
            <div class="card-body">
                <p class="mb-2"><strong>Sponsorship Offers:</strong> <?php echo count($sponsorships); ?></p>
                <p class="mb-2"><strong>Recorded Alumni Events:</strong> <?php echo count($event_participations); ?></p>
                <p class="mb-0"><strong>Tomorrow's Bid Date:</strong> <?php echo htmlspecialchars($bid_date, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
    </div>
</div>

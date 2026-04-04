<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="row">
    <div class="col-md-12 text-center mb-5">
        <h1><i class="fas fa-graduation-cap"></i> Alumni Influencers Platform</h1>
        <p class="lead text-muted">University of Westminster - Connecting Students with Successful Alumni</p>
        <hr>
    </div>
</div>

<!-- Today's Featured Alumni -->
<div class="row justify-content-center mb-5">
    <div class="col-md-8">
        <div class="card border-primary">
            <div class="card-header bg-primary text-white text-center">
                <h3 class="mb-0"><i class="fas fa-star"></i> Alumni of the Day - <?php echo date('F j, Y'); ?></h3>
            </div>
            <div class="card-body text-center">
                <?php if (isset($featured_today) && $featured_today): ?>
                    <?php if ($featured_today->profile_image): ?>
                        <img src="<?php echo base_url('uploads/profile_images/' . htmlspecialchars($featured_today->profile_image, ENT_QUOTES, 'UTF-8')); ?>"
                             alt="Featured Alumni" class="profile-image mb-3">
                    <?php endif; ?>
                    <h2><?php echo htmlspecialchars($featured_today->first_name . ' ' . $featured_today->last_name, ENT_QUOTES, 'UTF-8'); ?></h2>
                    <?php if ($featured_today->bio): ?>
                        <p class="lead"><?php echo htmlspecialchars($featured_today->bio, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                    <?php if ($featured_today->linkedin_url): ?>
                        <a href="<?php echo htmlspecialchars($featured_today->linkedin_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="btn btn-primary">
                            <i class="fab fa-linkedin"></i> Connect on LinkedIn
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo site_url('profile/view/' . $featured_today->alumni_id); ?>" class="btn btn-outline-primary">
                        <i class="fas fa-user"></i> View Full Profile
                    </a>
                <?php else: ?>
                    <p class="text-muted mb-0"><i class="fas fa-info-circle"></i> No featured alumni for today. Check back tomorrow!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Platform Features -->
<div class="row">
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-user-tie fa-3x text-primary mb-3"></i>
                <h4>Alumni Profiles</h4>
                <p class="text-muted">Showcase your professional achievements, degrees, certifications, and career history.</p>
                <?php if (!$this->session->userdata('logged_in')): ?>
                    <a href="<?php echo site_url('auth/register'); ?>" class="btn btn-primary">Register Now</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-gavel fa-3x text-warning mb-3"></i>
                <h4>Blind Bidding</h4>
                <p class="text-muted">Compete for the daily featured slot through our fair blind bidding system.</p>
                <?php if ($this->session->userdata('logged_in')): ?>
                    <a href="<?php echo site_url('bidding'); ?>" class="btn btn-warning">Place a Bid</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-code fa-3x text-success mb-3"></i>
                <h4>Developer API</h4>
                <p class="text-muted">Access alumni data through our RESTful API with comprehensive Swagger documentation.</p>
                <a href="<?php echo site_url('api-docs'); ?>" class="btn btn-success">View API Docs</a>
            </div>
        </div>
    </div>
</div>

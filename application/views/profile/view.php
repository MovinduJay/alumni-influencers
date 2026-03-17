<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <?php if (!empty($profile['alumni']->profile_image)): ?>
                    <img src="<?php echo base_url('uploads/profile_images/' . htmlspecialchars($profile['alumni']->profile_image, ENT_QUOTES, 'UTF-8')); ?>"
                         alt="Profile" class="profile-image mb-3">
                <?php else: ?>
                    <div class="profile-image mx-auto mb-3 d-flex align-items-center justify-content-center bg-secondary text-white" style="font-size: 48px;">
                        <i class="fas fa-user"></i>
                    </div>
                <?php endif; ?>

                <h3><?php echo htmlspecialchars($profile['alumni']->first_name . ' ' . $profile['alumni']->last_name, ENT_QUOTES, 'UTF-8'); ?></h3>

                <?php if (!empty($profile['alumni']->linkedin_url)): ?>
                    <a href="<?php echo htmlspecialchars($profile['alumni']->linkedin_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                        <i class="fab fa-linkedin"></i> LinkedIn
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($profile['alumni']->bio)): ?>
        <div class="card">
            <div class="card-header"><h5 class="mb-0">About</h5></div>
            <div class="card-body">
                <p><?php echo nl2br(htmlspecialchars($profile['alumni']->bio, ENT_QUOTES, 'UTF-8')); ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-md-8">
        <?php if (!empty($profile['degrees'])): ?>
        <div class="card">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-graduation-cap"></i> Degrees</h5></div>
            <div class="card-body">
                <?php foreach ($profile['degrees'] as $degree): ?>
                    <div class="mb-2 pb-2 border-bottom">
                        <strong><?php echo htmlspecialchars($degree->title, ENT_QUOTES, 'UTF-8'); ?></strong><br>
                        <span class="text-muted"><?php echo htmlspecialchars($degree->institution, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php if ($degree->completion_date): ?>
                            <br><small>Completed: <?php echo htmlspecialchars($degree->completion_date, ENT_QUOTES, 'UTF-8'); ?></small>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($profile['certifications'])): ?>
        <div class="card">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-certificate"></i> Certifications</h5></div>
            <div class="card-body">
                <?php foreach ($profile['certifications'] as $cert): ?>
                    <div class="mb-2 pb-2 border-bottom">
                        <strong><?php echo htmlspecialchars($cert->title, ENT_QUOTES, 'UTF-8'); ?></strong><br>
                        <span class="text-muted"><?php echo htmlspecialchars($cert->issuer, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($profile['licences'])): ?>
        <div class="card">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-id-card"></i> Licences</h5></div>
            <div class="card-body">
                <?php foreach ($profile['licences'] as $licence): ?>
                    <div class="mb-2 pb-2 border-bottom">
                        <strong><?php echo htmlspecialchars($licence->title, ENT_QUOTES, 'UTF-8'); ?></strong><br>
                        <span class="text-muted"><?php echo htmlspecialchars($licence->awarding_body, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($profile['courses'])): ?>
        <div class="card">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-book-open"></i> Courses</h5></div>
            <div class="card-body">
                <?php foreach ($profile['courses'] as $course): ?>
                    <div class="mb-2 pb-2 border-bottom">
                        <strong><?php echo htmlspecialchars($course->title, ENT_QUOTES, 'UTF-8'); ?></strong><br>
                        <span class="text-muted"><?php echo htmlspecialchars($course->provider, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($profile['employment_history'])): ?>
        <div class="card">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-briefcase"></i> Employment History</h5></div>
            <div class="card-body">
                <?php foreach ($profile['employment_history'] as $job): ?>
                    <div class="mb-2 pb-2 border-bottom">
                        <strong><?php echo htmlspecialchars($job->position, ENT_QUOTES, 'UTF-8'); ?></strong><br>
                        <span class="text-muted"><?php echo htmlspecialchars($job->company, ENT_QUOTES, 'UTF-8'); ?></span><br>
                        <small><?php echo htmlspecialchars($job->start_date, ENT_QUOTES, 'UTF-8'); ?> - <?php echo $job->end_date ? htmlspecialchars($job->end_date, ENT_QUOTES, 'UTF-8') : 'Present'; ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

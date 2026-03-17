<div class="row">
    <div class="col-md-4">
        <!-- Profile Card -->
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
                <p class="text-muted"><?php echo htmlspecialchars($profile['alumni']->email, ENT_QUOTES, 'UTF-8'); ?></p>

                <?php if (!empty($profile['alumni']->linkedin_url)): ?>
                    <a href="<?php echo htmlspecialchars($profile['alumni']->linkedin_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                        <i class="fab fa-linkedin"></i> LinkedIn Profile
                    </a>
                <?php endif; ?>

                <hr>
                <a href="<?php echo site_url('profile/edit'); ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-edit"></i> Edit Profile
                </a>
            </div>
        </div>

        <!-- Bio Card -->
        <?php if (!empty($profile['alumni']->bio)): ?>
        <div class="card">
            <div class="card-header"><h5 class="mb-0">About Me</h5></div>
            <div class="card-body">
                <p><?php echo nl2br(htmlspecialchars($profile['alumni']->bio, ENT_QUOTES, 'UTF-8')); ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-md-8">
        <!-- Degrees -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-graduation-cap"></i> Degrees</h5>
                <a href="<?php echo site_url('profile/degrees/add'); ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus"></i> Add
                </a>
            </div>
            <div class="card-body">
                <?php if (!empty($profile['degrees'])): ?>
                    <?php foreach ($profile['degrees'] as $degree): ?>
                        <div class="d-flex justify-content-between align-items-start mb-3 pb-3 border-bottom">
                            <div>
                                <strong><?php echo htmlspecialchars($degree->title, ENT_QUOTES, 'UTF-8'); ?></strong><br>
                                <span class="text-muted"><?php echo htmlspecialchars($degree->institution, ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php if ($degree->completion_date): ?>
                                    <br><small class="text-muted">Completed: <?php echo htmlspecialchars($degree->completion_date, ENT_QUOTES, 'UTF-8'); ?></small>
                                <?php endif; ?>
                                <?php if ($degree->url): ?>
                                    <br><a href="<?php echo htmlspecialchars($degree->url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="small">View Details</a>
                                <?php endif; ?>
                            </div>
                            <div>
                                <a href="<?php echo site_url('profile/degrees/edit/' . $degree->id); ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                <a href="<?php echo site_url('profile/degrees/delete/' . $degree->id); ?>" class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Are you sure you want to delete this degree?');"><i class="fas fa-trash"></i></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No degrees added yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Certifications -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-certificate"></i> Certifications</h5>
                <a href="<?php echo site_url('profile/certifications/add'); ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus"></i> Add
                </a>
            </div>
            <div class="card-body">
                <?php if (!empty($profile['certifications'])): ?>
                    <?php foreach ($profile['certifications'] as $cert): ?>
                        <div class="d-flex justify-content-between align-items-start mb-3 pb-3 border-bottom">
                            <div>
                                <strong><?php echo htmlspecialchars($cert->title, ENT_QUOTES, 'UTF-8'); ?></strong><br>
                                <span class="text-muted"><?php echo htmlspecialchars($cert->issuer, ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php if ($cert->completion_date): ?>
                                    <br><small class="text-muted">Completed: <?php echo htmlspecialchars($cert->completion_date, ENT_QUOTES, 'UTF-8'); ?></small>
                                <?php endif; ?>
                                <?php if ($cert->url): ?>
                                    <br><a href="<?php echo htmlspecialchars($cert->url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="small">View Details</a>
                                <?php endif; ?>
                            </div>
                            <div>
                                <a href="<?php echo site_url('profile/certifications/edit/' . $cert->id); ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                <a href="<?php echo site_url('profile/certifications/delete/' . $cert->id); ?>" class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Are you sure?');"><i class="fas fa-trash"></i></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No certifications added yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Licences -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-id-card"></i> Professional Licences</h5>
                <a href="<?php echo site_url('profile/licences/add'); ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus"></i> Add
                </a>
            </div>
            <div class="card-body">
                <?php if (!empty($profile['licences'])): ?>
                    <?php foreach ($profile['licences'] as $licence): ?>
                        <div class="d-flex justify-content-between align-items-start mb-3 pb-3 border-bottom">
                            <div>
                                <strong><?php echo htmlspecialchars($licence->title, ENT_QUOTES, 'UTF-8'); ?></strong><br>
                                <span class="text-muted"><?php echo htmlspecialchars($licence->awarding_body, ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php if ($licence->completion_date): ?>
                                    <br><small class="text-muted">Completed: <?php echo htmlspecialchars($licence->completion_date, ENT_QUOTES, 'UTF-8'); ?></small>
                                <?php endif; ?>
                                <?php if ($licence->url): ?>
                                    <br><a href="<?php echo htmlspecialchars($licence->url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="small">View Details</a>
                                <?php endif; ?>
                            </div>
                            <div>
                                <a href="<?php echo site_url('profile/licences/edit/' . $licence->id); ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                <a href="<?php echo site_url('profile/licences/delete/' . $licence->id); ?>" class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Are you sure?');"><i class="fas fa-trash"></i></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No licences added yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Courses -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-book-open"></i> Professional Courses</h5>
                <a href="<?php echo site_url('profile/courses/add'); ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus"></i> Add
                </a>
            </div>
            <div class="card-body">
                <?php if (!empty($profile['courses'])): ?>
                    <?php foreach ($profile['courses'] as $course): ?>
                        <div class="d-flex justify-content-between align-items-start mb-3 pb-3 border-bottom">
                            <div>
                                <strong><?php echo htmlspecialchars($course->title, ENT_QUOTES, 'UTF-8'); ?></strong><br>
                                <span class="text-muted"><?php echo htmlspecialchars($course->provider, ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php if ($course->completion_date): ?>
                                    <br><small class="text-muted">Completed: <?php echo htmlspecialchars($course->completion_date, ENT_QUOTES, 'UTF-8'); ?></small>
                                <?php endif; ?>
                                <?php if ($course->url): ?>
                                    <br><a href="<?php echo htmlspecialchars($course->url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="small">View Details</a>
                                <?php endif; ?>
                            </div>
                            <div>
                                <a href="<?php echo site_url('profile/courses/edit/' . $course->id); ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                <a href="<?php echo site_url('profile/courses/delete/' . $course->id); ?>" class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Are you sure?');"><i class="fas fa-trash"></i></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No courses added yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Employment History -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-briefcase"></i> Employment History</h5>
                <a href="<?php echo site_url('profile/employment/add'); ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus"></i> Add
                </a>
            </div>
            <div class="card-body">
                <?php if (!empty($profile['employment_history'])): ?>
                    <?php foreach ($profile['employment_history'] as $job): ?>
                        <div class="d-flex justify-content-between align-items-start mb-3 pb-3 border-bottom">
                            <div>
                                <strong><?php echo htmlspecialchars($job->position, ENT_QUOTES, 'UTF-8'); ?></strong><br>
                                <span class="text-muted"><?php echo htmlspecialchars($job->company, ENT_QUOTES, 'UTF-8'); ?></span><br>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($job->start_date, ENT_QUOTES, 'UTF-8'); ?> -
                                    <?php echo $job->end_date ? htmlspecialchars($job->end_date, ENT_QUOTES, 'UTF-8') : 'Present'; ?>
                                </small>
                            </div>
                            <div>
                                <a href="<?php echo site_url('profile/employment/edit/' . $job->id); ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                <a href="<?php echo site_url('profile/employment/delete/' . $job->id); ?>" class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Are you sure?');"><i class="fas fa-trash"></i></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No employment history added yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

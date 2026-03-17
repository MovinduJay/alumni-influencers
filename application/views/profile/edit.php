<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-edit"></i> Edit Profile</h4>
            </div>
            <div class="card-body">
                <!-- Profile Image Upload -->
                <div class="text-center mb-4">
                    <?php if (!empty($alumni->profile_image)): ?>
                        <img src="<?php echo base_url('uploads/profile_images/' . htmlspecialchars($alumni->profile_image, ENT_QUOTES, 'UTF-8')); ?>"
                             alt="Profile" class="profile-image mb-3">
                    <?php else: ?>
                        <div class="profile-image mx-auto mb-3 d-flex align-items-center justify-content-center bg-secondary text-white" style="font-size: 48px;">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>

                    <?php echo form_open_multipart('profile/image-upload'); ?>
                        <div class="input-group mb-3" style="max-width: 400px; margin: 0 auto;">
                            <input type="file" class="form-control" name="profile_image" accept="image/*">
                            <button class="btn btn-outline-primary" type="submit">
                                <i class="fas fa-upload"></i> Upload
                            </button>
                        </div>
                        <small class="text-muted">Max size: 2MB. Formats: JPG, PNG, GIF</small>
                    <?php echo form_close(); ?>
                </div>

                <hr>

                <!-- Profile Form -->
                <?php echo form_open('profile/edit'); ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control <?php echo form_error('first_name') ? 'is-invalid' : ''; ?>"
                                   id="first_name" name="first_name"
                                   value="<?php echo set_value('first_name', $alumni->first_name); ?>" required>
                            <div class="invalid-feedback"><?php echo form_error('first_name'); ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" class="form-control <?php echo form_error('last_name') ? 'is-invalid' : ''; ?>"
                                   id="last_name" name="last_name"
                                   value="<?php echo set_value('last_name', $alumni->last_name); ?>" required>
                            <div class="invalid-feedback"><?php echo form_error('last_name'); ?></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="linkedin_url" class="form-label">LinkedIn Profile URL</label>
                        <input type="url" class="form-control <?php echo form_error('linkedin_url') ? 'is-invalid' : ''; ?>"
                               id="linkedin_url" name="linkedin_url"
                               value="<?php echo set_value('linkedin_url', $alumni->linkedin_url); ?>"
                               placeholder="https://linkedin.com/in/yourprofile">
                        <div class="invalid-feedback"><?php echo form_error('linkedin_url'); ?></div>
                    </div>

                    <div class="mb-3">
                        <label for="bio" class="form-label">Biography</label>
                        <textarea class="form-control <?php echo form_error('bio') ? 'is-invalid' : ''; ?>"
                                  id="bio" name="bio" rows="5"
                                  placeholder="Tell us about yourself..."><?php echo set_value('bio', $alumni->bio); ?></textarea>
                        <div class="invalid-feedback"><?php echo form_error('bio'); ?></div>
                        <small class="form-text text-muted">Maximum 2000 characters.</small>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="<?php echo site_url('profile'); ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>

                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>

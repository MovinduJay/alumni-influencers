<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-user-plus"></i> Alumni Registration</h4>
            </div>
            <div class="card-body">
                <?php echo form_open('auth/register'); ?>

                    <div class="mb-3">
                        <label for="first_name" class="form-label">First Name *</label>
                        <input type="text" class="form-control <?php echo form_error('first_name') ? 'is-invalid' : ''; ?>"
                               id="first_name" name="first_name" value="<?php echo set_value('first_name'); ?>" required>
                        <div class="invalid-feedback"><?php echo form_error('first_name'); ?></div>
                    </div>

                    <div class="mb-3">
                        <label for="last_name" class="form-label">Last Name *</label>
                        <input type="text" class="form-control <?php echo form_error('last_name') ? 'is-invalid' : ''; ?>"
                               id="last_name" name="last_name" value="<?php echo set_value('last_name'); ?>" required>
                        <div class="invalid-feedback"><?php echo form_error('last_name'); ?></div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">University Email *</label>
                        <input type="email" class="form-control <?php echo form_error('email') ? 'is-invalid' : ''; ?>"
                               id="email" name="email" value="<?php echo set_value('email'); ?>"
                               placeholder="your.name@westminster.ac.uk" required>
                        <div class="invalid-feedback"><?php echo form_error('email'); ?></div>
                        <small class="form-text text-muted">Must be a valid @westminster.ac.uk email address.</small>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password *</label>
                        <input type="password" class="form-control <?php echo form_error('password') ? 'is-invalid' : ''; ?>"
                               id="password" name="password" required>
                        <div class="invalid-feedback"><?php echo form_error('password'); ?></div>
                        <small class="form-text text-muted">
                            Minimum 8 characters with uppercase, lowercase, number, and special character.
                        </small>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password *</label>
                        <input type="password" class="form-control <?php echo form_error('confirm_password') ? 'is-invalid' : ''; ?>"
                               id="confirm_password" name="confirm_password" required>
                        <div class="invalid-feedback"><?php echo form_error('confirm_password'); ?></div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Register
                        </button>
                    </div>

                <?php echo form_close(); ?>

                <hr>
                <p class="text-center mb-0">
                    Already have an account? <a href="<?php echo site_url('auth/login'); ?>">Login here</a>
                </p>
            </div>
        </div>
    </div>
</div>

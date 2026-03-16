<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0"><i class="fas fa-lock"></i> Reset Password</h4>
            </div>
            <div class="card-body">
                <?php echo form_open('auth/reset-password/' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8')); ?>

                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" class="form-control <?php echo form_error('password') ? 'is-invalid' : ''; ?>"
                               id="password" name="password" required>
                        <div class="invalid-feedback"><?php echo form_error('password'); ?></div>
                        <small class="form-text text-muted">
                            Minimum 8 characters with uppercase, lowercase, number, and special character.
                        </small>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control <?php echo form_error('confirm_password') ? 'is-invalid' : ''; ?>"
                               id="confirm_password" name="confirm_password" required>
                        <div class="invalid-feedback"><?php echo form_error('confirm_password'); ?></div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-lock"></i> Reset Password
                        </button>
                    </div>

                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h4 class="mb-0"><i class="fas fa-key"></i> Forgot Password</h4>
            </div>
            <div class="card-body">
                <p class="text-muted">Enter your email address and we'll send you a link to reset your password.</p>

                <?php echo form_open('auth/forgot-password'); ?>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control <?php echo form_error('email') ? 'is-invalid' : ''; ?>"
                               id="email" name="email" value="<?php echo set_value('email'); ?>" required>
                        <div class="invalid-feedback"><?php echo form_error('email'); ?></div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-paper-plane"></i> Send Reset Link
                        </button>
                    </div>

                <?php echo form_close(); ?>

                <hr>
                <p class="text-center mb-0">
                    Remember your password? <a href="<?php echo site_url('auth/login'); ?>">Login here</a>
                </p>
            </div>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-sign-in-alt"></i> Alumni Login</h4>
            </div>
            <div class="card-body">
                <?php echo form_open('auth/login'); ?>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control <?php echo form_error('email') ? 'is-invalid' : ''; ?>"
                               id="email" name="email" value="<?php echo set_value('email'); ?>" required>
                        <div class="invalid-feedback"><?php echo form_error('email'); ?></div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control <?php echo form_error('password') ? 'is-invalid' : ''; ?>"
                               id="password" name="password" required>
                        <div class="invalid-feedback"><?php echo form_error('password'); ?></div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </button>
                    </div>

                <?php echo form_close(); ?>

                <hr>
                <p class="text-center">
                    <a href="<?php echo site_url('auth/forgot-password'); ?>">Forgot your password?</a>
                </p>
                <p class="text-center mb-0">
                    Don't have an account? <a href="<?php echo site_url('auth/register'); ?>">Register here</a>
                </p>
            </div>
        </div>
    </div>
</div>

<style>
    .login-shell {
        min-height: calc(100vh - 210px);
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(360px, 460px);
        gap: 48px;
        align-items: center;
        max-width: 1180px;
        margin: 0 auto;
        padding: 28px 0 36px;
    }
    .login-intro {
        max-width: 620px;
    }
    .login-intro-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 18px;
        padding: 8px 12px;
        border: 1px solid #b8d8d4;
        border-radius: 999px;
        color: #0b4f4a;
        background: #e8f4f2;
        font-weight: 700;
        font-size: .86rem;
    }
    .login-intro h1 {
        font-size: clamp(2rem, 4vw, 3.4rem);
        line-height: 1.06;
        margin-bottom: 18px;
        max-width: 720px;
    }
    .login-intro p {
        color: #475569;
        font-size: 1.04rem;
        line-height: 1.7;
        max-width: 580px;
        margin-bottom: 26px;
    }
    .login-points {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
        max-width: 620px;
    }
    .login-point {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 14px;
        border: 1px solid #dbe5e4;
        border-radius: 8px;
        background: #fff;
    }
    .login-point i {
        color: #0b4f4a;
        margin-top: 3px;
    }
    .login-point strong {
        display: block;
        font-size: .95rem;
        margin-bottom: 2px;
    }
    .login-point span {
        display: block;
        color: #64748b;
        font-size: .86rem;
        line-height: 1.45;
    }
    .login-card-wrap {
        width: 100%;
    }
    .login-card-wrap .card {
        border: 1px solid #d8dee6;
        border-radius: 8px;
        box-shadow: 0 18px 45px rgba(15, 23, 42, .12);
    }
    @media (max-width: 900px) {
        .login-shell {
            grid-template-columns: 1fr;
            gap: 24px;
            padding-top: 10px;
        }
        .login-card-wrap {
            max-width: 520px;
        }
    }
    @media (max-width: 576px) {
        .login-points {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="login-shell">
    <section class="login-intro" aria-label="Platform overview">
        <div class="login-intro-badge">
            <i class="fas fa-graduation-cap"></i>
            Alumni Influencers Platform
        </div>
        <h1>Manage alumni visibility and graduate intelligence in one place.</h1>
        <p>
            Sign in to update alumni profiles, manage bidding, review API clients, or explore university analytics built from current alumni career data.
        </p>
        <div class="login-points">
            <div class="login-point">
                <i class="fas fa-user-graduate"></i>
                <div>
                    <strong>Alumni profiles</strong>
                    <span>Maintain qualifications, employment history, and professional evidence.</span>
                </div>
            </div>
            <div class="login-point">
                <i class="fas fa-chart-line"></i>
                <div>
                    <strong>Analytics</strong>
                    <span>Review programme, sector, skill, and career outcome trends.</span>
                </div>
            </div>
            <div class="login-point">
                <i class="fas fa-gavel"></i>
                <div>
                    <strong>Blind bidding</strong>
                    <span>Compete for Alumni of the Day placement with controlled bid rules.</span>
                </div>
            </div>
            <div class="login-point">
                <i class="fas fa-key"></i>
                <div>
                    <strong>Scoped API access</strong>
                    <span>Create bearer-token clients with limited permissions.</span>
                </div>
            </div>
        </div>
    </section>

    <div class="login-card-wrap">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-sign-in-alt"></i> Sign In</h4>
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
                    Alumni account needed? <a href="<?php echo site_url('auth/register'); ?>">Register here</a>
                </p>
            </div>
        </div>
    </div>
</div>

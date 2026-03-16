<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . ' - ' : ''; ?>Alumni Influencers Platform</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .navbar-brand { font-weight: bold; }
        .card { margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .featured-badge { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 8px 16px; border-radius: 20px; font-size: 14px; }
        .bid-status-winning { color: #28a745; font-weight: bold; }
        .bid-status-losing { color: #dc3545; font-weight: bold; }
        .profile-image { width: 150px; height: 150px; object-fit: cover; border-radius: 50%; border: 3px solid #667eea; }
        .footer { background-color: #343a40; color: white; padding: 20px 0; margin-top: 40px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="<?php echo site_url('/'); ?>">
                <i class="fas fa-graduation-cap"></i> Alumni Influencers
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if ($this->session->userdata('logged_in')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo site_url('profile'); ?>"><i class="fas fa-user"></i> Profile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo site_url('bidding'); ?>"><i class="fas fa-gavel"></i> Bidding</a>
                        </li>
                        <?php if ($this->session->userdata('role') === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo site_url('admin/api-clients'); ?>"><i class="fas fa-key"></i> API Clients</a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo site_url('api-docs'); ?>"><i class="fas fa-book"></i> API Docs</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if ($this->session->userdata('logged_in')): ?>
                        <li class="nav-item">
                            <span class="nav-link text-light">
                                <i class="fas fa-user-circle"></i>
                                <?php echo htmlspecialchars($this->session->userdata('first_name') . ' ' . $this->session->userdata('last_name'), ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo site_url('auth/logout'); ?>"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo site_url('auth/login'); ?>"><i class="fas fa-sign-in-alt"></i> Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo site_url('auth/register'); ?>"><i class="fas fa-user-plus"></i> Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if ($this->session->flashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo htmlspecialchars($this->session->flashdata('success'), ENT_QUOTES, 'UTF-8'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($this->session->flashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo htmlspecialchars($this->session->flashdata('error'), ENT_QUOTES, 'UTF-8'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($this->session->flashdata('info')): ?>
            <div class="alert alert-info alert-dismissible fade show">
                <?php echo htmlspecialchars($this->session->flashdata('info'), ENT_QUOTES, 'UTF-8'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

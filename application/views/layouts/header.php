<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . ' - ' : ''; ?>Alumni Influencers Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; color: #0f172a; font-family: "Inter", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; font-size: 15px; letter-spacing: 0; }
        h1, h2, h3, h4, h5, h6 { color: #0f172a; font-weight: 700; letter-spacing: 0; }
        .navbar-brand { font-weight: 800; letter-spacing: -0.01em; }
        .navbar .nav-link { font-weight: 500; }
        .btn { font-weight: 600; }
        .table { color: #1f2937; }
        .table th { color: #334155; font-size: .78rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
        code { font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace; font-size: .9em; }
        .card { margin-bottom: 20px; box-shadow: 0 1px 2px rgba(15,23,42,0.08); }
        .admin-page-title { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; margin-bottom: 18px; padding-bottom: 14px; border-bottom: 1px solid #d8dee6; }
        .admin-page-title h1 { font-size: 1.65rem; font-weight: 800; margin: 0 0 4px; letter-spacing: -0.015em; }
        .admin-page-title p { margin: 0; color: #64748b; }
        .admin-page-actions { display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
        .admin-hero { background: #102a43; color: #fff; border-radius: 8px; padding: 28px; margin-bottom: 24px; }
        .admin-hero .btn { border-color: rgba(255,255,255,0.55); }
        .admin-kpi { border: 1px solid #d8dee6; border-left: 3px solid #1f7a8c; box-shadow: none; }
        .admin-kpi .card-body { padding: 14px 16px; }
        .admin-kpi .kpi-icon { width: 34px; height: 34px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; background: #dcefed; color: #0b4f4a; }
        .admin-kpi .display-6 { font-size: 2rem; font-weight: 700; letter-spacing: -0.025em; }
        .admin-panel { border: 1px solid #d8dee6; box-shadow: none; }
        .admin-panel .card-header { background: #0b4f4a; color: #fff; padding: 12px 16px; border-bottom: 0; }
        .admin-panel .card-header h5 { color: #fff; font-size: .98rem; font-weight: 700; }
        .admin-panel .card-header .btn { border-color: rgba(255,255,255,0.65); color: #fff; }
        .admin-panel .card-header .btn:hover { background: #fff; color: #0b4f4a; }
        .admin-panel .card-body { padding: 14px 16px; }
        .admin-panel code { color: #334155; }
        .admin-section-title { font-size: .72rem; letter-spacing: .08em; text-transform: uppercase; color: #64748b; font-weight: 800; }
        .api-progress { height: 8px; background: #e9ecef; border-radius: 999px; overflow: hidden; }
        .api-progress span { display: block; height: 100%; background: #1f7a8c; }
        .quick-action { text-decoration: none; color: inherit; }
        .quick-action .card { transition: transform .15s ease, box-shadow .15s ease; }
        .quick-action:hover .card { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(16,42,67,.16); }
        .quick-action-row { display: flex; align-items: flex-start; gap: 10px; padding: 12px 0; border-bottom: 1px solid #e5e7eb; }
        .quick-action-row:last-child { border-bottom: 0; }
        .quick-action-row i { margin-top: 3px; color: #1f7a8c; width: 18px; }
        .quick-action-row strong { display: block; color: #0f172a; font-weight: 700; }
        .quick-action:hover strong { color: #0d6efd; }
        .featured-badge { background: #667eea; color: white; padding: 8px 16px; border-radius: 20px; font-size: 14px; }
        .bid-status-winning { color: #28a745; font-weight: bold; }
        .bid-status-losing { color: #dc3545; font-weight: bold; }
        .profile-image { width: 150px; height: 150px; object-fit: cover; border-radius: 50%; border: 3px solid #667eea; }
        .navbar.bg-dark { background-color: #0b4f4a !important; }
        .navbar-dark .navbar-nav .nav-link { color: rgba(255,255,255,0.78); }
        .navbar-dark .navbar-nav .nav-link:hover,
        .navbar-dark .navbar-nav .nav-link.active { color: #fff; }
        .btn-primary { background-color: #0b4f4a; border-color: #0b4f4a; }
        .btn-primary:hover,
        .btn-primary:focus { background-color: #073f3b; border-color: #073f3b; }
        .btn-outline-primary { color: #0b4f4a; border-color: #0b4f4a; }
        .btn-outline-primary:hover,
        .btn-outline-primary:focus { background-color: #0b4f4a; border-color: #0b4f4a; color: #fff; }
        .modal-backdrop.show { opacity: .42; backdrop-filter: blur(4px); }
        .modal-content { border: 0; box-shadow: 0 24px 60px rgba(15, 23, 42, .24); }
        .modal-header { background: #0b4f4a; color: #fff; border-bottom: 0; }
        .modal-header .modal-title { color: #fff; }
        .modal-header .btn-close { filter: invert(1) grayscale(100%) brightness(200%); opacity: .85; }
        .footer { background-color: #0b4f4a; color: white; padding: 20px 0; margin-top: 40px; }
    </style>
</head>
<body>
    <?php
        $current_uri = trim(uri_string(), '/');
        $is_admin_dashboard = $current_uri === 'admin';
        $is_analytics_dashboard = strpos($current_uri, 'analytics') === 0;
        $is_api_clients = strpos($current_uri, 'admin/api-clients') === 0 && $current_uri !== 'admin/api-clients/stats';
    ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid px-3">
            <a class="navbar-brand" href="<?php echo site_url('/'); ?>">
                <i class="fas fa-graduation-cap"></i> Alumni Influencers
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if ($this->session->userdata('user_type') === 'alumni'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo site_url('profile'); ?>"><i class="fas fa-user"></i> Profile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo site_url('bidding'); ?>"><i class="fas fa-gavel"></i> Bidding</a>
                        </li>
                    <?php endif; ?>
                    <?php if ($this->session->userdata('user_type') === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $is_admin_dashboard ? 'active' : ''; ?>" href="<?php echo site_url('admin'); ?>"><i class="fas fa-shield-alt"></i> Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $is_analytics_dashboard ? 'active' : ''; ?>" href="<?php echo site_url('analytics'); ?>"><i class="fas fa-chart-line"></i> University Analytics</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $is_api_clients ? 'active' : ''; ?>" href="<?php echo site_url('admin/api-clients'); ?>"><i class="fas fa-key"></i> API Clients</a>
                        </li>
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

    <div class="container-fluid mt-4 px-3">
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

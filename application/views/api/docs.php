<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation - Alumni Influencers Platform</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/5.9.0/swagger-ui.min.css">
    <style>
        body { margin: 0; background: #fafafa; }
        .topbar { display: none !important; }
        .header-bar { background: #0b4f4a; color: white; padding: 15px 30px; }
        .header-bar h1 { margin: 0; font-size: 24px; }
        .header-bar a { color: rgba(255,255,255,0.78); text-decoration: none; margin-left: 20px; }
        .header-bar a:hover { color: white; }
    </style>
</head>
<body>
    <div class="header-bar">
        <h1>
            <i class="fas fa-graduation-cap"></i> Alumni Influencers Platform - API Documentation
            <a href="<?php echo site_url('/'); ?>">Back to App</a>
        </h1>
    </div>

    <div id="swagger-ui" data-spec-url="<?php echo htmlspecialchars(site_url('docs/spec'), ENT_QUOTES, 'UTF-8'); ?>"></div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/5.9.0/swagger-ui-bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/5.9.0/swagger-ui-standalone-preset.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="<?php echo base_url('assets/js/api-docs.js'); ?>"></script>
</body>
</html>

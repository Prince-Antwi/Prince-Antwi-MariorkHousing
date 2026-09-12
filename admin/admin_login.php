<?php
session_start();
require_once __DIR__ . '/../config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin = admin_by_username(trim($_POST['username'] ?? ''));
    if ($admin && password_verify($_POST['password'] ?? '', $admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = $admin['username'];
        header('Location: admin_dashboard.php');
        exit;
    }
    $error = 'Invalid admin credentials.';
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Admin Access | Mariork House</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="../style.css"></head>
<body class="auth-page admin-auth-page"><header class="auth-header"><a class="wordmark" href="../index.php">MARIORK <i>HOUSE</i></a><a class="back-link" href="../index.php">Back to house ↗</a></header><main class="admin-auth-shell"><section class="auth-form-panel"><p class="eyebrow">Private workspace</p><h2>Admin portal.</h2><p class="auth-lede">Manage the collection and receive every new order in one quiet place.</p><?php if ($error): ?><p class="auth-error"><?= htmlspecialchars($error) ?></p><?php endif; ?><form method="post" class="auth-form"><label>Username<input type="text" name="username" required autocomplete="username"></label><label>Password<input type="password" name="password" required autocomplete="current-password"></label><button class="button button-dark" type="submit">Access dashboard <span>↗</span></button></form></section></main><script src="../customers/auth-transition.js"></script></body></html>

<?php
session_start();
require_once __DIR__ . '/../config.php';

$error = '';
$success = $_GET['success'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer = customer_by_email(strtolower(trim($_POST['email'] ?? '')));
    if ($customer && password_verify($_POST['password'] ?? '', $customer['password'])) {
        session_regenerate_id(true);
        $_SESSION['customer_id'] = (int) $customer['id'];
        $_SESSION['customer_name'] = $customer['fullname'];
        header('Location: ../index.php');
        exit;
    }
    $error = 'Invalid email or password.';
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Sign In | Mariork House</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="../style.css"></head>
<body class="auth-page"><header class="auth-header"><a class="wordmark" href="../index.php">MARIORK <i>HOUSE</i></a><a class="back-link" href="../index.php">Back to house ↗</a></header><main class="auth-shell"><div class="auth-art login-art"><div><p class="eyebrow">Welcome back</p><h1>Your ritual,<br><em>waiting.</em></h1><p>Pick up where you left off and make room for something beautiful.</p></div></div><section class="auth-form-panel"><p class="eyebrow">Your Mariork account</p><h2>Sign in.</h2><?php if ($error): ?><p class="auth-error"><?= htmlspecialchars($error) ?></p><?php elseif ($success): ?><p class="auth-success"><?= htmlspecialchars($success) ?></p><?php endif; ?><form method="post" class="auth-form"><label>Email address<input type="email" name="email" required autocomplete="email"></label><label>Password<input type="password" name="password" required autocomplete="current-password"></label><button class="button button-dark" type="submit">Sign in <span>↗</span></button></form><p class="auth-switch">New to the house? <a href="register.php" data-auth-link>Create an account</a></p></section></main><script src="auth-transition.js"></script></body></html>

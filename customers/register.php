<?php
session_start();
require_once __DIR__ . '/../config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = [
        'fullname' => $_POST['fullname'] ?? '',
        'email' => $_POST['email'] ?? '',
        'password' => $_POST['password'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'address' => $_POST['address'] ?? '',
    ];
    if (strlen($payload['password']) < 8) {
        $error = 'Please choose a password with at least 8 characters.';
    } elseif (customer_by_email(strtolower(trim($payload['email'])))) {
        $error = 'That email address is already registered.';
    } else {
        try {
            register_customer($payload);
            header('Location: login.php?success=' . urlencode('Registered successfully. Please sign in.'));
            exit;
        } catch (PDOException $exception) {
            $error = 'We could not create your account. Please check your details.';
        }
    }
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Join Mariork House</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="../style.css"></head>
<body class="auth-page"><header class="auth-header"><a class="wordmark" href="../index.php">MARIORK <i>HOUSE</i></a><a class="back-link" href="../index.php">Back to house ↗</a></header><main class="auth-shell"><div class="auth-art register-art"><div><p class="eyebrow">A place for your favourites</p><h1>Make it<br><em>personal.</em></h1><p>Save your details once and return to the pieces that feel like you.</p></div></div><section class="auth-form-panel"><p class="eyebrow">Create your account</p><h2>Join the house.</h2><?php if ($error): ?><p class="auth-error"><?= htmlspecialchars($error) ?></p><?php endif; ?><form method="post" class="auth-form"><label>Full name<input type="text" name="fullname" value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>" required autocomplete="name"></label><label>Email address<input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autocomplete="email"></label><div class="auth-form-row"><label>Phone<input type="tel" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required autocomplete="tel"></label><label>Password<input type="password" name="password" required minlength="8" autocomplete="new-password"></label></div><label>Delivery address<textarea name="address" required autocomplete="street-address"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea></label><button class="button button-dark" type="submit">Create account <span>↗</span></button></form><p class="auth-switch">Already a member? <a href="login.php" data-auth-link>Sign in</a></p></section></main><script src="auth-transition.js"></script></body></html>

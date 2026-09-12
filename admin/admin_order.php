<?php
session_start();
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit;
}
require_once __DIR__ . '/../config.php';

$orderId = (int) ($_GET['id'] ?? 0);
$pdo = database();
$order = null;
$items = [];
if ($pdo !== null && $orderId > 0) {
    $statement = $pdo->prepare('SELECT orders.*, customers.fullname, customers.email, customers.phone, customers.address FROM orders JOIN customers ON orders.customer_id = customers.id WHERE orders.id = ?');
    $statement->execute([$orderId]);
    $order = $statement->fetch() ?: null;
    if ($order) {
        $itemStatement = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
        $itemStatement->execute([$orderId]);
        $items = $itemStatement->fetchAll();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order #<?= $orderId ?> | Mariork House</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="../style.css">
</head>
<body class="admin-page order-page">
    <header class="admin-header"><a class="wordmark" href="../index.php">MARIORK <i>HOUSE</i></a><div><a class="admin-order-button" href="admin_dashboard.php">Return to dashboard <span>↗</span></a><a class="back-link" href="../customers/logout.php">Sign out ↗</a></div></header>
    <main class="admin-main order-detail-main">
        <?php if (!$order): ?>
            <section class="order-not-found"><p class="eyebrow">Order not found</p><h1>That ritual<br><em>has moved.</em></h1><a class="button button-dark" href="admin_dashboard.php">Return to orders <span>↗</span></a></section>
        <?php else: ?>
            <div class="order-detail-heading"><div><a class="back-to-orders" href="admin_dashboard.php">← All orders</a><p class="eyebrow">Order #<?= (int) $order['id'] ?></p><h1>Prepare the<br><em>details.</em></h1></div><div class="order-meta"><span class="status-pill status-<?= strtolower(htmlspecialchars($order['status'])) ?>"><?= htmlspecialchars($order['status']) ?></span><time>Received <?= htmlspecialchars(date('M j, Y · g:i a', strtotime($order['created_at']))) ?></time></div></div>
            <section class="order-summary-grid"><article class="order-summary-card customer-summary"><p class="eyebrow">Customer</p><h2><?= htmlspecialchars($order['fullname']) ?></h2><div class="customer-lines"><span><?= htmlspecialchars($order['email']) ?></span><span><?= htmlspecialchars($order['phone']) ?></span><p><?= nl2br(htmlspecialchars($order['address'])) ?></p></div><a href="mailto:<?= htmlspecialchars($order['email']) ?>" class="text-link">Email customer <span>↗</span></a></article><article class="order-summary-card pieces-summary"><div class="pieces-heading"><div><p class="eyebrow">Pieces requested</p><h2><?= count($items) ?> <?= count($items) === 1 ? 'piece' : 'pieces' ?></h2></div><span>Order #<?= (int) $order['id'] ?></span></div><div class="detail-lines"><?php foreach ($items as $item): ?><div class="detail-line"><div><strong><?= htmlspecialchars($item['product_name']) ?></strong><small>Quantity <?= (int) $item['quantity'] ?></small></div><b>$<?= number_format((float) $item['unit_price'] * (int) $item['quantity'], 2) ?></b></div><?php endforeach; ?></div><div class="detail-total"><span>Order total</span><strong>$<?= number_format((float) $order['total_amount'], 2) ?></strong></div></article></section>
        <?php endif; ?>
    </main>
</body>
</html>

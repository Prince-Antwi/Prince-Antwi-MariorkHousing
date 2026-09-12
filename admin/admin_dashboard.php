<?php
session_start();
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit;
}
require_once __DIR__ . '/../config.php';

$pdo = database();
$orders = [];
if ($pdo !== null) {
    $orders = $pdo->query("SELECT orders.*, customers.fullname, customers.email, customers.phone FROM orders JOIN customers ON orders.customer_id = customers.id ORDER BY orders.created_at DESC")->fetchAll();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Desk | Mariork House</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="../style.css">
</head>
<body class="admin-page">
    <header class="admin-header"><a class="wordmark" href="../index.php">MARIORK <i>HOUSE</i></a><div><a class="admin-order-button" href="admin_products.php">Manage products <span>↗</span></a><span class="admin-kicker">Signed in as <?= htmlspecialchars($_SESSION['admin_user']) ?></span><a class="back-link" href="../customers/logout.php">Sign out ↗</a></div></header>
    <main class="admin-main">
        <div class="admin-heading"><div><p class="eyebrow">The order desk</p><h1>Receive the<br><em>rituals.</em></h1></div><p class="section-note">Incoming orders from your Mariork House<br>customers, ready for your attention.</p></div>
        <section class="orders-panel"><div class="orders-panel-top"><div><p class="eyebrow">Live orders</p><h2><?= count($orders) ?> requests</h2></div><div class="admin-dashboard-links"><a href="admin_products.php">Manage products ↗</a><a href="../index.php">View storefront ↗</a></div></div>
        <?php if (!$orders): ?>
            <p class="empty-orders">No orders have arrived yet. They will appear here as soon as a customer checks out.</p>
        <?php else: ?>
            <div class="orders-table"><div class="orders-head"><span>Order</span><span>Customer</span><span>Contact</span><span>Total</span><span>Status</span><span>Received</span></div>
            <?php foreach ($orders as $order): ?><div class="order-row"><strong><a class="order-link" href="admin_order.php?id=<?= (int) $order['id'] ?>">#<?= (int) $order['id'] ?></a></strong><div><strong><?= htmlspecialchars($order['fullname']) ?></strong><small><?= htmlspecialchars($order['email']) ?></small></div><span><?= htmlspecialchars($order['phone']) ?></span><strong>$<?= number_format((float) $order['total_amount'], 2) ?></strong><span class="status-pill status-<?= strtolower(htmlspecialchars($order['status'])) ?>"><?= htmlspecialchars($order['status']) ?></span><time><?= htmlspecialchars(date('M j, Y', strtotime($order['created_at']))) ?></time></div><?php endforeach; ?></div>
+        <?php endif; ?></section>
+    </main>
+</body>
+</html>

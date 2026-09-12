<?php
session_start();
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['customer_id'])) {
    header('Location: login.php?success=' . urlencode('Sign in to place your order.'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $items = json_decode($_POST['items'] ?? '[]', true);
    if (!is_array($items) || !$items) {
        $error = 'Your bag is empty.';
    } else {
        $orderId = place_order((int) $_SESSION['customer_id'], $items);
        if ($orderId !== null) {
            header('Location: order_success.php?order=' . $orderId);
            exit;
        }
        $error = 'We could not place your order just now. Please try again.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout | Mariork House</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body class="checkout-page">
    <header class="checkout-header">
        <a class="wordmark" href="../index.php">MARIORK <i>HOUSE</i></a>
        <div class="checkout-progress"><span class="complete">01 Bag</span><i></i><span class="active">02 Checkout</span><i></i><span>03 Confirmation</span></div>
        <a class="back-link" href="../index.php">Back to shop ↗</a>
    </header>

    <main class="checkout-main">
        <section class="checkout-intro">
            <p class="eyebrow">A considered finish</p>
            <h1>Confirm your<br><em>ritual.</em></h1>
            <p class="checkout-lede">Everything is almost yours, <?= htmlspecialchars(explode(' ', $_SESSION['customer_name'])[0]) ?>. Review your edit and delivery details before we prepare it beautifully.</p>
            <div class="checkout-note"><span>✦</span><p>Complimentary delivery on orders over $150.<br>Every order is wrapped with care.</p></div>
        </section>

        <section class="checkout-card">
            <div class="checkout-card-heading"><div><p class="eyebrow">Your edit</p><h2>Shopping bag</h2></div><span id="checkout-count">0 pieces</span></div>
            <div class="checkout-items" id="checkout-items-list"><p class="checkout-empty">Your edit is waiting for something lovely.</p></div>
            <div class="delivery-block"><div class="checkout-card-heading"><div><p class="eyebrow">Delivering to</p><h2>Saved details</h2></div><a href="../customers/login.php">Edit</a></div><div class="delivery-details"><strong><?= htmlspecialchars($customer['fullname'] ?? $_SESSION['customer_name']) ?></strong><span><?= htmlspecialchars($customer['email'] ?? '') ?></span><span><?= htmlspecialchars($customer['phone'] ?? '') ?></span><span><?= nl2br(htmlspecialchars($customer['address'] ?? '')) ?></span></div></div>
            <?php if ($error): ?><p class="auth-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
            <form method="post" id="checkout-form"><input type="hidden" name="items" id="checkout-items"><div class="checkout-total"><span>Order total</span><strong id="checkout-total-price">$0.00</strong></div><button class="button button-dark place-order-button" type="submit">Place my order <span>↗</span></button><p class="secure-note">Your details are kept private and used only to fulfil this order.</p></form>
        </section>
    </main>

    <footer class="checkout-footer"><span>© 2026 Mariork House</span><span>Made for your ritual</span><a href="../index.php">Return to the house ↗</a></footer>
    <script>
        const storedItems = JSON.parse(localStorage.getItem('mariork-cart') || '[]');
        const itemInput = document.querySelector('#checkout-items');
        const itemList = document.querySelector('#checkout-items-list');
        const totalElement = document.querySelector('#checkout-total-price');
        const countElement = document.querySelector('#checkout-count');
        itemInput.value = JSON.stringify(storedItems);
        const total = storedItems.reduce((sum, item) => sum + (Number(item.price) * Number(item.quantity)), 0);
        const count = storedItems.reduce((sum, item) => sum + Number(item.quantity), 0);
        totalElement.textContent = `$${total.toFixed(2)}`;
        countElement.textContent = `${count} ${count === 1 ? 'piece' : 'pieces'}`;
        if (storedItems.length) {
            itemList.innerHTML = storedItems.map((item) => `<div class="checkout-item"><div><strong>${item.name}</strong><span>Quantity ${item.quantity}</span></div><b>$${(Number(item.price) * Number(item.quantity)).toFixed(2)}</b></div>`).join('');
        }
    </script>
</body>
</html>
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Checkout | Mariork House</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="style.css"></head><body class="auth-page"><header class="auth-header"><a class="wordmark" href="index.php">MARIORK <i>HOUSE</i></a><a class="back-link" href="index.php">Back to shop ↗</a></header><main class="checkout-shell"><p class="eyebrow">Almost yours</p><h1>Confirm your<br><em>ritual.</em></h1><p>Welcome, <?= htmlspecialchars($_SESSION['customer_name']) ?>. Your saved delivery details will be used for this order.</p><?php if ($error): ?><p class="auth-error"><?= htmlspecialchars($error) ?></p><?php endif; ?><form method="post" id="checkout-form"><input type="hidden" name="items" id="checkout-items"><button class="button button-dark" type="submit">Place order <span>↗</span></button></form></main><script>const items = localStorage.getItem('mariork-cart'); document.querySelector('#checkout-items').value = items || '[]';</script></body></html>

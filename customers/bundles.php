
<?php
require_once __DIR__ . '/../config.php';

$products = catalog();
$bundles = array_values(array_filter($products, static fn (array $product): bool => $product['category'] === 'Bundles' || !empty($product['is_promotion'])));
$choices = [
    'Hair' => array_values(array_filter($products, static fn (array $product): bool => $product['category'] === 'Hair')),
    'Fragrance' => array_values(array_filter($products, static fn (array $product): bool => $product['category'] === 'Fragrance')),
    'Jewelry' => array_values(array_filter($products, static fn (array $product): bool => $product['category'] === 'Jewelry')),
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>The Bundles | Mariork House</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body class="bundles-page">
    <div class="announcement">Complimentary delivery on orders over $150 <span>✦</span> curated for your next chapter</div>
    <header class="site-header bundle-header"><a class="wordmark" href="../index.php">MARIORK <i>HOUSE</i></a><nav class="main-nav" aria-label="Main navigation"><a href="../index.php#shop">Shop</a><a class="active-link" href="bundles.php">The bundles</a><a href="../index.php#story">Our edit</a></nav><a class="back-link" href="../index.php">Back to house ↗</a></header>
    <main>
        <section class="bundle-hero"><div><p class="eyebrow">The Mariork House bundles</p><h1>Three ready-made<br><em>moods.</em></h1><p>Beautifully wrapped and waiting for their moment. Or make something entirely your own.</p></div><div class="bundle-hero-art"><img src="https://images.unsplash.com/photo-1596462502278-27bfdc403348?auto=format&fit=crop&w=1200&q=90" alt="Curated Mariork House beauty bundle"><span>MH / 03</span></div></section>
        <section class="curated-bundles"><div class="section-heading"><div><p class="eyebrow">Already considered</p><h2>Choose your<br><em>mood.</em></h2></div><p class="section-note">The best things in life<br>come beautifully together.</p></div><div class="bundle-product-grid"><?php foreach ($bundles as $bundle): ?><article class="bundle-product"><div><img src="<?= htmlspecialchars($bundle['image_url']) ?>" alt="<?= htmlspecialchars($bundle['name']) ?>"><span><?= htmlspecialchars($bundle['badge'] ?? 'Curated') ?></span></div><p class="product-category">Bundle / Mariork House</p><div class="bundle-product-title"><h3><?= htmlspecialchars($bundle['name']) ?></h3><strong>$<?= number_format((float) $bundle['price'], 2) ?></strong></div><p><?= htmlspecialchars($bundle['description']) ?></p><button class="button button-dark bundle-add" type="button" data-name="<?= htmlspecialchars($bundle['name']) ?>" data-price="<?= htmlspecialchars((string) $bundle['price']) ?>">Add to bag <span>↗</span></button></article><?php endforeach; ?></div></section>
        <section class="builder-section" id="builder"><div class="builder-copy"><p class="eyebrow">Make it yours</p><h2>Build your<br><em>own bundle.</em></h2><p>Pick one piece from each chapter. We’ll take 15% off the finished ritual.</p><div class="builder-total"><span>Your bundle total</span><strong id="builder-price">Choose your edit</strong></div><button class="button button-dark" id="builder-add" type="button" disabled>Add bundle to bag <span>↗</span></button></div><div class="builder-choices"><?php foreach ($choices as $category => $items): ?><fieldset class="choice-group"><legend><span><?= sprintf('%02d', array_search($category, array_keys($choices), true) + 1) ?></span><?= htmlspecialchars($category) ?></legend><div><?php foreach ($items as $item): ?><button class="choice-button" type="button" data-choice-category="<?= htmlspecialchars($category) ?>" data-choice-name="<?= htmlspecialchars($item['name']) ?>" data-choice-price="<?= htmlspecialchars((string) $item['price']) ?>"><span><?= htmlspecialchars($item['name']) ?></span><small>$<?= number_format((float) $item['price'], 2) ?></small></button><?php endforeach; ?></div></fieldset><?php endforeach; ?></div></section>
    </main>
    <footer class="site-footer"><div class="footer-brand">MARIORK <i>HOUSE</i><p>Find your signature.</p></div><div><p class="footer-label">Explore</p><a href="../index.php#shop">Shop all</a><a href="bundles.php">Bundles</a><a href="../index.php#story">Our edit</a></div><div><p class="footer-label">Stay close</p><p class="footer-copy">Notes on beauty, new drops<br>and good things.</p><form class="newsletter"><input type="email" placeholder="Your email address" aria-label="Your email address"><button type="submit" aria-label="Subscribe">↗</button></form></div><div class="footer-bottom"><span>© 2026 Mariork House</span><span>Made for your ritual</span></div></footer>
    <div class="bundle-toast" id="bundle-toast" role="status"></div>
    <script src="bundles.js"></script>
</body>
</html>

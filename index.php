<?php
session_start();
require_once __DIR__ . '/config.php';

$products = catalog();
$site_media = site_media();
$banner_media = site_media_gallery();
$wishlist_ids = !empty($_SESSION['customer_id']) ? wishlist_product_ids((int) $_SESSION['customer_id']) : [];
$category_counts = array_count_values(array_column($products, 'category'));
$category_order = ['All', 'Hair', 'Fragrance', 'Jewelry', 'Bundles'];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Mariork House: expressive hair, fragrance and jewelry for your everyday ritual.">
    <title>Mariork House | Your signature, styled</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,400;1,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="home-page">
    <div class="announcement" id="announcement-bar">
        <div class="announcement-track">
            <span>Complimentary delivery on orders over $150</span> <span class="bullet">✦</span> 
            <span>Curated for your next chapter</span> <span class="bullet">✦</span> 
            <span>Complimentary delivery on orders over $150</span> <span class="bullet">✦</span> 
            <span>Curated for your next chapter</span>
        </div>
    </div>

    <header class="site-header">
        <a class="wordmark" href="#top" aria-label="Mariork House home">MARIORK <i>HOUSE</i></a>
        <nav class="main-nav" aria-label="Main navigation">
            <a href="#shop">Shop</a>
            <a href="customers/bundles.php">The bundles</a>
            <a href="#story">Our edit</a>
        </nav>
        <div class="header-actions">
            <button class="icon-button search-toggle" type="button" aria-label="Open search">⌕</button>
            <?php if (!empty($_SESSION['customer_id'])): ?>
                <a class="account-link" href="customers/logout.php" title="Sign out <?= htmlspecialchars($_SESSION['customer_name']) ?>">Hi, <?= htmlspecialchars(explode(' ', $_SESSION['customer_name'])[0]) ?></a>
            <?php else: ?>
                <a class="account-link" href="customers/login.php" title="Sign in or create an account">Account</a>
            <?php endif; ?>
            <button class="wishlist-header" type="button" aria-label="My Rituals">♡ <span id="wishlist-count"><?= count($wishlist_ids) ?></span></button>
            <button class="bag-button" type="button" aria-label="Open shopping bag">Bag <span id="bag-count">0</span></button>
        </div>
    </header>

    <main id="top">
        <section class="hero">
            <div class="hero-copy">
                <p class="eyebrow">The house of considered beauty</p>
                <h1>Wear your<br><em>signature.</em></h1>
                <p class="hero-intro">Hair, fragrance and jewelry chosen to make every version of you feel like the right one.</p>
                <div class="hero-cta-group">
                    <a class="button button-dark" href="#shop">Explore the edit <span>↘</span></a>
                    <a class="button button-outline" href="customers/bundles.php">View bundles</a>
                </div>
            </div>
            <div class="hero-image-wrap">
                <div class="hero-stamp">M<br><small>MH</small></div>
                <?php $hero_gallery = $banner_media['hero'] ?? [['type' => 'image', 'src' => $site_media['hero'] ?? '']]; ?>
                <div class="rotating-banner hero-banner" data-media="<?= htmlspecialchars(json_encode($hero_gallery, JSON_UNESCAPED_SLASHES)) ?>">
                    <img class="hero-image" src="<?= htmlspecialchars($hero_gallery[0]['src']) ?>" alt="Mariork House beauty collection">
                    <video class="banner-video" muted loop playsinline preload="metadata" aria-label="Mariork House hero video"></video>
                </div>
                <div class="hero-caption"><span class="caption-indicator"></span> 01 / 04 &nbsp; — &nbsp; a little luxury, every day</div>
            </div>
            <div class="hero-side-note">FOR THE<br>SOFTLY<br>UNFORGETTABLE</div>
        </section>

        <section class="marquee" aria-label="Mariork House categories">
            <div class="marquee-content">
                <span>HAIR <span>✦</span> FRAGRANCE <span>✦</span> JEWELRY <span>✦</span> YOUR RITUAL <span>✦</span></span>
                <span>HAIR <span>✦</span> FRAGRANCE <span>✦</span> JEWELRY <span>✦</span> YOUR RITUAL <span>✦</span></span>
            </div>
        </section>

        <section class="shop-section" id="shop">
            <div class="section-heading">
                <div><p class="eyebrow">Shop the house</p><h2>Find your <em>favourite.</em></h2></div>
                <p class="section-note">Small rituals, strong impressions.<br>Every piece is made to be kept in rotation.</p>
            </div>
            <div class="shop-toolbar">
                <div class="filter-tabs" role="tablist" aria-label="Product categories">
                    <?php foreach ($category_order as $category): ?>
                        <button class="filter-tab<?= $category === 'All' ? ' active' : '' ?>" type="button" data-filter="<?= htmlspecialchars($category) ?>">
                            <?= htmlspecialchars($category) ?> <sup><?= $category === 'All' ? count($products) : ($category_counts[$category] ?? 0) ?></sup>
                        </button>
                    <?php endforeach; ?>
                </div>
                <label class="search-field"><span>⌕</span><input id="product-search" type="search" placeholder="Search the edit" aria-label="Search products"></label>
            </div>
            <div class="product-grid" id="product-grid">
                <?php foreach ($products as $product): ?>
                    <article class="product-card" data-category="<?= htmlspecialchars($product['category']) ?>" data-name="<?= htmlspecialchars(strtolower($product['name'] . ' ' . $product['description'])) ?>">
                        <div class="product-image-wrap">
                            <?php
                            $product_images = $product['images'] ?? [$product['image_url']];
                            $product_media = array_map(static fn (string $image): array => ['type' => 'image', 'src' => $image], array_values($product_images));
                            foreach ($product['videos'] ?? [] as $video) {
                                $product_media[] = ['type' => 'video', 'src' => $video['video_url']];
                            }
                            ?>
                            <?php if (!empty($product['badge'])): ?><span class="product-badge"><?= htmlspecialchars($product['badge']) ?></span><?php endif; ?>
                            <button class="wishlist-button<?= in_array((int) $product['id'], $wishlist_ids, true) ? ' is-saved' : '' ?>" type="button" data-product-id="<?= (int) $product['id'] ?>" data-wishlist="<?= htmlspecialchars($product['name']) ?>" aria-label="Save <?= htmlspecialchars($product['name']) ?> to My Rituals">♡</button>
                            <button class="quick-view-trigger" type="button" data-name="<?= htmlspecialchars($product['name']) ?>" data-category="<?= htmlspecialchars($product['category']) ?>" data-description="<?= htmlspecialchars($product['description']) ?>" data-price="<?= htmlspecialchars((string) $product['price']) ?>" data-currency="<?= htmlspecialchars($product['currency'] ?? 'USD') ?>" data-media="<?= htmlspecialchars(json_encode($product_media, JSON_UNESCAPED_SLASHES)) ?>">Quick view</button>
                            <button class="quick-add" type="button" data-product="<?= htmlspecialchars($product['name']) ?>" data-price="<?= htmlspecialchars((string) $product['price']) ?>" data-currency="<?= htmlspecialchars($product['currency'] ?? 'USD') ?>">+ Add to bag</button>
                            <img class="product-image" src="<?= htmlspecialchars($product_media[0]['src'] ?? $product['image_url']) ?>" data-media="<?= htmlspecialchars(json_encode($product_media, JSON_UNESCAPED_SLASHES)) ?>" alt="<?= htmlspecialchars($product['name']) ?>" loading="lazy">
                            <video class="product-video" hidden muted loop playsinline preload="metadata" aria-label="<?= htmlspecialchars($product['name']) ?> video"></video>
                        </div>
                        <div class="product-info">
                            <div>
                                <p class="product-category"><?= htmlspecialchars($product['category']) ?></p>
                                <h3><?= htmlspecialchars($product['name']) ?></h3>
                            </div>
                            <strong><?= currency_symbol($product['currency'] ?? 'USD') ?><?= number_format((float) $product['price'], 2) ?></strong>
                        </div>
                        <p class="product-description"><?= htmlspecialchars($product['description']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
            <p class="empty-state" id="empty-state" hidden>No pieces match that search.</p>
        </section>

        <section class="bundle-section" id="bundles">
            <div class="bundle-intro">
                <p class="eyebrow">The Mariork House bundles</p>
                <h2>Give a little<br><em>more.</em></h2>
                <p>Three ready-made moods, beautifully wrapped and waiting for their moment.</p>
                <a class="text-link" href="customers/bundles.php">Shop bundles <span>↗</span></a>
            </div>
            <div class="bundle-list">
                <button class="bundle-item" type="button" data-bundle-filter="Bundles"><span>01</span><strong>The soft girl set</strong><i>↗</i></button>
                <button class="bundle-item" type="button" data-bundle-filter="Bundles"><span>02</span><strong>The signature set</strong><i>↗</i></button>
                <button class="bundle-item" type="button" data-bundle-filter="Bundles"><span>03</span><strong>The gift set</strong><i>↗</i></button>
            </div>
            <?php $bundle_gallery = $banner_media['bundle'] ?? [['type' => 'image', 'src' => $site_media['bundle'] ?? '']]; ?>
            <div class="bundle-image rotating-banner" data-media="<?= htmlspecialchars(json_encode($bundle_gallery, JSON_UNESCAPED_SLASHES)) ?>">
                <img src="<?= htmlspecialchars($bundle_gallery[0]['src']) ?>" alt="Gift-ready beauty products">
                <video class="banner-video" muted loop playsinline preload="metadata" aria-label="Mariork House bundle video"></video>
            </div>
        </section>

        <?php $story_gallery = $banner_media['story'] ?? [['type' => 'image', 'src' => $site_media['story'] ?? '']]; ?>
        <section class="story-section" id="story">
            <div class="story-image rotating-banner" data-media="<?= htmlspecialchars(json_encode($story_gallery, JSON_UNESCAPED_SLASHES)) ?>">
                <img src="<?= htmlspecialchars($story_gallery[0]['src']) ?>" alt="A quiet beauty ritual">
                <video class="banner-video" muted loop playsinline preload="metadata" aria-label="Mariork House story video"></video>
            </div>
            <div class="story-copy">
                <p class="eyebrow">An everyday point of view</p>
                <h2>Beauty that<br><em>stays with you.</em></h2>
                <p>Mariork House is a collection of the things that make getting ready feel like coming home. Thoughtful textures, quiet confidence, and a little more glow than you expected.</p>
                <a class="text-link" href="#shop">Meet the collection <span>↗</span></a>
            </div>
        </section>

        <section class="house-notes" aria-label="Mariork House promises">
            <div class="house-notes-intro">
                <p class="eyebrow">A little more from the house</p>
                <h2>Keep your<br><em>ritual close.</em></h2>
                <p>Considered pieces, softly delivered, and made to become part of the way you move through the world.</p>
            </div>
            <div class="house-notes-list">
                <article><span>01</span><div><h3>Curated with feeling</h3><p>Every piece earns its place in the edit, from the first spritz to the final finishing touch.</p></div></article>
                <article><span>02</span><div><h3>Wrapped with intention</h3><p>Your order arrives ready for its moment, whether it is a gift for someone else or a gift to yourself.</p></div></article>
                <article><span>03</span><div><h3>Made to stay in rotation</h3><p>Beauty should feel personal, useful, and entirely yours. Find the pieces you will reach for again.</p></div></article>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="footer-brand">
            MARIORK <i>HOUSE</i>
            <p>Find your signature.</p>
        </div>
        <div>
            <p class="footer-label">Explore</p>
            <a href="#shop">Shop all</a>
            <a href="customers/bundles.php">Bundles</a>
            <a href="#story">Our edit</a>
        </div>
        <div>
            <p class="footer-label">Stay close</p>
            <p class="footer-copy">Notes on beauty, new drops<br>and good things.</p>
            <form class="newsletter">
                <input type="email" placeholder="Your email address" aria-label="Your email address">
                <button type="submit" aria-label="Subscribe">↗</button>
            </form>
        </div>
        <div class="footer-bottom">
            <span>© 2026 Mariork House</span>
            <span>Made for your ritual</span>
        </div>
    </footer>

    <aside class="cart-drawer" id="cart-drawer" aria-label="Shopping bag" aria-hidden="true">
        <div class="cart-top">
            <div><p class="eyebrow">Your edit</p><h2>Shopping bag</h2></div>
            <button class="close-cart" type="button" aria-label="Close shopping bag">×</button>
        </div>
        <div id="cart-items" class="cart-items"><p class="cart-empty">Your bag is waiting for something lovely.</p></div>
        <div class="cart-bottom">
            <div><span>Subtotal</span><strong id="cart-total">$0.00</strong></div>
            <button class="button button-dark checkout-button" type="button">Checkout <span>↗</span></button>
        </div>
    </aside>
    <div class="drawer-overlay" id="drawer-overlay"></div>
    
    <div class="quick-view-modal" id="quick-view-modal" aria-hidden="true">
        <div class="quick-view-panel" role="dialog" aria-modal="true" aria-labelledby="quick-view-name">
            <button class="quick-view-close" type="button" aria-label="Close quick view">×</button>
            <div class="quick-view-media">
                <img id="quick-view-image" src="" alt="">
                <video id="quick-view-video" controls playsinline></video>
                <button class="quick-view-arrow quick-view-prev" type="button" aria-label="Previous media">‹</button>
                <button class="quick-view-arrow quick-view-next" type="button" aria-label="Next media">›</button>
                <span class="quick-view-counter" id="quick-view-counter"></span>
            </div>
            <div class="quick-view-copy">
                <p class="eyebrow" id="quick-view-category"></p>
                <h2 id="quick-view-name"></h2>
                <strong id="quick-view-price"></strong>
                <p id="quick-view-description"></p>
                <button class="button button-dark" id="quick-view-add" type="button">Add to bag <span>↗</span></button>
            </div>
        </div>
    </div>
    
    <div class="ritual-toast" id="ritual-toast" role="status" aria-live="polite"></div>
    <script>window.MARIORK_CUSTOMER_LOGGED_IN = <?= !empty($_SESSION['customer_id']) ? 'true' : 'false' ?>;</script>
    <script src="app.js"></script>
</body>
</html>
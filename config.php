<?php

declare(strict_types=1);

const DB_HOST = '127.0.0.1';
const DB_NAME = 'mariork_house';
const DB_USER = 'root';
const DB_PASS = '';
const DEFAULT_ADMIN_USERNAME = 'admin';
const DEFAULT_ADMIN_PASSWORD = 'Mariork@2026';

function database(): ?PDO
{
    static $pdo = null;
    static $attempted = false;

    if ($attempted) {
        return $pdo;
    }

    $attempted = true;

    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS products (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                category VARCHAR(40) NOT NULL,
                description VARCHAR(255) NOT NULL,
                price DECIMAL(10, 2) NOT NULL,
                image_url VARCHAR(500) NOT NULL,
                badge VARCHAR(40) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);

        $columns = array_column($pdo->query('SHOW COLUMNS FROM products')->fetchAll(), 'Field');
        $missing_columns = [
            'category' => "ALTER TABLE products ADD category VARCHAR(40) NOT NULL DEFAULT 'Hair'",
            'description' => "ALTER TABLE products ADD description VARCHAR(255) NOT NULL DEFAULT ''",
            'price' => "ALTER TABLE products ADD price DECIMAL(10, 2) NOT NULL DEFAULT 0",
            'image_url' => "ALTER TABLE products ADD image_url VARCHAR(500) NOT NULL DEFAULT ''",
            'badge' => "ALTER TABLE products ADD badge VARCHAR(40) DEFAULT NULL",
            'stock_quantity' => "ALTER TABLE products ADD stock_quantity INT UNSIGNED NOT NULL DEFAULT 0",
            'is_promotion' => "ALTER TABLE products ADD is_promotion TINYINT(1) NOT NULL DEFAULT 0",
            'currency' => "ALTER TABLE products ADD currency VARCHAR(3) NOT NULL DEFAULT 'USD'",
        ];
        foreach ($missing_columns as $column => $statement) {
            if (!in_array($column, $columns, true)) {
                $pdo->exec($statement);
            }
        }

        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS customers (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                fullname VARCHAR(120) NOT NULL,
                email VARCHAR(190) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                phone VARCHAR(40) NOT NULL,
                address VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS admins (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(80) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS orders (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                customer_id INT UNSIGNED NOT NULL,
                total_amount DECIMAL(10, 2) NOT NULL,
                status ENUM('Pending', 'Processing', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (customer_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS order_items (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                order_id INT UNSIGNED NOT NULL,
                product_name VARCHAR(120) NOT NULL,
                quantity INT UNSIGNED NOT NULL,
                unit_price DECIMAL(10, 2) NOT NULL,
                INDEX (order_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS categories (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(60) NOT NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS product_images (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                product_id INT UNSIGNED NOT NULL,
                image_url VARCHAR(500) NOT NULL,
                sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
                INDEX (product_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS product_videos (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                product_id INT UNSIGNED NOT NULL,
                video_url VARCHAR(500) NOT NULL,
                duration_seconds DECIMAL(6, 2) DEFAULT NULL,
                sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
                INDEX (product_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS site_media (
                media_key VARCHAR(40) PRIMARY KEY,
                media_url VARCHAR(500) NOT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS site_media_items (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                media_key VARCHAR(40) NOT NULL,
                media_type ENUM('image', 'video') NOT NULL DEFAULT 'image',
                media_url VARCHAR(500) NOT NULL,
                duration_seconds DECIMAL(6, 2) DEFAULT NULL,
                sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
                INDEX (media_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS wishlist (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                customer_id INT UNSIGNED NOT NULL,
                product_id INT UNSIGNED NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY customer_product (customer_id, product_id),
                INDEX (customer_id),
                INDEX (product_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL);

        $mediaDefaults = [
            'hero' => 'https://images.unsplash.com/photo-1608248543803-ba4f8c70ae0b?auto=format&fit=crop&w=1200&q=90',
            'bundle' => 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?auto=format&fit=crop&w=900&q=85',
            'story' => 'https://images.unsplash.com/photo-1556228720-195a672e8a03?auto=format&fit=crop&w=1100&q=85',
        ];
        $mediaInsert = $pdo->prepare('INSERT IGNORE INTO site_media (media_key, media_url) VALUES (?, ?)');
        foreach ($mediaDefaults as $mediaKey => $mediaUrl) {
            $mediaInsert->execute([$mediaKey, $mediaUrl]);
            $itemCheck = $pdo->prepare('SELECT id FROM site_media_items WHERE media_key = ? LIMIT 1');
            $itemCheck->execute([$mediaKey]);
            if (!$itemCheck->fetchColumn()) {
                $pdo->prepare('INSERT INTO site_media_items (media_key, media_type, media_url, sort_order) VALUES (?, ?, ?, 0)')->execute([$mediaKey, 'image', $mediaUrl]);
            }
        }

        $categoryCount = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
        if ($categoryCount === 0) {
            $categoryInsert = $pdo->prepare('INSERT INTO categories (name) VALUES (?)');
            foreach (['Hair', 'Fragrance', 'Jewelry', 'Bundles'] as $categoryName) {
                $categoryInsert->execute([$categoryName]);
            }
        }

        $admin_check = $pdo->prepare('SELECT id FROM admins WHERE username = ? LIMIT 1');
        $admin_check->execute([DEFAULT_ADMIN_USERNAME]);
        if (!$admin_check->fetchColumn()) {
            $admin_insert = $pdo->prepare('INSERT INTO admins (username, password) VALUES (?, ?)');
            $admin_insert->execute([DEFAULT_ADMIN_USERNAME, password_hash(DEFAULT_ADMIN_PASSWORD, PASSWORD_DEFAULT)]);
        }

    } catch (PDOException $exception) {
        $pdo = null;
    }

    return $pdo;
}

function catalog(): array
{
    $pdo = database();
    if ($pdo === null) {
        return fallback_catalog();
    }

    try {
            $products = $pdo->query('SELECT id, name, category, description, price, currency, image_url, badge, stock_quantity, is_promotion FROM products ORDER BY id')->fetchAll();
            foreach ($products as &$product) {
                $product['images'] = product_images((int) $product['id'], $product['image_url']);
                $product['videos'] = product_videos((int) $product['id']);
            }
            unset($product);
            return $products;
    } catch (PDOException $exception) {
        return [];
    }
}

function fallback_catalog(): array
{
    return [
        ['id' => 1, 'name' => 'Spiral Curls', 'category' => 'Hair', 'description' => 'Defined, soft curls with an easy, polished finish.', 'price' => 145, 'image_url' => 'https://images.unsplash.com/photo-1595476108010-b4d1f102b1b1?auto=format&fit=crop&w=900&q=85', 'badge' => 'Bestseller', 'stock_quantity' => 8, 'is_promotion' => 0],
        ['id' => 2, 'name' => 'Bone Straight', 'category' => 'Hair', 'description' => 'Sleek, glossy strands made for effortless styling.', 'price' => 160, 'image_url' => 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=900&q=85', 'badge' => 'New'],
        ['id' => 3, 'name' => 'Body Wave', 'category' => 'Hair', 'description' => 'Full-bodied movement with a naturally luxe wave.', 'price' => 155, 'image_url' => 'https://images.unsplash.com/photo-1562322140-8baeececf3df?auto=format&fit=crop&w=900&q=85', 'badge' => null],
        ['id' => 4, 'name' => 'Italian Curls', 'category' => 'Hair', 'description' => 'Romantic texture with volume that holds its shape.', 'price' => 150, 'image_url' => 'https://images.unsplash.com/photo-1534620808146-d33bb39128b2?auto=format&fit=crop&w=900&q=85', 'badge' => null],
        ['id' => 5, 'name' => 'The Soft Girl Set', 'category' => 'Bundles', 'description' => 'A gentle edit of signature hair and feminine fragrance.', 'price' => 220, 'image_url' => 'https://images.unsplash.com/photo-1585386959984-a4155224a1ad?auto=format&fit=crop&w=900&q=85', 'badge' => 'Curated'],
        ['id' => 6, 'name' => 'The Signature Set', 'category' => 'Bundles', 'description' => 'The full Mariork House ritual, from hair to jewels.', 'price' => 310, 'image_url' => 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?auto=format&fit=crop&w=900&q=85', 'badge' => 'Iconic'],
        ['id' => 7, 'name' => 'The Gift Set', 'category' => 'Bundles', 'description' => 'A considered little luxury for someone unforgettable.', 'price' => 185, 'image_url' => 'https://images.unsplash.com/photo-1602173574767-37ac01994b2a?auto=format&fit=crop&w=900&q=85', 'badge' => 'Gift ready'],
        ['id' => 8, 'name' => 'Velvet Bloom', 'category' => 'Fragrance', 'description' => 'A soft floral veil with a warm, lingering trail.', 'price' => 72, 'image_url' => 'https://images.unsplash.com/photo-1541643600914-78b084683601?auto=format&fit=crop&w=900&q=85', 'badge' => null],
        ['id' => 9, 'name' => 'Noir Muse', 'category' => 'Fragrance', 'description' => 'Deep amber and spice for evenings with intention.', 'price' => 78, 'image_url' => 'https://images.unsplash.com/photo-1594035910387-fea47794261f?auto=format&fit=crop&w=900&q=85', 'badge' => null],
        ['id' => 10, 'name' => 'Fine Chain', 'category' => 'Jewelry', 'description' => 'An everyday layer in a warm champagne-gold tone.', 'price' => 48, 'image_url' => 'https://images.unsplash.com/photo-1611652022419-a9419f74343d?auto=format&fit=crop&w=900&q=85', 'badge' => 'Everyday'],
        ['id' => 11, 'name' => 'Pearl Drop', 'category' => 'Jewelry', 'description' => 'A polished finishing touch with a quiet glow.', 'price' => 56, 'image_url' => 'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?auto=format&fit=crop&w=900&q=85', 'badge' => null],
        ['id' => 12, 'name' => 'Sculpted Cuff', 'category' => 'Jewelry', 'description' => 'A confident bracelet with a fluid, sculptural line.', 'price' => 64, 'image_url' => 'https://images.unsplash.com/photo-1573408301185-9146fe634ad0?auto=format&fit=crop&w=900&q=85', 'badge' => null],
    ];
}

function product_categories(): array
{
    $pdo = database();
    if ($pdo === null) {
        return ['Hair', 'Fragrance', 'Jewelry', 'Bundles'];
    }
    return array_column($pdo->query('SELECT name FROM categories ORDER BY name')->fetchAll(), 'name');
}

function currency_symbol(string $currency): string
{
    return strtoupper($currency) === 'GHS' ? 'GH₵' : '$';
}

function wishlist_product_ids(int $customerId): array
{
    $pdo = database();
    if ($pdo === null || $customerId < 1) {
        return [];
    }
    $statement = $pdo->prepare('SELECT product_id FROM wishlist WHERE customer_id = ? ORDER BY created_at DESC');
    $statement->execute([$customerId]);
    return array_map('intval', array_column($statement->fetchAll(), 'product_id'));
}

function update_wishlist(int $customerId, int $productId, bool $saved): bool
{
    $pdo = database();
    if ($pdo === null || $customerId < 1 || $productId < 1) {
        return false;
    }
    if ($saved) {
        $statement = $pdo->prepare('INSERT IGNORE INTO wishlist (customer_id, product_id) VALUES (?, ?)');
    } else {
        $statement = $pdo->prepare('DELETE FROM wishlist WHERE customer_id = ? AND product_id = ?');
    }
    return $statement->execute([$customerId, $productId]);
}

function site_media(): array
{
    $pdo = database();
    if ($pdo === null) {
        return [];
    }
    $media = [];
    foreach ($pdo->query('SELECT media_key, media_url FROM site_media')->fetchAll() as $row) {
        $media[$row['media_key']] = $row['media_url'];
    }
    return $media;
}

function update_site_media(string $key, string $url): bool
{
    $pdo = database();
    if ($pdo === null || trim($url) === '') {
        return false;
    }
    $statement = $pdo->prepare('INSERT INTO site_media (media_key, media_url) VALUES (?, ?) ON DUPLICATE KEY UPDATE media_url = VALUES(media_url)');
    return $statement->execute([$key, trim($url)]);
}

function site_media_gallery(): array
{
    $pdo = database();
    if ($pdo === null) {
        return [];
    }
    $gallery = [];
    foreach ($pdo->query('SELECT media_key, media_type, media_url FROM site_media_items ORDER BY media_key, sort_order, id')->fetchAll() as $item) {
        $gallery[$item['media_key']][] = ['type' => $item['media_type'], 'src' => $item['media_url']];
    }
    return $gallery;
}

function save_site_media_gallery(string $key, array $items): bool
{
    $pdo = database();
    if ($pdo === null || !in_array($key, ['hero', 'bundle', 'story'], true)) {
        return false;
    }
    $pdo->prepare('DELETE FROM site_media_items WHERE media_key = ?')->execute([$key]);
    $statement = $pdo->prepare('INSERT INTO site_media_items (media_key, media_type, media_url, duration_seconds, sort_order) VALUES (?, ?, ?, ?, ?)');
    foreach (array_slice($items, 0, 3) as $order => $item) {
        if (!empty($item['src']) && in_array($item['type'], ['image', 'video'], true)) {
            $statement->execute([$key, $item['type'], trim($item['src']), $item['duration'] ?? null, $order]);
        }
    }
    return true;
}

function create_category(string $name): bool
{
    $pdo = database();
    if ($pdo === null || trim($name) === '') {
        return false;
    }
    try {
        $statement = $pdo->prepare('INSERT INTO categories (name) VALUES (?)');
        return $statement->execute([trim($name)]);
    } catch (PDOException $exception) {
        return false;
    }
}

function create_product(array $product): bool
{
    $pdo = database();
    if ($pdo === null) {
        return false;
    }

    try {
        $categoryStatement = $pdo->prepare('SELECT id FROM categories WHERE name = ? LIMIT 1');
        $categoryStatement->execute([$product['category']]);
        $categoryId = (int) $categoryStatement->fetchColumn();
        if ($categoryId < 1) {
            return false;
        }
        $name = trim($product['name']);
        $slug = trim((string) preg_replace('/[^a-z0-9]+/i', '-', strtolower($name)), '-');
        $statement = $pdo->prepare('INSERT INTO products (category_id, name, slug, description, base_price, is_bundle, image_url, category, price, currency, badge, stock_quantity, is_promotion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $saved = $statement->execute([
            $categoryId,
            $name,
            $slug,
            trim($product['description']),
            (float) $product['price'],
            $product['category'] === 'Bundles' || !empty($product['is_promotion']) ? 1 : 0,
            trim($product['image_url']),
            $product['category'],
            (float) $product['price'],
            in_array(strtoupper($product['currency'] ?? 'USD'), ['USD', 'GHS'], true) ? strtoupper($product['currency']) : 'USD',
            trim($product['badge']) ?: null,
            max(0, (int) ($product['stock_quantity'] ?? 0)),
            !empty($product['is_promotion']) ? 1 : 0,
        ]);
        if ($saved) {
            save_product_images((int) $pdo->lastInsertId(), $product['gallery_images'] ?? []);
            save_product_videos((int) $pdo->lastInsertId(), $product['gallery_videos'] ?? []);
        }
        return $saved;
    } catch (PDOException $exception) {
        return false;
    }
}

function update_product(int $id, array $product): bool
{
    $pdo = database();
    if ($pdo === null) {
        return false;
    }

    try {
        $categoryStatement = $pdo->prepare('SELECT id FROM categories WHERE name = ? LIMIT 1');
        $categoryStatement->execute([$product['category']]);
        $categoryId = (int) $categoryStatement->fetchColumn();
        if ($categoryId < 1) {
            return false;
        }
        $name = trim($product['name']);
        $slug = trim((string) preg_replace('/[^a-z0-9]+/i', '-', strtolower($name)), '-');
        $statement = $pdo->prepare('UPDATE products SET category_id = ?, name = ?, slug = ?, description = ?, base_price = ?, is_bundle = ?, image_url = ?, category = ?, price = ?, currency = ?, badge = ?, stock_quantity = ?, is_promotion = ? WHERE id = ?');
        $saved = $statement->execute([
            $categoryId,
            $name,
            $slug,
            trim($product['description']),
            (float) $product['price'],
            $product['category'] === 'Bundles' || !empty($product['is_promotion']) ? 1 : 0,
            trim($product['image_url']),
            $product['category'],
            (float) $product['price'],
            in_array(strtoupper($product['currency'] ?? 'USD'), ['USD', 'GHS'], true) ? strtoupper($product['currency']) : 'USD',
            trim($product['badge']) ?: null,
            max(0, (int) ($product['stock_quantity'] ?? 0)),
            !empty($product['is_promotion']) ? 1 : 0,
            $id,
        ]);
        if ($saved && array_key_exists('gallery_images', $product)) {
            save_product_images($id, $product['gallery_images']);
        }
        if ($saved && array_key_exists('gallery_videos', $product)) {
            save_product_videos($id, $product['gallery_videos']);
        }
        return $saved;
    } catch (PDOException $exception) {
        return false;
    }
}

function product_images(int $productId, string $primaryImage = ''): array
{
    $pdo = database();
    if ($pdo === null) {
        return $primaryImage !== '' ? [$primaryImage] : [];
    }
    $statement = $pdo->prepare('SELECT image_url FROM product_images WHERE product_id = ? ORDER BY sort_order, id');
    $statement->execute([$productId]);
    $images = array_column($statement->fetchAll(), 'image_url');
    if (!$images && $primaryImage !== '') {
        $images[] = $primaryImage;
    }
    return array_values(array_unique($images));
}

function save_product_images(int $productId, array $images): void
{
    $pdo = database();
    if ($pdo === null) {
        return;
    }
    $images = array_values(array_unique(array_filter(array_map('trim', $images))));
    $images = array_slice($images, 0, 6);
    $pdo->prepare('DELETE FROM product_images WHERE product_id = ?')->execute([$productId]);
    $statement = $pdo->prepare('INSERT INTO product_images (product_id, image_url, sort_order) VALUES (?, ?, ?)');
    foreach ($images as $order => $image) {
        $statement->execute([$productId, $image, $order]);
    }
}

function product_videos(int $productId): array
{
    $pdo = database();
    if ($pdo === null) {
        return [];
    }
    $statement = $pdo->prepare('SELECT video_url, duration_seconds FROM product_videos WHERE product_id = ? ORDER BY sort_order, id');
    $statement->execute([$productId]);
    return $statement->fetchAll();
}

function save_product_videos(int $productId, array $videos): void
{
    $pdo = database();
    if ($pdo === null) {
        return;
    }
    $cleanVideos = [];
    foreach ($videos as $video) {
        $url = trim((string) ($video['url'] ?? ''));
        $duration = isset($video['duration']) && is_numeric($video['duration']) ? min(120, max(0, (float) $video['duration'])) : null;
        if ($url !== '' && count($cleanVideos) < 6) {
            $cleanVideos[] = ['url' => $url, 'duration' => $duration];
        }
    }
    $pdo->prepare('DELETE FROM product_videos WHERE product_id = ?')->execute([$productId]);
    $statement = $pdo->prepare('INSERT INTO product_videos (product_id, video_url, duration_seconds, sort_order) VALUES (?, ?, ?, ?)');
    foreach ($cleanVideos as $order => $video) {
        $statement->execute([$productId, $video['url'], $video['duration'], $order]);
    }
}

function delete_product(int $id): bool
{
    $pdo = database();
    if ($pdo === null) {
        return false;
    }

    try {
        $statement = $pdo->prepare('DELETE FROM products WHERE id = ?');
        return $statement->execute([$id]);
    } catch (PDOException $exception) {
        return false;
    }
}

function customer_by_email(string $email): ?array
{
    $pdo = database();
    if ($pdo === null) {
        return null;
    }
    $statement = $pdo->prepare('SELECT * FROM customers WHERE email = ? LIMIT 1');
    $statement->execute([$email]);
    return $statement->fetch() ?: null;
}

function register_customer(array $customer): bool
{
    $pdo = database();
    if ($pdo === null) {
        return false;
    }
    $statement = $pdo->prepare('INSERT INTO customers (fullname, email, password, phone, address) VALUES (?, ?, ?, ?, ?)');
    return $statement->execute([
        trim($customer['fullname']),
        strtolower(trim($customer['email'])),
        password_hash($customer['password'], PASSWORD_DEFAULT),
        trim($customer['phone']),
        trim($customer['address']),
    ]);
}

function admin_by_username(string $username): ?array
{
    $pdo = database();
    if ($pdo === null) {
        return null;
    }
    $statement = $pdo->prepare('SELECT * FROM admins WHERE username = ? LIMIT 1');
    $statement->execute([$username]);
    return $statement->fetch() ?: null;
}

function place_order(int $customerId, array $items): ?int
{
    $pdo = database();
    if ($pdo === null || !$items) {
        return null;
    }

    $catalogByName = [];
    foreach (catalog() as $product) {
        $catalogByName[$product['name']] = $product;
    }
    $normalizedItems = [];
    foreach ($items as $item) {
        $name = trim((string) ($item['name'] ?? ''));
        $quantity = max(1, min(99, (int) ($item['quantity'] ?? 0)));
        if ($name === '' || !isset($catalogByName[$name])) {
            continue;
        }
        $normalizedItems[] = ['name' => $name, 'quantity' => $quantity, 'price' => (float) $catalogByName[$name]['price']];
    }
    if (!$normalizedItems) {
        return null;
    }
    $total = array_reduce($normalizedItems, static fn (float $sum, array $item): float => $sum + ($item['price'] * $item['quantity']), 0.0);
    try {
        $pdo->beginTransaction();
        $order = $pdo->prepare('INSERT INTO orders (customer_id, total_amount) VALUES (?, ?)');
        $order->execute([$customerId, $total]);
        $orderId = (int) $pdo->lastInsertId();
        $line = $pdo->prepare('INSERT INTO order_items (order_id, product_name, quantity, unit_price) VALUES (?, ?, ?, ?)');
        foreach ($normalizedItems as $item) {
            $line->execute([$orderId, trim($item['name']), (int) $item['quantity'], (float) $item['price']]);
        }
        $pdo->commit();
        return $orderId;
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return null;
    }
}

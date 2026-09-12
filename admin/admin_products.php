<?php
session_start();
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit;
}
require_once __DIR__ . '/../config.php';

$categories = product_categories();
$message = '';
$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'category') {
        $message = create_category($_POST['category_name'] ?? '') ? 'Category created.' : 'Category already exists or could not be created.';
        $categories = product_categories();
    }
    $media = site_media();
    if ($action === 'media') {
        $mediaKey = in_array($_POST['media_key'] ?? '', ['hero', 'bundle', 'story'], true) ? $_POST['media_key'] : '';
        $mediaItems = [];
        $submittedMediaUrls = trim($_POST['media_urls'] ?? '');
        $hasUploadedMedia = !empty($_FILES['media_files']['name']) && is_array($_FILES['media_files']['name']) && count(array_filter($_FILES['media_files']['name'])) > 0;
        if ($submittedMediaUrls === '' && !$hasUploadedMedia) {
            $submittedMediaUrls = trim($_POST['media_url'] ?? '');
        }
        foreach (preg_split('/\r\n|\r|\n/', $submittedMediaUrls) ?: [] as $url) {
            $url = trim($url);
            if ($url !== '') $mediaItems[] = ['type' => preg_match('/\.(mp4|webm|mov)(\?.*)?$/i', $url) ? 'video' : 'image', 'src' => $url];
        }
        $fileCount = isset($_FILES['media_files']['name']) && is_array($_FILES['media_files']['name']) ? count($_FILES['media_files']['name']) : 0;
        $mediaDurations = $_POST['media_durations'] ?? [];
        $allowedMedia = ['image/jpeg' => ['ext' => 'jpg', 'type' => 'image'], 'image/png' => ['ext' => 'png', 'type' => 'image'], 'image/webp' => ['ext' => 'webp', 'type' => 'image'], 'image/gif' => ['ext' => 'gif', 'type' => 'image'], 'video/mp4' => ['ext' => 'mp4', 'type' => 'video'], 'video/webm' => ['ext' => 'webm', 'type' => 'video'], 'video/quicktime' => ['ext' => 'mov', 'type' => 'video']];
        for ($fileIndex = 0; $fileIndex < $fileCount && count($mediaItems) < 3; $fileIndex++) {
            if ($_FILES['media_files']['error'][$fileIndex] !== UPLOAD_ERR_OK || $_FILES['media_files']['size'][$fileIndex] > 50 * 1024 * 1024) continue;
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['media_files']['tmp_name'][$fileIndex]);
            if (!isset($allowedMedia[$mime])) continue;
            $mediaDirectory = __DIR__ . '/../uploads/banners';
            if (!is_dir($mediaDirectory)) mkdir($mediaDirectory, 0775, true);
            $filename = bin2hex(random_bytes(8)) . '.' . $allowedMedia[$mime]['ext'];
            if (move_uploaded_file($_FILES['media_files']['tmp_name'][$fileIndex], $mediaDirectory . '/' . $filename)) {
                $mediaItems[] = ['type' => $allowedMedia[$mime]['type'], 'src' => 'uploads/banners/' . $filename, 'duration' => $mediaDurations[$fileIndex] ?? null];
            }
        }
        $message = $mediaKey !== '' && $mediaItems && save_site_media_gallery($mediaKey, $mediaItems) ? 'Homepage media updated.' : 'Choose up to three valid images or short videos.';
    }
    if ($action === 'save') {
        $galleryImages = preg_split('/\r\n|\r|\n/', trim($_POST['gallery_urls'] ?? '')) ?: [];
        $galleryImages = array_values(array_filter(array_map('trim', $galleryImages)));
        $fileCount = isset($_FILES['image_files']['name']) && is_array($_FILES['image_files']['name']) ? count($_FILES['image_files']['name']) : 0;
        for ($fileIndex = 0; $fileIndex < $fileCount && count($galleryImages) < 6; $fileIndex++) {
            if ($_FILES['image_files']['error'][$fileIndex] !== UPLOAD_ERR_OK) {
                continue;
            }
            $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
            $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['image_files']['tmp_name'][$fileIndex]);
            if (isset($allowedTypes[$mimeType]) && $_FILES['image_files']['size'][$fileIndex] <= 5 * 1024 * 1024) {
                $uploadDirectory = __DIR__ . '/../uploads/products';
                if (!is_dir($uploadDirectory)) {
                    mkdir($uploadDirectory, 0775, true);
                }
                $filename = bin2hex(random_bytes(8)) . '.' . $allowedTypes[$mimeType];
                if (move_uploaded_file($_FILES['image_files']['tmp_name'][$fileIndex], $uploadDirectory . '/' . $filename)) {
                    $galleryImages[] = 'uploads/products/' . $filename;
                }
            }
        }
        $galleryImages = array_slice(array_values(array_unique($galleryImages)), 0, 6);
        $imageUrl = trim($_POST['image_url'] ?? '') ?: ($galleryImages[0] ?? '');
        if ($imageUrl !== '' && !in_array($imageUrl, $galleryImages, true)) {
            array_unshift($galleryImages, $imageUrl);
            $galleryImages = array_slice($galleryImages, 0, 6);
        }
        $galleryVideos = [];
        $videoDurations = json_decode($_POST['video_durations'] ?? '{}', true);
        if (!is_array($videoDurations)) {
            $videoDurations = [];
        }
        foreach (preg_split('/\r\n|\r|\n/', trim($_POST['video_urls'] ?? '')) ?: [] as $videoUrl) {
            $videoUrl = trim($videoUrl);
            if ($videoUrl !== '') {
                $galleryVideos[] = ['url' => $videoUrl, 'duration' => $videoDurations[$videoUrl] ?? null];
            }
        }
        $videoCount = isset($_FILES['video_files']['name']) && is_array($_FILES['video_files']['name']) ? count($_FILES['video_files']['name']) : 0;
        for ($videoIndex = 0; $videoIndex < $videoCount && count($galleryVideos) < 6; $videoIndex++) {
            if ($_FILES['video_files']['error'][$videoIndex] !== UPLOAD_ERR_OK || $_FILES['video_files']['size'][$videoIndex] > 50 * 1024 * 1024) {
                continue;
            }
            $allowedVideos = ['video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/quicktime' => 'mov'];
            $videoMime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['video_files']['tmp_name'][$videoIndex]);
            if (!isset($allowedVideos[$videoMime])) {
                continue;
            }
            $uploadDirectory = __DIR__ . '/../uploads/products';
            if (!is_dir($uploadDirectory)) {
                mkdir($uploadDirectory, 0775, true);
            }
            $videoFilename = bin2hex(random_bytes(8)) . '.' . $allowedVideos[$videoMime];
            if (move_uploaded_file($_FILES['video_files']['tmp_name'][$videoIndex], $uploadDirectory . '/' . $videoFilename)) {
                $videoPath = 'uploads/products/' . $videoFilename;
                $galleryVideos[] = ['url' => $videoPath, 'duration' => $_POST['uploaded_video_durations'][$videoIndex] ?? null];
            }
        }
        $payload = [
            'name' => $_POST['name'] ?? '',
            'category' => in_array($_POST['category'] ?? '', $categories, true) ? $_POST['category'] : 'Hair',
            'description' => $_POST['description'] ?? '',
            'price' => $_POST['price'] ?? 0,
            'currency' => $_POST['currency'] ?? 'USD',
            'image_url' => $imageUrl,
            'badge' => $_POST['badge'] ?? '',
            'stock_quantity' => $_POST['stock_quantity'] ?? 0,
            'is_promotion' => !empty($_POST['is_promotion']),
            'gallery_images' => $galleryImages,
            'gallery_videos' => $galleryVideos,
        ];
        $saved = !empty($_POST['id']) ? update_product((int) $_POST['id'], $payload) : create_product($payload);
        $message = $saved ? 'Your product has been saved.' : 'The product could not be saved. Check the database connection.';
    }
    if ($action === 'delete' && !empty($_POST['id'])) {
        $message = delete_product((int) $_POST['id']) ? 'Product removed from the house.' : 'The product could not be removed.';
    }
}

if (!empty($_GET['edit'])) {
    foreach (catalog() as $product) {
        if ((int) $product['id'] === (int) $_GET['edit']) {
            $editing = $product;
            break;
        }
    }
}

$products = catalog();
$sort = $_GET['sort'] ?? 'All';
if (in_array($sort, $categories, true)) {
    $products = array_values(array_filter($products, static fn (array $product): bool => $product['category'] === $sort));
}
$form = $editing ?? ['id' => '', 'name' => '', 'category' => $categories[0] ?? 'Hair', 'description' => '', 'price' => '', 'currency' => 'USD', 'image_url' => '', 'badge' => '', 'stock_quantity' => 0, 'is_promotion' => 0, 'images' => [], 'videos' => []];
$media = site_media();
$banner_gallery = site_media_gallery();
function admin_value(array $form, string $key): string { return htmlspecialchars((string) ($form[$key] ?? '')); }
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Product Atelier | Mariork House</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="../style.css">
</head>
<body class="admin-page">
    <header class="admin-header"><a class="wordmark" href="../index.php">MARIORK <i>HOUSE</i></a><div><a class="admin-order-button" href="admin_dashboard.php">Return to dashboard <span>↗</span></a><span class="admin-kicker">Private workspace</span><a class="back-link" href="../index.php">View storefront ↗</a></div></header>
    <main class="admin-main"><div class="admin-heading"><div><p class="eyebrow">The product atelier</p><h1>Shape the<br><em>collection.</em></h1></div><p class="section-note">Add, edit and arrange the pieces<br>that define Mariork House.</p></div>
        <?php if ($message): ?><div class="admin-message"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <section class="admin-layout"><div class="admin-form-panel"><p class="eyebrow"><?= $editing ? 'Edit a piece' : 'Add a new piece' ?></p><form method="post" enctype="multipart/form-data"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= admin_value($form, 'id') ?>"><label>Name<input name="name" value="<?= admin_value($form, 'name') ?>" required></label><label>Category<select name="category"><?php foreach ($categories as $category): ?><option value="<?= htmlspecialchars($category) ?>" <?= $form['category'] === $category ? 'selected' : '' ?>><?= htmlspecialchars($category) ?></option><?php endforeach; ?></select></label><label>Description<textarea name="description" required><?= admin_value($form, 'description') ?></textarea></label><div class="admin-form-row"><label>Price<input name="price" type="number" min="0" step="0.01" value="<?= admin_value($form, 'price') ?>" required></label><label>Stock count<input name="stock_quantity" type="number" min="0" step="1" value="<?= admin_value($form, 'stock_quantity') ?>" required></label></div><div class="admin-form-row"><label>Badge<input name="badge" value="<?= admin_value($form, 'badge') ?>" placeholder="New, Curated..."></label><label>Promotion bundle<input name="is_promotion" type="checkbox" value="1" <?= !empty($form['is_promotion']) ? 'checked' : '' ?>> Show on Bundles page</label></div><label>Primary image URL<input name="image_url" type="url" value="<?= admin_value($form, 'image_url') ?>" placeholder="https://..."></label><label>Gallery image URLs <span class="label-note">one URL per line, up to 6</span><textarea name="gallery_urls" placeholder="https://...&#10;https://..."><?= htmlspecialchars(implode("\n", $form['images'] ?? [])) ?></textarea></label><label>Upload gallery images <span class="label-note">select up to 6 files</span><input name="image_files[]" type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple></label><button class="button button-dark" type="submit"><?= $editing ? 'Update piece' : 'Add piece' ?> <span>↗</span></button><?php if ($editing): ?><a class="cancel-edit" href="admin_products.php">Cancel edit</a><?php endif; ?></form></div>
        <div class="admin-catalog"><div class="admin-catalog-top"><div><p class="eyebrow">Live catalog</p><h2><?= count($products) ?> pieces</h2></div><div class="admin-filters"><a class="<?= $sort === 'All' ? 'active' : '' ?>" href="admin_products.php">All</a><?php foreach ($categories as $category): ?><a class="<?= $sort === $category ? 'active' : '' ?>" href="?sort=<?= urlencode($category) ?>"><?= htmlspecialchars($category) ?></a><?php endforeach; ?></div></div><div class="admin-table"><div class="admin-table-head"><span>Piece</span><span>Category</span><span>Stock</span><span>Price</span><span>Actions</span></div><?php foreach ($products as $product): ?><div class="admin-row"><div class="admin-product-cell"><img src="<?= htmlspecialchars(str_starts_with($product['image_url'], 'http') ? $product['image_url'] : '../' . $product['image_url']) ?>" alt=""><div><strong><?= htmlspecialchars($product['name']) ?></strong><small><?= htmlspecialchars($product['description']) ?></small><?php if (!empty($product['is_promotion'])): ?><em>Promotion bundle</em><?php endif; ?></div></div><span><?= htmlspecialchars($product['category']) ?></span><span><?= (int) ($product['stock_quantity'] ?? 0) ?> in stock</span><strong>$<?= number_format((float) $product['price'], 2) ?></strong><div class="admin-actions"><a href="?edit=<?= (int) $product['id'] ?>">Edit</a><form method="post" onsubmit="return confirm('Remove this piece from the collection?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $product['id'] ?>"><button type="submit">Delete</button></form></div></div><?php endforeach; ?></div></div></section>
        <section class="category-manager"><div><p class="eyebrow">Grow the vocabulary</p><h2>New category.</h2></div><form method="post"><input type="hidden" name="action" value="category"><input name="category_name" placeholder="e.g. Body care" required><button class="button button-dark" type="submit">Add category <span>↗</span></button></form></section>
        <section class="banner-manager"><div class="banner-manager-heading"><p class="eyebrow">Homepage atmosphere</p><h2>Shape the<br><em>first impression.</em></h2><p>Change the three editorial images customers see first: the hero, the gift edit, and the story panel.</p></div><div class="banner-grid"><?php foreach (['hero' => 'Top hero', 'bundle' => 'Gift / bundle side', 'story' => 'The edit side'] as $mediaKey => $mediaLabel): ?><form class="banner-form" method="post" enctype="multipart/form-data"><input type="hidden" name="action" value="media"><input type="hidden" name="media_key" value="<?= $mediaKey ?>"><div class="banner-preview"><img src="<?= htmlspecialchars(str_starts_with($media[$mediaKey] ?? '', 'http') ? ($media[$mediaKey] ?? '') : '../' . ($media[$mediaKey] ?? '')) ?>" alt="<?= htmlspecialchars($mediaLabel) ?>"></div><strong><?= htmlspecialchars($mediaLabel) ?></strong><input type="url" name="media_url" value="<?= htmlspecialchars($media[$mediaKey] ?? '') ?>" placeholder="Paste image URL"><label class="banner-upload">Upload from storage<input type="file" name="media_file" accept="image/jpeg,image/png,image/webp,image/gif"></label><button class="button button-dark" type="submit">Update image <span>↗</span></button></form><?php endforeach; ?></div></section>
    </main>
<script>
const productForm = document.querySelector('form[enctype="multipart/form-data"]');
if (productForm) {
    const currencyField = document.createElement('label');
    currencyField.innerHTML = 'Currency<select name="currency"><option value="USD">Dollar ($)</option><option value="GHS">Cedi (GH₵)</option></select>';
    productForm.insertBefore(currencyField, productForm.querySelector('button[type="submit"]'));
    currencyField.querySelector('select').value = '<?= htmlspecialchars($form['currency'] ?? 'USD') ?>';
    const videoFields = document.createElement('div');
    videoFields.className = 'video-admin-fields';
    videoFields.innerHTML = '<label>Video URLs <span class="label-note">one per line, maximum 2 minutes each</span><textarea name="video_urls" placeholder="https://.../lookbook.mp4"><?= htmlspecialchars(implode("\n", array_column($form['videos'] ?? [], 'video_url'))) ?></textarea></label><label>Upload short videos <span class="label-note">MP4, WEBM or MOV, up to 50MB each</span><input id="video-files" name="video_files[]" type="file" accept="video/mp4,video/webm,video/quicktime" multiple><input type="hidden" name="video_durations" id="video-durations"><div id="video-duration-fields"></div></label>';
    productForm.insertBefore(videoFields, productForm.querySelector('button[type="submit"]'));
    const videoInput = videoFields.querySelector('#video-files');
    const durationFields = videoFields.querySelector('#video-duration-fields');
    videoInput.addEventListener('change', () => {
        durationFields.innerHTML = '';
        Array.from(videoInput.files).slice(0, 6).forEach((file, index) => {
            const probe = document.createElement('video');
            probe.preload = 'metadata';
            probe.onloadedmetadata = () => {
                URL.revokeObjectURL(probe.src);
                if (probe.duration > 120) {
                    alert(`${file.name} is longer than 2 minutes and was removed.`);
                    videoInput.value = '';
                    durationFields.innerHTML = '';
                    return;
                }
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = `uploaded_video_durations[${index}]`;
                hidden.value = probe.duration.toFixed(2);
                durationFields.appendChild(hidden);
            };
            probe.src = URL.createObjectURL(file);
        });
    });
}
document.querySelectorAll('.banner-form').forEach((bannerForm) => {
    const oldUrl = bannerForm.querySelector('input[name="media_url"]');
    const oldFile = bannerForm.querySelector('input[name="media_file"]');
    if (oldUrl) oldUrl.closest('input').style.display = 'none';
    if (oldFile) oldFile.closest('label').style.display = 'none';
    const fields = document.createElement('div');
    fields.innerHTML = '<label>Images or video URLs <span class="label-note">one per line, up to 3 total</span><textarea name="media_urls" placeholder="Image or .mp4 URL"></textarea></label><label class="banner-upload">Upload images or short videos<input class="media-files" name="media_files[]" type="file" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime" multiple><span>Up to 3 files · videos maximum 2 minutes</span></label><div class="media-duration-fields"></div>';
    bannerForm.insertBefore(fields, bannerForm.querySelector('button[type="submit"]'));
    const input = fields.querySelector('.media-files');
    input.addEventListener('change', () => {
        const durationFields = fields.querySelector('.media-duration-fields');
        durationFields.innerHTML = '';
        Array.from(input.files).slice(0, 3).forEach((file, index) => {
            if (!file.type.startsWith('video/')) return;
            const probe = document.createElement('video');
            probe.preload = 'metadata';
            probe.onloadedmetadata = () => {
                URL.revokeObjectURL(probe.src);
                if (probe.duration > 120) {
                    alert(`${file.name} is longer than 2 minutes and was removed.`);
                    input.value = '';
                    durationFields.innerHTML = '';
                    return;
                }
                durationFields.insertAdjacentHTML('beforeend', `<input type="hidden" name="media_durations[${index}]" value="${probe.duration.toFixed(2)}">`);
            };
            probe.src = URL.createObjectURL(file);
        });
    });
});
const savedBannerMedia = <?= json_encode($banner_gallery, JSON_UNESCAPED_SLASHES) ?>;
document.querySelectorAll('.banner-form').forEach((bannerForm) => {
    const key = bannerForm.querySelector('input[name="media_key"]')?.value;
    const first = savedBannerMedia[key]?.[0];
    const preview = bannerForm.querySelector('.banner-preview img');
    if (first && preview && first.type === 'image') {
        preview.src = first.src.startsWith('http') ? first.src : `../${first.src}`;
    }
});
</script>
</body>
</html>

<?php
session_start();
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');
if (empty($_SESSION['customer_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'requires_login' => true]);
    exit;
}

$productId = (int) ($_POST['product_id'] ?? 0);
$saved = filter_var($_POST['saved'] ?? false, FILTER_VALIDATE_BOOLEAN);
if ($productId < 1 || !update_wishlist((int) $_SESSION['customer_id'], $productId, $saved)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Wishlist could not be updated.']);
    exit;
}

echo json_encode(['ok' => true, 'saved' => $saved, 'count' => count(wishlist_product_ids((int) $_SESSION['customer_id']))]);

<?php
require_once '../includes/shop.php';
header('Content-Type: application/json');

$productId = intval($_POST['product_id'] ?? 0);
$quantity  = max(1, intval($_POST['quantity'] ?? 1));

if (!$productId) {
    echo json_encode(['success'=>false,'message'=>'Invalid product.']);
    exit;
}

$pdo = getDB();

// Check product exists and has stock
$stmt = $pdo->prepare("SELECT id, stock_quantity FROM products WHERE id=? AND is_active=1");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    echo json_encode(['success'=>false,'message'=>'Product not found.']);
    exit;
}
if ($product['stock_quantity'] < $quantity) {
    echo json_encode(['success'=>false,'message'=>'Not enough stock available.']);
    exit;
}

$inCart = cartQuantities()[$productId] ?? 0;
cartSet($productId, min($inCart + $quantity, (int)$product['stock_quantity']));

echo json_encode(['success'=>true,'cart_count'=>cartCount(),'message'=>'Added to cart!']);

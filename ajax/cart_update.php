<?php
require_once '../includes/shop.php';
header('Content-Type: application/json');

// cart_id is the product id now that the cart lives in the session
$productId = intval($_POST['cart_id'] ?? 0);
$quantity  = max(1, intval($_POST['quantity'] ?? 1));

if (!isset(cartQuantities()[$productId])) { echo json_encode(['success'=>false]); exit; }

cartSet($productId, $quantity);

echo json_encode(['success'=>true]);

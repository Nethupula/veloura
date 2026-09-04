<?php

require_once '../includes/bootstrap.php';

if (!isCustomerLoggedIn()) {
    redirect(baseUrl('customer/login.php'));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(baseUrl('cart/cart.php'));
}

$cartItemId = isset($_POST['cart_item_id'])
    ? (int) $_POST['cart_item_id']
    : 0;

if ($cartItemId <= 0) {
    redirect(baseUrl('cart/cart.php'));
}

$userId = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| Find the customer's cart
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id
    FROM carts
    WHERE user_id = ?
    LIMIT 1
");

$stmt->execute([$userId]);

$cart = $stmt->fetch();

if (!$cart) {
    redirect(baseUrl('cart/cart.php'));
}

$cartId = (int) $cart['id'];

/*
|--------------------------------------------------------------------------
| Remove the cart item
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    DELETE FROM cart_items
    WHERE id = ?
      AND cart_id = ?
");

$stmt->execute([
    $cartItemId,
    $cartId
]);

/*
|--------------------------------------------------------------------------
| Return to cart
|--------------------------------------------------------------------------
*/

redirect(baseUrl('cart/cart.php'));
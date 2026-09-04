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

$quantity = isset($_POST['quantity'])
    ? (int) $_POST['quantity']
    : 0;

if ($cartItemId <= 0 || $quantity <= 0) {
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
| Get cart item and stock information
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        ci.id,
        ci.product_id,
        ci.variant_id,
        p.stock_quantity AS product_stock,
        p.status AS product_status,
        pv.stock_quantity AS variant_stock,
        pv.status AS variant_status
    FROM cart_items ci
    INNER JOIN products p
        ON ci.product_id = p.id
    LEFT JOIN product_variants pv
        ON ci.variant_id = pv.id
    WHERE ci.id = ?
      AND ci.cart_id = ?
    LIMIT 1
");

$stmt->execute([$cartItemId, $cartId]);

$item = $stmt->fetch();

if (!$item) {
    redirect(baseUrl('cart/cart.php'));
}

/*
|--------------------------------------------------------------------------
| Check product status
|--------------------------------------------------------------------------
*/

if ($item['product_status'] !== 'active') {
    redirect(baseUrl('cart/cart.php'));
}

/*
|--------------------------------------------------------------------------
| Determine available stock
|--------------------------------------------------------------------------
*/

if (!empty($item['variant_id'])) {

    if ($item['variant_status'] !== 'active') {
        redirect(baseUrl('cart/cart.php'));
    }

    $availableStock = (int) $item['variant_stock'];

} else {

    $availableStock = (int) $item['product_stock'];
}

/*
|--------------------------------------------------------------------------
| Validate requested quantity against stock
|--------------------------------------------------------------------------
*/

if ($availableStock <= 0) {
    redirect(baseUrl('cart/cart.php'));
}

if ($quantity > $availableStock) {
    $quantity = $availableStock;
}

/*
|--------------------------------------------------------------------------
| Update cart item
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    UPDATE cart_items
    SET quantity = ?
    WHERE id = ?
      AND cart_id = ?
");

$stmt->execute([
    $quantity,
    $cartItemId,
    $cartId
]);

/*
|--------------------------------------------------------------------------
| Return to cart
|--------------------------------------------------------------------------
*/

redirect(baseUrl('cart/cart.php'));
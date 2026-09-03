<?php

require_once '../includes/bootstrap.php';

/*
|--------------------------------------------------------------------------
| Customer Login Check
|--------------------------------------------------------------------------
*/

if (!isCustomerLoggedIn()) {
    redirect(baseUrl('customer/login.php'));
}


/*
|--------------------------------------------------------------------------
| Get Submitted Data
|--------------------------------------------------------------------------
*/

$productId = (int) ($_POST['product_id'] ?? 0);
$variantId = !empty($_POST['variant_id'])
    ? (int) $_POST['variant_id']
    : null;

$quantity = (int) ($_POST['quantity'] ?? 1);

if ($quantity < 1) {
    $quantity = 1;
}


/*
|--------------------------------------------------------------------------
| Validate Product
|--------------------------------------------------------------------------
*/

$productQuery = $pdo->prepare("
    SELECT
        id,
        name,
        price,
        discount_price,
        stock_quantity
    FROM products
    WHERE id = ?
        AND status = 'active'
    LIMIT 1
");

$productQuery->execute([$productId]);

$product = $productQuery->fetch();

if (!$product) {
    redirect(baseUrl('shop.php'));
}


/*
|--------------------------------------------------------------------------
| Validate Variant
|--------------------------------------------------------------------------
*/

$variant = null;

if ($variantId !== null) {

    $variantQuery = $pdo->prepare("
        SELECT
            id,
            product_id,
            variant_name,
            price,
            stock_quantity
        FROM product_variants
        WHERE id = ?
            AND product_id = ?
            AND status = 'active'
        LIMIT 1
    ");

    $variantQuery->execute([
        $variantId,
        $productId
    ]);

    $variant = $variantQuery->fetch();

    if (!$variant) {
        redirect(
            baseUrl(
                'products/product-details.php?slug=' .
                urlencode(
                    $_POST['product_slug'] ?? ''
                )
            )
        );
    }
}


/*
|--------------------------------------------------------------------------
| Determine Available Stock
|--------------------------------------------------------------------------
*/

$availableStock = $variant
    ? (int) $variant['stock_quantity']
    : (int) $product['stock_quantity'];

if ($availableStock <= 0) {
    redirect(
        baseUrl(
            'products/product-details.php?slug=' .
            urlencode(
                $_POST['product_slug'] ?? ''
            )
        )
    );
}

if ($quantity > $availableStock) {
    $quantity = $availableStock;
}


/*
|--------------------------------------------------------------------------
| Find / Create Customer Cart
|--------------------------------------------------------------------------
*/

$cartQuery = $pdo->prepare("
    SELECT id
    FROM carts
    WHERE user_id = ?
    LIMIT 1
");

$cartQuery->execute([
    $_SESSION['user_id']
]);

$cart = $cartQuery->fetch();


if ($cart) {

    $cartId = $cart['id'];

} else {

    $createCart = $pdo->prepare("
        INSERT INTO carts (user_id)
        VALUES (?)
    ");

    $createCart->execute([
        $_SESSION['user_id']
    ]);

    $cartId = $pdo->lastInsertId();
}


/*
|--------------------------------------------------------------------------
| Check Existing Cart Item
|--------------------------------------------------------------------------
*/

if ($variantId !== null) {

    $existingItemQuery = $pdo->prepare("
        SELECT
            id,
            quantity
        FROM cart_items
        WHERE cart_id = ?
            AND product_id = ?
            AND variant_id = ?
        LIMIT 1
    ");

    $existingItemQuery->execute([
        $cartId,
        $productId,
        $variantId
    ]);

} else {

    $existingItemQuery = $pdo->prepare("
        SELECT
            id,
            quantity
        FROM cart_items
        WHERE cart_id = ?
            AND product_id = ?
            AND variant_id IS NULL
        LIMIT 1
    ");

    $existingItemQuery->execute([
        $cartId,
        $productId
    ]);
}

$existingItem = $existingItemQuery->fetch();


/*
|--------------------------------------------------------------------------
| Insert / Update Cart Item
|--------------------------------------------------------------------------
*/

if ($existingItem) {

    $newQuantity =
        (int) $existingItem['quantity'] + $quantity;

    if ($newQuantity > $availableStock) {
        $newQuantity = $availableStock;
    }

    $updateItem = $pdo->prepare("
        UPDATE cart_items
        SET quantity = ?
        WHERE id = ?
    ");

    $updateItem->execute([
        $newQuantity,
        $existingItem['id']
    ]);

} else {

    $insertItem = $pdo->prepare("
        INSERT INTO cart_items
        (
            cart_id,
            product_id,
            variant_id,
            quantity
        )
        VALUES
        (?, ?, ?, ?)
    ");

    $insertItem->execute([
        $cartId,
        $productId,
        $variantId,
        $quantity
    ]);
}


/*
|--------------------------------------------------------------------------
| Redirect to Cart
|--------------------------------------------------------------------------
*/

redirect(baseUrl('cart/cart.php'));
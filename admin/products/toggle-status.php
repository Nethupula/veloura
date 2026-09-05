<?php

require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(baseUrl('admin/products/index.php'));
}

$productId = (int) ($_POST['id'] ?? 0);

if ($productId <= 0) {
    redirect(baseUrl('admin/products/index.php'));
}


/*
|--------------------------------------------------------------------------
| Get Current Product Status
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id, name, status, stock_quantity
    FROM products
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$productId]);

$product = $stmt->fetch();

if (!$product) {
    redirect(baseUrl('admin/products/index.php'));
}


/*
|--------------------------------------------------------------------------
| Determine New Status
|--------------------------------------------------------------------------
*/

if ($product['status'] === 'active') {

    $newStatus = 'inactive';

} else {

    if ((int) $product['stock_quantity'] <= 0) {

        $newStatus = 'out_of_stock';

    } else {

        $newStatus = 'active';

    }
}


/*
|--------------------------------------------------------------------------
| Update Status
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare("
        UPDATE products
        SET status = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $newStatus,
        $productId
    ]);


    if ($newStatus === 'active') {

        $_SESSION['admin_product_success'] =
            'Product activated successfully.';

    } else {

        $_SESSION['admin_product_success'] =
            'Product deactivated successfully.';

    }

} catch (Throwable $e) {

    $_SESSION['admin_product_error'] =
        'Unable to update the product status. Please try again.';
}


redirect(baseUrl('admin/products/index.php'));
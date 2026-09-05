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
| Check Product
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id, name
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
| Check Existing Orders
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM order_items
    WHERE product_id = ?
");

$stmt->execute([$productId]);

$orderItemCount = (int) $stmt->fetchColumn();

if ($orderItemCount > 0) {

    $_SESSION['admin_product_error'] =
        'This product cannot be deleted because it has already been used in an order. Deactivate it instead.';

    redirect(baseUrl('admin/products/index.php'));
}


/*
|--------------------------------------------------------------------------
| Get Product Images
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT image_path
    FROM product_images
    WHERE product_id = ?
");

$stmt->execute([$productId]);

$images = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Delete Product
|--------------------------------------------------------------------------
*/

try {

    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | Delete Inventory
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        DELETE FROM inventory
        WHERE product_id = ?
    ");

    $stmt->execute([$productId]);


    /*
    |--------------------------------------------------------------------------
    | Delete Cart Items
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        DELETE FROM cart_items
        WHERE product_id = ?
    ");

    $stmt->execute([$productId]);


    /*
    |--------------------------------------------------------------------------
    | Delete Product Images
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        DELETE FROM product_images
        WHERE product_id = ?
    ");

    $stmt->execute([$productId]);


    /*
    |--------------------------------------------------------------------------
    | Delete Product
    |--------------------------------------------------------------------------
    |
    | product_variants are deleted automatically because the
    | foreign key uses ON DELETE CASCADE.
    |
    */

    $stmt = $pdo->prepare("
        DELETE FROM products
        WHERE id = ?
    ");

    $stmt->execute([$productId]);


    $pdo->commit();


    /*
    |--------------------------------------------------------------------------
    | Delete Physical Images
    |--------------------------------------------------------------------------
    */

    foreach ($images as $image) {

        if (empty($image['image_path'])) {
            continue;
        }

        $physicalPath =
            __DIR__ .
            '/../../assets/images/' .
            $image['image_path'];

        if (
            file_exists($physicalPath) &&
            is_file($physicalPath)
        ) {
            unlink($physicalPath);
        }
    }


    $_SESSION['admin_product_success'] =
        'Product deleted successfully.';

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['admin_product_error'] =
        'Unable to delete the product. Please try again.';
}


redirect(baseUrl('admin/products/index.php'));
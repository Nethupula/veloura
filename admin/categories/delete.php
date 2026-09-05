<?php

require_once '../includes/auth.php';


/*
|--------------------------------------------------------------------------
| POST Only
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    redirect(
        baseUrl('admin/categories/index.php')
    );
}


/*
|--------------------------------------------------------------------------
| Get Category ID
|--------------------------------------------------------------------------
*/

$categoryId = (int) ($_POST['id'] ?? 0);

if ($categoryId <= 0) {

    $_SESSION['admin_category_error'] =
        'Invalid category.';

    redirect(
        baseUrl('admin/categories/index.php')
    );
}


/*
|--------------------------------------------------------------------------
| Get Category
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        name,
        image
    FROM categories
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$categoryId]);

$category = $stmt->fetch();

if (!$category) {

    $_SESSION['admin_category_error'] =
        'Category not found.';

    redirect(
        baseUrl('admin/categories/index.php')
    );
}


/*
|--------------------------------------------------------------------------
| Check Products
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT COUNT(*) AS product_count
    FROM products
    WHERE category_id = ?
");

$stmt->execute([$categoryId]);

$productCount =
    (int) $stmt->fetch()['product_count'];


if ($productCount > 0) {

    $_SESSION['admin_category_error'] =
        'Cannot delete "' .
        $category['name'] .
        '" because it contains ' .
        $productCount .
        ' product' .
        ($productCount === 1 ? '' : 's') .
        '. Move or delete the products first.';

    redirect(
        baseUrl('admin/categories/index.php')
    );
}


/*
|--------------------------------------------------------------------------
| Delete Category
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare("
        DELETE FROM categories
        WHERE id = ?
    ");

    $stmt->execute([$categoryId]);


    /*
    |--------------------------------------------------------------------------
    | Delete Physical Image
    |--------------------------------------------------------------------------
    */

    if (!empty($category['image'])) {

        $imagePath =
            __DIR__ .
            '/../../assets/images/categories/' .
            $category['image'];

        if (
            file_exists($imagePath) &&
            is_file($imagePath)
        ) {

            unlink($imagePath);
        }
    }


    $_SESSION['admin_category_success'] =
        'Category "' .
        $category['name'] .
        '" deleted successfully.';

} catch (PDOException $e) {

    $_SESSION['admin_category_error'] =
        'Unable to delete the category. Please try again.';
}


redirect(
    baseUrl('admin/categories/index.php')
);
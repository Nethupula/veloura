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
| Get Current Category
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        name,
        status
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
| Toggle Status
|--------------------------------------------------------------------------
*/

$newStatus =
    $category['status'] === 'active'
        ? 'inactive'
        : 'active';


/*
|--------------------------------------------------------------------------
| Update Category
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare("
        UPDATE categories
        SET status = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $newStatus,
        $categoryId
    ]);


    $_SESSION['admin_category_success'] =
        'Category "' .
        $category['name'] .
        '" is now ' .
        $newStatus .
        '.';

} catch (PDOException $e) {

    $_SESSION['admin_category_error'] =
        'Unable to change category status.';
}


redirect(
    baseUrl('admin/categories/index.php')
);
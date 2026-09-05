<?php

$pageTitle = 'Categories';

require_once '../includes/header.php';

/*
|--------------------------------------------------------------------------
| Messages
|--------------------------------------------------------------------------
*/

$successMessage = $_SESSION['admin_category_success'] ?? '';
$errorMessage = $_SESSION['admin_category_error'] ?? '';

unset($_SESSION['admin_category_success']);
unset($_SESSION['admin_category_error']);

/*
|--------------------------------------------------------------------------
| Get Categories
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        c.id,
        c.name,
        c.slug,
        c.description,
        c.image,
        c.status,
        c.created_at,
        COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p
        ON c.id = p.category_id
    GROUP BY
        c.id,
        c.name,
        c.slug,
        c.description,
        c.image,
        c.status,
        c.created_at
    ORDER BY c.id ASC
");

$categories = $stmt->fetchAll();

?>

<?php if ($successMessage !== ''): ?>

    <div class="admin-category-success">
        <i class="fa-solid fa-circle-check"></i>
        <?= e($successMessage) ?>
    </div>

<?php endif; ?>


<?php if ($errorMessage !== ''): ?>

    <div class="admin-category-error">
        <i class="fa-solid fa-circle-exclamation"></i>
        <?= e($errorMessage) ?>
    </div>

<?php endif; ?>


<div class="admin-page-header">

    <div>
        <h2>Categories</h2>

        <p>
            Organize your Veloura jewelry collection.
        </p>
    </div>

    <a
        href="<?= e(baseUrl('admin/categories/add.php')) ?>"
        class="admin-primary-button"
    >
        <i class="fa-solid fa-plus"></i>
        Add Category
    </a>

</div>


<div class="admin-category-grid">

    <?php if (!empty($categories)): ?>

        <?php foreach ($categories as $category): ?>

            <?php

            if (!empty($category['image'])) {

                $categoryImage = baseUrl(
                    'assets/images/categories/' . $category['image']
                );

            } else {

                $categoryImage = baseUrl(
                    'assets/images/product-placeholder.jpg'
                );

            }

            ?>

            <div class="admin-category-card">

                <div class="admin-category-image">

                    <img
                        src="<?= e($categoryImage) ?>"
                        alt="<?= e($category['name']) ?>"
                    >

                    <span class="category-status category-status-<?= e($category['status']) ?>">
                        <?= e(ucfirst($category['status'])) ?>
                    </span>

                </div>


                <div class="admin-category-card-body">

                    <div class="admin-category-card-title">

                        <div>

                            <h3>
                                <?= e($category['name']) ?>
                            </h3>

                            <span>
                                <?= e($category['slug']) ?>
                            </span>

                        </div>

                    </div>


                    <p class="admin-category-description">

                        <?= e(
                            $category['description']
                            ?: 'No description available.'
                        ) ?>

                    </p>


                    <div class="admin-category-meta">

                        <span>

                            <i class="fa-solid fa-gem"></i>

                            <?= e(
                                number_format(
                                    (int) $category['product_count']
                                )
                            ) ?>

                            products

                        </span>

                    </div>


                    <div class="admin-category-actions">

                        <a
                            href="<?= e(
                                baseUrl(
                                    'admin/categories/edit.php?id=' .
                                    $category['id']
                                )
                            ) ?>"
                            class="category-edit-button"
                        >
                            <i class="fa-solid fa-pen"></i>
                            Edit
                        </a>


                        <form
                            method="POST"
                            action="<?= e(
                                baseUrl(
                                    'admin/categories/toggle-status.php'
                                )
                            ) ?>"
                        >

                            <input
                                type="hidden"
                                name="id"
                                value="<?= e($category['id']) ?>"
                            >

                            <button
                                type="submit"
                                class="category-status-button"
                                title="Change Status"
                                onclick="return confirm('Are you sure you want to change this category status?');"
                            >
                                <i class="fa-solid fa-power-off"></i>
                            </button>

                        </form>


                        <form
                            method="POST"
                            action="<?= e(
                                baseUrl(
                                    'admin/categories/delete.php'
                                )
                            ) ?>"
                        >

                            <input
                                type="hidden"
                                name="id"
                                value="<?= e($category['id']) ?>"
                            >

                            <button
                                type="submit"
                                class="category-delete-button"
                                title="Delete Category"
                                onclick="return confirm('Are you sure you want to permanently delete this category?');"
                            >
                                <i class="fa-solid fa-trash"></i>
                            </button>

                        </form>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <div class="categories-empty">

            <i class="fa-solid fa-layer-group"></i>

            <h3>No Categories Yet</h3>

            <p>
                Create your first jewelry category.
            </p>

            <a
                href="<?= e(baseUrl('admin/categories/add.php')) ?>"
                class="admin-primary-button"
            >
                <i class="fa-solid fa-plus"></i>
                Add Category
            </a>

        </div>

    <?php endif; ?>

</div>


<?php require_once '../includes/footer.php'; ?>
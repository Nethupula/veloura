<?php

$pageTitle = 'Products';

require_once '../includes/header.php';

/*
|--------------------------------------------------------------------------
| Get Products
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        p.id,
        p.name,
        p.sku,
        p.price,
        p.discount_price,
        p.stock_quantity,
        p.featured,
        p.status,
        p.created_at,
        c.name AS category_name,
        (
            SELECT pi.image_path
            FROM product_images pi
            WHERE pi.product_id = p.id
            ORDER BY pi.is_primary DESC, pi.sort_order ASC
            LIMIT 1
        ) AS image_path
    FROM products p
    INNER JOIN categories c
        ON p.category_id = c.id
    ORDER BY p.created_at DESC
");

$products = $stmt->fetchAll();

?>

<div class="admin-page-header">

    <div>

        <h2>Products</h2>

        <p>
            Manage your Veloura jewelry collection.
        </p>

    </div>

    <a
        href="#"
        class="admin-primary-button"
    >
        <i class="fa-solid fa-plus"></i>
        Add Product
    </a>

</div>


<div class="admin-products-card">

    <div class="admin-products-toolbar">

        <div class="product-count">

            <strong>
                <?= e(number_format(count($products))) ?>
            </strong>

            products

        </div>

    </div>


    <?php if (!empty($products)): ?>

        <div class="admin-products-table-wrapper">

            <table class="admin-products-table">

                <thead>

                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Featured</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>

                </thead>

                <tbody>

                <?php foreach ($products as $product): ?>

                    <?php

                    if (!empty($product['image_path'])) {

                        $imagePath = baseUrl(
                            'assets/images/' . $product['image_path']
                        );

                    } else {

                        $imagePath = baseUrl(
                            'assets/images/product-placeholder.jpg'
                        );

                    }

                    if ($product['discount_price'] !== null) {

                        $displayPrice = $product['discount_price'];

                    } else {

                        $displayPrice = $product['price'];

                    }

                    ?>

                    <tr>

                        <!-- Product -->

                        <td>

                            <div class="admin-product-info">

                                <div class="admin-product-image">

                                    <img
                                        src="<?= e($imagePath) ?>"
                                        alt="<?= e($product['name']) ?>"
                                    >

                                </div>

                                <div>

                                    <strong>
                                        <?= e($product['name']) ?>
                                    </strong>

                                    <span>
                                        SKU:
                                        <?= e($product['sku']) ?>
                                    </span>

                                </div>

                            </div>

                        </td>


                        <!-- Category -->

                        <td>

                            <span class="admin-category-name">

                                <?= e($product['category_name']) ?>

                            </span>

                        </td>


                        <!-- Price -->

                        <td>

                            <div class="admin-product-price">

                                <?php if ($product['discount_price'] !== null): ?>

                                    <strong>

                                        <?= e(
                                            formatPrice(
                                                $product['discount_price']
                                            )
                                        ) ?>

                                    </strong>

                                    <del>

                                        <?= e(
                                            formatPrice(
                                                $product['price']
                                            )
                                        ) ?>

                                    </del>

                                <?php else: ?>

                                    <strong>

                                        <?= e(
                                            formatPrice(
                                                $product['price']
                                            )
                                        ) ?>

                                    </strong>

                                <?php endif; ?>

                            </div>

                        </td>


                        <!-- Stock -->

                        <td>

                            <?php if ((int) $product['stock_quantity'] <= 0): ?>

                                <span class="stock-badge out">
                                    Out of Stock
                                </span>

                            <?php elseif ((int) $product['stock_quantity'] <= 5): ?>

                                <span class="stock-badge low">

                                    <?= e(
                                        number_format(
                                            (int) $product['stock_quantity']
                                        )
                                    ) ?>

                                    left

                                </span>

                            <?php else: ?>

                                <span class="stock-badge">

                                    <?= e(
                                        number_format(
                                            (int) $product['stock_quantity']
                                        )
                                    ) ?>

                                </span>

                            <?php endif; ?>

                        </td>


                        <!-- Featured -->

                        <td>

                            <?php if ((int) $product['featured'] === 1): ?>

                                <span class="featured-badge">

                                    <i class="fa-solid fa-star"></i>

                                    Featured

                                </span>

                            <?php else: ?>

                                <span class="not-featured">
                                    —
                                </span>

                            <?php endif; ?>

                        </td>


                        <!-- Status -->

                        <td>

                            <?php

                            $statusClass = $product['status'];

                            $statusText = ucfirst(
                                str_replace(
                                    '_',
                                    ' ',
                                    $product['status']
                                )
                            );

                            ?>

                            <span
                                class="product-status status-<?= e($statusClass) ?>"
                            >

                                <?= e($statusText) ?>

                            </span>

                        </td>


                        <!-- Actions -->

                        <td>

                            <div class="product-actions">

                                <a
                                    href="#"
                                    title="Edit Product"
                                >
                                    <i class="fa-solid fa-pen"></i>
                                </a>

                                <a
                                    href="#"
                                    title="Delete Product"
                                >
                                    <i class="fa-solid fa-trash"></i>
                                </a>

                            </div>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php else: ?>

        <div class="products-empty">

            <i class="fa-solid fa-gem"></i>

            <h3>No Products Yet</h3>

            <p>
                Start building your Veloura collection
                by adding your first product.
            </p>

            <a
                href="#"
                class="admin-primary-button"
            >
                <i class="fa-solid fa-plus"></i>
                Add Your First Product
            </a>

        </div>

    <?php endif; ?>

</div>


<?php require_once '../includes/footer.php'; ?>
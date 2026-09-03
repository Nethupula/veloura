<?php

require_once 'includes/header.php';

?>

<!-- ========================================
     HERO SECTION
======================================== -->

<section class="veloura-hero">

    <div class="veloura-hero-overlay"></div>

    <div class="container">
        <div class="veloura-hero-content">

            <p class="hero-eyebrow">
                TIMELESS JEWELRY
            </p>

            <h1>
                Timeless Elegance,<br>
                Made for You
            </h1>

            <p class="hero-description">
                Discover elegant pieces designed to make
                every moment shine.
            </p>

            <div class="hero-buttons">

                <a href="shop.php" class="btn veloura-btn-primary">
                    Shop Collection
                </a>

                <a href="shop.php" class="btn veloura-btn-secondary">
                    Explore Jewelry
                </a>

            </div>

        </div>
    </div>

</section>


<!-- ========================================
     SHOP BY CATEGORY
======================================== -->

<section class="category-section">

    <div class="container">

        <div class="section-heading">

            <p class="section-eyebrow">
                EXPLORE OUR COLLECTION
            </p>

            <h2>Shop by Category</h2>

            <p>
                Find the perfect piece to complement your style.
            </p>

        </div>

        <div class="row g-4">

            <?php

            $categoryQuery = $pdo->query("
                SELECT id, name, slug, image
                FROM categories
                WHERE status = 'active'
                ORDER BY id ASC
            ");

            $categories = $categoryQuery->fetchAll();

            foreach ($categories as $category):

                $categoryImage = !empty($category['image'])
                    ? 'assets/images/categories/' . $category['image']
                    : 'assets/images/category-placeholder.jpg';

            ?>

                <div class="col-6 col-md-4 col-lg-2">

                    <a
                        href="shop.php?category=<?= e($category['slug']) ?>"
                        class="category-card"
                    >

                        <div class="category-image">

                            <img
                                src="<?= e($categoryImage) ?>"
                                alt="<?= e($category['name']) ?>"
                            >

                        </div>

                        <h3>
                            <?= e($category['name']) ?>
                        </h3>

                        <span>
                            Explore
                            <i class="fa-solid fa-arrow-right"></i>
                        </span>

                    </a>

                </div>

            <?php endforeach; ?>

        </div>

    </div>

</section>
<!-- ========================================
     FEATURED PRODUCTS
======================================== -->

<section class="featured-section">

    <div class="container">

        <div class="section-heading">

            <p class="section-eyebrow">
                OUR FAVORITES
            </p>

            <h2>Featured Collection</h2>

            <p>
                Discover pieces selected to bring effortless elegance
                to every occasion.
            </p>

        </div>

        <div class="row g-4">

            <?php

            $featuredQuery = $pdo->query("
                SELECT
                    p.id,
                    p.name,
                    p.slug,
                    p.price,
                    p.discount_price,
                    p.material,
                    c.name AS category_name,
                    (
                        SELECT pi.image_path
                        FROM product_images pi
                        WHERE pi.product_id = p.id
                        ORDER BY pi.is_primary DESC, pi.sort_order ASC, pi.id ASC
                        LIMIT 1
                    ) AS image_path
                FROM products p
                INNER JOIN categories c
                    ON c.id = p.category_id
                WHERE p.status = 'active'
                    AND p.featured = 1
                    AND c.status = 'active'
                ORDER BY p.created_at DESC
                LIMIT 8
            ");

            $featuredProducts = $featuredQuery->fetchAll();

            foreach ($featuredProducts as $product):

                $productImage = !empty($product['image_path'])
                    ? 'assets/images/' . $product['image_path']
                    : 'assets/images/product-placeholder.jpg';

            ?>

                <div class="col-6 col-md-4 col-lg-3">

                    <div class="product-card">

                        <!-- Product Image -->
                        <a
                            href="products/product-details.php?slug=<?= e($product['slug']) ?>"
                            class="product-image"
                        >

                            <img
                                src="<?= e($productImage) ?>"
                                alt="<?= e($product['name']) ?>"
                            >

                            <?php if (!empty($product['discount_price'])): ?>

                                <span class="product-badge">
                                    SALE
                                </span>

                            <?php endif; ?>

                        </a>


                        <!-- Product Information -->
                        <div class="product-info">

                            <p class="product-category">
                                <?= e($product['category_name']) ?>
                            </p>

                            <h3>
                                <a href="products/product-details.php?slug=<?= e($product['slug']) ?>">
                                    <?= e($product['name']) ?>
                                </a>
                            </h3>

                            <div class="product-price">

                                <?php if (!empty($product['discount_price'])): ?>

                                    <span class="sale-price">
                                        <?= formatPrice($product['discount_price']) ?>
                                    </span>

                                    <span class="original-price">
                                        <?= formatPrice($product['price']) ?>
                                    </span>

                                <?php else: ?>

                                    <span class="sale-price">
                                        <?= formatPrice($product['price']) ?>
                                    </span>

                                <?php endif; ?>

                            </div>

                            <a
                                href="products/product-details.php?slug=<?= e($product['slug']) ?>"
                                class="product-view-btn"
                            >
                                View Product
                            </a>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    </div>

</section>


</body>
</html>
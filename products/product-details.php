<?php

require_once '../includes/header.php';

$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: ../shop.php');
    exit;
}

$productQuery = $pdo->prepare("
    SELECT
        p.*,
        c.name AS category_name
    FROM products p
    INNER JOIN categories c
        ON c.id = p.category_id
    WHERE p.slug = ?
        AND p.status = 'active'
        AND c.status = 'active'
    LIMIT 1
");

$productQuery->execute([$slug]);

$product = $productQuery->fetch();

if (!$product) {
    http_response_code(404);
    echo '<div class="container py-5">';
    echo '<h1>Product Not Found</h1>';
    echo '<p>The product you are looking for does not exist.</p>';
    echo '<a href="../shop.php">Back to Shop</a>';
    echo '</div>';
    exit;
}


/*
|--------------------------------------------------------------------------
| Product Images
|--------------------------------------------------------------------------
*/

$imageQuery = $pdo->prepare("
    SELECT
        image_path,
        alt_text,
        is_primary
    FROM product_images
    WHERE product_id = ?
    ORDER BY is_primary DESC, sort_order ASC, id ASC
");

$imageQuery->execute([$product['id']]);

$productImages = $imageQuery->fetchAll();


/*
|--------------------------------------------------------------------------
| Main Product Image
|--------------------------------------------------------------------------
*/

if (!empty($productImages)) {

    $mainImage = '../assets/images/' . $productImages[0]['image_path'];

} else {

    $mainImage = '../assets/images/product-placeholder.jpg';

}


/*
|--------------------------------------------------------------------------
| Product Price
|--------------------------------------------------------------------------
*/

$hasDiscount = !empty($product['discount_price']);

$currentPrice = $hasDiscount
    ? $product['discount_price']
    : $product['price'];

/*
|--------------------------------------------------------------------------
| Product Variants
|--------------------------------------------------------------------------
*/

$variantQuery = $pdo->prepare("
    SELECT
        id,
        variant_name,
        sku,
        price,
        stock_quantity
    FROM product_variants
    WHERE product_id = ?
        AND status = 'active'
        AND stock_quantity > 0
    ORDER BY id ASC
");

$variantQuery->execute([$product['id']]);

$productVariants = $variantQuery->fetchAll();    

?>

<!-- ========================================
     PRODUCT DETAILS
======================================== -->

<section class="product-details-section">

    <div class="container">

        <div class="row g-5">

            <!-- Product Image -->

            <div class="col-lg-6">

                <div class="product-details-image">

                    <img
                        src="<?= e($mainImage) ?>"
                        alt="<?= e($product['name']) ?>"
                    >

                    <?php if ($hasDiscount): ?>

                        <span class="product-details-badge">
                            SALE
                        </span>

                    <?php endif; ?>

                </div>

            </div>


            <!-- Product Information -->

            <div class="col-lg-6">

                <div class="product-details-info">

                    <p class="product-details-category">
                        <?= e($product['category_name']) ?>
                    </p>

                    <h1>
                        <?= e($product['name']) ?>
                    </h1>

                    <div class="product-details-price">

                        <span class="current-price">
                            <?= formatPrice($currentPrice) ?>
                        </span>

                        <?php if ($hasDiscount): ?>

                            <span class="old-price">
                                <?= formatPrice($product['price']) ?>
                            </span>

                        <?php endif; ?>

                    </div>


                    <?php if (!empty($product['short_description'])): ?>

                        <p class="product-short-description">
                            <?= e($product['short_description']) ?>
                        </p>

                    <?php endif; ?>


                    <?php if (!empty($product['description'])): ?>

                        <div class="product-description">

                            <?= nl2br(e($product['description'])) ?>

                        </div>

                    <?php endif; ?>


                    <!-- Product Information -->

                    <div class="product-meta">

                        <?php if (!empty($product['material'])): ?>

                            <div>
                                <strong>Material:</strong>
                                <?= e($product['material']) ?>
                            </div>

                        <?php endif; ?>

                        <?php if (!empty($product['color'])): ?>

                            <div>
                                <strong>Color:</strong>
                                <?= e($product['color']) ?>
                            </div>

                        <?php endif; ?>

                        <div>
                            <strong>SKU:</strong>
                            <?= e($product['sku']) ?>
                        </div>

                    </div>
                    <!-- Product Variant -->

<?php if (!empty($productVariants)): ?>

    <div class="product-variant">

        <label>
            Select Size
        </label>

        <div class="variant-options">

            <?php foreach ($productVariants as $index => $variant): ?>

                <label class="variant-option">

                    <input
                        type="radio"
                        name="variant_id"
                        value="<?= (int) $variant['id'] ?>"
                        <?= $index === 0 ? 'checked' : '' ?>
                    >

                    <span>
                        <?= e($variant['variant_name']) ?>
                    </span>

                </label>

            <?php endforeach; ?>

        </div>

    </div>

<?php endif; ?>


                    <!-- Quantity -->

                    <div class="product-quantity">

                        <label for="quantity">
                            Quantity
                        </label>

                        <div class="quantity-control">

                            <button
                                type="button"
                                id="decreaseQuantity"
                            >
                                −
                            </button>

                            <input
                                type="number"
                                id="quantity"
                                value="1"
                                min="1"
                                max="<?= (int) $product['stock_quantity'] ?>"
                            >

                            <button
                                type="button"
                                id="increaseQuantity"
                            >
                                +
                            </button>

                        </div>

                    </div>


                    <!-- Add to Cart -->

                    <form
    method="POST"
    action="<?= e(baseUrl('cart/add-to-cart.php')) ?>"
>

    <input
        type="hidden"
        name="product_id"
        value="<?= (int) $product['id'] ?>"
    >

    <input
        type="hidden"
        name="product_slug"
        value="<?= e($product['slug']) ?>"
    >

    <?php if (!empty($productVariants)): ?>

        <input
            type="hidden"
            name="variant_id"
            id="selectedVariant"
            value="<?= (int) $productVariants[0]['id'] ?>"
        >

    <?php endif; ?>


    <input
        type="hidden"
        name="quantity"
        id="cartQuantity"
        value="1"
    >


    <button
        type="submit"
        class="add-to-cart-btn"
    >

        <i class="fa-solid fa-bag-shopping"></i>

        Add to Cart

    </button>

</form>


                    <?php if ((int) $product['stock_quantity'] > 0): ?>

                        <p class="stock-message">
                            <i class="fa-solid fa-circle-check"></i>
                            In Stock
                        </p>

                    <?php else: ?>

                        <p class="stock-message out-of-stock">
                            Out of Stock
                        </p>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

</section>


<script>

const quantityInput =
    document.getElementById('quantity');

const cartQuantity =
    document.getElementById('cartQuantity');

const decreaseButton =
    document.getElementById('decreaseQuantity');

const increaseButton =
    document.getElementById('increaseQuantity');

const selectedVariant =
    document.getElementById('selectedVariant');


function updateCartQuantity() {

    if (cartQuantity) {
        cartQuantity.value = quantityInput.value;
    }

}


/*
|--------------------------------------------------------------------------
| Decrease Quantity
|--------------------------------------------------------------------------
*/

decreaseButton.addEventListener('click', function () {

    let quantity =
        parseInt(quantityInput.value);

    if (quantity > 1) {

        quantityInput.value =
            quantity - 1;

        updateCartQuantity();
    }

});


/*
|--------------------------------------------------------------------------
| Increase Quantity
|--------------------------------------------------------------------------
*/

increaseButton.addEventListener('click', function () {

    let quantity =
        parseInt(quantityInput.value);

    let maxQuantity =
        parseInt(quantityInput.getAttribute('max'));

    if (quantity < maxQuantity) {

        quantityInput.value =
            quantity + 1;

        updateCartQuantity();
    }

});


/*
|--------------------------------------------------------------------------
| Manual Quantity Change
|--------------------------------------------------------------------------
*/

quantityInput.addEventListener('change', function () {

    let quantity =
        parseInt(quantityInput.value);

    let maxQuantity =
        parseInt(quantityInput.getAttribute('max'));

    if (quantity < 1) {
        quantityInput.value = 1;
    }

    if (quantity > maxQuantity) {
        quantityInput.value = maxQuantity;
    }

    updateCartQuantity();

});


/*
|--------------------------------------------------------------------------
| Variant Selection
|--------------------------------------------------------------------------
*/

document.querySelectorAll(
    'input[name="variant_id"]'
).forEach(function (radio) {

    radio.addEventListener('change', function () {

        if (selectedVariant) {

            selectedVariant.value =
                this.value;

        }

    });

});

</script>


</body>
</html>
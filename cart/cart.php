<?php

require_once '../includes/bootstrap.php';

if (!isCustomerLoggedIn()) {
    redirect(baseUrl('customer/login.php'));
}

$userId = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| Get customer's cart
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

$cartItems = [];
$subtotal = 0;

if ($cart) {

    $cartId = (int) $cart['id'];

    /*
    |--------------------------------------------------------------------------
    | Get cart items
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            ci.id AS cart_item_id,
            ci.product_id,
            ci.variant_id,
            ci.quantity,

            p.name,
            p.slug,
            p.price,
            p.discount_price,
            p.stock_quantity AS product_stock,

            pv.variant_name,
            pv.sku AS variant_sku,
            pv.price AS variant_price,
            pv.stock_quantity AS variant_stock,

            pi.image_path

        FROM cart_items ci

        INNER JOIN products p
            ON ci.product_id = p.id

        LEFT JOIN product_variants pv
            ON ci.variant_id = pv.id

        LEFT JOIN product_images pi
            ON pi.product_id = p.id
            AND pi.is_primary = 1

        WHERE ci.cart_id = ?

        ORDER BY ci.created_at DESC
    ");

    $stmt->execute([$cartId]);

    $cartItems = $stmt->fetchAll();

    /*
    |--------------------------------------------------------------------------
    | Calculate subtotal
    |--------------------------------------------------------------------------
    */

    foreach ($cartItems as &$item) {

        if ($item['variant_id'] && $item['variant_price'] !== null) {

            $unitPrice = (float) $item['variant_price'];

        } elseif ($item['discount_price'] !== null) {

            $unitPrice = (float) $item['discount_price'];

        } else {

            $unitPrice = (float) $item['price'];
        }

        /*
        | Available stock
        */

        if ($item['variant_id']) {
            $availableStock = (int) $item['variant_stock'];
        } else {
            $availableStock = (int) $item['product_stock'];
        }

        $item['unit_price'] = $unitPrice;
        $item['available_stock'] = $availableStock;
        $item['item_total'] = $unitPrice * (int) $item['quantity'];

        $subtotal += $item['item_total'];
    }

    unset($item);
}

require_once '../includes/header.php';

?>

<main class="cart-page">

    <section class="cart-header-section">

        <div class="container">

            <div class="cart-header-content">

                <span class="cart-eyebrow">
                    Your Selection
                </span>

                <h1>
                    Shopping Cart
                </h1>

                <p>
                    Review your selected pieces before completing your order.
                </p>

            </div>

        </div>

    </section>


    <section class="cart-content-section">

        <div class="container">

            <?php if (empty($cartItems)): ?>

                <!-- Empty Cart -->

                <div class="empty-cart">

                    <div class="empty-cart-icon">
                        <i class="fa-solid fa-bag-shopping"></i>
                    </div>

                    <h2>
                        Your cart is empty
                    </h2>

                    <p>
                        Discover something beautiful and add it to your collection.
                    </p>

                    <a
                        href="<?= e(baseUrl('shop.php')) ?>"
                        class="cart-shop-btn"
                    >
                        Continue Shopping
                    </a>

                </div>

            <?php else: ?>

                <div class="cart-layout">

                    <!-- Cart Items -->

                    <div class="cart-items-container">

                        <?php foreach ($cartItems as $item): ?>

                            <div class="cart-item">

                                <!-- Product Image -->

                                <div class="cart-item-image">

                                    <?php if (!empty($item['image_path'])): ?>

                                        <img
                                            src="<?= e(baseUrl('assets/images/' . $item['image_path'])) ?>"
                                            alt="<?= e($item['name']) ?>"
                                        >

                                    <?php else: ?>

                                        <div class="cart-image-placeholder">
                                            <i class="fa-regular fa-image"></i>
                                        </div>

                                    <?php endif; ?>

                                </div>


                                <!-- Product Details -->

                                <div class="cart-item-details">

                                    <div class="cart-item-info">

                                        <span class="cart-item-category">
                                            Jewelry
                                        </span>

                                        <h3>
                                            <a href="<?= e(baseUrl('products/product-details.php?slug=' . $item['slug'])) ?>">
                                                <?= e($item['name']) ?>
                                            </a>
                                        </h3>

                                        <?php if (!empty($item['variant_name'])): ?>

                                            <p class="cart-item-variant">
                                                <?= e($item['variant_name']) ?>
                                            </p>

                                        <?php endif; ?>

                                        <p class="cart-item-price">
                                            <?= e(formatPrice($item['unit_price'])) ?>
                                        </p>

                                    </div>


                                    <!-- Quantity + Remove -->

                                    <div class="cart-item-actions">

                                        <form
                                            action="<?= e(baseUrl('cart/update-cart.php')) ?>"
                                            method="POST"
                                            class="cart-quantity-form"
                                        >

                                            <input
                                                type="hidden"
                                                name="cart_item_id"
                                                value="<?= (int) $item['cart_item_id'] ?>"
                                            >

                                            <button
                                                type="button"
                                                class="quantity-btn quantity-minus"
                                                data-target="quantity-<?= (int) $item['cart_item_id'] ?>"
                                            >
                                                −
                                            </button>

                                            <input
                                                type="number"
                                                id="quantity-<?= (int) $item['cart_item_id'] ?>"
                                                name="quantity"
                                                value="<?= (int) $item['quantity'] ?>"
                                                min="1"
                                                max="<?= (int) $item['available_stock'] ?>"
                                                class="quantity-input"
                                            >

                                            <button
                                                type="button"
                                                class="quantity-btn quantity-plus"
                                                data-target="quantity-<?= (int) $item['cart_item_id'] ?>"
                                            >
                                                +
                                            </button>

                                            <button
                                                type="submit"
                                                class="update-cart-btn"
                                            >
                                                Update
                                            </button>

                                        </form>


                                        <form
                                            action="<?= e(baseUrl('cart/remove-from-cart.php')) ?>"
                                            method="POST"
                                            class="remove-cart-form"
                                        >

                                            <input
                                                type="hidden"
                                                name="cart_item_id"
                                                value="<?= (int) $item['cart_item_id'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="remove-cart-btn"
                                            >
                                                <i class="fa-regular fa-trash-can"></i>
                                                Remove
                                            </button>

                                        </form>

                                    </div>

                                </div>


                                <!-- Item Total -->

                                <div class="cart-item-total">

                                    <?= e(formatPrice($item['item_total'])) ?>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>


                    <!-- Cart Summary -->

                    <aside class="cart-summary">

                        <h2>
                            Order Summary
                        </h2>

                        <div class="summary-row">

                            <span>
                                Subtotal
                            </span>

                            <strong>
                                <?= e(formatPrice($subtotal)) ?>
                            </strong>

                        </div>

                        <div class="summary-row">

                            <span>
                                Shipping
                            </span>

                            <span class="shipping-note">
                                Calculated at checkout
                            </span>

                        </div>

                        <div class="summary-divider"></div>

                        <div class="summary-total">

                            <span>
                                Total
                            </span>

                            <strong>
                                <?= e(formatPrice($subtotal)) ?>
                            </strong>

                        </div>

                        <a
                            href="<?= e(baseUrl('checkout/checkout.php')) ?>"
                            class="checkout-btn"
                        >
                            Proceed to Checkout
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>

                        <a
                            href="<?= e(baseUrl('shop.php')) ?>"
                            class="continue-shopping-btn"
                        >
                            Continue Shopping
                        </a>

                    </aside>

                </div>

            <?php endif; ?>

        </div>

    </section>

</main>


<script>

document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | Quantity Buttons
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll('.quantity-minus').forEach(function (button) {

        button.addEventListener('click', function () {

            const input = document.getElementById(
                this.dataset.target
            );

            if (!input) {
                return;
            }

            let value = parseInt(input.value) || 1;

            if (value > 1) {
                input.value = value - 1;
            }

        });

    });


    document.querySelectorAll('.quantity-plus').forEach(function (button) {

        button.addEventListener('click', function () {

            const input = document.getElementById(
                this.dataset.target
            );

            if (!input) {
                return;
            }

            let value = parseInt(input.value) || 1;
            let max = parseInt(input.max);

            if (!max || value < max) {
                input.value = value + 1;
            }

        });

    });

});

</script>

</body>
</html>
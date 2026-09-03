<?php

require_once '../includes/bootstrap.php';

require_once '../includes/header.php';


/*
|--------------------------------------------------------------------------
| Login Check
|--------------------------------------------------------------------------
*/

if (!isCustomerLoggedIn()):

?>

<section class="empty-cart-section">

    <div class="empty-cart">

        <i class="fa-solid fa-bag-shopping"></i>

        <h1>Your Cart</h1>

        <p>
            Please sign in to view your shopping cart.
        </p>

        <a
            href="<?= e(baseUrl('customer/login.php')) ?>"
            class="cart-action-btn"
        >
            Sign In
        </a>

    </div>

</section>

<?php

    exit;

endif;


/*
|--------------------------------------------------------------------------
| Get Cart
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

$cartItems = [];


if ($cart) {

    $itemsQuery = $pdo->prepare("
        SELECT
            ci.id,
            ci.quantity,

            p.id AS product_id,
            p.name,
            p.slug,
            p.price,
            p.discount_price,

            pv.id AS variant_id,
            pv.variant_name,
            pv.price AS variant_price,

            (
                SELECT pi.image_path
                FROM product_images pi
                WHERE pi.product_id = p.id
                ORDER BY
                    pi.is_primary DESC,
                    pi.sort_order ASC,
                    pi.id ASC
                LIMIT 1
            ) AS image_path

        FROM cart_items ci

        INNER JOIN products p
            ON p.id = ci.product_id

        LEFT JOIN product_variants pv
            ON pv.id = ci.variant_id

        WHERE ci.cart_id = ?

        ORDER BY ci.created_at DESC
    ");

    $itemsQuery->execute([
        $cart['id']
    ]);

    $cartItems = $itemsQuery->fetchAll();
}

?>

<!-- ========================================
     CART
======================================== -->

<section class="cart-section">

    <div class="container">

        <div class="cart-heading">

            <p class="section-eyebrow">
                YOUR SELECTION
            </p>

            <h1>Your Shopping Cart</h1>

        </div>


        <?php if (empty($cartItems)): ?>

            <div class="empty-cart">

                <i class="fa-solid fa-bag-shopping"></i>

                <h2>Your cart is empty</h2>

                <p>
                    Discover something beautiful from our collection.
                </p>

                <a
                    href="<?= e(baseUrl('shop.php')) ?>"
                    class="cart-action-btn"
                >
                    Continue Shopping
                </a>

            </div>


        <?php else: ?>


            <div class="row g-5">

                <!-- Cart Items -->

                <div class="col-lg-8">

                    <?php

                    $cartSubtotal = 0;

                    foreach ($cartItems as $item):

                        $unitPrice = !empty($item['variant_price'])
                            ? $item['variant_price']
                            : (
                                !empty($item['discount_price'])
                                    ? $item['discount_price']
                                    : $item['price']
                            );

                        $itemSubtotal =
                            $unitPrice * $item['quantity'];

                        $cartSubtotal += $itemSubtotal;

                        $itemImage = !empty($item['image_path'])
                            ? baseUrl(
                                'assets/images/' .
                                $item['image_path']
                            )
                            : baseUrl(
                                'assets/images/product-placeholder.jpg'
                            );

                    ?>

                        <div class="cart-item">

                            <div class="cart-item-image">

                                <img
                                    src="<?= e($itemImage) ?>"
                                    alt="<?= e($item['name']) ?>"
                                >

                            </div>


                            <div class="cart-item-details">

                                <p class="cart-item-category">
                                    Jewelry
                                </p>

                                <h3>
                                    <?= e($item['name']) ?>
                                </h3>

                                <?php if (!empty($item['variant_name'])): ?>

                                    <p>
                                        <?= e($item['variant_name']) ?>
                                    </p>

                                <?php endif; ?>

                                <p class="cart-item-price">
                                    <?= formatPrice($unitPrice) ?>
                                </p>

                                <p>
                                    Quantity:
                                    <?= (int) $item['quantity'] ?>
                                </p>

                            </div>


                            <div class="cart-item-total">

                                <?= formatPrice($itemSubtotal) ?>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>


                <!-- Summary -->

                <div class="col-lg-4">

                    <div class="cart-summary">

                        <h2>
                            Order Summary
                        </h2>

                        <div class="summary-row">

                            <span>
                                Subtotal
                            </span>

                            <strong>
                                <?= formatPrice($cartSubtotal) ?>
                            </strong>

                        </div>

                        <div class="summary-row">

                            <span>
                                Shipping
                            </span>

                            <span>
                                Calculated at checkout
                            </span>

                        </div>

                        <div class="summary-total">

                            <span>
                                Total
                            </span>

                            <strong>
                                <?= formatPrice($cartSubtotal) ?>
                            </strong>

                        </div>

                        <a
                            href="<?= e(baseUrl('checkout/checkout.php')) ?>"
                            class="checkout-btn"
                        >
                            Proceed to Checkout
                        </a>

                    </div>

                </div>

            </div>


        <?php endif; ?>

    </div>

</section>


</body>
</html>
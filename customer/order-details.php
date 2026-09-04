<?php

require_once '../includes/bootstrap.php';

if (!isCustomerLoggedIn()) {
    redirect(baseUrl('customer/login.php'));
}

$userId = (int) $_SESSION['user_id'];

$orderId = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

if ($orderId <= 0) {
    redirect(baseUrl('customer/orders.php'));
}

/*
|--------------------------------------------------------------------------
| Get Order
|--------------------------------------------------------------------------
|
| IMPORTANT:
| The user_id condition ensures that a customer can only
| view their own order.
|
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        order_number,
        subtotal,
        discount_amount,
        shipping_amount,
        total_amount,
        coupon_code,
        payment_method,
        payment_status,
        order_status,
        customer_name,
        customer_email,
        customer_phone,
        shipping_address,
        shipping_city,
        shipping_district,
        shipping_postal_code,
        notes,
        created_at
    FROM orders
    WHERE id = ?
      AND user_id = ?
    LIMIT 1
");

$stmt->execute([
    $orderId,
    $userId
]);

$order = $stmt->fetch();

if (!$order) {
    redirect(baseUrl('customer/orders.php'));
}


/*
|--------------------------------------------------------------------------
| Get Order Items
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        oi.product_id,
        oi.variant_id,
        oi.product_name,
        oi.sku,
        oi.quantity,
        oi.unit_price,
        oi.subtotal,
        pv.variant_name
    FROM order_items oi
    LEFT JOIN product_variants pv
        ON oi.variant_id = pv.id
    WHERE oi.order_id = ?
    ORDER BY oi.id ASC
");

$stmt->execute([
    $orderId
]);

$orderItems = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Calculate Total Quantity
|--------------------------------------------------------------------------
*/

$totalQuantity = 0;

foreach ($orderItems as $item) {
    $totalQuantity += (int) $item['quantity'];
}


/*
|--------------------------------------------------------------------------
| Format Order Status
|--------------------------------------------------------------------------
*/

$orderStatus = ucfirst(
    str_replace(
        '_',
        ' ',
        $order['order_status']
    )
);

$paymentStatus = ucfirst(
    str_replace(
        '_',
        ' ',
        $order['payment_status']
    )
);

$paymentMethod = strtoupper(
    $order['payment_method']
);


require_once '../includes/header.php';

?>

<main class="order-details-page">

    <!-- =====================================================
         Page Header
    ====================================================== -->

    <section class="order-details-header">

        <div class="container">

            <div class="order-details-header-content">

                <span class="order-details-eyebrow">
                    Order Details
                </span>

                <h1>
                    <?= e($order['order_number']) ?>
                </h1>

                <p>
                    Thank you for shopping with Veloura.
                </p>

            </div>

        </div>

    </section>


    <!-- =====================================================
         Order Details Content
    ====================================================== -->

    <section class="order-details-content">

        <div class="container">

            <div class="order-details-layout">


                <!-- =================================================
                     Main Content
                ================================================== -->

                <div class="order-details-main">


                    <!-- Order Status -->

                    <div class="order-details-card">

                        <div class="order-details-card-header">

                            <div>

                                <span class="order-details-card-eyebrow">
                                    Order Information
                                </span>

                                <h2>
                                    Order Status
                                </h2>

                            </div>

                            <span class="details-status-badge status-<?= e($order['order_status']) ?>">
                                <?= e($orderStatus) ?>
                            </span>

                        </div>


                        <div class="order-progress">

                            <div class="progress-step active">

                                <span class="progress-icon">
                                    <i class="fa-solid fa-receipt"></i>
                                </span>

                                <span>
                                    Order Placed
                                </span>

                            </div>


                            <div class="progress-line
                                <?= in_array(
                                    $order['order_status'],
                                    [
                                        'confirmed',
                                        'processing',
                                        'shipped',
                                        'delivered'
                                    ],
                                    true
                                ) ? 'active' : '' ?>">
                            </div>


                            <div class="progress-step
                                <?= in_array(
                                    $order['order_status'],
                                    [
                                        'confirmed',
                                        'processing',
                                        'shipped',
                                        'delivered'
                                    ],
                                    true
                                ) ? 'active' : '' ?>">

                                <span class="progress-icon">
                                    <i class="fa-solid fa-box"></i>
                                </span>

                                <span>
                                    Processing
                                </span>

                            </div>


                            <div class="progress-line
                                <?= in_array(
                                    $order['order_status'],
                                    [
                                        'shipped',
                                        'delivered'
                                    ],
                                    true
                                ) ? 'active' : '' ?>">
                            </div>


                            <div class="progress-step
                                <?= in_array(
                                    $order['order_status'],
                                    [
                                        'shipped',
                                        'delivered'
                                    ],
                                    true
                                ) ? 'active' : '' ?>">

                                <span class="progress-icon">
                                    <i class="fa-solid fa-truck"></i>
                                </span>

                                <span>
                                    Shipped
                                </span>

                            </div>


                            <div class="progress-line
                                <?= $order['order_status'] === 'delivered'
                                    ? 'active'
                                    : '' ?>">
                            </div>


                            <div class="progress-step
                                <?= $order['order_status'] === 'delivered'
                                    ? 'active'
                                    : '' ?>">

                                <span class="progress-icon">
                                    <i class="fa-solid fa-check"></i>
                                </span>

                                <span>
                                    Delivered
                                </span>

                            </div>

                        </div>

                    </div>


                    <!-- Order Items -->

                    <div class="order-details-card">

                        <div class="order-details-card-header">

                            <div>

                                <span class="order-details-card-eyebrow">
                                    Your Purchase
                                </span>

                                <h2>
                                    Order Items
                                </h2>

                            </div>

                            <span class="details-item-count">
                                <?= $totalQuantity ?>
                                <?= $totalQuantity === 1 ? 'Item' : 'Items' ?>
                            </span>

                        </div>


                        <div class="details-items">

                            <?php foreach ($orderItems as $item): ?>

                                <div class="details-item">

                                    <div class="details-item-image">

                                        <?php

                                        $stmt = $pdo->prepare("
                                            SELECT image_path
                                            FROM product_images
                                            WHERE product_id = ?
                                              AND is_primary = 1
                                            LIMIT 1
                                        ");

                                        $stmt->execute([
                                            $item['product_id']
                                        ]);

                                        $productImage = $stmt->fetch();

                                        ?>

                                        <?php if ($productImage && !empty($productImage['image_path'])): ?>

                                            <img
                                                src="<?= e(
                                                    baseUrl(
                                                        'assets/images/' .
                                                        $productImage['image_path']
                                                    )
                                                ) ?>"
                                                alt="<?= e($item['product_name']) ?>"
                                            >

                                        <?php else: ?>

                                            <div class="details-image-placeholder">
                                                <i class="fa-regular fa-image"></i>
                                            </div>

                                        <?php endif; ?>

                                    </div>


                                    <div class="details-item-info">

                                        <h3>
                                            <?= e($item['product_name']) ?>
                                        </h3>

                                        <?php if (!empty($item['variant_name'])): ?>

                                            <span>
                                                <?= e($item['variant_name']) ?>
                                            </span>

                                        <?php endif; ?>

                                        <small>
                                            SKU: <?= e($item['sku']) ?>
                                        </small>

                                    </div>


                                    <div class="details-item-quantity">

                                        × <?= (int) $item['quantity'] ?>

                                    </div>


                                    <div class="details-item-price">

                                        <?= e(
                                            formatPrice(
                                                $item['subtotal']
                                            )
                                        ) ?>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    </div>


                    <!-- Delivery Address -->

                    <div class="order-details-card">

                        <div class="order-details-card-header">

                            <div>

                                <span class="order-details-card-eyebrow">
                                    Delivery
                                </span>

                                <h2>
                                    Shipping Address
                                </h2>

                            </div>

                        </div>


                        <div class="details-address">

                            <div class="details-address-icon">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>

                            <div>

                                <strong>
                                    <?= e($order['customer_name']) ?>
                                </strong>

                                <p>
                                    <?= e($order['shipping_address']) ?><br>

                                    <?= e($order['shipping_city']) ?>,
                                    <?= e($order['shipping_district']) ?>

                                    <?php if (!empty($order['shipping_postal_code'])): ?>

                                        <br>
                                        <?= e($order['shipping_postal_code']) ?>

                                    <?php endif; ?>

                                </p>

                                <span>
                                    <i class="fa-solid fa-phone"></i>
                                    <?= e($order['customer_phone']) ?>
                                </span>

                            </div>

                        </div>

                    </div>


                    <!-- Notes -->

                    <?php if (!empty($order['notes'])): ?>

                        <div class="order-details-card">

                            <div class="order-details-card-header">

                                <div>

                                    <span class="order-details-card-eyebrow">
                                        Additional Information
                                    </span>

                                    <h2>
                                        Order Notes
                                    </h2>

                                </div>

                            </div>


                            <div class="details-notes">

                                <?= nl2br(e($order['notes'])) ?>

                            </div>

                        </div>

                    <?php endif; ?>

                </div>


                <!-- =================================================
                     Summary
                ================================================== -->

                <aside class="order-details-sidebar">

                    <div class="details-summary-card">

                        <span class="order-details-card-eyebrow">
                            Order Summary
                        </span>

                        <h2>
                            Summary
                        </h2>


                        <div class="details-summary-row">

                            <span>
                                Order Date
                            </span>

                            <strong>
                                <?= e(
                                    date(
                                        'd M Y',
                                        strtotime($order['created_at'])
                                    )
                                ) ?>
                            </strong>

                        </div>


                        <div class="details-summary-row">

                            <span>
                                Payment
                            </span>

                            <strong>
                                <?= e($paymentMethod) ?>
                            </strong>

                        </div>


                        <div class="details-summary-row">

                            <span>
                                Payment Status
                            </span>

                            <strong class="details-payment-status">
                                <?= e($paymentStatus) ?>
                            </strong>

                        </div>


                        <div class="details-summary-divider"></div>


                        <div class="details-summary-row">

                            <span>
                                Subtotal
                            </span>

                            <strong>
                                <?= e(
                                    formatPrice(
                                        $order['subtotal']
                                    )
                                ) ?>
                            </strong>

                        </div>


                        <div class="details-summary-row">

                            <span>
                                Discount
                            </span>

                            <strong>
                                <?= e(
                                    formatPrice(
                                        $order['discount_amount']
                                    )
                                ) ?>
                            </strong>

                        </div>


                        <div class="details-summary-row">

                            <span>
                                Shipping
                            </span>

                            <strong>
                                <?= e(
                                    formatPrice(
                                        $order['shipping_amount']
                                    )
                                ) ?>
                            </strong>

                        </div>


                        <div class="details-summary-divider"></div>


                        <div class="details-total">

                            <span>
                                Total
                            </span>

                            <strong>
                                <?= e(
                                    formatPrice(
                                        $order['total_amount']
                                    )
                                ) ?>
                            </strong>

                        </div>


                        <a
                            href="<?= e(
                                baseUrl('customer/orders.php')
                            ) ?>"
                            class="details-back-btn"
                        >
                            <i class="fa-solid fa-arrow-left"></i>
                            Back to My Orders
                        </a>


                        <a
                            href="<?= e(
                                baseUrl('shop.php')
                            ) ?>"
                            class="details-shop-btn"
                        >
                            Continue Shopping
                        </a>

                    </div>

                </aside>

            </div>

        </div>

    </section>

</main>

</body>
</html>
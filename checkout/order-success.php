<?php

require_once '../includes/bootstrap.php';

if (!isCustomerLoggedIn()) {
    redirect(baseUrl('customer/login.php'));
}

/*
|--------------------------------------------------------------------------
| Get Last Order Number
|--------------------------------------------------------------------------
*/

$orderNumber = $_SESSION['last_order_number'] ?? '';

if ($orderNumber === '') {
    redirect(baseUrl('index.php'));
}

/*
|--------------------------------------------------------------------------
| Get Order
|--------------------------------------------------------------------------
*/

$userId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT
        id,
        order_number,
        total_amount,
        payment_method,
        payment_status,
        order_status,
        customer_name,
        customer_email,
        shipping_address,
        shipping_city,
        shipping_district,
        shipping_postal_code,
        created_at
    FROM orders
    WHERE order_number = ?
      AND user_id = ?
    LIMIT 1
");

$stmt->execute([
    $orderNumber,
    $userId
]);

$order = $stmt->fetch();

if (!$order) {
    unset($_SESSION['last_order_number']);
    redirect(baseUrl('index.php'));
}

/*
|--------------------------------------------------------------------------
| Get Order Items
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        product_name,
        sku,
        quantity,
        unit_price,
        subtotal
    FROM order_items
    WHERE order_id = ?
    ORDER BY id ASC
");

$stmt->execute([
    $order['id']
]);

$orderItems = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Clear Temporary Session Order Number
|--------------------------------------------------------------------------
*/

unset($_SESSION['last_order_number']);

require_once '../includes/header.php';

?>

<main class="order-success-page">

    <!-- =====================================================
         Success Header
    ====================================================== -->

    <section class="order-success-header">

        <div class="container">

            <div class="order-success-header-content">

                <div class="success-icon">
                    <i class="fa-solid fa-check"></i>
                </div>

                <span class="success-eyebrow">
                    Thank You
                </span>

                <h1>
                    Your Order Has Been Placed
                </h1>

                <p>
                    Thank you for choosing Veloura.
                    Your order has been received successfully.
                </p>

            </div>

        </div>

    </section>


    <!-- =====================================================
         Order Confirmation
    ====================================================== -->

    <section class="order-success-content">

        <div class="container">

            <div class="success-layout">

                <!-- =================================================
                     Order Details
                ================================================== -->

                <div class="success-main">

                    <div class="success-card">

                        <div class="success-card-header">

                            <div>

                                <span class="success-card-eyebrow">
                                    Order Confirmation
                                </span>

                                <h2>
                                    <?= e($order['order_number']) ?>
                                </h2>

                            </div>

                            <span class="order-status-badge">
                                <?= e(ucfirst($order['order_status'])) ?>
                            </span>

                        </div>


                        <div class="success-info-grid">

                            <div class="success-info-item">

                                <span>
                                    Customer
                                </span>

                                <strong>
                                    <?= e($order['customer_name']) ?>
                                </strong>

                            </div>


                            <div class="success-info-item">

                                <span>
                                    Email
                                </span>

                                <strong>
                                    <?= e($order['customer_email']) ?>
                                </strong>

                            </div>


                            <div class="success-info-item">

                                <span>
                                    Payment
                                </span>

                                <strong>
                                    Cash on Delivery
                                </strong>

                            </div>


                            <div class="success-info-item">

                                <span>
                                    Payment Status
                                </span>

                                <strong>
                                    <?= e(ucfirst($order['payment_status'])) ?>
                                </strong>

                            </div>

                        </div>

                    </div>


                    <!-- Order Items -->

                    <div class="success-card">

                        <div class="success-section-title">

                            <h2>
                                Items in Your Order
                            </h2>

                        </div>


                        <div class="success-items">

                            <?php foreach ($orderItems as $item): ?>

                                <div class="success-item">

                                    <div class="success-item-details">

                                        <h3>
                                            <?= e($item['product_name']) ?>
                                        </h3>

                                        <span>
                                            SKU: <?= e($item['sku']) ?>
                                        </span>

                                    </div>


                                    <div class="success-item-quantity">

                                        × <?= (int) $item['quantity'] ?>

                                    </div>


                                    <div class="success-item-price">

                                        <?= e(formatPrice($item['subtotal'])) ?>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    </div>


                    <!-- Shipping Address -->

                    <div class="success-card">

                        <div class="success-section-title">

                            <h2>
                                Delivery Address
                            </h2>

                        </div>


                        <div class="success-address">

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

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     Order Summary
                ================================================== -->

                <aside class="success-summary">

                    <div class="success-summary-inner">

                        <span class="success-summary-eyebrow">
                            Your Order
                        </span>

                        <h2>
                            Order Summary
                        </h2>


                        <div class="success-summary-row">

                            <span>
                                Items
                            </span>

                            <strong>
                                <?= count($orderItems) ?>
                            </strong>

                        </div>


                        <div class="success-summary-row">

                            <span>
                                Payment
                            </span>

                            <strong>
                                COD
                            </strong>

                        </div>


                        <div class="success-summary-divider"></div>


                        <div class="success-total">

                            <span>
                                Total
                            </span>

                            <strong>
                                <?= e(formatPrice($order['total_amount'])) ?>
                            </strong>

                        </div>


                        <div class="success-note">

                            <i class="fa-solid fa-truck"></i>

                            <p>
                                Your order will be delivered to the address
                                provided during checkout.
                            </p>

                        </div>


                        <a
                            href="<?= e(baseUrl('index.php')) ?>"
                            class="success-home-btn"
                        >
                            Continue Shopping
                        </a>


                        <a
                            href="<?= e(baseUrl('customer/orders.php')) ?>"
                            class="success-orders-btn"
                        >
                            View My Orders
                        </a>

                    </div>

                </aside>

            </div>

        </div>

    </section>

</main>

</body>
</html>
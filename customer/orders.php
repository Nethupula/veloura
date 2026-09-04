<?php

require_once '../includes/bootstrap.php';

if (!isCustomerLoggedIn()) {
    redirect(baseUrl('customer/login.php'));
}

$userId = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| Get Customer Orders
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        order_number,
        subtotal,
        discount_amount,
        shipping_amount,
        total_amount,
        payment_method,
        payment_status,
        order_status,
        customer_name,
        created_at
    FROM orders
    WHERE user_id = ?
    ORDER BY created_at DESC
");

$stmt->execute([$userId]);

$orders = $stmt->fetchAll();

require_once '../includes/header.php';

?>

<main class="customer-orders-page">

    <!-- =====================================================
         Page Header
    ====================================================== -->

    <section class="customer-orders-header">

        <div class="container">

            <div class="customer-orders-header-content">

                <span class="customer-orders-eyebrow">
                    Your Account
                </span>

                <h1>
                    My Orders
                </h1>

                <p>
                    View and track your Veloura orders.
                </p>

            </div>

        </div>

    </section>


    <!-- =====================================================
         Orders Content
    ====================================================== -->

    <section class="customer-orders-content">

        <div class="container">

            <?php if (empty($orders)): ?>

                <!-- Empty Orders -->

                <div class="empty-orders">

                    <div class="empty-orders-icon">
                        <i class="fa-solid fa-box-open"></i>
                    </div>

                    <h2>
                        You haven't placed any orders yet
                    </h2>

                    <p>
                        Your beautiful Veloura pieces will appear here
                        after you place an order.
                    </p>

                    <a
                        href="<?= e(baseUrl('shop.php')) ?>"
                        class="orders-shop-btn"
                    >
                        Start Shopping
                    </a>

                </div>

            <?php else: ?>

                <!-- Orders -->

                <div class="orders-container">

                    <div class="orders-heading">

                        <div>

                            <span>
                                Order History
                            </span>

                            <h2>
                                Your Orders
                            </h2>

                        </div>

                        <span class="orders-count">
                            <?= count($orders) ?>
                            <?= count($orders) === 1 ? 'Order' : 'Orders' ?>
                        </span>

                    </div>


                    <div class="orders-list">

                        <?php foreach ($orders as $order): ?>

                            <article class="order-card">

                                <div class="order-card-top">

                                    <div class="order-number">

                                        <span>
                                            Order Number
                                        </span>

                                        <h3>
                                            <?= e($order['order_number']) ?>
                                        </h3>

                                    </div>


                                    <div class="order-date">

                                        <span>
                                            Date
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

                                </div>


                                <div class="order-card-middle">

                                    <div class="order-detail">

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


                                    <div class="order-detail">

                                        <span>
                                            Payment
                                        </span>

                                        <strong>
                                            <?= e(
                                                strtoupper(
                                                    $order['payment_method']
                                                )
                                            ) ?>
                                        </strong>

                                    </div>


                                    <div class="order-detail">

                                        <span>
                                            Payment Status
                                        </span>

                                        <span class="order-payment-status status-<?= e($order['payment_status']) ?>">
                                            <?= e(
                                                ucfirst(
                                                    $order['payment_status']
                                                )
                                            ) ?>
                                        </span>

                                    </div>


                                    <div class="order-detail">

                                        <span>
                                            Order Status
                                        </span>

                                        <span class="order-status status-<?= e($order['order_status']) ?>">
                                            <?= e(
                                                ucfirst(
                                                    $order['order_status']
                                                )
                                            ) ?>
                                        </span>

                                    </div>

                                </div>


                                <div class="order-card-bottom">

                                    <span class="order-customer-name">

                                        <i class="fa-regular fa-user"></i>

                                        <?= e($order['customer_name']) ?>

                                    </span>


                                    <a
                                        href="<?= e(
                                            baseUrl(
                                                'customer/order-details.php?id=' .
                                                (int) $order['id']
                                            )
                                        ) ?>"
                                        class="view-order-btn"
                                    >
                                        View Order
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </a>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                </div>

            <?php endif; ?>

        </div>

    </section>

</main>

</body>
</html>
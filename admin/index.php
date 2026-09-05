<?php

$pageTitle = 'Dashboard';

require_once 'includes/header.php';

/*
|--------------------------------------------------------------------------
| Dashboard Statistics
|--------------------------------------------------------------------------
*/

// Total sales
$stmt = $pdo->query("
    SELECT COALESCE(SUM(total_amount), 0)
    FROM orders
    WHERE payment_status = 'paid'
");

$totalSales = (float) $stmt->fetchColumn();


// Total orders
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM orders
");

$totalOrders = (int) $stmt->fetchColumn();


// Total customers
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM users
    WHERE status = 'active'
");

$totalCustomers = (int) $stmt->fetchColumn();


// Total active products
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM products
    WHERE status = 'active'
");

$totalProducts = (int) $stmt->fetchColumn();


// Low stock
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM inventory
    WHERE quantity <= reorder_level
");

$lowStock = (int) $stmt->fetchColumn();


// Pending orders
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM orders
    WHERE order_status = 'pending'
");

$pendingOrders = (int) $stmt->fetchColumn();


// Recent orders
$stmt = $pdo->query("
    SELECT
        id,
        order_number,
        customer_name,
        total_amount,
        payment_method,
        payment_status,
        order_status,
        created_at
    FROM orders
    ORDER BY created_at DESC
    LIMIT 8
");

$recentOrders = $stmt->fetchAll();

?>

<div class="dashboard-welcome">

    <h2>
        Welcome back,
        <?= e($_SESSION['admin_name'] ?? 'Admin') ?>.
    </h2>

    <p>
        Here's what's happening with your Veloura store today.
    </p>

</div>


<!-- Statistics -->

<div class="dashboard-stats">

    <div class="dashboard-stat-card">

        <div class="stat-icon">
            <i class="fa-solid fa-coins"></i>
        </div>

        <div class="stat-content">

            <span>Total Sales</span>

            <strong>
                <?= e(formatPrice($totalSales)) ?>
            </strong>

        </div>

    </div>


    <div class="dashboard-stat-card">

        <div class="stat-icon">
            <i class="fa-solid fa-bag-shopping"></i>
        </div>

        <div class="stat-content">

            <span>Total Orders</span>

            <strong>
                <?= e(number_format($totalOrders)) ?>
            </strong>

        </div>

    </div>


    <div class="dashboard-stat-card">

        <div class="stat-icon">
            <i class="fa-solid fa-users"></i>
        </div>

        <div class="stat-content">

            <span>Customers</span>

            <strong>
                <?= e(number_format($totalCustomers)) ?>
            </strong>

        </div>

    </div>


    <div class="dashboard-stat-card">

        <div class="stat-icon">
            <i class="fa-solid fa-gem"></i>
        </div>

        <div class="stat-content">

            <span>Products</span>

            <strong>
                <?= e(number_format($totalProducts)) ?>
            </strong>

        </div>

    </div>


    <div class="dashboard-stat-card">

        <div class="stat-icon warning">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>

        <div class="stat-content">

            <span>Low Stock</span>

            <strong>
                <?= e(number_format($lowStock)) ?>
            </strong>

        </div>

    </div>


    <div class="dashboard-stat-card">

        <div class="stat-icon pending">
            <i class="fa-solid fa-clock"></i>
        </div>

        <div class="stat-content">

            <span>Pending Orders</span>

            <strong>
                <?= e(number_format($pendingOrders)) ?>
            </strong>

        </div>

    </div>

</div>


<!-- Recent Orders -->

<div class="dashboard-section">

    <div class="dashboard-section-header">

        <div>

            <h3>Recent Orders</h3>

            <p>
                Latest orders placed in your store.
            </p>

        </div>

        <a href="#">
            View All
            <i class="fa-solid fa-arrow-right"></i>
        </a>

    </div>


    <?php if (!empty($recentOrders)): ?>

        <div class="dashboard-table-wrapper">

            <table class="dashboard-table">

                <thead>

                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>

                </thead>

                <tbody>

                <?php foreach ($recentOrders as $order): ?>

                    <tr>

                        <td>
                            <strong>
                                <?= e($order['order_number']) ?>
                            </strong>
                        </td>

                        <td>
                            <?= e($order['customer_name']) ?>
                        </td>

                        <td>
                            <?= e(formatPrice($order['total_amount'])) ?>
                        </td>

                        <td>

                            <span class="payment-method">
                                <?= e(
                                    strtoupper(
                                        $order['payment_method']
                                    )
                                ) ?>
                            </span>

                        </td>

                        <td>

                            <span class="order-status status-<?= e($order['order_status']) ?>">

                                <?= e(
                                    ucfirst(
                                        $order['order_status']
                                    )
                                ) ?>

                            </span>

                        </td>

                        <td>
                            <?= e(
                                date(
                                    'd M Y',
                                    strtotime(
                                        $order['created_at']
                                    )
                                )
                            ) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php else: ?>

        <div class="dashboard-empty">

            <i class="fa-solid fa-box-open"></i>

            <p>
                No orders have been placed yet.
            </p>

        </div>

    <?php endif; ?>

</div>


<?php require_once 'includes/footer.php'; ?>
<?php

$currentPage = basename($_SERVER['PHP_SELF']);

?>

<aside class="admin-sidebar" id="adminSidebar">

    <div class="admin-brand">

        <a href="<?= e(baseUrl('admin/index.php')) ?>">

            <span class="admin-brand-name">
                VELOURA
            </span>

            <span class="admin-brand-subtitle">
                ADMIN PANEL
            </span>

        </a>

    </div>

    <div class="admin-sidebar-divider"></div>

    <nav class="admin-sidebar-nav">

        <div class="sidebar-section-title">
            MAIN
        </div>

        <a
            href="<?= e(baseUrl('admin/index.php')) ?>"
            class="<?= $currentPage === 'index.php' ? 'active' : '' ?>"
        >
            <i class="fa-solid fa-chart-line"></i>
            <span>Dashboard</span>
        </a>

        <div class="sidebar-section-title">
            STORE
        </div>

        <a
    href="<?= e(baseUrl('admin/products/index.php')) ?>"
    class="<?= strpos($_SERVER['PHP_SELF'], '/products/') !== false ? 'active' : '' ?>"
>
    <i class="fa-solid fa-gem"></i>
    <span>Products</span>
</a>

        <a
    href="<?= e(
        baseUrl('admin/categories/index.php')
    ) ?>"
    class="<?= strpos(
        $_SERVER['PHP_SELF'],
        '/categories/'
    ) !== false ? 'active' : '' ?>"
>
    <i class="fa-solid fa-layer-group"></i>
    <span>Categories</span>
</a>

        <a href="#">
            <i class="fa-solid fa-box"></i>
            <span>Orders</span>
        </a>

        <a href="#">
            <i class="fa-solid fa-users"></i>
            <span>Customers</span>
        </a>

        <a href="#">
            <i class="fa-solid fa-warehouse"></i>
            <span>Inventory</span>
        </a>

        <div class="sidebar-section-title">
            MANAGEMENT
        </div>

        <a href="#">
            <i class="fa-solid fa-ticket"></i>
            <span>Coupons</span>
        </a>

        <a href="#">
            <i class="fa-solid fa-star"></i>
            <span>Reviews</span>
        </a>

        <a href="#">
            <i class="fa-solid fa-credit-card"></i>
            <span>Payments</span>
        </a>

        <a href="#">
            <i class="fa-solid fa-bell"></i>
            <span>Notifications</span>
        </a>

        <?php if (
            isset($_SESSION['admin_role']) &&
            $_SESSION['admin_role'] === 'super_admin'
        ): ?>

            <div class="sidebar-section-title">
                SYSTEM
            </div>

            <a href="#">
                <i class="fa-solid fa-user-shield"></i>
                <span>Administrators</span>
            </a>

            <a href="#">
                <i class="fa-solid fa-gear"></i>
                <span>Settings</span>
            </a>

        <?php endif; ?>

    </nav>

    <div class="admin-sidebar-bottom">

        <a
            href="<?= e(baseUrl('admin/logout.php')) ?>"
            class="admin-logout"
        >
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </a>

    </div>

</aside>

<div
    class="admin-sidebar-overlay"
    id="adminSidebarOverlay"
></div>
<?php

require_once __DIR__ . '/auth.php';

$pageTitle = $pageTitle ?? 'Dashboard';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title><?= e($pageTitle) ?> | Veloura Admin</title>

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <link
        rel="stylesheet"
        href="<?= e(baseUrl('assets/css/admin.css')) ?>"
    >

</head>

<body>

<div class="admin-layout">

    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <div class="admin-main">

        <header class="admin-topbar">

            <button
                type="button"
                class="admin-mobile-toggle"
                id="adminMobileToggle"
                aria-label="Open menu"
            >
                <i class="fa-solid fa-bars"></i>
            </button>

            <div class="admin-page-title">

                <h1><?= e($pageTitle) ?></h1>

                <p>
                    Veloura Admin Panel
                </p>

            </div>

            <div class="admin-topbar-right">

                <div class="admin-user">

                    <div class="admin-user-icon">
                        <i class="fa-regular fa-user"></i>
                    </div>

                    <div class="admin-user-details">

                        <strong>
                            <?= e($_SESSION['admin_name'] ?? 'Admin') ?>
                        </strong>

                        <span>
                            <?= e($_SESSION['admin_role'] ?? 'admin') ?>
                        </span>

                    </div>

                </div>

            </div>

        </header>

        <main class="admin-content">
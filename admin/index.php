<?php
require_once 'includes/auth.php';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Dashboard | Veloura</title>

    <style>

        body {
            margin: 0;
            padding: 40px;
            background: #F8F5EF;
            font-family: Arial, sans-serif;
            color: #242424;
        }

        .dashboard {
            max-width: 900px;
            margin: auto;
            background: #FFFFFF;
            padding: 40px;
            border: 1px solid #E8DED0;
        }

        h1 {
            margin-top: 0;
        }

        .welcome {
            margin-bottom: 30px;
            color: #77716A;
        }

        .admin-info {
            padding: 20px;
            background: #F8F5EF;
            margin-bottom: 25px;
        }

    </style>

</head>

<body>

<div class="dashboard">

    <h1>Veloura Admin Dashboard</h1>

    <p class="welcome">
        Welcome,
        <strong><?= e($_SESSION['admin_name']) ?></strong>
    </p>

    <div class="admin-info">

        <p>
            <strong>Email:</strong>
            <?= e($_SESSION['admin_email']) ?>
        </p>

        <p>
            <strong>Role:</strong>
            <?= e($_SESSION['admin_role']) ?>
        </p>

    </div>

    <p>
        Admin authentication is working successfully.
    </p>
    <a
    href="<?= e(baseUrl('admin/logout.php')) ?>"
    style="
        display: inline-block;
        padding: 12px 20px;
        background: #171717;
        color: #FFFFFF;
        text-decoration: none;
    "
>
    Logout
</a>

</div>

</body>

</html>
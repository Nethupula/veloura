<?php

require_once '../includes/bootstrap.php';

if (isAdminLoggedIn()) {
    redirect(baseUrl('admin/index.php'));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {

        $error = 'Please enter your email and password.';

    } else {

        $stmt = $pdo->prepare("
            SELECT id, name, email, password, role, status
            FROM admins
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);

        $admin = $stmt->fetch();

        if (
            $admin &&
            $admin['status'] === 'active' &&
            password_verify($password, $admin['password'])
        ) {

            session_regenerate_id(true);

            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_role'] = $admin['role'];

            $updateLogin = $pdo->prepare("
                UPDATE admins
                SET last_login = NOW()
                WHERE id = ?
            ");

            $updateLogin->execute([$admin['id']]);

            redirect(baseUrl('admin/index.php'));

        } else {

            $error = 'Invalid email or password.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Login | Veloura</title>

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

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #F8F5EF;
            font-family: 'DM Sans', sans-serif;
            color: #242424;
            padding: 20px;
        }

        .admin-login-wrapper {
            width: 100%;
            max-width: 430px;
        }

        .admin-login-card {
            background: #FFFFFF;
            padding: 45px 40px;
            border: 1px solid #E8DED0;
            box-shadow: 0 20px 50px rgba(23, 23, 23, 0.08);
        }

        .admin-logo {
            text-align: center;
            margin-bottom: 35px;
        }

        .admin-logo h1 {
            font-family: 'Playfair Display', serif;
            font-size: 34px;
            letter-spacing: 5px;
            color: #171717;
        }

        .admin-logo span {
            display: block;
            margin-top: 7px;
            font-size: 11px;
            letter-spacing: 2px;
            color: #C9A96E;
            text-transform: uppercase;
        }

        .login-heading {
            text-align: center;
            margin-bottom: 28px;
        }

        .login-heading h2 {
            font-family: 'Playfair Display', serif;
            font-size: 25px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .login-heading p {
            font-size: 13px;
            color: #77716A;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #77716A;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            height: 48px;
            padding: 0 15px 0 43px;
            border: 1px solid #DDD5CA;
            background: #FFFFFF;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s ease;
        }

        .form-control:focus {
            border-color: #C9A96E;
        }

        .login-error {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 12px 14px;
            margin-bottom: 20px;
            background: #FFF5F5;
            border: 1px solid #E3B8B8;
            color: #8A3F3F;
            font-size: 13px;
        }

        .login-button {
            width: 100%;
            height: 50px;
            border: none;
            background: #171717;
            color: #FFFFFF;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .login-button:hover {
            background: #C9A96E;
            color: #171717;
        }

        .admin-footer {
            text-align: center;
            margin-top: 25px;
            font-size: 11px;
            color: #77716A;
        }

        @media (max-width: 480px) {

            .admin-login-card {
                padding: 35px 25px;
            }

            .admin-logo h1 {
                font-size: 29px;
            }

        }

    </style>

</head>

<body>

<div class="admin-login-wrapper">

    <div class="admin-login-card">

        <div class="admin-logo">

            <h1>VELOURA</h1>

            <span>Admin Panel</span>

        </div>

        <div class="login-heading">

            <h2>Welcome Back</h2>

            <p>Sign in to manage your Veloura store.</p>

        </div>

        <?php if ($error !== ''): ?>

            <div class="login-error">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?= e($error) ?>

            </div>

        <?php endif; ?>

        <form method="POST">

            <div class="form-group">

                <label for="email">
                    Email Address
                </label>

                <div class="input-wrapper">

                    <i class="fa-regular fa-envelope"></i>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="Enter admin email"
                        value="<?= e($_POST['email'] ?? '') ?>"
                        required
                    >

                </div>

            </div>

            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <div class="input-wrapper">

                    <i class="fa-solid fa-lock"></i>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Enter password"
                        required
                    >

                </div>

            </div>

            <button
                type="submit"
                class="login-button"
            >
                SIGN IN
            </button>

        </form>

        <div class="admin-footer">
            Veloura — Made to Make You Shine
        </div>

    </div>

</div>

</body>

</html>
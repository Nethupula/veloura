<?php

require_once '../includes/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (
        empty($firstName) ||
        empty($lastName) ||
        empty($email) ||
        empty($password) ||
        empty($confirmPassword)
    ) {

        $error = 'Please fill in all required fields.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'Please enter a valid email address.';

    } elseif (strlen($password) < 8) {

        $error = 'Password must be at least 8 characters long.';

    } elseif ($password !== $confirmPassword) {

        $error = 'Passwords do not match.';

    } else {

        /*
        |--------------------------------------------------------------------------
        | Check Existing Email
        |--------------------------------------------------------------------------
        */

        $checkUser = $pdo->prepare("
            SELECT id
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $checkUser->execute([$email]);

        if ($checkUser->fetch()) {

            $error = 'An account with this email already exists.';

        } else {

            /*
            |--------------------------------------------------------------------------
            | Create Account
            |--------------------------------------------------------------------------
            */

            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $insertUser = $pdo->prepare("
                INSERT INTO users
                (
                    first_name,
                    last_name,
                    email,
                    phone,
                    password,
                    status
                )
                VALUES
                (
                    ?, ?, ?, ?, ?, 'active'
                )
            ");

            $insertUser->execute([
                $firstName,
                $lastName,
                $email,
                $phone,
                $hashedPassword
            ]);

            $success = 'Your account has been created successfully.';
        }
    }
}

?>

<!-- ========================================
     CUSTOMER REGISTRATION
======================================== -->

<section class="auth-section">

    <div class="auth-container">

        <div class="auth-header">

            <p class="section-eyebrow">
                JOIN VELOURA
            </p>

            <h1>Create Account</h1>

            <p>
                Create your account and discover your next favorite piece.
            </p>

        </div>


        <?php if (!empty($error)): ?>

            <div class="auth-error">
                <?= e($error) ?>
            </div>

        <?php endif; ?>


        <?php if (!empty($success)): ?>

            <div class="auth-success">
                <?= e($success) ?>
            </div>

            <div class="auth-success-action">

                <a
                    href="login.php"
                    class="auth-btn-link"
                >
                    Continue to Sign In
                </a>

            </div>

        <?php else: ?>


            <form method="POST" class="auth-form">

                <!-- First Name -->

                <div class="form-group">

                    <label for="first_name">
                        First Name *
                    </label>

                    <input
                        type="text"
                        id="first_name"
                        name="first_name"
                        value="<?= e($_POST['first_name'] ?? '') ?>"
                        required
                        autocomplete="given-name"
                    >

                </div>


                <!-- Last Name -->

                <div class="form-group">

                    <label for="last_name">
                        Last Name *
                    </label>

                    <input
                        type="text"
                        id="last_name"
                        name="last_name"
                        value="<?= e($_POST['last_name'] ?? '') ?>"
                        required
                        autocomplete="family-name"
                    >

                </div>


                <!-- Email -->

                <div class="form-group">

                    <label for="email">
                        Email Address *
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= e($_POST['email'] ?? '') ?>"
                        required
                        autocomplete="email"
                    >

                </div>


                <!-- Phone -->

                <div class="form-group">

                    <label for="phone">
                        Phone Number
                    </label>

                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        value="<?= e($_POST['phone'] ?? '') ?>"
                        autocomplete="tel"
                    >

                </div>


                <!-- Password -->

                <div class="form-group">

                    <label for="password">
                        Password *
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        minlength="8"
                        autocomplete="new-password"
                    >

                </div>


                <!-- Confirm Password -->

                <div class="form-group">

                    <label for="confirm_password">
                        Confirm Password *
                    </label>

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        required
                        minlength="8"
                        autocomplete="new-password"
                    >

                </div>


                <button
                    type="submit"
                    class="auth-btn"
                >
                    Create Account
                </button>

            </form>


            <p class="auth-footer">

                Already have an account?

                <a href="login.php">
                    Sign In
                </a>

            </p>


        <?php endif; ?>

    </div>

</section>


</body>
</html>
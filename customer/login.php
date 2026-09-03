<?php

require_once '../includes/bootstrap.php';

$error = '';

/*
|--------------------------------------------------------------------------
| Login Processing
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {

        $error = 'Please enter your email and password.';

    } else {

        $stmt = $pdo->prepare("
            SELECT
                id,
                first_name,
                last_name,
                email,
                password
            FROM users
            WHERE email = ?
                AND status = 'active'
            LIMIT 1
        ");

        $stmt->execute([$email]);

        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {

            /*
            |--------------------------------------------------------------------------
            | Create Secure Session
            |--------------------------------------------------------------------------
            */

            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];

            $_SESSION['user_name'] =
                $user['first_name'] . ' ' . $user['last_name'];

            $_SESSION['user_email'] =
                $user['email'];

            /*
            |--------------------------------------------------------------------------
            | Redirect
            |--------------------------------------------------------------------------
            */

            header('Location: ../index.php');
            exit;

        } else {

            $error = 'Invalid email or password.';

        }
    }
}


/*
|--------------------------------------------------------------------------
| Load Page Header
|--------------------------------------------------------------------------
*/

require_once '../includes/header.php';

?>

<!-- ========================================
     CUSTOMER LOGIN
======================================== -->

<section class="auth-section">

    <div class="auth-container">

        <div class="auth-header">

            <p class="section-eyebrow">
                WELCOME BACK
            </p>

            <h1>
                Sign In
            </h1>

            <p>
                Sign in to continue shopping with Veloura.
            </p>

        </div>


        <?php if (!empty($error)): ?>

            <div class="auth-error">
                <?= e($error) ?>
            </div>

        <?php endif; ?>


        <form
            method="POST"
            class="auth-form"
        >

            <!-- Email -->

            <div class="form-group">

                <label for="email">
                    Email Address
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


            <!-- Password -->

            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                >

            </div>


            <!-- Submit -->

            <button
                type="submit"
                class="auth-btn"
            >
                Sign In
            </button>

        </form>


        <p class="auth-footer">

            Don't have an account?

            <a href="register.php">
                Create an account
            </a>

        </p>

    </div>

</section>


</body>
</html>
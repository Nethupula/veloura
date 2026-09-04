<nav class="veloura-navbar">

    <div class="veloura-navbar-container">

        <!-- Brand -->
        <a class="veloura-brand" href="<?= e(baseUrl('index.php')) ?>">
            VELOURA
            <span>Made to Make You Shine</span>
        </a>


        <!-- Desktop Navigation -->
        <div class="veloura-nav-menu" id="velouraNavMenu">

            <a href="<?= e(baseUrl('index.php')) ?>">
                Home
            </a>

            <a href="<?= e(baseUrl('shop.php')) ?>">
                Shop
            </a>

            <a href="<?= e(baseUrl('shop.php?category=rings')) ?>">
                Rings
            </a>

            <a href="<?= e(baseUrl('shop.php?category=necklaces')) ?>">
                Necklaces
            </a>

            <a href="<?= e(baseUrl('shop.php?category=earrings')) ?>">
                Earrings
            </a>

            <a href="<?= e(baseUrl('contact.php')) ?>">
                Contact
            </a>

        </div>


        <!-- Right Side -->
        <div class="veloura-nav-right">

            <div class="veloura-nav-icons">

                <!-- Search -->
                <a
                    href="<?= e(baseUrl('shop.php')) ?>"
                    aria-label="Search"
                >
                    <i class="fa-solid fa-magnifying-glass"></i>
                </a>


                <!-- Account -->
                <div class="veloura-account">

                    <button
                        type="button"
                        class="veloura-account-toggle"
                        aria-label="Account"
                        aria-expanded="false"
                        id="velouraAccountToggle"
                    >
                        <i class="fa-regular fa-user"></i>
                    </button>


                    <div
                        class="veloura-account-menu"
                        id="velouraAccountMenu"
                    >

                        <?php if (isCustomerLoggedIn()): ?>

                            <div class="account-menu-header">

                                <span>
                                    Signed in as
                                </span>

                                <strong>
                                    <?= e(
                                        $_SESSION['user_name']
                                        ?? 'Customer'
                                    ) ?>
                                </strong>

                            </div>


                            <a
                                href="<?= e(
                                    baseUrl('customer/orders.php')
                                ) ?>"
                            >
                                <i class="fa-solid fa-box"></i>
                                My Orders
                            </a>


                            <a
                                href="<?= e(
                                    baseUrl('customer/logout.php')
                                ) ?>"
                            >
                                <i class="fa-solid fa-right-from-bracket"></i>
                                Logout
                            </a>


                        <?php else: ?>

                            <div class="account-menu-header">

                                <span>
                                    Welcome to
                                </span>

                                <strong>
                                    Veloura
                                </strong>

                            </div>


                            <a
                                href="<?= e(
                                    baseUrl('customer/login.php')
                                ) ?>"
                            >
                                <i class="fa-solid fa-right-to-bracket"></i>
                                Login
                            </a>


                            <a
                                href="<?= e(
                                    baseUrl('customer/register.php')
                                ) ?>"
                            >
                                <i class="fa-solid fa-user-plus"></i>
                                Create Account
                            </a>

                        <?php endif; ?>

                    </div>

                </div>
                <!-- END Account -->


                <!-- Shopping Cart -->
                <a
                    href="<?= e(baseUrl('cart/cart.php')) ?>"
                    class="cart-icon"
                    aria-label="Shopping Cart"
                >
                    <i class="fa-solid fa-bag-shopping"></i>

                    <span class="cart-count">
                        <?= e(getCartItemCount()) ?>
                    </span>
                </a>

            </div>
            <!-- END nav-icons -->


            <!-- Mobile Menu Button -->
            <button
                type="button"
                class="veloura-menu-toggle"
                id="velouraMenuToggle"
                aria-label="Open navigation menu"
                aria-expanded="false"
            >
                <i class="fa-solid fa-bars"></i>
            </button>

        </div>
        <!-- END nav-right -->

    </div>
    <!-- END navbar-container -->

</nav>


<script>

document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | Mobile Navigation
    |--------------------------------------------------------------------------
    */

    const menuToggle =
        document.getElementById('velouraMenuToggle');

    const navMenu =
        document.getElementById('velouraNavMenu');


    if (menuToggle && navMenu) {

        menuToggle.addEventListener('click', function () {

            navMenu.classList.toggle('active');

            const isOpen =
                navMenu.classList.contains('active');

            menuToggle.setAttribute(
                'aria-expanded',
                isOpen
            );

            menuToggle.innerHTML = isOpen
                ? '<i class="fa-solid fa-xmark"></i>'
                : '<i class="fa-solid fa-bars"></i>';

        });


        navMenu.querySelectorAll('a').forEach(function (link) {

            link.addEventListener('click', function () {

                navMenu.classList.remove('active');

                menuToggle.setAttribute(
                    'aria-expanded',
                    'false'
                );

                menuToggle.innerHTML =
                    '<i class="fa-solid fa-bars"></i>';

            });

        });

    }


    /*
    |--------------------------------------------------------------------------
    | Account Dropdown
    |--------------------------------------------------------------------------
    */

    const accountToggle =
        document.getElementById('velouraAccountToggle');

    const accountMenu =
        document.getElementById('velouraAccountMenu');


    if (accountToggle && accountMenu) {

        accountToggle.addEventListener(
            'click',
            function (event) {

                event.stopPropagation();

                accountMenu.classList.toggle('active');

                const isOpen =
                    accountMenu.classList.contains('active');

                accountToggle.setAttribute(
                    'aria-expanded',
                    isOpen
                );

            }
        );


        document.addEventListener(
            'click',
            function () {

                accountMenu.classList.remove('active');

                accountToggle.setAttribute(
                    'aria-expanded',
                    'false'
                );

            }
        );


        accountMenu.addEventListener(
            'click',
            function (event) {

                event.stopPropagation();

            }
        );

    }

});

</script>
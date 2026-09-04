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

                <a
                    href="<?= e(baseUrl('shop.php')) ?>"
                    aria-label="Search"
                >
                    <i class="fa-solid fa-magnifying-glass"></i>
                </a>

                <a
                    href="<?= e(baseUrl('customer/login.php')) ?>"
                    aria-label="Account"
                >
                    <i class="fa-regular fa-user"></i>
                </a>

                <a
                    href="<?= e(baseUrl('cart/cart.php')) ?>"
                    class="cart-icon"
                    aria-label="Shopping Cart"
                >
                    <i class="fa-solid fa-bag-shopping"></i>

                    <span class="cart-count"><?= e(getCartItemCount()) ?></span>
                </a>

            </div>


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

    </div>

</nav>


<script>

document.addEventListener('DOMContentLoaded', function () {

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


        // Close menu after selecting a link
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

});

</script>
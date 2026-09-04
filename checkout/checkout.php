<?php

require_once '../includes/bootstrap.php';

if (!isCustomerLoggedIn()) {
    redirect(baseUrl('customer/login.php'));
}

$userId = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| Get Customer
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        first_name,
        last_name,
        email,
        phone
    FROM users
    WHERE id = ?
      AND status = 'active'
    LIMIT 1
");

$stmt->execute([$userId]);

$user = $stmt->fetch();

if (!$user) {
    session_unset();
    session_destroy();

    redirect(baseUrl('customer/login.php'));
}


/*
|--------------------------------------------------------------------------
| Get Customer Cart
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id
    FROM carts
    WHERE user_id = ?
    LIMIT 1
");

$stmt->execute([$userId]);

$cart = $stmt->fetch();

if (!$cart) {
    redirect(baseUrl('cart/cart.php'));
}

$cartId = (int) $cart['id'];


/*
|--------------------------------------------------------------------------
| Get Cart Items
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        ci.id AS cart_item_id,
        ci.product_id,
        ci.variant_id,
        ci.quantity,

        p.name,
        p.slug,
        p.price,
        p.discount_price,
        p.stock_quantity,

        pv.variant_name,
        pv.price AS variant_price,
        pv.stock_quantity AS variant_stock,

        pi.image_path

    FROM cart_items ci

    INNER JOIN products p
        ON ci.product_id = p.id

    LEFT JOIN product_variants pv
        ON ci.variant_id = pv.id

    LEFT JOIN product_images pi
        ON pi.product_id = p.id
        AND pi.is_primary = 1

    WHERE ci.cart_id = ?

    ORDER BY ci.created_at DESC
");

$stmt->execute([$cartId]);

$cartItems = $stmt->fetchAll();

if (empty($cartItems)) {
    redirect(baseUrl('cart/cart.php'));
}


/*
|--------------------------------------------------------------------------
| Calculate Order Summary
|--------------------------------------------------------------------------
*/

$subtotal = 0;

foreach ($cartItems as &$item) {

    if (
        !empty($item['variant_id']) &&
        $item['variant_price'] !== null
    ) {

        $unitPrice = (float) $item['variant_price'];

    } elseif ($item['discount_price'] !== null) {

        $unitPrice = (float) $item['discount_price'];

    } else {

        $unitPrice = (float) $item['price'];
    }

    $item['unit_price'] = $unitPrice;

    $item['item_total'] =
        $unitPrice * (int) $item['quantity'];

    $subtotal += $item['item_total'];
}

unset($item);


/*
|--------------------------------------------------------------------------
| Get Saved Addresses
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        full_name,
        phone,
        address_line_1,
        address_line_2,
        city,
        district,
        postal_code,
        is_default
    FROM addresses
    WHERE user_id = ?
    ORDER BY is_default DESC, created_at DESC
");

$stmt->execute([$userId]);

$addresses = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Default Address
|--------------------------------------------------------------------------
*/

$defaultAddress = null;

foreach ($addresses as $address) {

    if ((int) $address['is_default'] === 1) {

        $defaultAddress = $address;

        break;
    }
}

if (!$defaultAddress && !empty($addresses)) {
    $defaultAddress = $addresses[0];
}


require_once '../includes/header.php';

$checkoutErrors = $_SESSION['checkout_errors'] ?? [];

unset($_SESSION['checkout_errors']);


?>

<main class="checkout-page">

    <!-- =====================================================
         Checkout Header
    ====================================================== -->

    <section class="checkout-header-section">

        <div class="container">
            <?php if (!empty($checkoutErrors)): ?>

    <div class="checkout-errors">

        <?php foreach ($checkoutErrors as $error): ?>

            <div class="checkout-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?= e($error) ?>
            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>

            <div class="checkout-header-content">

                <span class="checkout-eyebrow">
                    Secure Checkout
                </span>

                <h1>
                    Complete Your Order
                </h1>

                <p>
                    Enter your delivery details and review your order.
                </p>

            </div>

        </div>

    </section>


    <!-- =====================================================
         Checkout Content
    ====================================================== -->

    <section class="checkout-content-section">

        <div class="container">

            <div class="checkout-layout">


                <!-- =================================================
                     Checkout Form
                ================================================== -->

                <div class="checkout-form-container">

                    <form
                        action="<?= e(baseUrl('checkout/place-order.php')) ?>"
                        method="POST"
                        id="checkoutForm"
                    >

                        <!-- Customer Information -->

                        <div class="checkout-card">

                            <div class="checkout-card-header">

                                <span class="checkout-step">
                                    01
                                </span>

                                <div>
                                    <h2>
                                        Contact Information
                                    </h2>

                                    <p>
                                        We'll use these details for your order.
                                    </p>
                                </div>

                            </div>


                            <div class="checkout-form-grid">

                                <div class="checkout-form-group">

                                    <label for="first_name">
                                        First Name
                                    </label>

                                    <input
                                        type="text"
                                        id="first_name"
                                        name="first_name"
                                        value="<?= e($user['first_name']) ?>"
                                        required
                                    >

                                </div>


                                <div class="checkout-form-group">

                                    <label for="last_name">
                                        Last Name
                                    </label>

                                    <input
                                        type="text"
                                        id="last_name"
                                        name="last_name"
                                        value="<?= e($user['last_name']) ?>"
                                        required
                                    >

                                </div>


                                <div class="checkout-form-group">

                                    <label for="email">
                                        Email Address
                                    </label>

                                    <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        value="<?= e($user['email']) ?>"
                                        required
                                    >

                                </div>


                                <div class="checkout-form-group">

                                    <label for="phone">
                                        Phone Number
                                    </label>

                                    <input
                                        type="tel"
                                        id="phone"
                                        name="phone"
                                        value="<?= e($user['phone'] ?? '') ?>"
                                        required
                                    >

                                </div>

                            </div>

                        </div>


                        <!-- Shipping Information -->

                        <div class="checkout-card">

                            <div class="checkout-card-header">

                                <span class="checkout-step">
                                    02
                                </span>

                                <div>
                                    <h2>
                                        Shipping Address
                                    </h2>

                                    <p>
                                        Where should we deliver your jewelry?
                                    </p>

                                </div>

                            </div>


                            <?php if (!empty($addresses)): ?>

                                <div class="saved-addresses">

                                    <label class="saved-address-label">
                                        Saved Address
                                    </label>

                                    <div class="address-options">

                                        <?php foreach ($addresses as $index => $address): ?>

                                            <label class="address-option">

                                                <input
                                                    type="radio"
                                                    name="saved_address_id"
                                                    value="<?= (int) $address['id'] ?>"
                                                    <?= (
                                                        $defaultAddress &&
                                                        (int) $defaultAddress['id'] === (int) $address['id']
                                                    ) ? 'checked' : '' ?>
                                                >

                                                <span class="address-option-content">

                                                    <strong>
                                                        <?= e($address['full_name']) ?>
                                                    </strong>

                                                    <small>
                                                        <?= e($address['phone']) ?>
                                                    </small>

                                                    <small>
                                                        <?= e($address['address_line_1']) ?>

                                                        <?php if (!empty($address['address_line_2'])): ?>
                                                            , <?= e($address['address_line_2']) ?>
                                                        <?php endif; ?>
                                                    </small>

                                                    <small>
                                                        <?= e($address['city']) ?>,
                                                        <?= e($address['district']) ?>
                                                        <?php if (!empty($address['postal_code'])): ?>
                                                            - <?= e($address['postal_code']) ?>
                                                        <?php endif; ?>
                                                    </small>

                                                </span>

                                            </label>

                                        <?php endforeach; ?>

                                    </div>

                                </div>

                            <?php endif; ?>


                            <div class="checkout-form-grid">

                                <div class="checkout-form-group checkout-full-width">

                                    <label for="address_line_1">
                                        Address Line 1
                                    </label>

                                    <input
                                        type="text"
                                        id="address_line_1"
                                        name="address_line_1"
                                        value="<?= e($defaultAddress['address_line_1'] ?? '') ?>"
                                        required
                                    >

                                </div>


                                <div class="checkout-form-group checkout-full-width">

                                    <label for="address_line_2">
                                        Address Line 2
                                        <span>(Optional)</span>
                                    </label>

                                    <input
                                        type="text"
                                        id="address_line_2"
                                        name="address_line_2"
                                        value="<?= e($defaultAddress['address_line_2'] ?? '') ?>"
                                    >

                                </div>


                                <div class="checkout-form-group">

                                    <label for="city">
                                        City
                                    </label>

                                    <input
                                        type="text"
                                        id="city"
                                        name="city"
                                        value="<?= e($defaultAddress['city'] ?? '') ?>"
                                        required
                                    >

                                </div>


                                <div class="checkout-form-group">

                                    <label for="district">
                                        District
                                    </label>

                                    <input
                                        type="text"
                                        id="district"
                                        name="district"
                                        value="<?= e($defaultAddress['district'] ?? '') ?>"
                                        required
                                    >

                                </div>


                                <div class="checkout-form-group">

                                    <label for="postal_code">
                                        Postal Code
                                    </label>

                                    <input
                                        type="text"
                                        id="postal_code"
                                        name="postal_code"
                                        value="<?= e($defaultAddress['postal_code'] ?? '') ?>"
                                    >

                                </div>

                            </div>

                        </div>


                        <!-- Order Notes -->

                        <div class="checkout-card">

                            <div class="checkout-card-header">

                                <span class="checkout-step">
                                    03
                                </span>

                                <div>

                                    <h2>
                                        Order Notes
                                    </h2>

                                    <p>
                                        Have any special delivery instructions?
                                    </p>

                                </div>

                            </div>


                            <div class="checkout-form-group">

                                <label for="notes">
                                    Notes
                                    <span>(Optional)</span>
                                </label>

                                <textarea
                                    id="notes"
                                    name="notes"
                                    rows="4"
                                    placeholder="Add any special instructions for your order..."
                                ></textarea>

                            </div>

                        </div>


                        <!-- Payment Method -->

                        <div class="checkout-card">

                            <div class="checkout-card-header">

                                <span class="checkout-step">
                                    04
                                </span>

                                <div>

                                    <h2>
                                        Payment Method
                                    </h2>

                                    <p>
                                        Choose how you'd like to pay.
                                    </p>

                                </div>

                            </div>


                            <div class="payment-options">

                                <label class="payment-option">

                                    <input
                                        type="radio"
                                        name="payment_method"
                                        value="cod"
                                        checked
                                    >

                                    <span class="payment-option-content">

                                        <span class="payment-option-icon">
                                            <i class="fa-solid fa-money-bill-wave"></i>
                                        </span>

                                        <span>

                                            <strong>
                                                Cash on Delivery
                                            </strong>

                                            <small>
                                                Pay when your order arrives.
                                            </small>

                                        </span>

                                    </span>

                                </label>


                                <label class="payment-option">

                                    <input
                                        type="radio"
                                        name="payment_method"
                                        value="online"
                                    >

                                    <span class="payment-option-content">

                                        <span class="payment-option-icon">
                                            <i class="fa-regular fa-credit-card"></i>
                                        </span>

                                        <span>

                                            <strong>
                                                Online Payment
                                            </strong>

                                            <small>
                                                Secure online payment.
                                            </small>

                                        </span>

                                    </span>

                                </label>

                            </div>

                        </div>

                    </form>

                </div>


                <!-- =================================================
                     Order Summary
                ================================================== -->

                <aside class="checkout-summary">

                    <div class="checkout-summary-inner">

                        <h2>
                            Your Order
                        </h2>


                        <div class="checkout-products">

                            <?php foreach ($cartItems as $item): ?>

                                <div class="checkout-product">

                                    <div class="checkout-product-image">

                                        <?php if (!empty($item['image_path'])): ?>

                                            <img
                                                src="<?= e(baseUrl('assets/images/' . $item['image_path'])) ?>"
                                                alt="<?= e($item['name']) ?>"
                                            >

                                        <?php else: ?>

                                            <div class="checkout-product-placeholder">
                                                <i class="fa-regular fa-image"></i>
                                            </div>

                                        <?php endif; ?>

                                        <span class="checkout-product-quantity">
                                            <?= (int) $item['quantity'] ?>
                                        </span>

                                    </div>


                                    <div class="checkout-product-details">

                                        <h3>
                                            <?= e($item['name']) ?>
                                        </h3>

                                        <?php if (!empty($item['variant_name'])): ?>

                                            <span>
                                                <?= e($item['variant_name']) ?>
                                            </span>

                                        <?php endif; ?>

                                    </div>


                                    <strong class="checkout-product-price">
                                        <?= e(formatPrice($item['item_total'])) ?>
                                    </strong>

                                </div>

                            <?php endforeach; ?>

                        </div>


                        <div class="checkout-summary-divider"></div>


                        <div class="checkout-summary-row">

                            <span>
                                Subtotal
                            </span>

                            <strong>
                                <?= e(formatPrice($subtotal)) ?>
                            </strong>

                        </div>


                        <div class="checkout-summary-row">

                            <span>
                                Shipping
                            </span>

                            <span class="checkout-shipping-note">
                                Calculated at checkout
                            </span>

                        </div>


                        <div class="checkout-summary-divider"></div>


                        <div class="checkout-total">

                            <span>
                                Total
                            </span>

                            <strong>
                                <?= e(formatPrice($subtotal)) ?>
                            </strong>

                        </div>


                        <button
                            type="submit"
                            form="checkoutForm"
                            class="place-order-btn"
                        >
                            Place Order
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>


                        <div class="checkout-security">

                            <i class="fa-solid fa-lock"></i>

                            <span>
                                Your information is protected and secure.
                            </span>

                        </div>


                        <a
                            href="<?= e(baseUrl('cart/cart.php')) ?>"
                            class="back-to-cart"
                        >
                            <i class="fa-solid fa-arrow-left"></i>
                            Back to Cart
                        </a>

                    </div>

                </aside>

            </div>

        </div>

    </section>

</main>

</body>
</html>
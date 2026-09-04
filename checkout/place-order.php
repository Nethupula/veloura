<?php

require_once '../includes/bootstrap.php';

if (!isCustomerLoggedIn()) {
    redirect(baseUrl('customer/login.php'));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(baseUrl('cart/cart.php'));
}

$userId = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| Get Submitted Checkout Data
|--------------------------------------------------------------------------
*/

$firstName = trim($_POST['first_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');

$addressLine1 = trim($_POST['address_line_1'] ?? '');
$addressLine2 = trim($_POST['address_line_2'] ?? '');
$city = trim($_POST['city'] ?? '');
$district = trim($_POST['district'] ?? '');
$postalCode = trim($_POST['postal_code'] ?? '');

$notes = trim($_POST['notes'] ?? '');
$paymentMethod = $_POST['payment_method'] ?? '';


/*
|--------------------------------------------------------------------------
| Basic Validation
|--------------------------------------------------------------------------
*/

$errors = [];

if ($firstName === '') {
    $errors[] = 'First name is required.';
}

if ($lastName === '') {
    $errors[] = 'Last name is required.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}

if ($phone === '') {
    $errors[] = 'Phone number is required.';
}

if ($addressLine1 === '') {
    $errors[] = 'Address Line 1 is required.';
}

if ($city === '') {
    $errors[] = 'City is required.';
}

if ($district === '') {
    $errors[] = 'District is required.';
}


/*
|--------------------------------------------------------------------------
| Payment Method
|--------------------------------------------------------------------------
|
| COD is currently supported.
| Online payment will be enabled after gateway integration.
|
*/

if ($paymentMethod !== 'cod') {
    $errors[] = 'Online payment is not available yet. Please select Cash on Delivery.';
}


/*
|--------------------------------------------------------------------------
| Stop if Validation Failed
|--------------------------------------------------------------------------
*/

if (!empty($errors)) {

    $_SESSION['checkout_errors'] = $errors;

    redirect(baseUrl('checkout/checkout.php'));
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
| Start Transaction
|--------------------------------------------------------------------------
*/

try {

    $pdo->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | Get Cart Items
    |--------------------------------------------------------------------------
    |
    | FOR UPDATE locks the cart rows during the transaction.
    |
    */

    $stmt = $pdo->prepare("
        SELECT
            ci.id AS cart_item_id,
            ci.product_id,
            ci.variant_id,
            ci.quantity,

            p.name AS product_name,
            p.sku AS product_sku,
            p.price AS product_price,
            p.discount_price,
            p.stock_quantity AS product_stock,
            p.status AS product_status,

            pv.variant_name,
            pv.sku AS variant_sku,
            pv.price AS variant_price,
            pv.stock_quantity AS variant_stock,
            pv.status AS variant_status

        FROM cart_items ci

        INNER JOIN products p
            ON ci.product_id = p.id

        LEFT JOIN product_variants pv
            ON ci.variant_id = pv.id

        WHERE ci.cart_id = ?

        FOR UPDATE
    ");

    $stmt->execute([$cartId]);

    $cartItems = $stmt->fetchAll();


    /*
    |--------------------------------------------------------------------------
    | Check Cart
    |--------------------------------------------------------------------------
    */

    if (empty($cartItems)) {

        $pdo->rollBack();

        redirect(baseUrl('cart/cart.php'));
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Products and Calculate Subtotal
    |--------------------------------------------------------------------------
    */

    $subtotal = 0.00;

    foreach ($cartItems as &$item) {

        /*
        | Product must be active
        */

        if ($item['product_status'] !== 'active') {

            throw new Exception(
                'The product "' .
                $item['product_name'] .
                '" is no longer available.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Variant Product
        |--------------------------------------------------------------------------
        */

        if ($item['variant_id'] !== null) {

            if (
                $item['variant_status'] !== 'active' ||
                $item['variant_stock'] === null
            ) {

                throw new Exception(
                    'The selected variant of "' .
                    $item['product_name'] .
                    '" is no longer available.'
                );
            }


            $availableStock = (int) $item['variant_stock'];


            /*
            | Variant price
            */

            if ($item['variant_price'] !== null) {

                $unitPrice = (float) $item['variant_price'];

            } elseif ($item['discount_price'] !== null) {

                $unitPrice = (float) $item['discount_price'];

            } else {

                $unitPrice = (float) $item['product_price'];
            }


            /*
            | Variant stock validation
            */

            if ($availableStock < (int) $item['quantity']) {

                throw new Exception(
                    'Not enough stock available for ' .
                    $item['product_name'] .
                    ' - ' .
                    $item['variant_name'] .
                    '. Available stock: ' .
                    $availableStock .
                    '.'
                );
            }

        }

        /*
        |--------------------------------------------------------------------------
        | Product Without Variant
        |--------------------------------------------------------------------------
        */

        else {

            $availableStock = (int) $item['product_stock'];


            /*
            | Product price
            */

            if ($item['discount_price'] !== null) {

                $unitPrice = (float) $item['discount_price'];

            } else {

                $unitPrice = (float) $item['product_price'];
            }


            /*
            | Product stock validation
            */

            if ($availableStock < (int) $item['quantity']) {

                throw new Exception(
                    'Not enough stock available for ' .
                    $item['product_name'] .
                    '. Available stock: ' .
                    $availableStock .
                    '.'
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Calculate Item Total
        |--------------------------------------------------------------------------
        */

        $itemSubtotal =
            $unitPrice * (int) $item['quantity'];

        $item['unit_price'] = $unitPrice;
        $item['item_subtotal'] = $itemSubtotal;

        $subtotal += $itemSubtotal;
    }

    unset($item);


    /*
    |--------------------------------------------------------------------------
    | Shipping
    |--------------------------------------------------------------------------
    |
    | Free shipping for the current version.
    |
    */

    $shippingAmount = 0.00;

    $discountAmount = 0.00;

    $totalAmount =
        $subtotal
        - $discountAmount
        + $shippingAmount;


    /*
    |--------------------------------------------------------------------------
    | Generate Order Number
    |--------------------------------------------------------------------------
    */

    $orderNumber =
        'VEL-' .
        date('YmdHis') .
        '-' .
        random_int(1000, 9999);


    /*
    |--------------------------------------------------------------------------
    | Customer Name
    |--------------------------------------------------------------------------
    */

    $customerName =
        $firstName . ' ' . $lastName;


    /*
    |--------------------------------------------------------------------------
    | Shipping Address
    |--------------------------------------------------------------------------
    */

    $shippingAddress = $addressLine1;

    if ($addressLine2 !== '') {

        $shippingAddress .=
            ', ' . $addressLine2;
    }


    /*
    |--------------------------------------------------------------------------
    | Create Order
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        INSERT INTO orders (
            user_id,
            order_number,
            subtotal,
            discount_amount,
            shipping_amount,
            total_amount,
            coupon_code,
            payment_method,
            payment_status,
            order_status,
            customer_name,
            customer_email,
            customer_phone,
            shipping_address,
            shipping_city,
            shipping_district,
            shipping_postal_code,
            notes
        )
        VALUES (
            ?, ?, ?, ?, ?, ?, NULL, ?, 'pending', 'pending',
            ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");

    $stmt->execute([
        $userId,
        $orderNumber,
        $subtotal,
        $discountAmount,
        $shippingAmount,
        $totalAmount,
        'cod',
        $customerName,
        $email,
        $phone,
        $shippingAddress,
        $city,
        $district,
        $postalCode !== ''
            ? $postalCode
            : null,
        $notes !== ''
            ? $notes
            : null
    ]);

    $orderId = (int) $pdo->lastInsertId();


    /*
    |--------------------------------------------------------------------------
    | Create Order Items
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        INSERT INTO order_items (
            order_id,
            product_id,
            variant_id,
            product_name,
            sku,
            quantity,
            unit_price,
            subtotal
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($cartItems as $item) {

        $sku = $item['variant_id'] !== null
            ? $item['variant_sku']
            : $item['product_sku'];

        $variantId = $item['variant_id'] !== null
            ? (int) $item['variant_id']
            : null;

        $stmt->execute([
            $orderId,
            $item['product_id'],
            $variantId,
            $item['product_name'],
            $sku,
            $item['quantity'],
            $item['unit_price'],
            $item['item_subtotal']
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Create Payment Record
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        INSERT INTO payments (
            order_id,
            payment_method,
            transaction_id,
            amount,
            status,
            gateway_response,
            paid_at
        )
        VALUES (
            ?,
            'cod',
            NULL,
            ?,
            'pending',
            NULL,
            NULL
        )
    ");

    $stmt->execute([
        $orderId,
        $totalAmount
    ]);


    /*
    |--------------------------------------------------------------------------
    | Update Inventory + Product/Variant Stock
    |--------------------------------------------------------------------------
    */

    foreach ($cartItems as $item) {

        $quantity = (int) $item['quantity'];


        /*
        |--------------------------------------------------------------------------
        | Product With Variant
        |--------------------------------------------------------------------------
        */

        if ($item['variant_id'] !== null) {

            $variantId = (int) $item['variant_id'];


            /*
            | Lock inventory row
            */

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    quantity,
                    reserved_quantity
                FROM inventory
                WHERE product_id = ?
                  AND variant_id = ?
                LIMIT 1
                FOR UPDATE
            ");

            $stmt->execute([
                $item['product_id'],
                $variantId
            ]);

            $inventory = $stmt->fetch();


            if (!$inventory) {

                throw new Exception(
                    'Inventory record not found for ' .
                    $item['product_name'] .
                    ' - ' .
                    $item['variant_name'] .
                    '.'
                );
            }


            /*
            | Validate inventory quantity
            */

            if ((int) $inventory['quantity'] < $quantity) {

                throw new Exception(
                    'Inventory is insufficient for ' .
                    $item['product_name'] .
                    ' - ' .
                    $item['variant_name'] .
                    '.'
                );
            }


            /*
            | Reduce inventory quantity
            */

            $stmt = $pdo->prepare("
                UPDATE inventory
                SET quantity = quantity - ?
                WHERE id = ?
                  AND quantity >= ?
            ");

            $stmt->execute([
                $quantity,
                $inventory['id'],
                $quantity
            ]);


            if ($stmt->rowCount() !== 1) {

                throw new Exception(
                    'Unable to update inventory.'
                );
            }


            /*
            | Reduce variant stock
            */

            $stmt = $pdo->prepare("
                UPDATE product_variants
                SET stock_quantity = stock_quantity - ?
                WHERE id = ?
                  AND stock_quantity >= ?
            ");

            $stmt->execute([
                $quantity,
                $variantId,
                $quantity
            ]);


            if ($stmt->rowCount() !== 1) {

                throw new Exception(
                    'Unable to update product variant stock.'
                );
            }


            /*
            | Keep parent product stock synchronized
            |
            | Parent stock = total stock of its active variants.
            |
            */

            $stmt = $pdo->prepare("
                UPDATE products
                SET stock_quantity = (
                    SELECT COALESCE(
                        SUM(stock_quantity),
                        0
                    )
                    FROM product_variants
                    WHERE product_id = ?
                      AND status = 'active'
                )
                WHERE id = ?
            ");

            $stmt->execute([
                $item['product_id'],
                $item['product_id']
            ]);

        }


        /*
        |--------------------------------------------------------------------------
        | Product Without Variant
        |--------------------------------------------------------------------------
        */

        else {

            /*
            | Lock inventory row
            */

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    quantity,
                    reserved_quantity
                FROM inventory
                WHERE product_id = ?
                  AND variant_id IS NULL
                LIMIT 1
                FOR UPDATE
            ");

            $stmt->execute([
                $item['product_id']
            ]);

            $inventory = $stmt->fetch();


            if (!$inventory) {

                throw new Exception(
                    'Inventory record not found for ' .
                    $item['product_name'] .
                    '.'
                );
            }


            /*
            | Validate inventory quantity
            */

            if ((int) $inventory['quantity'] < $quantity) {

                throw new Exception(
                    'Inventory is insufficient for ' .
                    $item['product_name'] .
                    '.'
                );
            }


            /*
            | Reduce inventory
            */

            $stmt = $pdo->prepare("
                UPDATE inventory
                SET quantity = quantity - ?
                WHERE id = ?
                  AND quantity >= ?
            ");

            $stmt->execute([
                $quantity,
                $inventory['id'],
                $quantity
            ]);


            if ($stmt->rowCount() !== 1) {

                throw new Exception(
                    'Unable to update inventory.'
                );
            }


            /*
            | Reduce product stock
            */

            $stmt = $pdo->prepare("
                UPDATE products
                SET stock_quantity = stock_quantity - ?
                WHERE id = ?
                  AND stock_quantity >= ?
            ");

            $stmt->execute([
                $quantity,
                $item['product_id'],
                $quantity
            ]);


            if ($stmt->rowCount() !== 1) {

                throw new Exception(
                    'Unable to update product stock.'
                );
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Clear Customer Cart
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        DELETE FROM cart_items
        WHERE cart_id = ?
    ");

    $stmt->execute([
        $cartId
    ]);


    /*
    |--------------------------------------------------------------------------
    | Commit Transaction
    |--------------------------------------------------------------------------
    */

    $pdo->commit();


    /*
    |--------------------------------------------------------------------------
    | Save Order Number
    |--------------------------------------------------------------------------
    */

    $_SESSION['last_order_number'] =
        $orderNumber;


    /*
    |--------------------------------------------------------------------------
    | Redirect to Success Page
    |--------------------------------------------------------------------------
    */

    redirect(
        baseUrl('checkout/order-success.php')
    );


} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | Rollback Everything
    |--------------------------------------------------------------------------
    */

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }


    /*
    |--------------------------------------------------------------------------
    | Save Error
    |--------------------------------------------------------------------------
    */

    $_SESSION['checkout_errors'] = [
        'We could not place your order. ' .
        $e->getMessage()
    ];


    redirect(
        baseUrl('checkout/checkout.php')
    );
}
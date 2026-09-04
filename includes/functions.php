<?php

/**
 * Escape output safely for HTML.
 */
function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/**
 * Redirect to another page.
 */
function redirect($url)
{
    header("Location: $url");
    exit;
}


/**
 * Check whether a customer is logged in.
 */
function isCustomerLoggedIn()
{
    return isset($_SESSION['user_id']);
}


/**
 * Check whether an admin is logged in.
 */
function isAdminLoggedIn()
{
    return isset($_SESSION['admin_id']);
}


/**
 * Format a price in Sri Lankan Rupees.
 */
function formatPrice($amount)
{
    return 'Rs. ' . number_format(
        (float) $amount,
        2
    );
}
/**
 * Generate a URL from the Veloura project root.
 */
function baseUrl($path = '')
{
    return '/veloura/' . ltrim($path, '/');
}
/**
 * Get the total number of items in the customer's cart.
 */
function getCartItemCount()
{
    global $pdo;

    if (!isCustomerLoggedIn()) {
        return 0;
    }

    $userId = (int) $_SESSION['user_id'];

    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(ci.quantity), 0) AS item_count
        FROM carts c
        INNER JOIN cart_items ci
            ON c.id = ci.cart_id
        WHERE c.user_id = ?
    ");

    $stmt->execute([$userId]);

    $result = $stmt->fetch();

    return (int) ($result['item_count'] ?? 0);
}

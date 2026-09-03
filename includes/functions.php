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
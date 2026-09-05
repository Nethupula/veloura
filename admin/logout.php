<?php

require_once '../includes/bootstrap.php';

$_SESSION['admin_id'] = null;
$_SESSION['admin_name'] = null;
$_SESSION['admin_email'] = null;
$_SESSION['admin_role'] = null;

unset(
    $_SESSION['admin_id'],
    $_SESSION['admin_name'],
    $_SESSION['admin_email'],
    $_SESSION['admin_role']
);

session_regenerate_id(true);

redirect(baseUrl('admin/login.php'));
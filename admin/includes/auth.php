<?php

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!isAdminLoggedIn()) {
    redirect(baseUrl('admin/login.php'));
}
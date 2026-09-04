<?php

require_once '../includes/bootstrap.php';

/*
|--------------------------------------------------------------------------
| Clear Customer Session
|--------------------------------------------------------------------------
*/

$_SESSION = [];

/*
|--------------------------------------------------------------------------
| Destroy Session
|--------------------------------------------------------------------------
*/

if (ini_get('session.use_cookies')) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

/*
|--------------------------------------------------------------------------
| Redirect Home
|--------------------------------------------------------------------------
*/

redirect(baseUrl('index.php'));
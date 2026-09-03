<?php

require_once __DIR__ . '/bootstrap.php';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description"
          content="Veloura - Discover elegant and timeless jewelry designed to make you shine.">

    <meta name="keywords"
          content="Veloura, jewelry, rings, necklaces, earrings, bangles, bracelets">

    <meta name="author" content="Veloura">

    <title>Veloura - Made to Make You Shine</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap"
          rel="stylesheet">

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <!-- Veloura CSS -->
    <link rel="stylesheet" href="<?= e(baseUrl('assets/css/style.css')) ?>">

</head>

<body>
    <?php require_once __DIR__ . '/navbar.php'; ?>
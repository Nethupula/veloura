<?php

require_once '../includes/auth.php';

$errors = [];

$productId = (int) ($_GET['id'] ?? $_POST['product_id'] ?? 0);

if ($productId <= 0) {
    redirect(baseUrl('admin/products/index.php'));
}


/*
|--------------------------------------------------------------------------
| Get Product
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM products
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$productId]);

$product = $stmt->fetch();

if (!$product) {
    redirect(baseUrl('admin/products/index.php'));
}


/*
|--------------------------------------------------------------------------
| Get Categories
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT id, name
    FROM categories
    WHERE status = 'active'
    ORDER BY name ASC
");

$categories = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Get Variants
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM product_variants
    WHERE product_id = ?
    ORDER BY id ASC
");

$stmt->execute([$productId]);

$existingVariants = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Existing Product Image
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM product_images
    WHERE product_id = ?
    ORDER BY is_primary DESC, sort_order ASC
    LIMIT 1
");

$stmt->execute([$productId]);

$currentImage = $stmt->fetch();

$currentImagePath = $currentImage['image_path'] ?? null;


/*
|--------------------------------------------------------------------------
| Form Values
|--------------------------------------------------------------------------
*/

$name = $product['name'];
$sku = $product['sku'];
$categoryId = (int) $product['category_id'];

$description = $product['description'] ?? '';
$shortDescription = $product['short_description'] ?? '';

$price = $product['price'];
$discountPrice = $product['discount_price'];

$material = $product['material'] ?? '';
$color = $product['color'] ?? '';

$gender = $product['gender'];
$stockQuantity = (int) $product['stock_quantity'];

$featured = (int) $product['featured'];
$status = $product['status'];


/*
|--------------------------------------------------------------------------
| Handle Form Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $sku = trim($_POST['sku'] ?? '');

    $categoryId = (int) ($_POST['category_id'] ?? 0);

    $description = trim($_POST['description'] ?? '');
    $shortDescription = trim($_POST['short_description'] ?? '');

    $price = trim($_POST['price'] ?? '');
    $discountPrice = trim($_POST['discount_price'] ?? '');

    $material = trim($_POST['material'] ?? '');
    $color = trim($_POST['color'] ?? '');

    $gender = $_POST['gender'] ?? 'women';

    $featured = isset($_POST['featured']) ? 1 : 0;

    $status = $_POST['status'] ?? 'active';

    $hasVariants = isset($_POST['has_variants']);

    $stockQuantity = (int) ($_POST['stock_quantity'] ?? 0);


    /*
    |--------------------------------------------------------------------------
    | Basic Validation
    |--------------------------------------------------------------------------
    */

    if ($name === '') {
        $errors[] = 'Product name is required.';
    }

    if ($sku === '') {
        $errors[] = 'SKU is required.';
    }

    if ($categoryId <= 0) {
        $errors[] = 'Please select a category.';
    }

    if (
        $price === '' ||
        !is_numeric($price) ||
        (float) $price < 0
    ) {
        $errors[] = 'Please enter a valid regular price.';
    }

    if (
        $discountPrice !== '' &&
        (
            !is_numeric($discountPrice) ||
            (float) $discountPrice < 0
        )
    ) {
        $errors[] = 'Please enter a valid discount price.';
    }

    if (
        $discountPrice !== '' &&
        is_numeric($price) &&
        (float) $discountPrice >= (float) $price
    ) {
        $errors[] =
            'Discount price must be lower than the regular price.';
    }

    if (!in_array(
        $gender,
        ['women', 'men', 'unisex'],
        true
    )) {
        $errors[] = 'Invalid gender selected.';
    }

    if (!in_array(
        $status,
        ['active', 'inactive', 'out_of_stock'],
        true
    )) {
        $errors[] = 'Invalid product status.';
    }

    if (!$hasVariants && $stockQuantity < 0) {
        $errors[] = 'Stock quantity cannot be negative.';
    }


    /*
    |--------------------------------------------------------------------------
    | Process Variants
    |--------------------------------------------------------------------------
    */

    $submittedVariants = [];

    if ($hasVariants) {

        $variantIds =
            $_POST['variant_id'] ?? [];

        $variantNames =
            $_POST['variant_name'] ?? [];

        $variantSkus =
            $_POST['variant_sku'] ?? [];

        $variantPrices =
            $_POST['variant_price'] ?? [];

        $variantStocks =
            $_POST['variant_stock'] ?? [];

        $variantReorderLevels =
            $_POST['variant_reorder_level'] ?? [];


        foreach ($variantNames as $index => $variantName) {

            $variantName = trim($variantName);

            if ($variantName === '') {
                continue;
            }

            $variantId = (int) (
                $variantIds[$index] ?? 0
            );

            $variantSku = trim(
                $variantSkus[$index] ?? ''
            );

            $variantPrice = trim(
                $variantPrices[$index] ?? ''
            );

            $variantStock = (int) (
                $variantStocks[$index] ?? 0
            );

            $variantReorderLevel = (int) (
                $variantReorderLevels[$index] ?? 1
            );


            if ($variantSku === '') {

                $errors[] =
                    'Every variant must have a SKU.';

            }

            if (
                $variantPrice !== '' &&
                (
                    !is_numeric($variantPrice) ||
                    (float) $variantPrice < 0
                )
            ) {

                $errors[] =
                    'Variant prices must be valid.';

            }

            if ($variantStock < 0) {

                $errors[] =
                    'Variant stock cannot be negative.';

            }

            if ($variantReorderLevel < 0) {

                $errors[] =
                    'Variant reorder level cannot be negative.';

            }


            $submittedVariants[] = [
                'id' => $variantId,
                'name' => $variantName,
                'sku' => $variantSku,
                'price' => $variantPrice,
                'stock' => $variantStock,
                'reorder_level' => $variantReorderLevel
            ];
        }


        if (empty($submittedVariants)) {

            $errors[] =
                'Please keep at least one product variant.';

        }
    }


    /*
    |--------------------------------------------------------------------------
    | Check Product SKU
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        $stmt = $pdo->prepare("
            SELECT id
            FROM products
            WHERE sku = ?
            AND id != ?
            LIMIT 1
        ");

        $stmt->execute([
            $sku,
            $productId
        ]);

        if ($stmt->fetch()) {

            $errors[] =
                'Another product already uses this SKU.';

        }
    }


    /*
    |--------------------------------------------------------------------------
    | Check Variant SKUs
    |--------------------------------------------------------------------------
    */

    if (empty($errors) && $hasVariants) {

        foreach ($submittedVariants as $variant) {

            $stmt = $pdo->prepare("
                SELECT id
                FROM product_variants
                WHERE sku = ?
                AND id != ?
                LIMIT 1
            ");

            $stmt->execute([
                $variant['sku'],
                $variant['id']
            ]);

            if ($stmt->fetch()) {

                $errors[] =
                    'Variant SKU "' .
                    $variant['sku'] .
                    '" is already in use.';

                break;
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Upload New Image
    |--------------------------------------------------------------------------
    */

    $newImagePath = null;
    $newPhysicalPath = null;

    if (
        empty($errors) &&
        isset($_FILES['product_image']) &&
        $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        $image = $_FILES['product_image'];

        if ($image['error'] !== UPLOAD_ERR_OK) {

            $errors[] =
                'There was a problem uploading the product image.';

        } elseif ($image['size'] > 5 * 1024 * 1024) {

            $errors[] =
                'Product image must be 5MB or smaller.';

        } else {

            $finfo = new finfo(FILEINFO_MIME_TYPE);

            $mimeType = $finfo->file(
                $image['tmp_name']
            );

            $allowedTypes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp'
            ];

            if (!isset($allowedTypes[$mimeType])) {

                $errors[] =
                    'Only JPG, PNG, and WEBP images are allowed.';

            } else {

                $extension =
                    $allowedTypes[$mimeType];

                $fileName =
                    'product_' .
                    bin2hex(random_bytes(8)) .
                    '.' .
                    $extension;

                $uploadDirectory =
                    __DIR__ .
                    '/../../assets/images/products/';

                if (!is_dir($uploadDirectory)) {

                    mkdir(
                        $uploadDirectory,
                        0755,
                        true
                    );
                }

                $newPhysicalPath =
                    $uploadDirectory .
                    $fileName;

                $newImagePath =
                    'products/' .
                    $fileName;

                if (
                    !move_uploaded_file(
                        $image['tmp_name'],
                        $newPhysicalPath
                    )
                ) {

                    $errors[] =
                        'Failed to save the product image.';

                    $newPhysicalPath = null;
                    $newImagePath = null;
                }
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Update Database
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        try {

            $pdo->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | Calculate Stock
            |--------------------------------------------------------------------------
            */

            if ($hasVariants) {

                $stockQuantity = 0;

                foreach ($submittedVariants as $variant) {

                    $stockQuantity +=
                        $variant['stock'];
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Update Product
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                UPDATE products
                SET
                    category_id = ?,
                    name = ?,
                    sku = ?,
                    description = ?,
                    short_description = ?,
                    price = ?,
                    discount_price = ?,
                    material = ?,
                    color = ?,
                    gender = ?,
                    stock_quantity = ?,
                    featured = ?,
                    status = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $categoryId,
                $name,
                $sku,
                $description !== ''
                    ? $description
                    : null,
                $shortDescription !== ''
                    ? $shortDescription
                    : null,
                (float) $price,
                $discountPrice !== ''
                    ? (float) $discountPrice
                    : null,
                $material !== ''
                    ? $material
                    : null,
                $color !== ''
                    ? $color
                    : null,
                $gender,
                $stockQuantity,
                $featured,
                $status,
                $productId
            ]);


            /*
            |--------------------------------------------------------------------------
            | Update / Add Variants
            |--------------------------------------------------------------------------
            */

            if ($hasVariants) {

                $updateVariant = $pdo->prepare("
                    UPDATE product_variants
                    SET
                        variant_name = ?,
                        sku = ?,
                        price = ?,
                        stock_quantity = ?
                    WHERE id = ?
                    AND product_id = ?
                ");

                $insertVariant = $pdo->prepare("
                    INSERT INTO product_variants (
                        product_id,
                        variant_name,
                        sku,
                        price,
                        stock_quantity,
                        status
                    )
                    VALUES (?, ?, ?, ?, ?, 'active')
                ");


                foreach ($submittedVariants as $variant) {

                    $variantPrice =
                        $variant['price'] !== ''
                            ? (float) $variant['price']
                            : null;


                    if ($variant['id'] > 0) {

                        $updateVariant->execute([
                            $variant['name'],
                            $variant['sku'],
                            $variantPrice,
                            $variant['stock'],
                            $variant['id'],
                            $productId
                        ]);

                        $variantId =
                            $variant['id'];

                    } else {

                        $insertVariant->execute([
                            $productId,
                            $variant['name'],
                            $variant['sku'],
                            $variantPrice,
                            $variant['stock']
                        ]);

                        $variantId =
                            (int) $pdo->lastInsertId();
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Update Variant Inventory
                    |--------------------------------------------------------------------------
                    */

                    $inventoryCheck = $pdo->prepare("
                        SELECT id
                        FROM inventory
                        WHERE product_id = ?
                        AND variant_id = ?
                        LIMIT 1
                        FOR UPDATE
                    ");

                    $inventoryCheck->execute([
                        $productId,
                        $variantId
                    ]);

                    $inventory =
                        $inventoryCheck->fetch();


                    if ($inventory) {

                        $inventoryUpdate = $pdo->prepare("
                            UPDATE inventory
                            SET
                                quantity = ?,
                                reorder_level = ?,
                                updated_at = CURRENT_TIMESTAMP
                            WHERE id = ?
                        ");

                        $inventoryUpdate->execute([
                            $variant['stock'],
                            $variant['reorder_level'],
                            $inventory['id']
                        ]);

                    } else {

                        $inventoryInsert = $pdo->prepare("
                            INSERT INTO inventory (
                                product_id,
                                variant_id,
                                quantity,
                                reserved_quantity,
                                reorder_level
                            )
                            VALUES (?, ?, ?, 0, ?)
                        ");

                        $inventoryInsert->execute([
                            $productId,
                            $variantId,
                            $variant['stock'],
                            $variant['reorder_level']
                        ]);
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | Synchronize Parent Product Stock
                |--------------------------------------------------------------------------
                */

                $stmt = $pdo->prepare("
                    SELECT COALESCE(
                        SUM(stock_quantity),
                        0
                    )
                    FROM product_variants
                    WHERE product_id = ?
                    AND status = 'active'
                ");

                $stmt->execute([$productId]);

                $totalVariantStock =
                    (int) $stmt->fetchColumn();


                $stmt = $pdo->prepare("
                    UPDATE products
                    SET stock_quantity = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $totalVariantStock,
                    $productId
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Product-level Inventory
            |--------------------------------------------------------------------------
            */

            if (!$hasVariants) {

                $inventoryCheck = $pdo->prepare("
                    SELECT id
                    FROM inventory
                    WHERE product_id = ?
                    AND variant_id IS NULL
                    LIMIT 1
                    FOR UPDATE
                ");

                $inventoryCheck->execute([
                    $productId
                ]);

                $inventory =
                    $inventoryCheck->fetch();


                if ($inventory) {

                    $inventoryUpdate = $pdo->prepare("
                        UPDATE inventory
                        SET
                            quantity = ?,
                            reorder_level = 1,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE id = ?
                    ");

                    $inventoryUpdate->execute([
                        $stockQuantity,
                        $inventory['id']
                    ]);

                } else {

                    $inventoryInsert = $pdo->prepare("
                        INSERT INTO inventory (
                            product_id,
                            variant_id,
                            quantity,
                            reserved_quantity,
                            reorder_level
                        )
                        VALUES (?, NULL, ?, 0, 1)
                    ");

                    $inventoryInsert->execute([
                        $productId,
                        $stockQuantity
                    ]);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Save New Image
            |--------------------------------------------------------------------------
            */

            if ($newImagePath !== null) {

                if ($currentImage) {

                    $stmt = $pdo->prepare("
                        UPDATE product_images
                        SET
                            image_path = ?,
                            alt_text = ?,
                            is_primary = 1
                        WHERE id = ?
                    ");

                    $stmt->execute([
                        $newImagePath,
                        $name,
                        $currentImage['id']
                    ]);

                } else {

                    $stmt = $pdo->prepare("
                        INSERT INTO product_images (
                            product_id,
                            image_path,
                            alt_text,
                            is_primary,
                            sort_order
                        )
                        VALUES (?, ?, ?, 1, 1)
                    ");

                    $stmt->execute([
                        $productId,
                        $newImagePath,
                        $name
                    ]);
                }
            }


            $pdo->commit();


            /*
            |--------------------------------------------------------------------------
            | Delete Old Physical Image
            |--------------------------------------------------------------------------
            */

            if (
                $newImagePath !== null &&
                $currentImagePath !== null
            ) {

                $oldPhysicalPath =
                    __DIR__ .
                    '/../../assets/images/' .
                    $currentImagePath;

                if (
                    file_exists($oldPhysicalPath) &&
                    is_file($oldPhysicalPath)
                ) {
                    unlink($oldPhysicalPath);
                }
            }


            redirect(
                baseUrl('admin/products/index.php')
            );

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if (
                $newPhysicalPath !== null &&
                file_exists($newPhysicalPath)
            ) {
                unlink($newPhysicalPath);
            }

            $errors[] =
                'Unable to update the product. Please try again.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| Load Header After POST Processing
|--------------------------------------------------------------------------
*/

$pageTitle = 'Edit Product';

require_once '../includes/header.php';

?>

<div class="admin-page-header">

    <div>

        <h2>Edit Product</h2>

        <p>
            Update <?= e($name) ?>.
        </p>

    </div>

    <a
        href="<?= e(baseUrl('admin/products/index.php')) ?>"
        class="admin-secondary-button"
    >
        <i class="fa-solid fa-arrow-left"></i>
        Back to Products
    </a>

</div>


<?php if (!empty($errors)): ?>

    <div class="admin-form-errors">

        <?php foreach ($errors as $error): ?>

            <div class="admin-form-error">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?= e($error) ?>

            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>


<form
    method="POST"
    enctype="multipart/form-data"
    class="admin-product-form"
>

    <input
        type="hidden"
        name="product_id"
        value="<?= e($productId) ?>"
    >


    <!-- BASIC INFORMATION -->

    <div class="admin-form-card">

        <div class="admin-form-card-header">

            <h3>Basic Information</h3>

            <p>
                Update the main product information.
            </p>

        </div>

        <div class="admin-form-card-body">

            <div class="admin-form-grid">

                <div class="admin-form-group full">

                    <label for="name">
                        Product Name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?= e($name) ?>"
                        required
                    >

                </div>


                <div class="admin-form-group">

                    <label for="sku">
                        SKU
                    </label>

                    <input
                        type="text"
                        id="sku"
                        name="sku"
                        value="<?= e($sku) ?>"
                        required
                    >

                </div>


                <div class="admin-form-group">

                    <label for="category_id">
                        Category
                    </label>

                    <select
                        id="category_id"
                        name="category_id"
                        required
                    >

                        <?php foreach ($categories as $category): ?>

                            <option
                                value="<?= e($category['id']) ?>"
                                <?= $categoryId ===
                                    (int) $category['id']
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= e($category['name']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="admin-form-group full">

                    <label for="short_description">
                        Short Description
                    </label>

                    <input
                        type="text"
                        id="short_description"
                        name="short_description"
                        maxlength="500"
                        value="<?= e($shortDescription) ?>"
                    >

                </div>


                <div class="admin-form-group full">

                    <label for="description">
                        Description
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        rows="6"
                    ><?= e($description) ?></textarea>

                </div>

            </div>

        </div>

    </div>


    <!-- PRICING -->

    <div class="admin-form-card">

        <div class="admin-form-card-header">

            <h3>Pricing & Details</h3>

            <p>
                Update pricing and product characteristics.
            </p>

        </div>

        <div class="admin-form-card-body">

            <div class="admin-form-grid">

                <div class="admin-form-group">

                    <label for="price">
                        Regular Price (Rs.)
                    </label>

                    <input
                        type="number"
                        id="price"
                        name="price"
                        min="0"
                        step="0.01"
                        value="<?= e($price) ?>"
                        required
                    >

                </div>


                <div class="admin-form-group">

                    <label for="discount_price">
                        Discount Price (Rs.)
                    </label>

                    <input
                        type="number"
                        id="discount_price"
                        name="discount_price"
                        min="0"
                        step="0.01"
                        value="<?= e($discountPrice ?? '') ?>"
                    >

                </div>


                <div class="admin-form-group">

                    <label for="material">
                        Material
                    </label>

                    <input
                        type="text"
                        id="material"
                        name="material"
                        value="<?= e($material) ?>"
                    >

                </div>


                <div class="admin-form-group">

                    <label for="color">
                        Color
                    </label>

                    <input
                        type="text"
                        id="color"
                        name="color"
                        value="<?= e($color) ?>"
                    >

                </div>


                <div class="admin-form-group">

                    <label for="gender">
                        Gender
                    </label>

                    <select
                        id="gender"
                        name="gender"
                    >

                        <option
                            value="women"
                            <?= $gender === 'women'
                                ? 'selected'
                                : '' ?>
                        >
                            Women
                        </option>

                        <option
                            value="men"
                            <?= $gender === 'men'
                                ? 'selected'
                                : '' ?>
                        >
                            Men
                        </option>

                        <option
                            value="unisex"
                            <?= $gender === 'unisex'
                                ? 'selected'
                                : '' ?>
                        >
                            Unisex
                        </option>

                    </select>

                </div>


                <div class="admin-form-group">

                    <label for="status">
                        Status
                    </label>

                    <select
                        id="status"
                        name="status"
                    >

                        <option
                            value="active"
                            <?= $status === 'active'
                                ? 'selected'
                                : '' ?>
                        >
                            Active
                        </option>

                        <option
                            value="inactive"
                            <?= $status === 'inactive'
                                ? 'selected'
                                : '' ?>
                        >
                            Inactive
                        </option>

                        <option
                            value="out_of_stock"
                            <?= $status === 'out_of_stock'
                                ? 'selected'
                                : '' ?>
                        >
                            Out of Stock
                        </option>

                    </select>

                </div>

            </div>

        </div>

    </div>


    <!-- PRODUCT IMAGE -->

    <div class="admin-form-card">

        <div class="admin-form-card-header">

            <h3>Product Image</h3>

            <p>
                Replace the current product image if needed.
            </p>

        </div>

        <div class="admin-form-card-body">

            <?php if ($currentImagePath): ?>

                <div class="current-product-image">

                    <img
                        src="<?= e(
                            baseUrl(
                                'assets/images/' .
                                $currentImagePath
                            )
                        ) ?>"
                        alt="<?= e($name) ?>"
                    >

                </div>

            <?php endif; ?>


            <div class="admin-form-group">

                <label for="product_image">
                    New Product Image
                </label>

                <input
                    type="file"
                    id="product_image"
                    name="product_image"
                    accept=".jpg,.jpeg,.png,.webp"
                >

                <small class="admin-form-help">
                    Leave empty to keep the current image.
                    JPG, PNG or WEBP. Maximum 5MB.
                </small>

            </div>

        </div>

    </div>


    <!-- INVENTORY -->

    <div class="admin-form-card">

        <div class="admin-form-card-header">

            <h3>Inventory</h3>

            <p>
                Manage stock and product variants.
            </p>

        </div>

        <div class="admin-form-card-body">

            <label class="admin-checkbox-row">

                <input
                    type="checkbox"
                    name="has_variants"
                    id="hasVariants"
                    value="1"
                    <?= !empty($existingVariants)
                        ? 'checked'
                        : '' ?>
                >

                <span>
                    This product has variants
                </span>

            </label>


            <div
                id="simpleStockSection"
                class="simple-stock-section"
                style="<?= !empty($existingVariants)
                    ? 'display:none;'
                    : '' ?>"
            >

                <div class="admin-form-group">

                    <label for="stock_quantity">
                        Stock Quantity
                    </label>

                    <input
                        type="number"
                        id="stock_quantity"
                        name="stock_quantity"
                        min="0"
                        value="<?= e($stockQuantity) ?>"
                    >

                </div>

            </div>


            <div
                id="variantsSection"
                class="variants-section"
                style="<?= !empty($existingVariants)
                    ? ''
                    : 'display:none;' ?>"
            >

                <div class="variants-header">

                    <div>

                        <h4>Product Variants</h4>

                        <p>
                            Update existing variants or add new ones.
                        </p>

                    </div>

                    <button
                        type="button"
                        id="addVariantButton"
                        class="admin-small-button"
                    >
                        <i class="fa-solid fa-plus"></i>
                        Add Variant
                    </button>

                </div>


                <div id="variantsContainer">

                    <?php foreach (
                        $existingVariants as $variant
                    ): ?>

                        <?php

                        $inventoryStmt = $pdo->prepare("
                            SELECT reorder_level
                            FROM inventory
                            WHERE product_id = ?
                            AND variant_id = ?
                            LIMIT 1
                        ");

                        $inventoryStmt->execute([
                            $productId,
                            $variant['id']
                        ]);

                        $inventoryData =
                            $inventoryStmt->fetch();

                        $reorderLevel =
                            (int) (
                                $inventoryData['reorder_level']
                                ?? 1
                            );

                        ?>

                        <div class="variant-row">

                            <input
                                type="hidden"
                                name="variant_id[]"
                                value="<?= e($variant['id']) ?>"
                            >

                            <div class="variant-field">

                                <label>Variant Name</label>

                                <input
                                    type="text"
                                    name="variant_name[]"
                                    value="<?= e(
                                        $variant['variant_name']
                                    ) ?>"
                                    required
                                >

                            </div>


                            <div class="variant-field">

                                <label>SKU</label>

                                <input
                                    type="text"
                                    name="variant_sku[]"
                                    value="<?= e(
                                        $variant['sku']
                                    ) ?>"
                                    required
                                >

                            </div>


                            <div class="variant-field">

                                <label>Price</label>

                                <input
                                    type="number"
                                    name="variant_price[]"
                                    min="0"
                                    step="0.01"
                                    value="<?= e(
                                        $variant['price'] ?? ''
                                    ) ?>"
                                >

                            </div>


                            <div class="variant-field">

                                <label>Stock</label>

                                <input
                                    type="number"
                                    name="variant_stock[]"
                                    min="0"
                                    value="<?= e(
                                        $variant['stock_quantity']
                                    ) ?>"
                                    required
                                >

                            </div>


                            <div class="variant-field">

                                <label>Reorder Level</label>

                                <input
                                    type="number"
                                    name="variant_reorder_level[]"
                                    min="0"
                                    value="<?= e(
                                        $reorderLevel
                                    ) ?>"
                                    required
                                >

                            </div>


                            <button
                                type="button"
                                class="remove-variant"
                                title="Remove Variant"
                            >
                                <i class="fa-solid fa-xmark"></i>
                            </button>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        </div>

    </div>


    <!-- STORE OPTIONS -->

    <div class="admin-form-card">

        <div class="admin-form-card-header">

            <h3>Store Options</h3>

            <p>
                Control how this product appears in your store.
            </p>

        </div>

        <div class="admin-form-card-body">

            <label class="admin-checkbox-row">

                <input
                    type="checkbox"
                    name="featured"
                    value="1"
                    <?= $featured
                        ? 'checked'
                        : '' ?>
                >

                <span>
                    Show this product as a featured product.
                </span>

            </label>

        </div>

    </div>


    <!-- ACTIONS -->

    <div class="admin-form-actions">

        <a
            href="<?= e(baseUrl('admin/products/index.php')) ?>"
            class="admin-cancel-button"
        >
            Cancel
        </a>

        <button
            type="submit"
            class="admin-primary-button"
        >
            <i class="fa-solid fa-floppy-disk"></i>
            Save Changes
        </button>

    </div>

</form>


<script>

document.addEventListener('DOMContentLoaded', function () {

    const hasVariants =
        document.getElementById('hasVariants');

    const simpleStockSection =
        document.getElementById('simpleStockSection');

    const variantsSection =
        document.getElementById('variantsSection');

    const addVariantButton =
        document.getElementById('addVariantButton');

    const variantsContainer =
        document.getElementById('variantsContainer');


    function updateVariantMode() {

        if (hasVariants.checked) {

            simpleStockSection.style.display = 'none';

            variantsSection.style.display = 'block';

        } else {

            simpleStockSection.style.display = 'block';

            variantsSection.style.display = 'none';

        }

    }


    function addVariant() {

        const row =
            document.createElement('div');

        row.className = 'variant-row';

        row.innerHTML = `

            <input
                type="hidden"
                name="variant_id[]"
                value="0"
            >

            <div class="variant-field">

                <label>Variant Name</label>

                <input
                    type="text"
                    name="variant_name[]"
                    placeholder="Size 9"
                    required
                >

            </div>

            <div class="variant-field">

                <label>SKU</label>

                <input
                    type="text"
                    name="variant_sku[]"
                    placeholder="VEL-RNG-001-S9"
                    required
                >

            </div>

            <div class="variant-field">

                <label>Price</label>

                <input
                    type="number"
                    name="variant_price[]"
                    min="0"
                    step="0.01"
                    placeholder="7500.00"
                >

            </div>

            <div class="variant-field">

                <label>Stock</label>

                <input
                    type="number"
                    name="variant_stock[]"
                    min="0"
                    value="0"
                    required
                >

            </div>

            <div class="variant-field">

                <label>Reorder Level</label>

                <input
                    type="number"
                    name="variant_reorder_level[]"
                    min="0"
                    value="1"
                    required
                >

            </div>

            <button
                type="button"
                class="remove-variant"
                title="Remove Variant"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>
        `;


        variantsContainer.appendChild(row);


        row.querySelector(
            '.remove-variant'
        ).addEventListener(
            'click',
            function () {
                row.remove();
            }
        );

    }


    document
        .querySelectorAll('.remove-variant')
        .forEach(function (button) {

            button.addEventListener(
                'click',
                function () {

                    const row =
                        button.closest('.variant-row');

                    if (row) {
                        row.remove();
                    }

                }
            );

        });


    hasVariants.addEventListener(
        'change',
        updateVariantMode
    );


    addVariantButton.addEventListener(
        'click',
        addVariant
    );

});

</script>


<?php require_once '../includes/footer.php'; ?>